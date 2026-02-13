<?php
/**
 * SITAPSI - Logout Handler
 * Menghapus session dan cookie dengan aman
 */

session_start();

// Hapus semua session variables
$_SESSION = array();

// Hapus session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Hapus remember me cookie jika ada
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('remember_user', '', time() - 3600, '/');
}

// Hancurkan session
session_destroy();

// Redirect ke halaman login
header('Location: ../views/login.php');
exit;
?>