<?php
/**
 * SITAPSI - Rapor Karakter Siswa (CETAK)
 * Step 3: Tampilkan rapor karakter per siswa dengan predikat A/B/C/D
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_anggota = $_GET['id'] ?? null;

if (!$id_anggota) {
    $_SESSION['error_message'] = '❌ ID siswa tidak valid';
    header('Location: rapor_karakter.php');
    exit;
}

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// Ambil data siswa
$siswa = fetchOne("
    SELECT 
        s.*,
        a.id_anggota,
        a.poin_kelakuan,
        a.poin_kerajinan,
        a.poin_kerapian,
        a.total_poin_umum,
        k.nama_kelas,
        k.id_kelas
    FROM tb_anggota_kelas a
    JOIN tb_siswa s ON a.nis = s.nis
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE a.id_anggota = :id
", ['id' => $id_anggota]);

if (!$siswa) {
    $_SESSION['error_message'] = '❌ Siswa tidak ditemukan';
    header('Location: rapor_karakter.php');
    exit;
}

// Ambil predikat dari database
$predikat_kelakuan = fetchAll("SELECT * FROM tb_predikat_nilai WHERE id_kategori = 1 ORDER BY batas_bawah");
$predikat_kerajinan = fetchAll("SELECT * FROM tb_predikat_nilai WHERE id_kategori = 2 ORDER BY batas_bawah");
$predikat_kerapian = fetchAll("SELECT * FROM tb_predikat_nilai WHERE id_kategori = 3 ORDER BY batas_bawah");

// Fungsi konversi poin ke predikat
function getPredikat($poin, $predikat_list) {
    foreach ($predikat_list as $p) {
        if ($poin >= $p['batas_bawah'] && $poin <= $p['batas_atas']) {
            return [
                'huruf' => $p['huruf_mutu'],
                'keterangan' => $p['keterangan'],
                'batas' => $p['batas_bawah'] . '-' . $p['batas_atas']
            ];
        }
    }
    return ['huruf' => '-', 'keterangan' => '-', 'batas' => '-'];
}

$pred_kelakuan = getPredikat($siswa['poin_kelakuan'], $predikat_kelakuan);
$pred_kerajinan = getPredikat($siswa['poin_kerajinan'], $predikat_kerajinan);
$pred_kerapian = getPredikat($siswa['poin_kerapian'], $predikat_kerapian);

// Warna badge
function getBadgeColor($huruf) {
    switch($huruf) {
        case 'A': return 'bg-green-500';
        case 'B': return 'bg-blue-500';
        case 'C': return 'bg-yellow-500';
        case 'D': return 'bg-red-500';
        default: return 'bg-gray-500';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapor Karakter - <?= $siswa['nama_siswa'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 'navy': '#000080' }
                }
            }
        }
    </script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
            }
            .print-full-width {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 20mm !important;
            }
        }
        @page {
            size: A4;
            margin: 20mm;
        }
    </style>
</head>
<body class="bg-gray-50">

<!-- Tombol Aksi (Tidak tercetak) -->
<div class="no-print fixed top-4 right-4 z-50 flex space-x-2">
    <a href="rapor_karakter_list.php?kelas=<?= $siswa['id_kelas'] ?>" 
       class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium flex items-center space-x-2 shadow-lg">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        <span>Kembali</span>
    </a>
    <button onclick="window.print()" 
            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium flex items-center space-x-2 shadow-lg">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
        </svg>
        <span>Cetak PDF</span>
    </button>
</div>

<!-- Konten Rapor (Akan dicetak) -->
<div class="min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full print-full-width">
        
        <!-- Header Sekolah -->
        <div class="border-b-4 border-navy p-8 text-center">
            <h1 class="text-3xl font-bold text-navy mb-2">SMP KATOLIK SANTA MARIA 2 MALANG</h1>
            <p class="text-sm text-gray-600">Jl. Raya Langsep No. 24 Malang • Telp: (0341) 123456</p>
            <div class="mt-4 pt-4 border-t border-gray-300">
                <h2 class="text-2xl font-bold text-gray-800">RAPOR KARAKTER SISWA</h2>
                <p class="text-sm text-gray-600 mt-1">Tahun Ajaran <?= $tahun_aktif['nama_tahun'] ?> • Semester <?= $tahun_aktif['semester_aktif'] ?></p>
            </div>
        </div>

        <!-- Data Siswa -->
        <div class="p-8 bg-gray-50">
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <div class="flex">
                        <span class="w-32 text-gray-600 font-medium">Nama</span>
                        <span class="flex-1">: <strong><?= htmlspecialchars($siswa['nama_siswa']) ?></strong></span>
                    </div>
                    <div class="flex">
                        <span class="w-32 text-gray-600 font-medium">NIS</span>
                        <span class="flex-1">: <?= $siswa['nis'] ?></span>
                    </div>
                    <div class="flex">
                        <span class="w-32 text-gray-600 font-medium">Kelas</span>
                        <span class="flex-1">: <?= $siswa['nama_kelas'] ?></span>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex">
                        <span class="w-32 text-gray-600 font-medium">Jenis Kelamin</span>
                        <span class="flex-1">: <?= $siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></span>
                    </div>
                    <div class="flex">
                        <span class="w-32 text-gray-600 font-medium">Nama Orang Tua</span>
                        <span class="flex-1">: <?= htmlspecialchars($siswa['nama_ortu']) ?></span>
                    </div>
                    <div class="flex">
                        <span class="w-32 text-gray-600 font-medium">Total Poin</span>
                        <span class="flex-1">: <strong class="text-red-600"><?= $siswa['total_poin_umum'] ?> poin</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nilai Karakter -->
        <div class="p-8">
            <h3 class="text-xl font-bold text-gray-800 mb-6 border-b-2 border-gray-300 pb-2">PENILAIAN KARAKTER</h3>
            
            <div class="space-y-6">
                
                <!-- KELAKUAN -->
                <div class="border-2 border-red-200 rounded-lg p-6 bg-red-50">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-bold text-red-700">🚨 ASPEK KELAKUAN</h4>
                        <div class="flex items-center space-x-4">
                            <div class="text-right">
                                <p class="text-xs text-gray-600">Akumulasi Poin</p>
                                <p class="text-2xl font-bold text-red-700"><?= $siswa['poin_kelakuan'] ?></p>
                            </div>
                            <div class="<?= getBadgeColor($pred_kelakuan['huruf']) ?> text-white w-20 h-20 rounded-full flex items-center justify-center">
                                <span class="text-4xl font-bold"><?= $pred_kelakuan['huruf'] ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded p-4">
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div><strong>Predikat:</strong> <?= $pred_kelakuan['keterangan'] ?></div>
                            <div><strong>Range Poin:</strong> <?= $pred_kelakuan['batas'] ?></div>
                        </div>
                    </div>
                </div>

                <!-- KERAJINAN -->
                <div class="border-2 border-blue-200 rounded-lg p-6 bg-blue-50">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-bold text-blue-700">📘 ASPEK KERAJINAN</h4>
                        <div class="flex items-center space-x-4">
                            <div class="text-right">
                                <p class="text-xs text-gray-600">Akumulasi Poin</p>
                                <p class="text-2xl font-bold text-blue-700"><?= $siswa['poin_kerajinan'] ?></p>
                            </div>
                            <div class="<?= getBadgeColor($pred_kerajinan['huruf']) ?> text-white w-20 h-20 rounded-full flex items-center justify-center">
                                <span class="text-4xl font-bold"><?= $pred_kerajinan['huruf'] ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded p-4">
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div><strong>Predikat:</strong> <?= $pred_kerajinan['keterangan'] ?></div>
                            <div><strong>Range Poin:</strong> <?= $pred_kerajinan['batas'] ?></div>
                        </div>
                    </div>
                </div>

                <!-- KERAPIAN -->
                <div class="border-2 border-yellow-200 rounded-lg p-6 bg-yellow-50">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-bold text-yellow-700">👔 ASPEK KERAPIAN</h4>
                        <div class="flex items-center space-x-4">
                            <div class="text-right">
                                <p class="text-xs text-gray-600">Akumulasi Poin</p>
                                <p class="text-2xl font-bold text-yellow-700"><?= $siswa['poin_kerapian'] ?></p>
                            </div>
                            <div class="<?= getBadgeColor($pred_kerapian['huruf']) ?> text-white w-20 h-20 rounded-full flex items-center justify-center">
                                <span class="text-4xl font-bold"><?= $pred_kerapian['huruf'] ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded p-4">
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div><strong>Predikat:</strong> <?= $pred_kerapian['keterangan'] ?></div>
                            <div><strong>Range Poin:</strong> <?= $pred_kerapian['batas'] ?></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Keterangan Nilai -->
        <div class="p-8 bg-gray-50 border-t border-gray-300">
            <h4 class="font-bold text-gray-800 mb-3">Keterangan Nilai:</h4>
            <div class="grid grid-cols-4 gap-4 text-sm">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-500 text-white rounded flex items-center justify-center font-bold mr-2">A</div>
                    <span class="text-gray-700">Sangat Baik</span>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-500 text-white rounded flex items-center justify-center font-bold mr-2">B</div>
                    <span class="text-gray-700">Baik</span>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-yellow-500 text-white rounded flex items-center justify-center font-bold mr-2">C</div>
                    <span class="text-gray-700">Cukup</span>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-red-500 text-white rounded flex items-center justify-center font-bold mr-2">D</div>
                    <span class="text-gray-700">Kurang</span>
                </div>
            </div>
        </div>

        <!-- TTD -->
        <div class="p-8">
            <div class="grid grid-cols-2 gap-8">
                <div class="text-center">
                    <p class="mb-16">Mengetahui,<br><strong>Kepala Sekolah</strong></p>
                    <div class="border-t border-gray-800 pt-2">
                        <strong>(__________________)</strong>
                    </div>
                </div>
                <div class="text-center">
                    <p class="mb-16">Malang, <?= date('d F Y') ?><br><strong>Wali Kelas</strong></p>
                    <div class="border-t border-gray-800 pt-2">
                        <strong>(__________________)</strong>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>