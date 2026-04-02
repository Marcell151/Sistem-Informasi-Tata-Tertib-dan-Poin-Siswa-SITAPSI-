<?php
session_start();
require_once '../config/database.php'; // Panggil config

// Validasi hanya Admin yang boleh
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'SuperAdmin'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_sp = $_POST['id_sp'] ?? '';
    $catatan_admin = $_POST['catatan_admin'] ?? '';
    
    // Fallback URL jika HTTP_REFERER kosong
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? '../views/admin/manajemen_sp.php';

    if (empty($id_sp) || empty(trim($catatan_admin))) {
        $_SESSION['error_message'] = "Gagal mengirim pesan. Pesan tidak boleh kosong.";
        header("Location: " . $redirect_url);
        exit;
    }

    try {
        // Bersihkan input agar aman
        $catatan_bersih = htmlspecialchars(trim($catatan_admin), ENT_QUOTES, 'UTF-8');

        // MENGGUNAKAN FUNGSI executeQuery() BAWAAN ANDA
        executeQuery("UPDATE tb_riwayat_sp SET catatan_admin = ? WHERE id_sp = ?", [$catatan_bersih, $id_sp]);

        $_SESSION['success_message'] = "Pesan berhasil dikirim dan akan langsung muncul di Portal Wali Murid.";
        header("Location: " . $redirect_url);
        exit;

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Terjadi kesalahan sistem: " . $e->getMessage();
        header("Location: " . $redirect_url);
        exit;
    }
} else {
    header("Location: ../views/admin/manajemen_sp.php");
    exit;
}
?>