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
    $tahun_aktif = fetchOne("
        SELECT id_tahun, semester_aktif 
        FROM tb_tahun_ajaran 
        WHERE status = 'Aktif' 
        LIMIT 1
    ");
    
    $semester_baru = $tahun_aktif['semester_aktif'] === 'Ganjil' ? 'Genap' : 'Ganjil';
    
    // Update semester di tb_tahun_ajaran
    executeQuery("
        UPDATE tb_tahun_ajaran 
        SET semester_aktif = :semester 
        WHERE id_tahun = :id
    ", [
        'semester' => $semester_baru,
        'id' => $tahun_aktif['id_tahun']
    ]);
    
    $_SESSION['success_message'] = "✅ Semester berhasil diganti ke $semester_baru! Tampilan pelanggaran siswa kini menampilkan lembaran semester baru.";
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal ganti semester: ' . $e->getMessage();
}

header('Location: ../views/admin/pengaturan_akademik.php');
exit;
?>