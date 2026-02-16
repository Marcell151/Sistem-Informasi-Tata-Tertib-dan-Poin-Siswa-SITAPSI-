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
    $id_sanksi_ref = $_POST['id_sanksi_ref'];
    $kode_sanksi = trim($_POST['kode_sanksi']);
    $deskripsi = trim($_POST['deskripsi']);
    
    if (empty($id_sanksi_ref) || empty($kode_sanksi) || empty($deskripsi)) {
        throw new Exception('Semua field wajib diisi');
    }
    
    // Cek duplikasi kode (kecuali diri sendiri)
    $check = fetchOne("
        SELECT id_sanksi_ref 
        FROM tb_sanksi_ref 
        WHERE kode_sanksi = :kode 
        AND id_sanksi_ref != :id
    ", [
        'kode' => $kode_sanksi,
        'id' => $id_sanksi_ref
    ]);
    
    if ($check) {
        throw new Exception('Kode sanksi sudah digunakan oleh sanksi lain!');
    }
    
    executeQuery("
        UPDATE tb_sanksi_ref 
        SET kode_sanksi = :kode_sanksi,
            deskripsi = :deskripsi
        WHERE id_sanksi_ref = :id
    ", [
        'kode_sanksi' => $kode_sanksi,
        'deskripsi' => $deskripsi,
        'id' => $id_sanksi_ref
    ]);
    
    $_SESSION['success_message'] = '✅ Sanksi berhasil diupdate!';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal mengupdate: ' . $e->getMessage();
}

header('Location: ../views/admin/manajemen_aturan.php?tab=sanksi');
exit;
?>