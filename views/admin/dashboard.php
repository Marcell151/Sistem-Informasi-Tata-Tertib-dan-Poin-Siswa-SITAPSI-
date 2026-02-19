<?php
/**
 * SITAPSI - Dashboard Admin (UPDATE - SP PER KATEGORI)
 * Tambah statistik breakdown SP per kategori
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$tahun_aktif = fetchOne("
    SELECT id_tahun, nama_tahun, semester_aktif 
    FROM tb_tahun_ajaran 
    WHERE status = 'Aktif' 
    LIMIT 1
");

// Statistik umum
$stats = fetchOne("
    SELECT 
        COUNT(DISTINCT a.nis) as total_siswa,
        COUNT(DISTINCT CASE WHEN a.status_sp_terakhir != 'Aman' THEN a.nis END) as siswa_sp,
        SUM(a.total_poin_umum) as total_poin
    FROM tb_anggota_kelas a
    WHERE a.id_tahun = :id_tahun
", ['id_tahun' => $tahun_aktif['id_tahun']]);

// STATISTIK SP PER KATEGORI (BARU)
$stats_sp = fetchOne("
    SELECT 
        COUNT(CASE WHEN status_sp_kelakuan != 'Aman' THEN 1 END) as sp_kelakuan,
        COUNT(CASE WHEN status_sp_kerajinan != 'Aman' THEN 1 END) as sp_kerajinan,
        COUNT(CASE WHEN status_sp_kerapian != 'Aman' THEN 1 END) as sp_kerapian
    FROM tb_anggota_kelas
    WHERE id_tahun = :id_tahun
", ['id_tahun' => $tahun_aktif['id_tahun']]);

// Aktivitas 30 hari terakhir
$aktivitas_30_hari = fetchAll("
    SELECT 
        DATE(h.tanggal) as tanggal,
        COUNT(*) as jumlah
    FROM tb_pelanggaran_header h
    WHERE h.id_tahun = :id_tahun
    AND h.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(h.tanggal)
    ORDER BY tanggal DESC
    LIMIT 30
", ['id_tahun' => $tahun_aktif['id_tahun']]);

// Top 5 siswa poin tertinggi
$top_siswa = fetchAll("
    SELECT 
        s.nama_siswa,
        s.nis,
        k.nama_kelas,
        a.total_poin_umum,
        a.status_sp_terakhir,
        a.status_sp_kelakuan,
        a.status_sp_kerajinan,
        a.status_sp_kerapian
    FROM tb_anggota_kelas a
    JOIN tb_siswa s ON a.nis = s.nis
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE a.id_tahun = :id_tahun
    ORDER BY a.total_poin_umum DESC
    LIMIT 5
", ['id_tahun' => $tahun_aktif['id_tahun']]);

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: { colors: { 'navy': '#000080' } }
            }
        }
    </script>
</head>
<body class="bg-gray-50">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto bg-gray-100">
        
        <div class="bg-white shadow-sm border-b px-6 py-4 sticky top-0 z-30">
            <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
            <p class="text-sm text-gray-500">Tahun Ajaran: <?= $tahun_aktif['nama_tahun'] ?> (<?= $tahun_aktif['semester_aktif'] ?>)</p>
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

            <!-- Statistik Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <!-- Total Siswa -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div class="bg-blue-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Total Siswa</p>
                            <p class="text-3xl font-bold text-blue-600"><?= $stats['total_siswa'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Siswa Kena SP - DENGAN BREAKDOWN PER KATEGORI -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-red-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Siswa Kena SP</p>
                            <p class="text-3xl font-bold text-red-600"><?= $stats['siswa_sp'] ?></p>
                        </div>
                    </div>
                    <!-- Breakdown per kategori -->
                    <div class="grid grid-cols-3 gap-2 pt-3 border-t border-gray-100">
                        <div class="text-center">
                            <p class="text-xs text-gray-500">Kelakuan</p>
                            <p class="text-lg font-bold text-red-600"><?= $stats_sp['sp_kelakuan'] ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-500">Kerajinan</p>
                            <p class="text-lg font-bold text-blue-600"><?= $stats_sp['sp_kerajinan'] ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-500">Kerapian</p>
                            <p class="text-lg font-bold text-yellow-600"><?= $stats_sp['sp_kerapian'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Poin -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div class="bg-yellow-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Total Poin</p>
                            <p class="text-3xl font-bold text-yellow-600"><?= number_format($stats['total_poin']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Semester Aktif -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div class="bg-green-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Semester</p>
                            <p class="text-2xl font-bold text-green-600"><?= $tahun_aktif['semester_aktif'] ?></p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Chart Aktivitas -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold text-gray-800 mb-4">📊 Aktivitas Pelanggaran (30 Hari Terakhir)</h3>
                <canvas id="chartAktivitas" height="80"></canvas>
            </div>

            <!-- Top 5 Siswa Poin Tertinggi dengan SP Per Kategori -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b font-bold text-gray-700">
                    🔥 Top 5 Siswa dengan Poin Tertinggi
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="p-4">Peringkat</th>
                                <th class="p-4">Nama Siswa</th>
                                <th class="p-4">Kelas</th>
                                <th class="p-4 text-center">Total Poin</th>
                                <th class="p-4 text-center">SP Kelakuan</th>
                                <th class="p-4 text-center">SP Kerajinan</th>
                                <th class="p-4 text-center">SP Kerapian</th>
                                <th class="p-4 text-center">SP Tertinggi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($top_siswa)): ?>
                            <tr>
                                <td colspan="8" class="p-8 text-center text-gray-500">
                                    Belum ada data pelanggaran
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($top_siswa as $idx => $siswa): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4 text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full font-bold text-white
                                        <?= $idx === 0 ? 'bg-yellow-500' : '' ?>
                                        <?= $idx === 1 ? 'bg-gray-400' : '' ?>
                                        <?= $idx === 2 ? 'bg-orange-400' : '' ?>
                                        <?= $idx >= 3 ? 'bg-gray-300 text-gray-700' : '' ?>">
                                        <?= $idx + 1 ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <p class="font-bold text-gray-800"><?= htmlspecialchars($siswa['nama_siswa']) ?></p>
                                    <p class="text-xs text-gray-500"><?= $siswa['nis'] ?></p>
                                </td>
                                <td class="p-4 text-gray-600"><?= $siswa['nama_kelas'] ?></td>
                                <td class="p-4 text-center">
                                    <span class="px-3 py-1 bg-gray-800 text-white rounded-full font-bold text-sm">
                                        <?= $siswa['total_poin_umum'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-1 rounded text-xs font-bold <?= $siswa['status_sp_kelakuan'] === 'Aman' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $siswa['status_sp_kelakuan'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-1 rounded text-xs font-bold <?= $siswa['status_sp_kerajinan'] === 'Aman' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                                        <?= $siswa['status_sp_kerajinan'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-1 rounded text-xs font-bold <?= $siswa['status_sp_kerapian'] === 'Aman' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                        <?= $siswa['status_sp_kerapian'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $siswa['status_sp_terakhir'] === 'Aman' ? 'bg-green-100 text-green-800' : 'bg-red-600 text-white' ?>">
                                        <?= $siswa['status_sp_terakhir'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Chart Aktivitas
const ctxAktivitas = document.getElementById('chartAktivitas').getContext('2d');
const dataAktivitas = <?= json_encode(array_reverse($aktivitas_30_hari)) ?>;

new Chart(ctxAktivitas, {
    type: 'line',
    data: {
        labels: dataAktivitas.map(d => {
            const date = new Date(d.tanggal);
            return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
        }),
        datasets: [{
            label: 'Jumlah Pelanggaran',
            data: dataAktivitas.map(d => d.jumlah),
            borderColor: '#000080',
            backgroundColor: 'rgba(0, 0, 128, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});
</script>

</body>
</html>