<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = trim($_POST['nik_ortu']);
    $nama_ayah = trim($_POST['nama_ayah']);
    $pekerjaan_ayah = trim($_POST['pekerjaan_ayah']);
    $nama_ibu = trim($_POST['nama_ibu']);
    $pekerjaan_ibu = trim($_POST['pekerjaan_ibu']);
    $no_hp = trim($_POST['no_hp_ortu']);
    $alamat = trim($_POST['alamat']);

    if (empty($nik) || empty($nama_ayah) || empty($nama_ibu)) {
        $_SESSION['error_message'] = "Data wajib belum lengkap!";
        header("Location: ../views/admin/data_ortu.php");
        exit;
    }

    try {
        // Cek duplikasi NIK
        $cek = fetchOne("SELECT id_ortu FROM tb_orang_tua WHERE nik_ortu = ?", [$nik]);
        if ($cek) {
            throw new Exception("NIK tersebut sudah terdaftar di sistem!");
        }

        $pass_default = md5('123456');

        executeQuery("
            INSERT INTO tb_orang_tua (nik_ortu, password, nama_ayah, pekerjaan_ayah, nama_ibu, pekerjaan_ibu, no_hp_ortu, alamat)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", [$nik, $pass_default, $nama_ayah, $pekerjaan_ayah, $nama_ibu, $pekerjaan_ibu, $no_hp, $alamat]);

        $_SESSION['success_message'] = "✅ Data Wali Murid berhasil ditambahkan. Sandi default: 123456";

    } catch (Exception $e) {
        $_SESSION['error_message'] = "❌ Gagal: " . $e->getMessage();
    }
}

header("Location: ../views/admin/data_ortu.php");
exit;