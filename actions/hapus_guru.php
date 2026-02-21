<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

$id_guru = $_GET['id'] ?? null;

if (!$id_guru) {
    $_SESSION['error_message'] = '❌ ID guru tidak valid';
    header('Location: ../views/admin/data_guru.php');
    exit;
}

try {
    // Cek apakah guru pernah input pelanggaran
    $cek_transaksi = fetchOne("
        SELECT COUNT(*) as total 
        FROM tb_pelanggaran_header 
        WHERE id_guru = :id
    ", ['id' => $id_guru]);
    
    if ($cek_transaksi['total'] > 0) {
        throw new Exception('Tidak dapat menghapus! Guru ini memiliki riwayat input pelanggaran. Ubah status menjadi Non-Aktif.');
    }
    
    executeQuery("DELETE FROM tb_guru WHERE id_guru = :id", ['id' => $id_guru]);
    
    $_SESSION['success_message'] = '✅ Guru berhasil dihapus!';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal: ' . $e->getMessage();
}

header('Location: ../views/admin/data_guru.php');
exit;
?>