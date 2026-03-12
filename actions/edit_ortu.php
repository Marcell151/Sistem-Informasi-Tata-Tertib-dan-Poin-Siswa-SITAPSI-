<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_ortu = $_POST['id_ortu'];
    $nik_lama = $_POST['nik_lama'];
    $nik_baru = trim($_POST['nik_ortu']);
    $nama_ayah = trim($_POST['nama_ayah']);
    $pekerjaan_ayah = trim($_POST['pekerjaan_ayah']);
    $nama_ibu = trim($_POST['nama_ibu']);
    $pekerjaan_ibu = trim($_POST['pekerjaan_ibu']);
    $no_hp = trim($_POST['no_hp_ortu']);
    $alamat = trim($_POST['alamat']);

    try {
        // Jika NIK diubah, cek apakah NIK baru sudah dipakai orang lain
        if ($nik_lama !== $nik_baru) {
            $cek = fetchOne("SELECT id_ortu FROM tb_orang_tua WHERE nik_ortu = ? AND id_ortu != ?", [$nik_baru, $id_ortu]);
            if ($cek) {
                throw new Exception("NIK yang baru Anda masukkan sudah terdaftar untuk Wali Murid lain!");
            }
        }

        executeQuery("
            UPDATE tb_orang_tua SET 
                nik_ortu = ?, nama_ayah = ?, pekerjaan_ayah = ?, 
                nama_ibu = ?, pekerjaan_ibu = ?, no_hp_ortu = ?, alamat = ?
            WHERE id_ortu = ?
        ", [$nik_baru, $nama_ayah, $pekerjaan_ayah, $nama_ibu, $pekerjaan_ibu, $no_hp, $alamat, $id_ortu]);

        $_SESSION['success_message'] = "✅ Data Wali Murid berhasil diperbarui!";

    } catch (Exception $e) {
        $_SESSION['error_message'] = "❌ Gagal: " . $e->getMessage();
    }
}

header("Location: ../views/admin/data_ortu.php");
exit;