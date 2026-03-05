<?php
/**
 * SITAPSI - Import Siswa Dinamis (Ala Dapodik)
 * Versi PURE PHP (CSV) - Langsung Jalan Tanpa Composer!
 */

session_start();
require_once '../config/database.php';

// Validasi Keamanan
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'SuperAdmin' && $_SESSION['role'] !== 'Admin')) {
    die("Akses ditolak!");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_excel'])) {

    // Ambil ekstensi file
    $arr_file = explode('.', $_FILES['file_excel']['name']);
    $extension = strtolower(end($arr_file));

    // Validasi ekstensi HARUS CSV
    if ($extension !== 'csv') {
        $_SESSION['flash_message'] = "ERROR: Format file harus .csv! Silakan download template yang kami sediakan.";
        header("Location: ../views/admin/data_siswa.php");
        exit;
    }

    $file_tmp = $_FILES['file_excel']['tmp_name'];
    $handle = fopen($file_tmp, "r");

    if ($handle !== FALSE) {

        // Buang karakter BOM (Byte Order Mark) rahasia jika ada
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF")
            rewind($handle);

        // Deteksi Pemisah: Apakah Excel admin pakai koma (,) atau titik koma (;) ?
        $baris_pertama = fgets($handle);
        $delimiter = (strpos($baris_pertama, ';') !== false) ? ';' : ',';

        // Kembalikan kursor ke baris pertama setelah dicek
        rewind($handle);
        if ($bom === "\xEF\xBB\xBF")
            fread($handle, 3);

        // 1. BACA HEADER KOLOM
        $headers = fgetcsv($handle, 10000, $delimiter);
        if (empty($headers) || count($headers) < 2) {
            die("File kosong atau format berantakan.");
        }

        $map = [];

        // 2. PEMETAAN KOLOM CERDAS
        foreach ($headers as $index => $nama_kolom) {
            $kolom_bersih = strtolower(trim($nama_kolom));

            if (in_array($kolom_bersih, ['nipd', 'nisn', 'nis', 'no induk', 'nomor induk']))
                $map['no_induk'] = $index;
            elseif (in_array($kolom_bersih, ['nama peserta didik', 'nama', 'nama siswa']))
                $map['nama'] = $index;
            elseif (in_array($kolom_bersih, ['jk', 'jenis kelamin', 'l/p']))
                $map['jk'] = $index;
            elseif (in_array($kolom_bersih, ['tempat lahir', 'kota lahir']))
                $map['kota'] = $index;
            elseif (in_array($kolom_bersih, ['tanggal lahir', 'tgl lahir']))
                $map['tgl_lahir'] = $index;
            elseif (in_array($kolom_bersih, ['alamat jalan', 'alamat', 'tempat tinggal']))
                $map['alamat'] = $index;
            elseif (in_array($kolom_bersih, ['nama ayah', 'nama ayah kandung']))
                $map['nama_ayah'] = $index;
            elseif (in_array($kolom_bersih, ['pekerjaan ayah']))
                $map['pekerjaan_ayah'] = $index;
            elseif (in_array($kolom_bersih, ['nama ibu kandung', 'nama ibu']))
                $map['nama_ibu'] = $index;
            elseif (in_array($kolom_bersih, ['pekerjaan ibu kandung', 'pekerjaan ibu']))
                $map['pekerjaan_ibu'] = $index;
            elseif (in_array($kolom_bersih, ['nomor telepon seluler', 'no hp', 'hp ortu', 'no telepon']))
                $map['no_hp'] = $index;
            elseif (in_array($kolom_bersih, ['rombel saat ini', 'rombongan belajar', 'kelas', 'nama kelas']))
                $map['kelas'] = $index;
        }

        if (!isset($map['no_induk']) || !isset($map['nama'])) {
            die("Gagal: Kolom wajib 'No Induk' dan 'Nama' tidak ditemukan di baris pertama CSV!");
        }

        $tahun_aktif = fetchOne("SELECT id_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
        if (!$tahun_aktif)
            die("Tidak ada tahun ajaran aktif!");
        $id_tahun = $tahun_aktif['id_tahun'];

        $sukses = 0;
        global $pdo;

        // 3. EKSEKUSI BACA BARIS DATA
        while (($row = fgetcsv($handle, 10000, $delimiter)) !== FALSE) {
            if (empty(array_filter($row)))
                continue;

            $no_induk = isset($map['no_induk']) && isset($row[$map['no_induk']]) ? trim($row[$map['no_induk']]) : '';
            $nama = isset($map['nama']) && isset($row[$map['nama']]) ? trim($row[$map['nama']]) : '';

            if (empty($no_induk) || empty($nama))
                continue;

            $jk = isset($map['jk']) && isset($row[$map['jk']]) && trim($row[$map['jk']]) != '' ? trim($row[$map['jk']]) : 'L';
            $kota = isset($map['kota']) && isset($row[$map['kota']]) ? trim($row[$map['kota']]) : null;
            $tgl_lahir = isset($map['tgl_lahir']) && isset($row[$map['tgl_lahir']]) && trim($row[$map['tgl_lahir']]) != '' ? trim($row[$map['tgl_lahir']]) : null;
            $alamat = isset($map['alamat']) && isset($row[$map['alamat']]) ? trim($row[$map['alamat']]) : null;
            $nama_ayah = isset($map['nama_ayah']) && isset($row[$map['nama_ayah']]) ? trim($row[$map['nama_ayah']]) : null;
            $pekerjaan_ayah = isset($map['pekerjaan_ayah']) && isset($row[$map['pekerjaan_ayah']]) ? trim($row[$map['pekerjaan_ayah']]) : null;
            $nama_ibu = isset($map['nama_ibu']) && isset($row[$map['nama_ibu']]) ? trim($row[$map['nama_ibu']]) : null;
            $pekerjaan_ibu = isset($map['pekerjaan_ibu']) && isset($row[$map['pekerjaan_ibu']]) ? trim($row[$map['pekerjaan_ibu']]) : null;
            $no_hp = isset($map['no_hp']) && isset($row[$map['no_hp']]) ? trim($row[$map['no_hp']]) : null;
            $nama_kelas_xls = isset($map['kelas']) && isset($row[$map['kelas']]) ? trim($row[$map['kelas']]) : null;

            // A. Simpan ke tb_siswa
            $stmt = $pdo->prepare("
                INSERT INTO tb_siswa 
                (no_induk, nama_siswa, jenis_kelamin, kota, tanggal_lahir, alamat, nama_ayah, pekerjaan_ayah, nama_ibu, pekerjaan_ibu, no_hp_ortu, status_aktif) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Aktif')
                ON DUPLICATE KEY UPDATE 
                nama_siswa = VALUES(nama_siswa), jenis_kelamin = VALUES(jenis_kelamin), kota = VALUES(kota),
                tanggal_lahir = VALUES(tanggal_lahir), alamat = VALUES(alamat), nama_ayah = VALUES(nama_ayah),
                pekerjaan_ayah = VALUES(pekerjaan_ayah), nama_ibu = VALUES(nama_ibu), pekerjaan_ibu = VALUES(pekerjaan_ibu),
                no_hp_ortu = VALUES(no_hp_ortu), status_aktif = 'Aktif'
            ");
            $stmt->execute([$no_induk, $nama, $jk, $kota, $tgl_lahir, $alamat, $nama_ayah, $pekerjaan_ayah, $nama_ibu, $pekerjaan_ibu, $no_hp]);

            // B. Simpan ke tb_anggota_kelas
            if (!empty($nama_kelas_xls)) {
                $stmt_kelas = $pdo->prepare("SELECT id_kelas FROM tb_kelas WHERE nama_kelas = ? LIMIT 1");
                $stmt_kelas->execute([$nama_kelas_xls]);
                $kelas_db = $stmt_kelas->fetch(PDO::FETCH_ASSOC);

                if ($kelas_db) {
                    $id_kelas = $kelas_db['id_kelas'];
                    $stmt_cek = $pdo->prepare("SELECT id_anggota FROM tb_anggota_kelas WHERE no_induk = ? AND id_tahun = ?");
                    $stmt_cek->execute([$no_induk, $id_tahun]);

                    if ($stmt_cek->rowCount() == 0) {
                        $stmt_ins_kls = $pdo->prepare("INSERT INTO tb_anggota_kelas (no_induk, id_kelas, id_tahun) VALUES (?, ?, ?)");
                        $stmt_ins_kls->execute([$no_induk, $id_kelas, $id_tahun]);
                    }
                    else {
                        $stmt_upd_kls = $pdo->prepare("UPDATE tb_anggota_kelas SET id_kelas = ? WHERE no_induk = ? AND id_tahun = ?");
                        $stmt_upd_kls->execute([$id_kelas, $no_induk, $id_tahun]);
                    }
                }
            }
            $sukses++;
        }

        fclose($handle);
        $_SESSION['flash_message'] = "Berhasil import/update $sukses data siswa!";
        header("Location: ../views/admin/data_siswa.php");
        exit;

    }
    else {
        die("Gagal membuka file.");
    }
}
?>