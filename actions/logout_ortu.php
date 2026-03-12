<?php
/**
 * SITAPSI - Logout Khusus Orang Tua
 */
session_start();

// Hapus hanya session orang tua (Jika suatu saat ada admin yang login barengan di browser yang sama, tidak bentrok)
unset($_SESSION['ortu_id']);
unset($_SESSION['ortu_nik']);
unset($_SESSION['nama_user']);

if (isset($_SESSION['role']) && $_SESSION['role'] === 'Ortu') {
    unset($_SESSION['role']);
}

// Redirect kembali ke halaman login Ortu
header("Location: ../views/ortu/login.php");
exit;