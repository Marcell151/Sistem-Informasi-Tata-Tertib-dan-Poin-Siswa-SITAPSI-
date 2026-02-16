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
    $id_guru = $_POST['id_guru'];
    $nama_guru = trim($_POST['nama_guru']);
    $nip = trim($_POST['nip']) ?: NULL;
    $pin_validasi = trim($_POST['pin_validasi']);
    
    if (empty($nama_guru) || empty($pin_validasi)) {
        throw new Exception('Nama guru dan PIN wajib diisi');
    }
    
    if (!preg_match('/^[0-9]{6}$/', $pin_validasi)) {
        throw new Exception('PIN harus 6 digit angka');
    }
    
    executeQuery("
        UPDATE tb_guru 
        SET nama_guru = :nama_guru,
            nip = :nip,
            pin_validasi = :pin_validasi
        WHERE id_guru = :id_guru
    ", [
        'nama_guru' => $nama_guru,
        'nip' => $nip,
        'pin_validasi' => $pin_validasi,
        'id_guru' => $id_guru
    ]);
    
    $_SESSION['success_message'] = '✅ Data guru berhasil diupdate!';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal mengupdate: ' . $e->getMessage();
}

header('Location: ../views/admin/data_guru.php');
exit;
?>