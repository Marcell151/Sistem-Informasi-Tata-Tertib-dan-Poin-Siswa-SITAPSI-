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
    $id_kelas = $_POST['id_kelas'];
    $tingkat = (int)$_POST['tingkat'];
    $nama_kelas = trim($_POST['nama_kelas']);
    
    if (empty($id_kelas) || empty($nama_kelas) || !in_array($tingkat, [7, 8, 9])) {
        throw new Exception('Data tidak valid');
    }
    
    // Cek duplikasi nama kelas (kecuali diri sendiri)
    $check = fetchOne("
        SELECT id_kelas 
        FROM tb_kelas 
        WHERE nama_kelas = :nama 
        AND id_kelas != :id
    ", [
        'nama' => $nama_kelas,
        'id' => $id_kelas
    ]);
    
    if ($check) {
        throw new Exception('Nama kelas sudah digunakan oleh kelas lain!');
    }
    
    executeQuery("
        UPDATE tb_kelas 
        SET nama_kelas = :nama_kelas,
            tingkat = :tingkat
        WHERE id_kelas = :id
    ", [
        'nama_kelas' => $nama_kelas,
        'tingkat' => $tingkat,
        'id' => $id_kelas
    ]);
    
    $_SESSION['success_message'] = "✅ Kelas berhasil diupdate!";
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal mengupdate: ' . $e->getMessage();
}

header('Location: ../views/admin/manajemen_kelas.php');
exit;
?>