<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

$id_kelas = $_GET['id'] ?? null;

if (!$id_kelas) {
    $_SESSION['error_message'] = '❌ ID kelas tidak valid';
    header('Location: ../views/admin/manajemen_kelas.php');
    exit;
}

try {
    // Cek apakah ada siswa di kelas ini
    $check_siswa = fetchOne("
        SELECT COUNT(*) as total 
        FROM tb_anggota_kelas 
        WHERE id_kelas = :id
    ", ['id' => $id_kelas]);
    
    if ($check_siswa['total'] > 0) {
        throw new Exception('Tidak dapat menghapus! Masih ada ' . $check_siswa['total'] . ' siswa di kelas ini.');
    }
    
    executeQuery("DELETE FROM tb_kelas WHERE id_kelas = :id", ['id' => $id_kelas]);
    
    $_SESSION['success_message'] = '✅ Kelas berhasil dihapus!';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal menghapus: ' . $e->getMessage();
}

header('Location: ../views/admin/manajemen_kelas.php');
exit;
?>