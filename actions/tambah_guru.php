<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/data_guru.php');
    exit;
}

try {
    $nama_guru = trim($_POST['nama_guru']);
    $pin = trim($_POST['pin']);
    
    if (empty($nama_guru) || empty($pin)) {
        throw new Exception('Nama guru dan PIN wajib diisi');
    }
    
    if (!preg_match('/^[0-9]{6}$/', $pin)) {
        throw new Exception('PIN harus 6 digit angka');
    }
    
    executeQuery("
        INSERT INTO tb_guru (nama_guru, pin, status)
        VALUES (:nama_guru, :pin, 'Aktif')
    ", [
        'nama_guru' => $nama_guru,
        'pin' => $pin
    ]);
    
    $_SESSION['success_message'] = '✅ Guru berhasil ditambahkan!';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal menambah guru: ' . $e->getMessage();
}

header('Location: ../views/admin/data_guru.php');
exit;
?>