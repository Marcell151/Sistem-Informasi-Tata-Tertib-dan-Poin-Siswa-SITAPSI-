<?php
/**
 * SITAPSI - Tambah Siswa Manual
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/data_siswa.php');
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Ambil tahun ajaran aktif
    $tahun_aktif = fetchOne("SELECT id_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
    
    $nis = trim($_POST['nis']);
    $nama_siswa = trim($_POST['nama_siswa']);
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $id_kelas = $_POST['id_kelas'];
    $nama_ortu = trim($_POST['nama_ortu']);
    $no_hp_ortu = trim($_POST['no_hp_ortu']);
    
    // Validasi
    if (empty($nis) || empty($nama_siswa) || empty($jenis_kelamin) || empty($id_kelas)) {
        throw new Exception('Data wajib belum lengkap');
    }
    
    $pdo->beginTransaction();
    
    // Insert siswa
    executeQuery("
        INSERT INTO tb_siswa (nis, nama_siswa, jenis_kelamin, nama_ortu, no_hp_ortu, status_aktif)
        VALUES (:nis, :nama_siswa, :jk, :nama_ortu, :no_hp_ortu, 'Aktif')
    ", [
        'nis' => $nis,
        'nama_siswa' => $nama_siswa,
        'jk' => $jenis_kelamin,
        'nama_ortu' => $nama_ortu,
        'no_hp_ortu' => $no_hp_ortu
    ]);
    
    // Insert anggota kelas
    executeQuery("
        INSERT INTO tb_anggota_kelas (nis, id_kelas, id_tahun)
        VALUES (:nis, :id_kelas, :id_tahun)
    ", [
        'nis' => $nis,
        'id_kelas' => $id_kelas,
        'id_tahun' => $tahun_aktif['id_tahun']
    ]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = '✅ Siswa berhasil ditambahkan!';
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = '❌ Gagal menambah siswa: ' . $e->getMessage();
}

header('Location: ../views/admin/data_siswa.php');
exit;
?>