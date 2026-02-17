<?php
/**
 * SITAPSI - Rapor Karakter (REVISED)
 * Step 1: Pilih Kelas
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// Ambil daftar kelas
$kelas_list = fetchAll("SELECT id_kelas, nama_kelas, tingkat FROM tb_kelas ORDER BY tingkat, nama_kelas");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapor Karakter - SITAPSI</title>
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
</head>
<body class="bg-gray-50">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto bg-gray-100">
        
        <div class="bg-white shadow-sm border-b px-6 py-4 sticky top-0 z-30 flex items-center space-x-4">
            <a href="pelaporan_rekap.php" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Rapor Karakter Siswa</h1>
                <p class="text-sm text-gray-500">Laporan penilaian karakter per siswa</p>
            </div>
        </div>

        <div class="p-6 space-y-6">

            <!-- Info Header -->
            <div class="bg-gradient-to-r from-purple-600 to-purple-800 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">Pilih Kelas</h2>
                        <p class="text-purple-200">Tahun Ajaran <?= $tahun_aktif['nama_tahun'] ?> • Semester <?= $tahun_aktif['semester_aktif'] ?></p>
                    </div>
                    <div class="text-6xl">🎓</div>
                </div>
            </div>

            <!-- Grid Kelas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php 
                $colors = [
                    7 => ['bg' => 'bg-blue-500', 'hover' => 'hover:bg-blue-600'],
                    8 => ['bg' => 'bg-green-500', 'hover' => 'hover:bg-green-600'],
                    9 => ['bg' => 'bg-purple-500', 'hover' => 'hover:bg-purple-600']
                ];
                
                foreach ($kelas_list as $kelas): 
                    $color = $colors[$kelas['tingkat']] ?? ['bg' => 'bg-gray-500', 'hover' => 'hover:bg-gray-600'];
                    
                    // Hitung jumlah siswa per kelas
                    $jumlah_siswa = fetchOne("
                        SELECT COUNT(*) as total 
                        FROM tb_anggota_kelas 
                        WHERE id_kelas = :id_kelas 
                        AND id_tahun = :id_tahun
                    ", [
                        'id_kelas' => $kelas['id_kelas'],
                        'id_tahun' => $tahun_aktif['id_tahun']
                    ])['total'] ?? 0;
                ?>
                <a href="rapor_karakter_list.php?kelas=<?= $kelas['id_kelas'] ?>" class="block group">
                    <div class="<?= $color['bg'] ?> <?= $color['hover'] ?> text-white rounded-xl shadow-lg p-6 transition-all transform group-hover:scale-105 group-hover:shadow-xl">
                        <div class="text-5xl mb-4 text-center">🎓</div>
                        <h3 class="text-2xl font-bold mb-2 text-center"><?= $kelas['nama_kelas'] ?></h3>
                        <div class="flex items-center justify-center text-sm">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                            </svg>
                            <span><?= $jumlah_siswa ?> Siswa</span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

        </div>

    </div>

</div>

</body>
</html>