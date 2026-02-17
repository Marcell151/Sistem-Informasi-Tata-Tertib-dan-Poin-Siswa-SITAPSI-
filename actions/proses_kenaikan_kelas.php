<?php
/**
 * SITAPSI - Proses Kenaikan Kelas (FIXED)
 * Fix: Cek duplikat sebelum insert - jika sudah ada di tahun ini, UPDATE saja
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

    $tahun_aktif = fetchOne("
        SELECT id_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1
    ");

    $success_count = 0;

    foreach ($siswa_nis_list as $nis) {
        // Cek apakah siswa sudah punya row di tahun aktif
        $existing = fetchOne("
            SELECT id_anggota FROM tb_anggota_kelas
            WHERE nis = :nis AND id_tahun = :tahun
        ", [
            'nis' => $nis,
            'tahun' => $tahun_aktif['id_tahun']
        ]);

        if ($existing) {
            // UPDATE saja - pindahkan ke kelas tujuan
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
        } else {
            // INSERT baru jika belum ada
            executeQuery("
                INSERT INTO tb_anggota_kelas (nis, id_kelas, id_tahun)
                VALUES (:nis, :id_kelas, :id_tahun)
            ", [
                'nis' => $nis,
                'id_kelas' => $id_kelas_tujuan,
                'id_tahun' => $tahun_aktif['id_tahun']
            ]);
        }

        $success_count++;
    }

    $pdo->commit();

    // Ambil nama kelas tujuan untuk pesan
    $kelas_tujuan = fetchOne("SELECT nama_kelas FROM tb_kelas WHERE id_kelas = :id", 
        ['id' => $id_kelas_tujuan]);

    $_SESSION['success_message'] = "✅ Berhasil! $success_count siswa dipindahkan ke kelas {$kelas_tujuan['nama_kelas']}.";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = '❌ Gagal proses kenaikan: ' . $e->getMessage();
}

header('Location: ../views/admin/kenaikan_kelas.php');
exit;
?>