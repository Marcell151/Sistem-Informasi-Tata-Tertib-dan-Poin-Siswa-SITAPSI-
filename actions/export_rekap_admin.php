<?php
/**
 * SITAPSI - Export Rekapitulasi Kelas (Excel)
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

$id_kelas = $_GET['kelas'] ?? null;

if (!$id_kelas) {
    die("Kelas tidak dipilih.");
}

// Ambil tahun ajaran aktif
$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// Ambil nama kelas
$kelas_info = fetchOne("SELECT nama_kelas FROM tb_kelas WHERE id_kelas = :id_kelas", ['id_kelas' => $id_kelas]);

// Ambil data siswa
$siswa_kelas = fetchAll("
    SELECT 
        s.nis,
        s.nama_siswa,
        a.poin_kelakuan,
        a.poin_kerajinan,
        a.poin_kerapian,
        a.total_poin_umum,
        a.status_sp_terakhir
    FROM tb_siswa s
    JOIN tb_anggota_kelas a ON s.nis = a.nis
    WHERE s.status_aktif = 'Aktif' 
    AND a.id_tahun = :id_tahun
    AND a.id_kelas = :id_kelas
    ORDER BY s.nama_siswa
", [
    'id_tahun' => $tahun_aktif['id_tahun'],
    'id_kelas' => $id_kelas
]);

// Set headers untuk download CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Rekap_' . $kelas_info['nama_kelas'] . '_' . date('Y-m-d') . '.csv"');

// Output CSV
$output = fopen('php://output', 'w');

// BOM untuk UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header CSV
fputcsv($output, ['REKAPITULASI KELAS ' . $kelas_info['nama_kelas']]);
fputcsv($output, ['Tahun Ajaran: ' . $tahun_aktif['nama_tahun']]);
fputcsv($output, ['Tanggal Cetak: ' . date('d-m-Y H:i:s')]);
fputcsv($output, []);
fputcsv($output, ['No', 'NIS', 'Nama Siswa', 'Poin Kelakuan', 'Poin Kerajinan', 'Poin Kerapian', 'Total Poin', 'Status SP']);

// Data siswa
$no = 1;
foreach ($siswa_kelas as $siswa) {
    fputcsv($output, [
        $no++,
        $siswa['nis'],
        $siswa['nama_siswa'],
        $siswa['poin_kelakuan'],
        $siswa['poin_kerajinan'],
        $siswa['poin_kerapian'],
        $siswa['total_poin_umum'],
        $siswa['status_sp_terakhir']
    ]);
}

fclose($output);
exit;
?>