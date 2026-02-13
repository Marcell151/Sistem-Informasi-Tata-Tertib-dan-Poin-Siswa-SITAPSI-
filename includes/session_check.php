<?php
/**
 * SITAPSI - Session Security Check
 * File ini harus di-include di setiap halaman yang memerlukan autentikasi
 * 
 * Usage: require_once '../includes/session_check.php';
 */

// Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login
 */
function checkLogin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        // Redirect ke login
        header('Location: ../../views/login.php');
        exit;
    }
    
    // Cek session timeout (2 jam)
    $timeout = 2 * 60 * 60; // 2 jam dalam detik
    
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $timeout)) {
        // Session expired
        session_destroy();
        header('Location: ../../views/login.php?timeout=1');
        exit;
    }
    
    // Update last activity time
    $_SESSION['login_time'] = time();
}

/**
 * Cek apakah user memiliki role tertentu
 */
function checkRole($allowed_roles = []) {
    checkLogin();
    
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // Akses ditolak
        http_response_code(403);
        die("Akses Ditolak: Anda tidak memiliki hak akses ke halaman ini.");
    }
}

/**
 * Cek apakah user adalah Admin
 */
function requireAdmin() {
    checkRole(['Admin', 'SuperAdmin']);
}

/**
 * Cek apakah user adalah Guru
 */
function requireGuru() {
    checkRole(['Guru']);
}

/**
 * Get current user info
 */
function getCurrentUser() {
    checkLogin();
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nama_lengkap' => $_SESSION['nama_lengkap'],
        'role' => $_SESSION['role'],
        'login_type' => $_SESSION['login_type']
    ];
}

/**
 * Cek apakah user adalah admin atau guru
 */
function isAdmin() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['Admin', 'SuperAdmin']);
}

function isGuru() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Guru';
}

// Auto check login saat file di-include
checkLogin();
?>