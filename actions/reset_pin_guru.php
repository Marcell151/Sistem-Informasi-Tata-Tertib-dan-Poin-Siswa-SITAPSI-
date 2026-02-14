<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

$id_guru = $_GET['id'] ?? null;
$new_pin = $_GET['pin'] ?? null;

if (!$id_guru || !$new_pin) {
    $_SESSION['error_message'] = '❌ Data tidak valid';
    header('Location: ../views/admin/data_guru.php');
    exit;
}

try {
    executeQuery("UPDATE tb_guru SET pin = :pin WHERE id_guru = :id", [
        'pin' => $new_pin,
        'id' => $id_guru
    ]);
    
    $_SESSION['success_message'] = '✅ PIN berhasil direset!';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal reset PIN: ' . $e->getMessage();
}

header('Location: ../views/admin/data_guru.php');
exit;
?>