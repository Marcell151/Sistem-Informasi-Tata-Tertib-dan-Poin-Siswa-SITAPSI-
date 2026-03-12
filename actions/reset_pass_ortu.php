<?php
/**
 * SITAPSI - Action Reset Password Orang Tua oleh Admin
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

$id_ortu = $_GET['id'] ?? null;

if (!$id_ortu) {
    $_SESSION['error_message'] = '❌ ID Wali Murid tidak valid.';
    header('Location: ../views/admin/data_ortu.php');
    exit;
}

try {
    // Password default: 123456 (Di MD5 kan)
    $password_default_md5 = md5('123456');

    // Update password di database
    executeQuery("
        UPDATE tb_orang_tua 
        SET password = :pass 
        WHERE id_ortu = :id
    ", [
        'pass' => $password_default_md5,
        'id' => $id_ortu
    ]);

    $_SESSION['success_message'] = "✅ Password berhasil di-reset menjadi: 123456";

} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal me-reset password: ' . $e->getMessage();
}

header('Location: ../views/admin/data_ortu.php');
exit;