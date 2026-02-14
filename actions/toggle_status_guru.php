<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

$id_guru = $_GET['id'] ?? null;

if (!$id_guru) {
    $_SESSION['error_message'] = '❌ ID tidak valid';
    header('Location: ../views/admin/data_guru.php');
    exit;
}

try {
    executeQuery("
        UPDATE tb_guru 
        SET status = CASE 
            WHEN status = 'Aktif' THEN 'Nonaktif' 
            ELSE 'Aktif' 
        END 
        WHERE id_guru = :id
    ", ['id' => $id_guru]);
    
    $_SESSION['success_message'] = '✅ Status guru berhasil diubah!';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal mengubah status: ' . $e->getMessage();
}

header('Location: ../views/admin/data_guru.php');
exit;
?>