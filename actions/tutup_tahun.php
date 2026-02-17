<?php
/**
 * SITAPSI - Tutup Tahun Ajaran
 * Arsip tahun lama, buat tahun baru, auto kelulusan kelas 9
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/pengaturan_akademik.php');
    exit;
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    $nama_tahun_baru = trim($_POST['nama_tahun_baru']);
    
    if (empty($nama_tahun_baru)) {
        throw new Exception('Nama tahun ajaran baru wajib diisi');
    }
    
    // 1. Arsipkan tahun lama
    executeQuery("UPDATE tb_tahun_ajaran SET status = 'Arsip' WHERE status = 'Aktif'");
    
    // 2. Buat tahun baru
    executeQuery("
        INSERT INTO tb_tahun_ajaran (nama_tahun, semester_aktif, status)
        VALUES (:nama_tahun, 'Ganjil', 'Aktif')
    ", ['nama_tahun' => $nama_tahun_baru]);
    
    $id_tahun_baru = getLastInsertId();
    
    // 3. Keluluskan siswa kelas 9 (set status jadi Lulus)
    $kelas_9 = fetchAll("SELECT id_kelas FROM tb_kelas WHERE nama_kelas LIKE '9%'");
    
    if (!empty($kelas_9)) {
        $kelas_ids = array_column($kelas_9, 'id_kelas');
        $placeholders = implode(',', array_fill(0, count($kelas_ids), '?'));
        
        $stmt = $pdo->prepare("
            UPDATE tb_siswa 
            SET status_aktif = 'Lulus' 
            WHERE nis IN (
                SELECT DISTINCT nis FROM tb_anggota_kelas 
                WHERE id_kelas IN ($placeholders)
            )
        ");
        $stmt->execute($kelas_ids);
    }
    
    // 4. Copy siswa aktif ke tahun baru (kecuali yang sudah lulus)
    $siswa_aktif = fetchAll("
        SELECT DISTINCT s.nis, a.id_kelas
        FROM tb_siswa s
        JOIN tb_anggota_kelas a ON s.nis = a.nis
        WHERE s.status_aktif = 'Aktif'
    ");
    
    foreach ($siswa_aktif as $siswa) {
        executeQuery("
            INSERT INTO tb_anggota_kelas (nis, id_kelas, id_tahun)
            VALUES (:nis, :id_kelas, :id_tahun)
        ", [
            'nis' => $siswa['nis'],
            'id_kelas' => $siswa['id_kelas'],
            'id_tahun' => $id_tahun_baru
        ]);
    }
    
    $pdo->commit();
    
    $_SESSION['success_message'] = "✅ Tahun ajaran berhasil ditutup! Tahun $nama_tahun_baru telah aktif. Siswa kelas 9 sudah diluluskan.";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = '❌ Gagal tutup tahun: ' . $e->getMessage();
}

header('Location: ../views/admin/pengaturan_akademik.php');
exit;
?>