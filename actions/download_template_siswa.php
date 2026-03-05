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
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header kolom disesuaikan dengan Database Baru
fputcsv($output, [
    'No Induk',
    'Nama Lengkap',
    'Jenis Kelamin (L/P)',
    'Kota (Tempat Lahir)',
    'Tanggal Lahir (YYYY-MM-DD)',
    'Alamat',
    'Nama Ayah',
    'Pekerjaan Ayah',
    'Nama Ibu',
    'Pekerjaan Ibu',
    'No HP Orang Tua',
    'Kelas (contoh: VII A, VIII B)'
]);

// Data contoh
fputcsv($output, [
    '2024001',
    'Ahmad Dani',
    'L',
    'Malang',
    '2010-05-15',
    'Jl. Merdeka No. 1',
    'Bpk. Dani',
    'Swasta',
    'Ibu Dani',
    'Ibu Rumah Tangga',
    '081234567890',
    'VIIA'
]);

fclose($output);
exit;