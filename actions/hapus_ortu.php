<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

$id_ortu = $_GET['id'] ?? null;

if ($id_ortu) {
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();

        // 1. Kosongkan relasi di anak (Supaya aplikasi tidak error, siswa ini jadi tidak punya portal ortu)
        executeQuery("UPDATE tb_siswa SET id_ortu = NULL WHERE id_ortu = ?", [$id_ortu]);

        // 2. Hapus akun orang tua
        executeQuery("DELETE FROM tb_orang_tua WHERE id_ortu = ?", [$id_ortu]);

        $pdo->commit();
        $_SESSION['success_message'] = "✅ Data Wali Murid berhasil dihapus. Akses portal anak terkait telah diputuskan.";

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_message'] = "❌ Gagal menghapus: " . $e->getMessage();
    }
}

header("Location: ../views/admin/data_ortu.php");
exit;