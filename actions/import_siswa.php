<?php
/**
 * SITAPSI - Action Import Siswa
 * Dilengkapi dengan Validasi Ekstensi File & Notifikasi UI
 */
session_start();
require_once '../config/database.php';

// Validasi Keamanan
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'SuperAdmin' && $_SESSION['role'] !== 'Admin')) {
    die("Akses ditolak!");
}

if (isset($_POST) && isset($_FILES['file_excel'])) {
    
    // 1. CEK ERROR UPLOAD DASAR
    if ($_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error_message'] = "Terjadi kesalahan sistem saat mengunggah file.";
        header("Location: ../views/admin/data_siswa.php");
        exit;
    }

    // 2. VALIDASI EKSTENSI FILE (BLOKIR VIRUS ATAU FILE SALAH)
    $fileName = $_FILES['file_excel']['name'];
    $fileTmpName = $_FILES['file_excel']['tmp_name'];
    
    // Ambil ekstensi (contoh: 'csv', 'php', 'jpg')
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Daftar ekstensi yang diizinkan (Hanya CSV)
    $allowedExtensions = ['csv']; 

    if (!in_array($fileExtension, $allowedExtensions)) {
        // TENDANG KELUAR JIKA BUKAN CSV
        $_SESSION['error_message'] = "Format file ditolak! Anda mengunggah file .$fileExtension. Silakan gunakan template CSV yang disediakan.";
        header("Location: ../views/admin/data_siswa.php");
        exit;
    }

    // 3. PROSES BACA FILE
    $handle = fopen($fileTmpName, "r");
    if ($handle !== FALSE) {
        
        // Deteksi Pemisah
        $baris_pertama = fgets($handle);
        $delimiter = (strpos($baris_pertama, ';') !== false) ? ';' : ',';
        rewind($handle); // Kembalikan ke baris 1

        $headers = fgetcsv($handle, 10000, $delimiter);
        
        // Hapus karakter gaib (BOM) di awal file jika ada
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);
        }
        
        if (empty($headers) || count($headers) < 2) {
            $_SESSION['error_message'] = "Gagal! File CSV kosong atau format kolom berantakan.";
            header("Location: ../views/admin/data_siswa.php");
            exit;
        }

        // Mapping Header (Mencari letak kolom)
        $map = [];
        foreach ($headers as $index => $col_name) {
            $col_name = strtolower(trim($col_name));
            if (strpos($col_name, 'induk') !== false) $map['no_induk'] = $index;
            elseif (strpos($col_name, 'nama peserta') !== false || strpos($col_name, 'nama siswa') !== false || $col_name == 'nama') $map['nama'] = $index;
            elseif ($col_name == 'l/p' || strpos($col_name, 'kelamin') !== false) $map['jk'] = $index;
            elseif (strpos($col_name, 'tempat') !== false) $map['kota'] = $index;
            elseif (strpos($col_name, 'tanggal lahir') !== false || strpos($col_name, 'tgl lahir') !== false) $map['tgl_lahir'] = $index;
            elseif (strpos($col_name, 'alamat') !== false) $map['alamat'] = $index;
            elseif (strpos($col_name, 'ayah') !== false && strpos($col_name, 'pekerjaan') === false) $map['nama_ayah'] = $index;
            elseif (strpos($col_name, 'pekerjaan ayah') !== false) $map['pekerjaan_ayah'] = $index;
            elseif (strpos($col_name, 'ibu') !== false && strpos($col_name, 'pekerjaan') === false) $map['nama_ibu'] = $index;
            elseif (strpos($col_name, 'pekerjaan ibu') !== false) $map['pekerjaan_ibu'] = $index;
            elseif (strpos($col_name, 'hp') !== false) $map['no_hp'] = $index;
            elseif (strpos($col_name, 'kelas') !== false) $map['kelas'] = $index;
        }

        if (!isset($map['no_induk']) || !isset($map['nama'])) {
            $_SESSION['error_message'] = "Gagal! Kolom 'No Induk' dan 'Nama' wajib ada di dalam file.";
            header("Location: ../views/admin/data_siswa.php");
            exit;
        }

        // 4. KONEKSI MANDIRI & INSERT DATABASE
        try {
            $db_import = new PDO("mysql:host=localhost;dbname=db_sitapsi;charset=utf8", "root", "");
            $db_import->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Ambil Tahun Aktif
            $stmt_tahun = $db_import->query("SELECT id_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
            $tahun_aktif = $stmt_tahun->fetch(PDO::FETCH_ASSOC);
            $id_tahun = $tahun_aktif ? $tahun_aktif['id_tahun'] : null;

            if (!$id_tahun) {
                $_SESSION['error_message'] = "Gagal! Tidak ada Tahun Ajaran yang diatur sebagai Aktif.";
                header("Location: ../views/admin/data_siswa.php");
                exit;
            }

            $sukses = 0;
            while (($row = fgetcsv($handle, 10000, $delimiter)) !== FALSE) {
                if (empty(array_filter($row))) continue; 

                $no_induk = isset($map['no_induk']) && isset($row[$map['no_induk']]) ? trim($row[$map['no_induk']]) : '';
                $nama     = isset($map['nama']) && isset($row[$map['nama']]) ? trim($row[$map['nama']]) : '';
                
                if (empty($no_induk) || empty($nama)) continue;

                $jk             = isset($map['jk']) && isset($row[$map['jk']]) && trim($row[$map['jk']]) != '' ? trim($row[$map['jk']]) : 'L';
                $kota           = isset($map['kota']) && isset($row[$map['kota']]) ? trim($row[$map['kota']]) : null;
                $tgl_lahir      = isset($map['tgl_lahir']) && isset($row[$map['tgl_lahir']]) && trim($row[$map['tgl_lahir']]) != '' ? trim($row[$map['tgl_lahir']]) : null;
                $alamat         = isset($map['alamat']) && isset($row[$map['alamat']]) ? trim($row[$map['alamat']]) : null;
                $nama_ayah      = isset($map['nama_ayah']) && isset($row[$map['nama_ayah']]) ? trim($row[$map['nama_ayah']]) : null;
                $pekerjaan_ayah = isset($map['pekerjaan_ayah']) && isset($row[$map['pekerjaan_ayah']]) ? trim($row[$map['pekerjaan_ayah']]) : null;
                $nama_ibu       = isset($map['nama_ibu']) && isset($row[$map['nama_ibu']]) ? trim($row[$map['nama_ibu']]) : null;
                $pekerjaan_ibu  = isset($map['pekerjaan_ibu']) && isset($row[$map['pekerjaan_ibu']]) ? trim($row[$map['pekerjaan_ibu']]) : null;
                $no_hp          = isset($map['no_hp']) && isset($row[$map['no_hp']]) ? trim($row[$map['no_hp']]) : null;
                $nama_kelas_xls = isset($map['kelas']) && isset($row[$map['kelas']]) ? trim($row[$map['kelas']]) : null;

                // A. Simpan ke tb_siswa
                $stmt = $db_import->prepare("
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
                    $stmt_kelas = $db_import->prepare("SELECT id_kelas FROM tb_kelas WHERE nama_kelas = ? LIMIT 1");
                    $stmt_kelas->execute([$nama_kelas_xls]);
                    $kelas_db = $stmt_kelas->fetch(PDO::FETCH_ASSOC);

                    if ($kelas_db) {
                        $id_kelas = $kelas_db['id_kelas'];
                        $stmt_cek = $db_import->prepare("SELECT id_anggota FROM tb_anggota_kelas WHERE no_induk = ? AND id_tahun = ?");
                        $stmt_cek->execute([$no_induk, $id_tahun]);
                        
                        if ($stmt_cek->rowCount() == 0) {
                            $stmt_ins_kls = $db_import->prepare("INSERT INTO tb_anggota_kelas (no_induk, id_kelas, id_tahun) VALUES (?, ?, ?)");
                            $stmt_ins_kls->execute([$no_induk, $id_kelas, $id_tahun]);
                        } else {
                            $stmt_upd_kls = $db_import->prepare("UPDATE tb_anggota_kelas SET id_kelas = ? WHERE no_induk = ? AND id_tahun = ?");
                            $stmt_upd_kls->execute([$id_kelas, $no_induk, $id_tahun]);
                        }
                    }
                }
                $sukses++;
            }

            fclose($handle);
            
            // PESAN SUKSES
            $_SESSION['success_message'] = "Berhasil memproses $sukses data siswa!";
            header("Location: ../views/admin/data_siswa.php");
            exit;

        } catch (Exception $e) {
            $_SESSION['error_message'] = "Terjadi kesalahan sistem: " . $e->getMessage();
            header("Location: ../views/admin/data_siswa.php");
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Gagal membuka file. Pastikan file tidak sedang dibuka di Excel.";
        header("Location: ../views/admin/data_siswa.php");
        exit;
    }
} else {
    header("Location: ../views/admin/data_siswa.php");
    exit;
}
?>