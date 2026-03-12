<?php
/**
 * SITAPSI - Action Update PIN oleh Guru Sendiri
 * [FIX]: Menyesuaikan nama kolom menjadi 'pin_validasi'
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireGuru();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_guru = $_SESSION['user_id'];
    $old_pin = $_POST['old_pin'] ?? '';
    $new_pin = $_POST['new_pin'] ?? '';
    $confirm_pin = $_POST['confirm_pin'] ?? '';
    
    // Tangkap URL halaman asal (supaya setelah ganti PIN, dia tidak kembali ke index yang salah)
    $redirect_url = $_POST['current_page'] ?? '../views/guru/input_pelanggaran.php';

    if (empty($old_pin) || empty($new_pin) || empty($confirm_pin)) {
        $_SESSION['pin_error_message'] = "⚠️ Semua kolom PIN harus diisi.";
        header("Location: " . $redirect_url);
        exit;
    }

    if ($new_pin !== $confirm_pin) {
        $_SESSION['pin_error_message'] = "⚠️ Konfirmasi PIN baru tidak cocok.";
        header("Location: " . $redirect_url);
        exit;
    }

    try {
        // Cek PIN lama menggunakan kolom pin_validasi
        $guru = fetchOne("SELECT pin_validasi FROM tb_guru WHERE id_guru = :id", ['id' => $id_guru]);

        if (!$guru || $guru['pin_validasi'] !== $old_pin) {
            $_SESSION['pin_error_message'] = "❌ PIN lama yang Anda masukkan salah.";
            header("Location: " . $redirect_url);
            exit;
        }

        // Update dengan PIN Baru pada kolom pin_validasi
        executeQuery("UPDATE tb_guru SET pin_validasi = :new_pin WHERE id_guru = :id", [
            'new_pin' => $new_pin,
            'id' => $id_guru
        ]);

        $_SESSION['pin_success_message'] = "✅ PIN berhasil diubah! Gunakan PIN baru Anda untuk sesi login berikutnya.";
        
    } catch (Exception $e) {
        $_SESSION['pin_error_message'] = "🚨 Terjadi kesalahan sistem: " . $e->getMessage();
    }
}

header("Location: " . $redirect_url);
exit;