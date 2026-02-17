<?php
/**
 * SITAPSI - Download Template Import Siswa
 * Generate file Excel template untuk import siswa
 */

require_once '../config/database.php';

// Set header untuk download CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Template_Import_Siswa_SITAPSI.csv"');

// Buat output stream
$output = fopen('php://output', 'w');

// Tulis BOM untuk UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header kolom
fputcsv($output, [
    'NIS',
    'Nama Lengkap',
    'Jenis Kelamin (L/P)',
    'Tempat Lahir',
    'Tanggal Lahir (YYYY-MM-DD)',
    'Alamat',
    'Nama Orang Tua',
    'No HP Orang Tua',
    'Kelas (contoh: 7A, 8B)'
]);

// Data contoh
fputcsv($output, [
    '2024001',
    'Ahmad Dani',
    'L',
    'Malang',
    '2010-05-15',
    'Jl. Mawar No. 10 Malang',
    'Bpk. Dani',
    '081234567890',
    '7A'
]);

fputcsv($output, [
    '2024002',
    'Siti Nurhaliza',
    'P',
    'Surabaya',
    '2010-08-20',
    'Jl. Melati No. 5 Surabaya',
    'Ibu. Haliza',
    '081987654321',
    '7B'
]);

fclose($output);
exit;
?>