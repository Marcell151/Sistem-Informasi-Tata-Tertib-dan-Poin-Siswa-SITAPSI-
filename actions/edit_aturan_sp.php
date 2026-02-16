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
    $id_aturan_sp = $_POST['id_aturan_sp'];
    $batas_bawah_poin = (int)$_POST['batas_bawah_poin'];
    
    if (empty($id_aturan_sp) || $batas_bawah_poin < 0) {
        throw new Exception('Data tidak valid');
    }
    
    // PERBAIKAN: Gunakan kolom id_aturan_sp
    executeQuery("
        UPDATE tb_aturan_sp 
        SET batas_bawah_poin = :batas_bawah_poin
        WHERE id_aturan_sp = :id_aturan_sp
    ", [
        'batas_bawah_poin' => $batas_bawah_poin,
        'id_aturan_sp' => $id_aturan_sp
    ]);
    
    $_SESSION['success_message'] = '✅ Aturan SP berhasil diupdate!';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal mengupdate: ' . $e->getMessage();
}

header('Location: ../views/admin/manajemen_aturan.php');
exit;
?>