<?php
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
    
    $tahun_aktif = fetchOne("SELECT id_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
    
    // Ambil semua kelas tingkat 9
    $kelas_9 = fetchAll("SELECT id_kelas FROM tb_kelas WHERE tingkat = 9");
    
    if (empty($kelas_9)) {
        throw new Exception('Tidak ada kelas 9 yang ditemukan');
    }
    
    $kelas_ids = array_column($kelas_9, 'id_kelas');
    $placeholders = implode(',', array_fill(0, count($kelas_ids), '?'));
    
    // Update status siswa kelas 9 menjadi Lulus
    $params = $kelas_ids;
    $params[] = $tahun_aktif['id_tahun'];
    
    $stmt = $pdo->prepare("
        UPDATE tb_siswa 
        SET status_aktif = 'Lulus' 
        WHERE nis IN (
            SELECT DISTINCT nis 
            FROM tb_anggota_kelas 
            WHERE id_kelas IN ($placeholders)
            AND id_tahun = ?
        )
    ");
    $stmt->execute($params);
    
    $jumlah_lulus = $stmt->rowCount();
    
    $pdo->commit();
    
    $_SESSION['success_message'] = "✅ Berhasil! $jumlah_lulus siswa kelas 9 telah diluluskan.";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = '❌ Gagal proses kelulusan: ' . $e->getMessage();
}

header('Location: ../views/admin/pengaturan_akademik.php');
exit;
?>