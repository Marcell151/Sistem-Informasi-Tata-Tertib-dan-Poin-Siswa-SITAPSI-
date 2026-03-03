<?php
/**
 * SITAPSI - Hapus Siswa
 * Menghapus siswa dari tb_siswa dan semua relasi
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

$no_induk = $_GET['no_induk'] ?? null;

if (!$no_induk) {
    $_SESSION['error_message'] = '❌ No Induk tidak valid';
    header('Location: ../views/admin/data_siswa.php');
    exit;
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();

    // Ambil nama siswa untuk pesan sukses
    $siswa = fetchOne("SELECT nama_siswa FROM tb_siswa WHERE no_induk = :no_induk", ['no_induk' => $no_induk]);

    if (!$siswa) {
        throw new Exception('Siswa tidak ditemukan');
    }

    // Cek apakah ada pelanggaran aktif
    $cek_pelanggaran = fetchOne("
        SELECT COUNT(*) as total
        FROM tb_pelanggaran_header h
        JOIN tb_anggota_kelas a ON h.id_anggota = a.id_anggota
        WHERE a.no_induk = :no_induk
    ", ['no_induk' => $no_induk]);

    if ($cek_pelanggaran['total'] > 0) {
        throw new Exception(
            'Tidak dapat menghapus! Siswa ini memiliki ' 
            . $cek_pelanggaran['total'] 
            . ' riwayat pelanggaran. Ubah status menjadi Lulus/Keluar.'
        );
    }

    // Hapus dari tb_anggota_kelas dulu
    executeQuery("DELETE FROM tb_anggota_kelas WHERE no_induk = :no_induk", ['no_induk' => $no_induk]);

    // Hapus dari tb_siswa
    executeQuery("DELETE FROM tb_siswa WHERE no_induk = :no_induk", ['no_induk' => $no_induk]);

    $pdo->commit();

    $_SESSION['success_message'] = "✅ Siswa {$siswa['nama_siswa']} ($no_induk) berhasil dihapus permanen.";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = '❌ Gagal: ' . $e->getMessage();
}

header('Location: ../views/admin/data_siswa.php');
exit;