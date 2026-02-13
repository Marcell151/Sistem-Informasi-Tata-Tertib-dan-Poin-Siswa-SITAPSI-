<?php
/**
 * SITAPSI - Entry Point
 * Redirect ke halaman login atau dashboard sesuai status login
 */

session_start();

// Cek apakah user sudah login
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // Redirect ke dashboard sesuai role
    if ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'SuperAdmin') {
        header('Location: views/admin/dashboard.php');
    } else {
        header('Location: views/guru/input_pelanggaran.php');
    }
} else {
    // Redirect ke halaman login
    header('Location: views/login.php');
}

exit;
?>