<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/manajemen_aturan.php');
    exit;
}

try {
    $id_kategori = $_POST['id_kategori'];
    $sub_kategori = trim($_POST['sub_kategori']);
    $nama_pelanggaran = trim($_POST['nama_pelanggaran']);
    $poin_default = (int)$_POST['poin_default'];
    $sanksi_default = trim($_POST['sanksi_default']);
    
    if (empty($id_kategori) || empty($nama_pelanggaran) || $poin_default <= 0) {
        throw new Exception('Data wajib belum lengkap');
    }
    
    executeQuery("
        INSERT INTO tb_jenis_pelanggaran (id_kategori, sub_kategori, nama_pelanggaran, poin_default, sanksi_default)
        VALUES (:id_kategori, :sub_kategori, :nama_pelanggaran, :poin_default, :sanksi_default)
    ", [
        'id_kategori' => $id_kategori,
        'sub_kategori' => $sub_kategori,
        'nama_pelanggaran' => $nama_pelanggaran,
        'poin_default' => $poin_default,
        'sanksi_default' => $sanksi_default
    ]);
    
    $_SESSION['success_message'] = '✅ Jenis pelanggaran berhasil ditambahkan!';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal menambah: ' . $e->getMessage();
}

header('Location: ../views/admin/manajemen_aturan.php');
exit;
?>