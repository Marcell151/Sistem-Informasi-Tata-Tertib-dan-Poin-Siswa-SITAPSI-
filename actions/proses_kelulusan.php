<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    // Cari kelas 9 (asumsi: nama_kelas LIKE '9%')
    $kelas_9 = fetchAll("SELECT id_kelas FROM tb_kelas WHERE nama_kelas LIKE '9%'");
    
    if (empty($kelas_9)) {
        throw new Exception('Tidak ada kelas 9 ditemukan');
    }
    
    $kelas_ids = array_column($kelas_9, 'id_kelas');
    $placeholders = implode(',', array_fill(0, count($kelas_ids), '?'));
    
    // Update status siswa jadi Lulus
    $stmt = $pdo->prepare("
        UPDATE tb_siswa 
        SET status_aktif = 'Lulus' 
        WHERE nis IN (
            SELECT nis FROM tb_anggota_kelas 
            WHERE id_kelas IN ($placeholders)
        )
    ");
    $stmt->execute($kelas_ids);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = '✅ Proses kelulusan selesai! Siswa kelas 9 telah diset status Lulus.';
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = '❌ Gagal proses kelulusan: ' . $e->getMessage();
}

header('Location: ../views/admin/pengaturan_akademik.php');
exit;
?>