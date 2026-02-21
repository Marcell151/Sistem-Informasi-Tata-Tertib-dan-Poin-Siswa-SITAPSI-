<?php
/**
 * SITAPSI - Simpan Pelanggaran (FIX - Upload Foto Bukti)
 */

date_default_timezone_set('Asia/Jakarta');

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';
require_once '../includes/sp_helper.php';

requireGuru();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Siapkan array untuk menampung nama file jika error agar bisa dihapus (rollback file)
    $foto_filenames = []; 
    
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();

        $id_anggota = $_POST['id_anggota'] ?? '';
        $pelanggaran_ids = $_POST['pelanggaran'] ?? [];
        $sanksi_ids = $_POST['sanksi'] ?? [];
        $tipe_form = $_POST['tipe_form'] ?? 'Piket';
        
        $id_guru = $_SESSION['user_id'] ?? null;

        $tanggal = !empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d');
        $waktu = !empty($_POST['waktu']) ? $_POST['waktu'] : date('H:i:s');

        // Validasi
        if (empty($id_guru)) throw new Exception('Sesi login Guru tidak terdeteksi.');
        if (empty($id_anggota)) throw new Exception('Siswa belum dipilih!');
        if (empty($pelanggaran_ids)) throw new Exception('Minimal pilih satu jenis pelanggaran!');

        $tahun_aktif = fetchOne("SELECT id_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
        if (!$tahun_aktif) throw new Exception('Tahun ajaran aktif tidak ditemukan di sistem.');

        // ========================================================
        // BLOK BARU: PROSES UPLOAD FOTO (MULTI UPLOAD)
        // ========================================================
        if (isset($_FILES['bukti_foto']) && is_array($_FILES['bukti_foto']['name'])) {
            $upload_dir = '../assets/uploads/bukti/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true); // Buat folder jika belum ada
            
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'heic'];

            foreach ($_FILES['bukti_foto']['name'] as $key => $name) {
                if ($_FILES['bukti_foto']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['bukti_foto']['tmp_name'][$key];
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    
                    if (in_array($ext, $allowed_ext)) {
                        // Generate nama unik
                        $new_name = "bukti_" . time() . "_" . uniqid() . "_" . $key . "." . $ext;
                        
                        if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                            $foto_filenames[] = $new_name; // Simpan ke array
                        }
                    }
                }
            }
        }
        
        // Ubah array jadi format JSON (misal: '["foto1.jpg", "foto2.png"]') atau NULL jika kosong
        $json_foto = !empty($foto_filenames) ? json_encode($foto_filenames) : null;
        // ========================================================

        // Insert header (FIX: Tambahkan kolom bukti_foto ke query)
        $stmtHeader = $pdo->prepare("
            INSERT INTO tb_pelanggaran_header 
            (id_anggota, id_guru, id_tahun, tanggal, waktu, semester, tipe_form, bukti_foto) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtHeader->execute([
            $id_anggota,
            $id_guru,
            $tahun_aktif['id_tahun'],
            $tanggal,
            $waktu,
            $tahun_aktif['semester_aktif'],
            $tipe_form,
            $json_foto // Parameter baru masuk ke sini
        ]);

        $id_transaksi = $pdo->lastInsertId();

        // Insert detail & hitung poin per kategori
        $poin_per_kategori = [1 => 0, 2 => 0, 3 => 0];

        $stmtDetail = $pdo->prepare("INSERT INTO tb_pelanggaran_detail (id_transaksi, id_jenis, poin_saat_itu) VALUES (?, ?, ?)");
        $stmtGetPoin = $pdo->prepare("SELECT poin_default, id_kategori FROM tb_jenis_pelanggaran WHERE id_jenis = ?");

        foreach ($pelanggaran_ids as $id_jenis) {
            $stmtGetPoin->execute([$id_jenis]);
            $pelanggaran = $stmtGetPoin->fetch(PDO::FETCH_ASSOC);
            
            if ($pelanggaran) {
                $stmtDetail->execute([$id_transaksi, $id_jenis, $pelanggaran['poin_default']]);
                $poin_per_kategori[$pelanggaran['id_kategori']] += $pelanggaran['poin_default'];
            }
        }

        // Insert sanksi
        if (!empty($sanksi_ids)) {
            $stmtSanksi = $pdo->prepare("INSERT INTO tb_pelanggaran_sanksi (id_transaksi, id_sanksi_ref) VALUES (?, ?)");
            foreach ($sanksi_ids as $id_sanksi) {
                $stmtSanksi->execute([$id_transaksi, $id_sanksi]);
            }
        }

        // Update poin di tb_anggota_kelas
        $sql_update = "
            UPDATE tb_anggota_kelas 
            SET poin_kelakuan = poin_kelakuan + :kelakuan,
                poin_kerajinan = poin_kerajinan + :kerajinan,
                poin_kerapian = poin_kerapian + :kerapian,
                total_poin_umum = total_poin_umum + :total
            WHERE id_anggota = :id
        ";

        $total_poin = array_sum($poin_per_kategori);
        executeQuery($sql_update, [
            'kelakuan' => $poin_per_kategori[1],
            'kerajinan' => $poin_per_kategori[2],
            'kerapian' => $poin_per_kategori[3],
            'total' => $total_poin,
            'id' => $id_anggota
        ]);

        $pdo->commit();

        // Recalculate SP
        recalculateStatusSP($id_anggota);

        $siswa = fetchOne("SELECT s.nama_siswa FROM tb_anggota_kelas a JOIN tb_siswa s ON a.nis = s.nis WHERE a.id_anggota = :id", ['id' => $id_anggota]);

        $_SESSION['success_message'] = "✅ Pelanggaran berhasil disimpan untuk " . htmlspecialchars($siswa['nama_siswa']) . " (+{$total_poin} Poin)";
        header("Location: ../views/guru/input_pelanggaran.php?mode=" . strtolower($tipe_form));
        exit;

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // CLEANUP: Hapus foto fisik jika database gagal disimpan agar tidak menumpuk sampah
        if (!empty($foto_filenames)) {
            foreach ($foto_filenames as $f) {
                $path = '../assets/uploads/bukti/' . $f;
                if (file_exists($path)) unlink($path);
            }
        }

        $_SESSION['error_message'] = "❌ Gagal: " . $e->getMessage();
        header("Location: ../views/guru/input_pelanggaran.php");
        exit;
    }
}
?>