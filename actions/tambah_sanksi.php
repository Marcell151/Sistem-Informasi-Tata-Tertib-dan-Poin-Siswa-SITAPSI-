<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/manajemen_aturan.php?tab=sanksi');
    exit;
}

try {
    $kode_sanksi = trim($_POST['kode_sanksi']);
    $deskripsi = trim($_POST['deskripsi']);
    
    if (empty($kode_sanksi) || empty($deskripsi)) {
        throw new Exception('Kode dan deskripsi sanksi wajib diisi');
    }
    
    // Cek duplikasi kode
    $check = fetchOne("SELECT id_sanksi_ref FROM tb_sanksi_ref WHERE kode_sanksi = :kode", ['kode' => $kode_sanksi]);
    if ($check) {
        throw new Exception('Kode sanksi sudah digunakan!');
    }
    
    executeQuery("
        INSERT INTO tb_sanksi_ref (kode_sanksi, deskripsi)
        VALUES (:kode_sanksi, :deskripsi)
    ", [
        'kode_sanksi' => $kode_sanksi,
        'deskripsi' => $deskripsi
    ]);
    
    $_SESSION['success_message'] = '✅ Sanksi berhasil ditambahkan!';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal menambah: ' . $e->getMessage();
}

header('Location: ../views/admin/manajemen_aturan.php?tab=sanksi');
exit;
?>