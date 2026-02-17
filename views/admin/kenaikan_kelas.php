<?php
/**
 * SITAPSI - Kenaikan Kelas
 * Pilih kelas asal → Pilih siswa → Tentukan kelas tujuan
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// Ambil kelas yang ada
$kelas_list = fetchAll("SELECT * FROM tb_kelas ORDER BY tingkat, nama_kelas");

// Group by tingkat
$kelas_by_tingkat = [];
foreach ($kelas_list as $k) {
    $kelas_by_tingkat[$k['tingkat']][] = $k;
}

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kenaikan Kelas - SITAPSI</title>
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
            <a href="pengaturan_akademik.php" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Proses Kenaikan Kelas</h1>
                <p class="text-sm text-gray-500">Pindahkan siswa ke kelas berikutnya</p>
            </div>
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
            <div class="bg-gradient-to-r from-purple-600 to-purple-800 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">Kenaikan Kelas Tahun Ajaran <?= $tahun_aktif['nama_tahun'] ?></h2>
                        <p class="text-purple-200">Pilih kelas asal untuk memulai proses kenaikan kelas</p>
                    </div>
                    <div class="text-6xl">📈</div>
                </div>
            </div>

            <!-- Instruksi -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h4 class="font-bold text-blue-800 mb-1">ℹ️ Cara Kerja Kenaikan Kelas</h4>
                        <ol class="text-sm text-blue-700 list-decimal list-inside space-y-1">
                            <li>Pilih kelas ASAL (misal: 7A tahun lalu)</li>
                            <li>Sistem akan tampilkan siswa yang masih di kelas tersebut</li>
                            <li>Centang siswa yang akan naik kelas</li>
                            <li>Pilih kelas TUJUAN (misal: 8A tahun sekarang)</li>
                            <li>Klik "Proses Kenaikan" untuk memindahkan</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Pilih Kelas Asal -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold text-gray-800 mb-4 text-lg">Pilih Kelas Asal (Tingkat Lama)</h3>
                
                <?php foreach ($kelas_by_tingkat as $tingkat => $kelas_items): ?>
                <div class="mb-6">
                    <h4 class="font-bold text-gray-700 mb-3 flex items-center">
                        <span class="bg-navy text-white px-3 py-1 rounded-full mr-2">Tingkat <?= $tingkat ?></span>
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        <?php foreach ($kelas_items as $k): 
                            // Hitung siswa yang belum dipindahkan
                            $jumlah = fetchOne("
                                SELECT COUNT(*) as total 
                                FROM tb_anggota_kelas 
                                WHERE id_kelas = :id 
                                AND id_tahun = :tahun
                            ", [
                                'id' => $k['id_kelas'],
                                'tahun' => $tahun_aktif['id_tahun']
                            ])['total'] ?? 0;
                        ?>
                        <a href="kenaikan_kelas_proses.php?kelas_asal=<?= $k['id_kelas'] ?>" 
                           class="block bg-gray-50 hover:bg-navy hover:text-white border-2 border-gray-200 hover:border-navy rounded-lg p-4 text-center transition-all group">
                            <p class="text-2xl font-bold mb-1"><?= $k['nama_kelas'] ?></p>
                            <p class="text-xs text-gray-500 group-hover:text-white"><?= $jumlah ?> siswa</p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>

    </div>

</div>

</body>
</html>