<?php
session_start();
require_once '../config/database.php'; // Panggil config

// Validasi akses Ortu
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Ortu') {
    header("Location: ../ortu/dashboard.php");
    exit;
}

$id_ortu = $_POST['id_ortu'] ?? '';
$no_induk = $_POST['no_induk'] ?? '';
$id_sp = $_POST['id_sp'] ?? '';
$isi_feedback = $_POST['isi_feedback'] ?? '';

// Fallback URL
$redirect_url = $_SERVER['HTTP_REFERER'] ?? "../views/ortu/tatib/detail_anak.php?induk=" . urlencode($no_induk);

// Cek form kosong
if (empty($id_ortu) || empty($id_sp) || empty(trim($isi_feedback))) {
    $_SESSION['feedback_error'] = "Gagal mengirim pesan. Pastikan semua kolom terisi.";
    header("Location: " . $redirect_url);
    exit;
}

try {
    $isi_bersih = htmlspecialchars(trim($isi_feedback), ENT_QUOTES, 'UTF-8');

    // MENGGUNAKAN FUNGSI executeQuery() BAWAAN ANDA
    executeQuery("INSERT INTO tb_feedback_ortu (id_ortu, id_sp, isi_feedback, status_baca) VALUES (?, ?, ?, 'Belum Dibaca')", [$id_ortu, $id_sp, $isi_bersih]);

    $_SESSION['feedback_success'] = "Balasan Anda berhasil dikirim ke Admin Kedisiplinan. Terima kasih atas kerjasamanya.";
    header("Location: " . $redirect_url);
    exit;

} catch (Exception $e) {
    $_SESSION['feedback_error'] = "Terjadi kesalahan sistem: " . $e->getMessage();
    header("Location: " . $redirect_url);
    exit;
}
?>