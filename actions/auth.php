<?php
/**
 * SITAPSI - Authentication Handler
 * Logic Login untuk Admin dan Guru dengan Security Best Practices
 * 
 * @author Senior PHP Developer
 * @version 1.0
 */

session_start();
require_once '../config/database.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    redirectToDashboard();
}

// Validasi request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectToLogin('Akses tidak valid.');
}

// Ambil login type
$login_type = $_POST['login_type'] ?? '';

try {
    if ($login_type === 'admin') {
        handleAdminLogin();
    } elseif ($login_type === 'guru') {
        handleGuruLogin();
    } else {
        redirectToLogin('Tipe login tidak valid.');
    }
} catch (Exception $e) {
    // Log error untuk debugging (jangan tampilkan ke user)
    error_log("Login Error: " . $e->getMessage());
    redirectToLogin('Terjadi kesalahan sistem. Silakan coba lagi.');
}

/**
 * Handle Admin Login
 */
function handleAdminLogin() {
    // Validasi input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        redirectToLogin('Username dan password harus diisi.');
    }
    
    // Query admin dengan prepared statement
    $sql = "SELECT id_admin, username, password, nama_lengkap, role 
            FROM tb_admin 
            WHERE username = :username 
            LIMIT 1";
    
    $admin = fetchOne($sql, ['username' => $username]);
    
    // Cek apakah admin ditemukan
    if (!$admin) {
        // Delay untuk mencegah brute force
        sleep(1);
        redirectToLogin('Username atau password salah.');
    }
    
    // Verifikasi password
    // CATATAN: Untuk data testing, password masih plain text
    // Dalam production, gunakan password_hash() dan password_verify()
    $password_valid = false;
    
    // Cek apakah password sudah di-hash
    if (password_get_info($admin['password'])['algo'] !== null) {
        // Password sudah di-hash, gunakan password_verify
        $password_valid = password_verify($password, $admin['password']);
    } else {
        // Password masih plain text (untuk testing)
        $password_valid = ($password === $admin['password']);
    }
    
    if (!$password_valid) {
        sleep(1);
        redirectToLogin('Username atau password salah.');
    }
    
    // Login berhasil, set session
    $_SESSION['user_id'] = $admin['id_admin'];
    $_SESSION['username'] = $admin['username'];
    $_SESSION['nama_lengkap'] = $admin['nama_lengkap'];
    $_SESSION['role'] = $admin['role'];
    $_SESSION['login_type'] = 'admin';
    $_SESSION['login_time'] = time();
    
    // Redirect ke dashboard admin
    header('Location: ../views/admin/dashboard.php');
    exit;
}

/**
 * Handle Guru Login
 */
function handleGuruLogin() {
    // Validasi input
    $guru_id = $_POST['guru_id'] ?? '';
    $pin = $_POST['pin'] ?? '';
    $remember_me = isset($_POST['remember_me']) ? true : false;
    
    if (empty($guru_id) || empty($pin)) {
        redirectToLogin('Nama guru dan PIN harus diisi.');
    }
    
    // Validasi PIN format (6 digit angka)
    if (!preg_match('/^[0-9]{6}$/', $pin)) {
        redirectToLogin('PIN harus 6 digit angka.');
    }
    
    // Query guru dengan prepared statement
    $sql = "SELECT id_guru, nama_guru, nip, pin_validasi, status 
            FROM tb_guru 
            WHERE id_guru = :guru_id 
            AND status = 'Aktif' 
            LIMIT 1";
    
    $guru = fetchOne($sql, ['guru_id' => $guru_id]);
    
    // Cek apakah guru ditemukan
    if (!$guru) {
        sleep(1);
        redirectToLogin('Data guru tidak ditemukan atau tidak aktif.');
    }
    
    // Verifikasi PIN
    if ($pin !== $guru['pin_validasi']) {
        sleep(1);
        redirectToLogin('PIN salah.');
    }
    
    // Login berhasil, set session
    $_SESSION['user_id'] = $guru['id_guru'];
    $_SESSION['username'] = $guru['nama_guru'];
    $_SESSION['nama_lengkap'] = $guru['nama_guru'];
    $_SESSION['nip'] = $guru['nip'];
    $_SESSION['role'] = 'Guru';
    $_SESSION['login_type'] = 'guru';
    $_SESSION['login_time'] = time();
    
    // Jika "Ingat Saya" dicentang, set cookie PERMANENT
    if ($remember_me) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (365 * 24 * 60 * 60 * 10); // 10 tahun (praktis permanent)
        
        // Simpan token ke cookie
        setcookie('remember_token', $token, $expiry, '/', '', false, true); // httpOnly = true
        setcookie('remember_user', $guru['id_guru'], $expiry, '/', '', false, false);
        
        // Optional: Simpan token ke database untuk validasi lebih ketat
        // Di sini kita skip untuk kesederhanaan
    }
    
    // Redirect ke halaman input pelanggaran
    header('Location: ../views/guru/input_pelanggaran.php');
    exit;
}

/**
 * Redirect ke halaman login dengan pesan error
 */
function redirectToLogin($message) {
    $_SESSION['login_error'] = $message;
    header('Location: ../views/login.php');
    exit;
}

/**
 * Redirect ke dashboard sesuai role
 */
function redirectToDashboard() {
    if ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'SuperAdmin') {
        header('Location: ../views/admin/dashboard.php');
    } else {
        header('Location: ../views/guru/input_pelanggaran.php');
    }
    exit;
}

/**
 * Helper: Generate secure random token
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}
?>