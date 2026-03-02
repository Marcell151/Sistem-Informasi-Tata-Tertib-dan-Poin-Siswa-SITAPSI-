<?php
/**
 * SITAPSI - Dashboard Admin (UI ALIGNED WITH GLOBAL PORTAL)
 * FIX LOGIKA: Menyesuaikan Grafik & Aktivitas berdasarkan Semester Aktif
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

// Statistik umum (Memanfaatkan total poin yang sudah difilter oleh sistem semester)
$stats = fetchOne("
    SELECT 
        COUNT(DISTINCT a.nis) as total_siswa,
        COUNT(DISTINCT CASE WHEN a.status_sp_terakhir != 'Aman' THEN a.nis END) as siswa_sp,
        SUM(a.total_poin_umum) as total_poin
    FROM tb_anggota_kelas a
    WHERE a.id_tahun = :id_tahun
", ['id_tahun' => $tahun_aktif['id_tahun']]);

// STATISTIK SP PER KATEGORI
$stats_sp = fetchOne("
    SELECT 
        COUNT(CASE WHEN status_sp_kelakuan != 'Aman' THEN 1 END) as sp_kelakuan,
        COUNT(CASE WHEN status_sp_kerajinan != 'Aman' THEN 1 END) as sp_kerajinan,
        COUNT(CASE WHEN status_sp_kerapian != 'Aman' THEN 1 END) as sp_kerapian
    FROM tb_anggota_kelas
    WHERE id_tahun = :id_tahun
", ['id_tahun' => $tahun_aktif['id_tahun']]);

// LOGIKA BARU: Aktivitas 30 hari terakhir HANYA PADA SEMESTER AKTIF
$aktivitas_30_hari = fetchAll("
    SELECT 
        DATE(h.tanggal) as tanggal,
        COUNT(*) as jumlah
    FROM tb_pelanggaran_header h
    WHERE h.id_tahun = :id_tahun
    AND h.semester = :semester
    AND h.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(h.tanggal)
    ORDER BY tanggal DESC
    LIMIT 30
", [
    'id_tahun' => $tahun_aktif['id_tahun'],
    'semester' => $tahun_aktif['semester_aktif']
]);

// Top 5 siswa poin tertinggi (Total poin umum sudah mewakili semester aktif berkat Ganti Semester Smart Sync)
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
    AND a.total_poin_umum > 0
    ORDER BY a.total_poin_umum DESC
    LIMIT 5
", ['id_tahun' => $tahun_aktif['id_tahun']]);

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// --- UI CONFIG VARIABLES ---
$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm p-6";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 py-4 sticky top-0 z-30">
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Dashboard</h1>
            <p class="text-sm font-medium text-slate-500">Tahun Ajaran: <?= $tahun_aktif['nama_tahun'] ?> (<?= $tahun_aktif['semester_aktif'] ?>)</p>
        </div>

        <div class="p-6 space-y-6 max-w-7xl mx-auto">

            <?php if ($success): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <p class="font-medium text-sm"><?= htmlspecialchars($success) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <p class="font-medium text-sm"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <div class="<?= $card_class ?> flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Siswa</p>
                            <p class="text-3xl font-extrabold text-slate-800"><?= $stats['total_siswa'] ?></p>
                        </div>
                    </div>
                </div>

                <div class="<?= $card_class ?> flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-red-50 border border-red-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Siswa SP</p>
                            <p class="text-3xl font-extrabold text-red-600"><?= $stats['siswa_sp'] ?></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 pt-3 border-t border-[#E2E8F0]">
                        <div class="text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Kelakuan</p>
                            <p class="text-sm font-bold text-slate-700"><?= $stats_sp['sp_kelakuan'] ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Kerajinan</p>
                            <p class="text-sm font-bold text-slate-700"><?= $stats_sp['sp_kerajinan'] ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Kerapian</p>
                            <p class="text-sm font-bold text-slate-700"><?= $stats_sp['sp_kerapian'] ?></p>
                        </div>
                    </div>
                </div>

                <div class="<?= $card_class ?> flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-orange-50 border border-orange-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Poin</p>
                            <p class="text-3xl font-extrabold text-orange-500"><?= number_format($stats['total_poin'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>

                <div class="<?= $card_class ?> flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Semester</p>
                            <p class="text-2xl font-extrabold text-emerald-600"><?= $tahun_aktif['semester_aktif'] ?></p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="<?= $card_class ?>">
                <h3 class="font-bold text-slate-800 mb-4 flex items-center text-sm">
                    <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    Aktivitas Pelanggaran (30 Hari Terakhir)
                </h3>
                <div class="h-64">
                    <canvas id="chartAktivitas"></canvas>
                </div>
            </div>

            <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-[#E2E8F0] bg-slate-50/50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800 text-sm flex items-center">
                        <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                        Top 5 Poin Tertinggi
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-white text-xs text-slate-500 uppercase border-b border-[#E2E8F0]">
                            <tr>
                                <th class="p-4 font-bold">Rank</th>
                                <th class="p-4 font-bold">Nama Siswa</th>
                                <th class="p-4 font-bold">Kelas</th>
                                <th class="p-4 font-bold text-center">Total Poin</th>
                                <th class="p-4 font-bold text-center">SP Tertinggi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php if (empty($top_siswa)): ?>
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-400 text-sm font-medium">Belum ada data pelanggaran di semester ini</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($top_siswa as $idx => $siswa): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-4 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg font-bold text-xs shadow-sm border
                                        <?= $idx === 0 ? 'bg-amber-100 text-amber-700 border-amber-200' : '' ?>
                                        <?= $idx === 1 ? 'bg-slate-100 text-slate-600 border-slate-200' : '' ?>
                                        <?= $idx === 2 ? 'bg-orange-100 text-orange-700 border-orange-200' : '' ?>
                                        <?= $idx >= 3 ? 'bg-white text-slate-500 border-[#E2E8F0]' : '' ?>">
                                        #<?= $idx + 1 ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <p class="font-bold text-slate-800 text-[13px]"><?= htmlspecialchars($siswa['nama_siswa']) ?></p>
                                    <p class="text-[10px] font-medium text-slate-400"><?= $siswa['nis'] ?></p>
                                </td>
                                <td class="p-4 text-slate-600 font-medium"><?= $siswa['nama_kelas'] ?></td>
                                <td class="p-4 text-center">
                                    <span class="px-2.5 py-1 bg-[#000080]/10 text-[#000080] rounded-md font-bold text-xs border border-[#000080]/20">
                                        <?= $siswa['total_poin_umum'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if($siswa['status_sp_terakhir'] === 'Aman'): ?>
                                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">Aman</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-red-50 text-red-600 border border-red-200"><?= $siswa['status_sp_terakhir'] ?></span>
                                    <?php endif; ?>
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
            backgroundColor: 'rgba(0, 0, 128, 0.05)',
            borderWidth: 2,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#000080',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, color: '#94a3b8', font: {size: 11} },
                grid: { color: '#f1f5f9', drawBorder: false }
            },
            x: {
                ticks: { color: '#94a3b8', font: {size: 11} },
                grid: { display: false, drawBorder: false }
            }
        }
    }
});
</script>

</body>
</html>