<?php
/**
 * SITAPSI - Edit Aturan Pelanggaran
 * Update data jenis pelanggaran di master data
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/manajemen_aturan.php');
    exit;
}

try {
    $id_jenis = $_POST['id_jenis'];
    $id_kategori = $_POST['id_kategori'];
    $sub_kategori = trim($_POST['sub_kategori']);
    $nama_pelanggaran = trim($_POST['nama_pelanggaran']);
    $poin_default = (int)$_POST['poin_default'];
    $sanksi_default = trim($_POST['sanksi_default']);
    
    // Validasi
    if (empty($id_jenis) || empty($nama_pelanggaran) || $poin_default < 0) {
        throw new Exception('Data tidak lengkap atau tidak valid');
    }
    
    // Update aturan pelanggaran
    executeQuery("
        UPDATE tb_jenis_pelanggaran 
        SET id_kategori = :id_kategori,
            sub_kategori = :sub_kategori,
            nama_pelanggaran = :nama_pelanggaran,
            poin_default = :poin_default,
            sanksi_default = :sanksi_default
        WHERE id_jenis = :id_jenis
    ", [
        'id_kategori' => $id_kategori,
        'sub_kategori' => $sub_kategori,
        'nama_pelanggaran' => $nama_pelanggaran,
        'poin_default' => $poin_default,
        'sanksi_default' => $sanksi_default,
        'id_jenis' => $id_jenis
    ]);
    
    $_SESSION['success_message'] = '✅ Aturan pelanggaran berhasil diupdate!';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal mengupdate: ' . $e->getMessage();
}

header('Location: ../views/admin/manajemen_aturan.php');
exit;
?>