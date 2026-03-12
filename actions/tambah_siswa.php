<?php
/**
 * SITAPSI - Tambah Siswa Manual
 * PENYESUAIAN: Penambahan tangkapan data id_ortu
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
    
    $no_induk = trim($_POST['no_induk']);
    $nama_siswa = trim($_POST['nama_siswa']);
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $id_kelas = $_POST['id_kelas'];
    $nama_ayah = trim($_POST['nama_ayah']);
    $nama_ibu = trim($_POST['nama_ibu']);
    $no_hp_ortu = trim($_POST['no_hp_ortu']);
    
    // [BARU] Tangkap id_ortu dari form, set null jika tidak dipilih
    $id_ortu = !empty($_POST['id_ortu']) ? $_POST['id_ortu'] : null;
    
    // Validasi
    if (empty($no_induk) || empty($nama_siswa) || empty($jenis_kelamin) || empty($id_kelas)) {
        throw new Exception('Data wajib belum lengkap');
    }
    
    // Cek duplikasi no_induk
    $cek_induk = fetchOne("SELECT no_induk FROM tb_siswa WHERE no_induk = :no_induk", ['no_induk' => $no_induk]);
    if ($cek_induk) {
        throw new Exception('No Induk sudah terdaftar di database.');
    }
    
    $pdo->beginTransaction();
    
    // Insert siswa (Ditambahkan kolom id_ortu)
    executeQuery("
        INSERT INTO tb_siswa (no_induk, nama_siswa, jenis_kelamin, nama_ayah, nama_ibu, no_hp_ortu, id_ortu, status_aktif)
        VALUES (:no_induk, :nama_siswa, :jk, :nama_ayah, :nama_ibu, :no_hp_ortu, :id_ortu, 'Aktif')
    ", [
        'no_induk' => $no_induk,
        'nama_siswa' => $nama_siswa,
        'jk' => $jenis_kelamin,
        'nama_ayah' => $nama_ayah,
        'nama_ibu' => $nama_ibu,
        'no_hp_ortu' => $no_hp_ortu,
        'id_ortu' => $id_ortu // <--- Variabel id_ortu masuk ke sini
    ]);
    
    // Insert anggota kelas
    executeQuery("
        INSERT INTO tb_anggota_kelas (no_induk, id_kelas, id_tahun)
        VALUES (:no_induk, :id_kelas, :id_tahun)
    ", [
        'no_induk' => $no_induk,
        'id_kelas' => $id_kelas,
        'id_tahun' => $tahun_aktif['id_tahun']
    ]);
    
    $pdo->commit();
    $_SESSION['success_message'] = "✅ Siswa $nama_siswa berhasil ditambahkan!";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = '❌ Gagal: ' . $e->getMessage();
}

header('Location: ../views/admin/data_siswa.php');
exit;