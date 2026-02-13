<?php
/**
 * SITAPSI - Validasi SP
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

$id_sp = $_GET['id'] ?? null;

if (!$id_sp) {
    $_SESSION['error_message'] = '❌ ID SP tidak valid';
    header('Location: ../views/admin/manajemen_sp.php');
    exit;
}

try {
    executeQuery("
        UPDATE tb_riwayat_sp 
        SET status = 'Selesai', 
            tanggal_validasi = CURDATE(),
            id_admin = :admin_id
        WHERE id_sp = :id_sp
    ", [
        'admin_id' => $_SESSION['user_id'],
        'id_sp' => $id_sp
    ]);
    
    $_SESSION['success_message'] = '✅ SP berhasil divalidasi';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal validasi: ' . $e->getMessage();
}

header('Location: ../views/admin/manajemen_sp.php');
exit;
?>