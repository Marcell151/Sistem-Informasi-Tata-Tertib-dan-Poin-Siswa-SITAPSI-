<?php
/**
 * SITAPSI - List Siswa di Kelas
 * Menampilkan daftar siswa dalam kelas yang dipilih
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_kelas = $_GET['kelas'] ?? null;

if (!$id_kelas) {
    $_SESSION['error_message'] = '❌ Kelas tidak valid';
    header('Location: monitoring_siswa.php');
    exit;
}

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// Info kelas
$kelas = fetchOne("SELECT * FROM tb_kelas WHERE id_kelas = :id", ['id' => $id_kelas]);

if (!$kelas) {
    $_SESSION['error_message'] = '❌ Kelas tidak ditemukan';
    header('Location: monitoring_siswa.php');
    exit;
}

// Ambil siswa dalam kelas
$siswa_list = fetchAll("
    SELECT 
        s.nis,
        s.nama_siswa,
        s.jenis_kelamin,
        s.foto_profil,
        a.id_anggota,
        a.poin_kelakuan,
        a.poin_kerajinan,
        a.poin_kerapian,
        a.total_poin_umum,
        a.status_sp_terakhir
    FROM tb_anggota_kelas a
    JOIN tb_siswa s ON a.nis = s.nis
    WHERE a.id_kelas = :id_kelas
    AND a.id_tahun = :id_tahun
    AND s.status_aktif = 'Aktif'
    ORDER BY s.nama_siswa
", [
    'id_kelas' => $id_kelas,
    'id_tahun' => $tahun_aktif['id_tahun']
]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siswa Kelas <?= $kelas['nama_kelas'] ?> - SITAPSI</title>
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
            <a href="monitoring_siswa.php" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Siswa Kelas <?= $kelas['nama_kelas'] ?></h1>
                <p class="text-sm text-gray-500">Pilih siswa untuk melihat detail pelanggaran</p>
            </div>
        </div>

        <div class="p-6 space-y-6">

            <!-- Header Info -->
            <div class="bg-gradient-to-r from-navy to-blue-800 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">Kelas <?= $kelas['nama_kelas'] ?></h2>
                        <p class="text-blue-200">Tingkat <?= $kelas['tingkat'] ?> • Tahun Ajaran <?= $tahun_aktif['nama_tahun'] ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-blue-200 text-sm">Total Siswa</p>
                        <p class="text-4xl font-bold"><?= count($siswa_list) ?></p>
                    </div>
                </div>
            </div>

            <!-- Grid Siswa -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($siswa_list)): ?>
                <div class="col-span-full bg-white rounded-xl shadow-sm p-12 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <p class="text-gray-500 font-medium">Belum ada siswa di kelas ini</p>
                </div>
                <?php else: ?>
                <?php foreach ($siswa_list as $siswa): 
                    // Tentukan border color berdasarkan total poin
                    $border_color = 'border-blue-500';
                    if ($siswa['total_poin_umum'] > 200) {
                        $border_color = 'border-red-500';
                    } elseif ($siswa['total_poin_umum'] > 100) {
                        $border_color = 'border-yellow-500';
                    }
                ?>
                <a href="detail_siswa.php?id=<?= $siswa['id_anggota'] ?>" 
                   class="block group">
                    <div class="bg-white rounded-xl shadow-sm border-l-4 <?= $border_color ?> p-6 transition-all hover:shadow-lg transform hover:-translate-y-1">
                        
                        <!-- Foto & Nama -->
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="w-16 h-16 bg-navy rounded-full flex items-center justify-center overflow-hidden flex-shrink-0">
                                <?php if($siswa['foto_profil']): ?>
                                    <img src="../../assets/uploads/siswa/<?= $siswa['foto_profil'] ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="text-white font-bold text-2xl"><?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-bold text-gray-800 truncate group-hover:text-navy transition-colors">
                                    <?= htmlspecialchars($siswa['nama_siswa']) ?>
                                </h3>
                                <p class="text-sm text-gray-500"><?= $siswa['nis'] ?> • <?= $siswa['jenis_kelamin'] === 'L' ? '👦' : '👧' ?></p>
                            </div>
                        </div>

                        <!-- Poin Cards -->
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            <div class="bg-red-50 p-3 rounded-lg text-center">
                                <p class="text-xs text-red-600 font-medium">Kelakuan</p>
                                <p class="text-xl font-bold text-red-700"><?= $siswa['poin_kelakuan'] ?></p>
                            </div>
                            <div class="bg-blue-50 p-3 rounded-lg text-center">
                                <p class="text-xs text-blue-600 font-medium">Kerajinan</p>
                                <p class="text-xl font-bold text-blue-700"><?= $siswa['poin_kerajinan'] ?></p>
                            </div>
                            <div class="bg-yellow-50 p-3 rounded-lg text-center">
                                <p class="text-xs text-yellow-600 font-medium">Kerapian</p>
                                <p class="text-xl font-bold text-yellow-700"><?= $siswa['poin_kerapian'] ?></p>
                            </div>
                        </div>

                        <!-- Total Poin & Status -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <div>
                                <p class="text-xs text-gray-500 font-medium">Total Poin</p>
                                <p class="text-2xl font-bold text-gray-800"><?= $siswa['total_poin_umum'] ?></p>
                            </div>
                            <div>
                                <?php if ($siswa['status_sp_terakhir'] !== 'Aman'): ?>
                                <span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-bold rounded-full">
                                    <?= $siswa['status_sp_terakhir'] ?>
                                </span>
                                <?php else: ?>
                                <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">
                                    Aman
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Lihat Detail Button -->
                        <div class="mt-4">
                            <div class="w-full bg-navy text-white text-center py-2 rounded-lg font-medium group-hover:bg-blue-900 transition-colors flex items-center justify-center">
                                <span>Lihat Detail</span>
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>

                    </div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

    </div>

</div>

</body>
</html>