<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/data_siswa.php');
    exit;
}

try {
    $nis = $_POST['nis'];
    $nama_siswa = trim($_POST['nama_siswa']);
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $status_aktif = $_POST['status_aktif'];
    $nama_ortu = trim($_POST['nama_ortu']);
    $no_hp_ortu = trim($_POST['no_hp_ortu']);
    
    if (empty($nis) || empty($nama_siswa)) {
        throw new Exception('Data wajib belum lengkap');
    }
    
    executeQuery("
        UPDATE tb_siswa 
        SET nama_siswa = :nama_siswa,
            jenis_kelamin = :jenis_kelamin,
            status_aktif = :status_aktif,
            nama_ortu = :nama_ortu,
            no_hp_ortu = :no_hp_ortu
        WHERE nis = :nis
    ", [
        'nama_siswa' => $nama_siswa,
        'jenis_kelamin' => $jenis_kelamin,
        'status_aktif' => $status_aktif,
        'nama_ortu' => $nama_ortu,
        'no_hp_ortu' => $no_hp_ortu,
        'nis' => $nis
    ]);
    
    $_SESSION['success_message'] = '✅ Data siswa berhasil diupdate!';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal mengupdate: ' . $e->getMessage();
}

header('Location: ../views/admin/data_siswa.php');
exit;
?>