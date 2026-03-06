<?php
/**
 * SITAPSI - Stress Test Data Generator
 * Membuat 500 Siswa Dummy secara instan
 */

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Stress_Test_500_Siswa.csv');
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');

// Tambahkan BOM agar rapi di Excel
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

// 1. Header (Standar Dapodik)
$headers = [
    'No Induk', 'Nama Peserta Didik', 'L/P', 'Tempat Lahir', 'Tanggal Lahir', 
    'Alamat Jalan', 'Nama Ayah', 'Pekerjaan Ayah', 'Nama Ibu', 'Pekerjaan Ibu', 
    'No HP', 'Kelas'
];
fputcsv($output, $headers, ',');

// 2. Data Referensi Acak
$kelas_array = [
    'VII A', 'VII B', 'VII C', 'VII D', 'VII E',
    'VIII A', 'VIII B', 'VIII C', 'VIII D', 'VIII E',
    'IX A', 'IX B', 'IX C', 'IX D', 'IX E'
];
$pekerjaan = ['Wiraswasta', 'PNS', 'Karyawan Swasta', 'Buruh', 'Petani', 'TNI/Polri'];

// 3. Looping Pembuatan 500 Data
for ($i = 1; $i <= 500; $i++) {
    // Membuat No Induk berurutan (Misal: 2026001, 2026002...)
    $no_induk = 2026000 + $i;
    
    // Jenis Kelamin Acak (L atau P)
    $jk = (rand(0, 1) == 0) ? 'L' : 'P';
    
    // Nama Siswa urut
    $nama = "Siswa Uji Coba " . $i;
    
    // Tanggal Lahir Acak (Tahun 2010 - 2012)
    $thn = rand(2010, 2012);
    $bln = str_pad(rand(1, 12), 2, "0", STR_PAD_LEFT);
    $tgl = str_pad(rand(1, 28), 2, "0", STR_PAD_LEFT);
    $tgl_lahir = "$thn-$bln-$tgl";
    
    // Kelas Acak
    $kelas = $kelas_array[array_rand($kelas_array)];
    
    // Orang Tua Acak
    $kerja_ayah = $pekerjaan[array_rand($pekerjaan)];
    $kerja_ibu = (rand(0, 1) == 0) ? 'Ibu Rumah Tangga' : $pekerjaan[array_rand($pekerjaan)];
    
    // No HP Acak
    $hp = "0812" . str_pad(rand(1, 99999999), 8, "0", STR_PAD_LEFT);

    $row = [
        $no_induk,
        $nama,
        $jk,
        'Malang',
        $tgl_lahir,
        "Jl. Simulasi Sistem No. " . $i . ", Kota Malang",
        "Bapak " . $i,
        $kerja_ayah,
        "Ibu " . $i,
        $kerja_ibu,
        $hp,
        $kelas
    ];
    
    fputcsv($output, $row, ',');
}

fclose($output);
exit;
?>