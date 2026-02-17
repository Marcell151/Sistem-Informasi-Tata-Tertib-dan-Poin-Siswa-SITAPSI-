<?php
/**
 * SITAPSI - Action Proses Kenaikan Kelas
 * Memindahkan siswa dari kelas lama ke kelas baru di tahun ajaran aktif
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/kenaikan_kelas.php');
    exit;
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    $id_kelas_asal = $_POST['id_kelas_asal'];
    $id_kelas_tujuan = $_POST['id_kelas_tujuan'];
    $siswa_nis_list = $_POST['siswa'] ?? [];
    
    if (empty($id_kelas_tujuan) || empty($siswa_nis_list)) {
        throw new Exception('Data tidak lengkap');
    }
    
    $tahun_aktif = fetchOne("SELECT id_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
    
    $success_count = 0;
    
    foreach ($siswa_nis_list as $nis) {
        // Update kelas siswa di tahun ajaran aktif
        executeQuery("
            UPDATE tb_anggota_kelas 
            SET id_kelas = :kelas_tujuan
            WHERE nis = :nis 
            AND id_tahun = :tahun
        ", [
            'kelas_tujuan' => $id_kelas_tujuan,
            'nis' => $nis,
            'tahun' => $tahun_aktif['id_tahun']
        ]);
        
        $success_count++;
    }
    
    $pdo->commit();
    
    $_SESSION['success_message'] = "✅ Berhasil! $success_count siswa telah dinaikkan kelasnya.";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = '❌ Gagal proses kenaikan: ' . $e->getMessage();
}

header('Location: ../views/admin/kenaikan_kelas.php');
exit;
?>