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
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    $nis = $_POST['nis'];
    $nama_siswa = trim($_POST['nama_siswa']);
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $status_aktif = $_POST['status_aktif'];
    $nama_ortu = trim($_POST['nama_ortu']);
    $no_hp_ortu = trim($_POST['no_hp_ortu']);
    $id_kelas_baru = $_POST['id_kelas'] ?? null;
    
    if (empty($nis) || empty($nama_siswa)) {
        throw new Exception('Data wajib belum lengkap');
    }
    
    // 1. Update data siswa
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
    
    // 2. Update kelas jika ada perubahan
    if (!empty($id_kelas_baru)) {
        $tahun_aktif = fetchOne("SELECT id_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
        
        // Cek apakah siswa sudah ada di anggota kelas tahun ini
        $anggota = fetchOne("
            SELECT id_anggota FROM tb_anggota_kelas 
            WHERE nis = :nis AND id_tahun = :id_tahun
        ", [
            'nis' => $nis,
            'id_tahun' => $tahun_aktif['id_tahun']
        ]);
        
        if ($anggota) {
            // Update kelas di anggota_kelas
            executeQuery("
                UPDATE tb_anggota_kelas 
                SET id_kelas = :id_kelas
                WHERE nis = :nis 
                AND id_tahun = :id_tahun
            ", [
                'id_kelas' => $id_kelas_baru,
                'nis' => $nis,
                'id_tahun' => $tahun_aktif['id_tahun']
            ]);
        } else {
            // Insert baru di anggota_kelas (siswa belum ada di tahun ini)
            executeQuery("
                INSERT INTO tb_anggota_kelas (nis, id_kelas, id_tahun)
                VALUES (:nis, :id_kelas, :id_tahun)
            ", [
                'nis' => $nis,
                'id_kelas' => $id_kelas_baru,
                'id_tahun' => $tahun_aktif['id_tahun']
            ]);
        }
    }
    
    $pdo->commit();
    
    $_SESSION['success_message'] = "✅ Data siswa $nama_siswa berhasil diupdate!";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = '❌ Gagal mengupdate: ' . $e->getMessage();
}

header('Location: ../views/admin/data_siswa.php');
exit;
?>