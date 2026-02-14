<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/pengaturan_akademik.php');
    exit;
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    $nama_tahun_baru = trim($_POST['nama_tahun_baru']);
    
    if (empty($nama_tahun_baru)) {
        throw new Exception('Nama tahun ajaran baru wajib diisi');
    }
    
    // 1. Arsipkan tahun lama
    executeQuery("UPDATE tb_tahun_ajaran SET status = 'Arsip' WHERE status = 'Aktif'");
    
    // 2. Buat tahun baru
    executeQuery("
        INSERT INTO tb_tahun_ajaran (nama_tahun, semester_aktif, status)
        VALUES (:nama_tahun, 'Ganjil', 'Aktif')
    ", ['nama_tahun' => $nama_tahun_baru]);
    
    $id_tahun_baru = getLastInsertId();
    
    // 3. Copy siswa aktif ke tahun baru (reset poin)
    $siswa_aktif = fetchAll("
        SELECT DISTINCT s.nis, a.id_kelas
        FROM tb_siswa s
        JOIN tb_anggota_kelas a ON s.nis = a.nis
        WHERE s.status_aktif = 'Aktif'
    ");
    
    foreach ($siswa_aktif as $siswa) {
        executeQuery("
            INSERT INTO tb_anggota_kelas (nis, id_kelas, id_tahun)
            VALUES (:nis, :id_kelas, :id_tahun)
        ", [
            'nis' => $siswa['nis'],
            'id_kelas' => $siswa['id_kelas'],
            'id_tahun' => $id_tahun_baru
        ]);
    }
    
    $pdo->commit();
    
    $_SESSION['success_message'] = "✅ Tahun ajaran berhasil ditutup! Tahun $nama_tahun_baru telah aktif.";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = '❌ Gagal tutup tahun: ' . $e->getMessage();
}

header('Location: ../views/admin/pengaturan_akademik.php');
exit;
?>