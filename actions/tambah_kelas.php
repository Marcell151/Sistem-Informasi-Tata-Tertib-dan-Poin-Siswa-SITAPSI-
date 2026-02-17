<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/manajemen_kelas.php');
    exit;
}

try {
    $tingkat = (int)$_POST['tingkat'];
    $nama_kelas = trim($_POST['nama_kelas']);
    
    if (empty($nama_kelas) || !in_array($tingkat, [7, 8, 9])) {
        throw new Exception('Data tidak valid');
    }
    
    // Cek duplikasi nama kelas
    $check = fetchOne("SELECT id_kelas FROM tb_kelas WHERE nama_kelas = :nama", ['nama' => $nama_kelas]);
    if ($check) {
        throw new Exception('Nama kelas sudah digunakan!');
    }
    
    executeQuery("
        INSERT INTO tb_kelas (nama_kelas, tingkat)
        VALUES (:nama_kelas, :tingkat)
    ", [
        'nama_kelas' => $nama_kelas,
        'tingkat' => $tingkat
    ]);
    
    $_SESSION['success_message'] = "✅ Kelas $nama_kelas berhasil ditambahkan!";
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal menambah kelas: ' . $e->getMessage();
}

header('Location: ../views/admin/manajemen_kelas.php');
exit;
?>