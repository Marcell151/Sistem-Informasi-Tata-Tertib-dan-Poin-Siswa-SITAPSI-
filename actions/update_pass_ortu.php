<?php
/**
 * SITAPSI - Action Update Password oleh Orang Tua Sendiri
 */

session_start();
require_once '../config/database.php';

// Validasi akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Ortu') {
    header("Location: ../views/ortu/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_ortu = $_SESSION['ortu_id'];
    $old_pass = $_POST['old_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $conf_pass = $_POST['confirm_password'] ?? '';

    if (empty($old_pass) || empty($new_pass) || empty($conf_pass)) {
        $_SESSION['error_message'] = "⚠️ Semua kolom sandi harus diisi.";
        header("Location: ../views/ortu/dashboard.php");
        exit;
    }

    if ($new_pass !== $conf_pass) {
        $_SESSION['error_message'] = "⚠️ Konfirmasi kata sandi baru tidak cocok.";
        header("Location: ../views/ortu/dashboard.php");
        exit;
    }

    try {
        // 1. Verifikasi Password Lama
        $hashed_old = md5($old_pass);
        $cek_ortu = fetchOne("SELECT id_ortu FROM tb_orang_tua WHERE id_ortu = :id AND password = :pass", [
            'id' => $id_ortu,
            'pass' => $hashed_old
        ]);

        if (!$cek_ortu) {
            $_SESSION['error_message'] = "❌ Kata sandi lama yang Anda masukkan salah.";
            header("Location: ../views/ortu/dashboard.php");
            exit;
        }

        // 2. Update dengan Password Baru
        $hashed_new = md5($new_pass);
        executeQuery("UPDATE tb_orang_tua SET password = :new_pass WHERE id_ortu = :id", [
            'new_pass' => $hashed_new,
            'id' => $id_ortu
        ]);

        $_SESSION['success_message'] = "✅ Kata sandi berhasil diubah! Gunakan sandi baru ini untuk login berikutnya.";
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "🚨 Terjadi kesalahan sistem: " . $e->getMessage();
    }
}

header("Location: ../views/ortu/dashboard.php");
exit;