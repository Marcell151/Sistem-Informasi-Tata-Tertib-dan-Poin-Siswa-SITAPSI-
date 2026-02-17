<?php
/**
 * SITAPSI - Monitoring Siswa
 * Flow: Pilih Kelas → Pilih Siswa → Detail dengan 3 Tabel Kategori
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// Ambil daftar kelas
$kelas_list = fetchAll("SELECT id_kelas, nama_kelas, tingkat FROM tb_kelas ORDER BY tingkat, nama_kelas");

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Siswa - SITAPSI</title>
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
        
        <div class="bg-white shadow-sm border-b px-6 py-4 sticky top-0 z-30">
            <h1 class="text-2xl font-bold text-gray-800">Monitoring Siswa</h1>
            <p class="text-sm text-gray-500">Pilih kelas untuk melihat daftar siswa dan detail pelanggaran</p>
        </div>

        <div class="p-6 space-y-6">

            <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <p class="text-green-700 font-medium"><?= htmlspecialchars($success) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <p class="text-red-700 font-medium"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <!-- Info Header -->
            <div class="bg-gradient-to-r from-navy to-blue-800 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">Pilih Kelas</h2>
                        <p class="text-blue-200">Tahun Ajaran <?= $tahun_aktif['nama_tahun'] ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-blue-200 text-sm">Total Kelas</p>
                        <p class="text-4xl font-bold"><?= count($kelas_list) ?></p>
                    </div>
                </div>
            </div>

            <!-- Grid Kelas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php 
                $colors = [
                    7 => ['bg' => 'bg-blue-500', 'hover' => 'hover:bg-blue-600', 'icon' => '🎒'],
                    8 => ['bg' => 'bg-green-500', 'hover' => 'hover:bg-green-600', 'icon' => '📚'],
                    9 => ['bg' => 'bg-purple-500', 'hover' => 'hover:bg-purple-600', 'icon' => '🎓']
                ];
                
                foreach ($kelas_list as $kelas): 
                    $color = $colors[$kelas['tingkat']] ?? ['bg' => 'bg-gray-500', 'hover' => 'hover:bg-gray-600', 'icon' => '📖'];
                    
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
                <a href="monitoring_siswa_list.php?kelas=<?= $kelas['id_kelas'] ?>" 
                   class="block group">
                    <div class="<?= $color['bg'] ?> <?= $color['hover'] ?> text-white rounded-xl shadow-lg p-6 transition-all transform group-hover:scale-105 group-hover:shadow-xl">
                        <div class="flex items-center justify-between mb-4">
                            <div class="text-5xl"><?= $color['icon'] ?></div>
                            <div class="text-right">
                                <p class="text-white/80 text-xs font-medium">Kelas</p>
                                <p class="text-3xl font-bold"><?= $kelas['tingkat'] ?></p>
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold mb-2"><?= $kelas['nama_kelas'] ?></h3>
                        <div class="flex items-center justify-between">
                            <p class="text-white/90 text-sm">
                                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                                </svg>
                                <?= $jumlah_siswa ?> Siswa
                            </p>
                            <svg class="w-6 h-6 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($kelas_list)): ?>
            <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <p class="text-gray-500 font-medium">Belum ada kelas yang terdaftar</p>
            </div>
            <?php endif; ?>

        </div>

    </div>

</div>

</body>
</html>