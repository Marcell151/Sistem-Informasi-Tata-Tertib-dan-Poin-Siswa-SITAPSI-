<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

$id_jenis = $_GET['id'] ?? null;

if (!$id_jenis) {
    $_SESSION['error_message'] = '❌ ID tidak valid';
    header('Location: ../views/admin/manajemen_aturan.php');
    exit;
}

try {
    // Cek apakah ada transaksi yang menggunakan pelanggaran ini
    $check = fetchOne("
        SELECT COUNT(*) as total 
        FROM tb_pelanggaran_detail 
        WHERE id_jenis = :id
    ", ['id' => $id_jenis]);
    
    if ($check['total'] > 0) {
        throw new Exception('Tidak dapat menghapus! Pelanggaran ini sudah digunakan di ' . $check['total'] . ' transaksi.');
    }
    
    executeQuery("DELETE FROM tb_jenis_pelanggaran WHERE id_jenis = :id", ['id' => $id_jenis]);
    
    $_SESSION['success_message'] = '✅ Jenis pelanggaran berhasil dihapus!';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal menghapus: ' . $e->getMessage();
}

header('Location: ../views/admin/manajemen_aturan.php');
exit;
?>