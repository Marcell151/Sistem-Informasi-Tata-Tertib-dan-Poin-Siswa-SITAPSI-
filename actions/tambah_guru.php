<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/data_guru.php');
    exit;
}

try {
    $nama_guru = trim($_POST['nama_guru']);
    $nip = trim($_POST['nip']);
    $pin_validasi = trim($_POST['pin_validasi']);
    $id_kelas = !empty($_POST['id_kelas']) ? (int)$_POST['id_kelas'] : null;
    $status = $_POST['status'];
    
    if (empty($nama_guru) || empty($pin_validasi)) {
        throw new Exception('Nama guru dan PIN wajib diisi');
    }
    
    if (strlen($pin_validasi) !== 6 || !ctype_digit($pin_validasi)) {
        throw new Exception('PIN harus 6 digit angka');
    }
    
    // Cek apakah kelas sudah punya wali kelas
    if ($id_kelas) {
        $cek_wali = fetchOne("SELECT id_guru, nama_guru FROM tb_guru WHERE id_kelas = :id_kelas AND id_guru != 0", ['id_kelas' => $id_kelas]);
        if ($cek_wali) {
            throw new Exception("Kelas ini sudah memiliki wali kelas: {$cek_wali['nama_guru']}");
        }
    }
    
    executeQuery("
        INSERT INTO tb_guru (nama_guru, nip, id_kelas, pin_validasi, status) 
        VALUES (:nama_guru, :nip, :id_kelas, :pin_validasi, :status)
    ", [
        'nama_guru' => $nama_guru,
        'nip' => $nip ?: null,
        'id_kelas' => $id_kelas,
        'pin_validasi' => $pin_validasi,
        'status' => $status
    ]);
    
    $_SESSION['success_message'] = '✅ Guru berhasil ditambahkan!';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal: ' . $e->getMessage();
}

header('Location: ../views/admin/data_guru.php');
exit;
?>