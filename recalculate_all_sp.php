<?php
/**
 * TEMPORARY FILE - Recalculate All SP
 * Jalankan sekali via browser: http://localhost/sitapsi/recalculate_all_sp.php
 * Lalu HAPUS file ini!
 */

session_start();
require_once 'config/database.php';
require_once 'includes/sp_helper.php';

// Security: Hanya bisa dijalankan oleh admin yang login
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die('❌ Akses ditolak! Login sebagai admin dulu.');
}

echo "<h1>Recalculate All SP</h1>";
echo "<p>Memproses semua siswa...</p>";
echo "<hr>";

// Ambil tahun aktif
$tahun_aktif = fetchOne("
    SELECT id_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1
");

// Ambil semua siswa di tahun aktif
$all_siswa = fetchAll("
    SELECT id_anggota, nis 
    FROM tb_anggota_kelas 
    WHERE id_tahun = :id_tahun
", ['id_tahun' => $tahun_aktif['id_tahun']]);

echo "<p>Total siswa: " . count($all_siswa) . "</p>";

$success = 0;
$error = 0;

foreach ($all_siswa as $siswa) {
    try {
        $result = recalculateStatusSP($siswa['id_anggota']);
        echo "<p>✅ {$siswa['nis']} - Kelakuan: {$result['kelakuan']}, Kerajinan: {$result['kerajinan']}, Kerapian: {$result['kerapian']}, Tertinggi: {$result['tertinggi']}</p>";
        $success++;
    } catch (Exception $e) {
        echo "<p>❌ {$siswa['nis']} - Error: {$e->getMessage()}</p>";
        $error++;
    }
}

echo "<hr>";
echo "<h2>SELESAI!</h2>";
echo "<p>Berhasil: $success siswa</p>";
echo "<p>Gagal: $error siswa</p>";
echo "<p><strong>PENTING: Hapus file recalculate_all_sp.php setelah selesai!</strong></p>";
?>