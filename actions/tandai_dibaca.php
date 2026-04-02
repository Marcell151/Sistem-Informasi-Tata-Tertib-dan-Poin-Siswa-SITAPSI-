<?php
session_start();
require_once '../config/database.php'; // Panggil config

// Validasi hanya Admin yang bisa mengeksekusi
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['Admin', 'SuperAdmin']) && isset($_GET['id'])) {
    $id_feedback = $_GET['id'];
    
    try {
        // MENGGUNAKAN FUNGSI executeQuery() BAWAAN ANDA
        executeQuery("UPDATE tb_feedback_ortu SET status_baca = 'Sudah Dibaca', id_admin_pembaca = ? WHERE id_feedback = ?", [$_SESSION['user_id'], $id_feedback]);
        
        echo "success";
    } catch (Exception $e) {
        echo "error";
    }
} else {
    echo "unauthorized";
}
?>