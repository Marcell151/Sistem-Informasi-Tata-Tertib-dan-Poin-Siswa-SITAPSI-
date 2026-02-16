<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

$id_sanksi_ref = $_GET['id'] ?? null;

if (!$id_sanksi_ref) {
    $_SESSION['error_message'] = '❌ ID tidak valid';
    header('Location: ../views/admin/manajemen_aturan.php?tab=sanksi');
    exit;
}

try {
    // Cek apakah ada transaksi yang menggunakan sanksi ini
    $check = fetchOne("
        SELECT COUNT(*) as total 
        FROM tb_pelanggaran_sanksi 
        WHERE id_sanksi_ref = :id
    ", ['id' => $id_sanksi_ref]);
    
    if ($check['total'] > 0) {
        throw new Exception('Tidak dapat menghapus! Sanksi ini sudah digunakan di ' . $check['total'] . ' transaksi.');
    }
    
    executeQuery("DELETE FROM tb_sanksi_ref WHERE id_sanksi_ref = :id", ['id' => $id_sanksi_ref]);
    
    $_SESSION['success_message'] = '✅ Sanksi berhasil dihapus!';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal menghapus: ' . $e->getMessage();
}

header('Location: ../views/admin/manajemen_aturan.php?tab=sanksi');
exit;
?>