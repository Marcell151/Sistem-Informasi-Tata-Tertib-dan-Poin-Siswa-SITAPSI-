<?php
/**
 * SITAPSI - Hapus Siswa
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

$nis = $_GET['nis'] ?? null;

if (!$nis) {
    $_SESSION['error_message'] = '❌ NIS tidak valid';
    header('Location: ../views/admin/data_siswa.php');
    exit;
}

try {
    // Hapus siswa (CASCADE akan handle tb_anggota_kelas dan pelanggaran)
    executeQuery("DELETE FROM tb_siswa WHERE nis = :nis", ['nis' => $nis]);
    
    $_SESSION['success_message'] = '✅ Siswa berhasil dihapus';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal menghapus: ' . $e->getMessage();
}

header('Location: ../views/admin/data_siswa.php');
exit;
?>