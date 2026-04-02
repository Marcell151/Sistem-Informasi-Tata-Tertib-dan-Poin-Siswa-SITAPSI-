<?php
/**
 * SITAPSI - Dashboard Admin (STANDALONE VERSION)
 * FIX LOGIKA: Menyesuaikan Aktivitas berdasarkan Semester Aktif
 * PENAMBAHAN: System Initialization Barrier di baris paling atas
 * REVISI UI: Hapus Grafik, Ganti Tabel Analitik & Tambah Metrik Reward
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

// Pastikan hanya Admin yang bisa mengakses halaman ini
requireAdmin();

// ===================================================================================
// 1. SYSTEM INITIALIZATION BARRIER (Pengecekan Tahun Ajaran Kosong)
// ===================================================================================
$tahun_aktif = fetchOne("
    SELECT id_tahun, nama_tahun, semester_aktif 
    FROM tb_tahun_ajaran 
    WHERE status = 'Aktif' 
    LIMIT 1
");

// Jika Tahun Ajaran belum di-setup sama sekali (Database kosong)
if (!$tahun_aktif) {
    header("Location: setup_tahun_ajaran.php");
    exit;
}
// ===================================================================================

$id_tahun = $tahun_aktif['id_tahun'];
$hari_ini = date('Y-m-d');

// =========================================================================
// 2. QUERY METRIK UTAMA (TOP CARDS)
// =========================================================================

// Statistik Umum (Total Siswa, Siswa SP, dan Kandidat Reward / Poin 0)
$stats = fetchOne("
    SELECT 
        COUNT(DISTINCT a.no_induk) as total_siswa,
        COUNT(DISTINCT CASE WHEN a.status_sp_terakhir != 'Aman' THEN a.no_induk END) as siswa_sp,
        COUNT(DISTINCT CASE WHEN a.total_poin_umum = 0 THEN a.no_induk END) as kandidat_reward
    FROM tb_anggota_kelas a
    WHERE a.id_tahun = :id_tahun
", ['id_tahun' => $id_tahun]);

// Total Transaksi (Pelanggaran) Hari Ini
$q_trans_hari_ini = fetchOne("SELECT COUNT(*) as total FROM tb_pelanggaran_header WHERE id_tahun = ? AND tanggal = ?", [$id_tahun, $hari_ini]);
$tot_hari_ini = $q_trans_hari_ini['total'] ?? 0;

// STATISTIK SP PER KATEGORI (Silo)
$stats_sp = fetchOne("
    SELECT 
        COUNT(CASE WHEN status_sp_kelakuan != 'Aman' THEN 1 END) as sp_kelakuan,
        COUNT(CASE WHEN status_sp_kerajinan != 'Aman' THEN 1 END) as sp_kerajinan,
        COUNT(CASE WHEN status_sp_kerapian != 'Aman' THEN 1 END) as sp_kerapian
    FROM tb_anggota_kelas
    WHERE id_tahun = :id_tahun
", ['id_tahun' => $id_tahun]);

// =========================================================================
// 3. QUERY ANALITIK CEPAT (TABEL TOP 5 & LOG)
// =========================================================================

// Analitik 1: Top 5 siswa poin tertinggi
$top_siswa = fetchAll("
    SELECT 
        s.nama_siswa,
        s.no_induk,
        k.nama_kelas,
        a.total_poin_umum,
        a.status_sp_terakhir
    FROM tb_anggota_kelas a
    JOIN tb_siswa s ON a.no_induk = s.no_induk
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE a.id_tahun = :id_tahun
    AND a.total_poin_umum > 0
    ORDER BY a.total_poin_umum DESC
    LIMIT 5
", ['id_tahun' => $id_tahun]);

// Analitik 2: Top 5 Kelas dengan Akumulasi Poin Tertinggi
$top_kelas = fetchAll("
    SELECT k.nama_kelas, SUM(ak.total_poin_umum) as total_poin_kelas, COUNT(ak.id_anggota) as jml_siswa_melanggar
    FROM tb_anggota_kelas ak
    JOIN tb_kelas k ON ak.id_kelas = k.id_kelas
    WHERE ak.id_tahun = ? AND ak.total_poin_umum > 0
    GROUP BY k.id_kelas
    ORDER BY total_poin_kelas DESC
    LIMIT 5
", [$id_tahun]);

// Analitik 3: Transaksi Terbaru
$recent_trans = fetchAll("
    SELECT ph.id_transaksi, s.nama_siswa, k.nama_kelas, g.nama_guru, ph.tanggal, ph.waktu,
           (SELECT SUM(poin_saat_itu) FROM tb_pelanggaran_detail WHERE id_transaksi = ph.id_transaksi) as total_poin
    FROM tb_pelanggaran_header ph
    JOIN tb_anggota_kelas ak ON ph.id_anggota = ak.id_anggota
    JOIN tb_siswa s ON ak.no_induk = s.no_induk
    JOIN tb_kelas k ON ak.id_kelas = k.id_kelas
    JOIN tb_guru g ON ph.id_guru = g.id_guru
    WHERE ph.id_tahun = ?
    ORDER BY ph.tanggal DESC, ph.waktu DESC
    LIMIT 5
", [$id_tahun]);


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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 py-4 sticky top-0 z-30 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Dashboard Kedisiplinan</h1>
                <p class="text-sm font-medium text-slate-500">Tahun Ajaran: <?= htmlspecialchars($tahun_aktif['nama_tahun']) ?> (<?= htmlspecialchars($tahun_aktif['semester_aktif']) ?>)</p>
            </div>
            <div class="hidden sm:flex items-center gap-3 bg-slate-50 px-4 py-2 rounded-lg border border-slate-200">
                <div class="w-8 h-8 rounded-full bg-[#000080] text-white flex items-center justify-center font-bold text-sm">
                    <?= substr($_SESSION['nama_lengkap'] ?? 'A', 0, 1) ?>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold text-slate-800 leading-tight"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin') ?></p>
                    <p class="text-[10px] font-bold text-slate-400 uppercase"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></p>
                </div>
            </div>
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
                
                <div class="<?= $card_class ?> flex flex-col justify-between hover:shadow-md transition-shadow group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Siswa Aktif</p>
                            <p class="text-3xl font-extrabold text-slate-800"><?= number_format($stats['total_siswa'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>

                <div class="<?= $card_class ?> flex flex-col justify-between hover:shadow-md transition-shadow group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-red-50 border border-red-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path><line x1="12" y1="11" x2="12" y2="17"></line><line x1="9" y1="14" x2="15" y2="14"></line></svg>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1" title="Siswa yang sedang dalam status hukuman SP">Siswa Kena SP</p>
                            <p class="text-3xl font-extrabold text-red-600"><?= number_format($stats['siswa_sp'] ?? 0) ?></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 pt-3 border-t border-[#E2E8F0]">
                        <div class="text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Kelakuan</p>
                            <p class="text-sm font-bold text-slate-700"><?= $stats_sp['sp_kelakuan'] ?? 0 ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Kerajinan</p>
                            <p class="text-sm font-bold text-slate-700"><?= $stats_sp['sp_kerajinan'] ?? 0 ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Kerapian</p>
                            <p class="text-sm font-bold text-slate-700"><?= $stats_sp['sp_kerapian'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>

                <div class="<?= $card_class ?> flex flex-col justify-between hover:shadow-md transition-shadow group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1" title="Siswa yang belum pernah melanggar sama sekali (Poin 0)">Kandidat Reward</p>
                            <p class="text-3xl font-extrabold text-emerald-600"><?= number_format($stats['kandidat_reward'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>

                <div class="<?= $card_class ?> flex flex-col justify-between hover:shadow-md transition-shadow group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Kasus Hari Ini</p>
                            <p class="text-3xl font-extrabold text-indigo-600"><?= $tot_hari_ini ?></p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-[#E2E8F0] bg-slate-50/50 flex justify-between items-center">
                        <h3 class="font-bold text-slate-800 text-sm flex items-center">
                            <svg class="w-4 h-4 mr-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                            Top 5 Poin Siswa Tertinggi
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-white text-[10px] font-extrabold text-slate-500 uppercase tracking-wider border-b border-[#E2E8F0]">
                                <tr>
                                    <th class="p-4">Rank</th>
                                    <th class="p-4">Siswa & Kelas</th>
                                    <th class="p-4 text-center">Status SP</th>
                                    <th class="p-4 text-right">Poin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E2E8F0]">
                                <?php if (empty($top_siswa)): ?>
                                <tr>
                                    <td colspan="4" class="p-8 text-center text-slate-400 font-medium">Belum ada data poin siswa</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($top_siswa as $idx => $siswa): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="p-4 text-center">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg font-bold text-xs shadow-sm border <?= $idx === 0 ? 'bg-amber-100 text-amber-700 border-amber-200' : ($idx === 1 ? 'bg-slate-100 text-slate-600 border-slate-200' : ($idx === 2 ? 'bg-orange-100 text-orange-700 border-orange-200' : 'bg-white text-slate-500 border-[#E2E8F0]')) ?>">
                                            #<?= $idx + 1 ?>
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <p class="font-bold text-slate-800 text-[13px]"><?= htmlspecialchars($siswa['nama_siswa']) ?></p>
                                        <p class="text-[10px] font-medium text-slate-400"><?= htmlspecialchars($siswa['nama_kelas']) ?></p>
                                    </td>
                                    <td class="p-4 text-center">
                                        <?php if($siswa['status_sp_terakhir'] === 'Aman'): ?>
                                            <span class="px-2 py-1 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">Aman</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 rounded-md text-[10px] font-bold bg-red-50 text-red-600 border border-red-200"><?= $siswa['status_sp_terakhir'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-right font-extrabold text-rose-600">
                                        <?= $siswa['total_poin_umum'] ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-[#E2E8F0] bg-slate-50/50 flex justify-between items-center">
                        <h3 class="font-bold text-slate-800 text-sm flex items-center">
                            <svg class="w-4 h-4 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                            Top 5 Kelas Akumulasi Poin
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-white text-[10px] font-extrabold text-slate-500 uppercase tracking-wider border-b border-[#E2E8F0]">
                                <tr>
                                    <th class="p-4">Kelas</th>
                                    <th class="p-4 text-center">Jml Siswa Bermasalah</th>
                                    <th class="p-4 text-right">Total Poin Kelas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E2E8F0]">
                                <?php if (empty($top_kelas)): ?>
                                <tr>
                                    <td colspan="3" class="p-8 text-center text-slate-400 font-medium">Belum ada data pelanggaran kelas</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($top_kelas as $tk): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="p-4 font-bold text-slate-800 text-[13px]">
                                        <?= htmlspecialchars($tk['nama_kelas']) ?>
                                    </td>
                                    <td class="p-4 text-center text-slate-500 font-medium">
                                        <span class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-md text-xs font-bold">
                                            <?= $tk['jml_siswa_melanggar'] ?> Siswa
                                        </span>
                                    </td>
                                    <td class="p-4 text-right font-extrabold text-amber-600">
                                        <?= number_format($tk['total_poin_kelas']) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-[#E2E8F0] bg-slate-50/50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800 text-sm flex items-center">
                        <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="12 8 12 12 14 14"></polyline><circle cx="12" cy="12" r="10"></circle></svg>
                        Riwayat Pelanggaran Terbaru
                    </h3>
                    <a href="audit_harian.php" class="text-[11px] font-bold text-[#000080] hover:underline uppercase tracking-wider">Lihat Semua</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-white text-[10px] font-extrabold text-slate-500 uppercase tracking-wider border-b border-[#E2E8F0]">
                            <tr>
                                <th class="p-4">Waktu Kejadian</th>
                                <th class="p-4">Siswa</th>
                                <th class="p-4">Pelapor (Guru)</th>
                                <th class="p-4 text-right">Poin Ditambah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php if (empty($recent_trans)): ?>
                            <tr>
                                <td colspan="4" class="p-8 text-center text-slate-400 font-medium">Belum ada riwayat pelanggaran.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($recent_trans as $rt): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-slate-800 text-[12px]"><?= date('d M Y', strtotime($rt['tanggal'])) ?></div>
                                    <div class="text-[10px] font-medium text-slate-400"><?= date('H:i', strtotime($rt['waktu'])) ?> WIB</div>
                                </td>
                                <td class="p-4">
                                    <p class="font-bold text-slate-800 text-[13px]"><?= htmlspecialchars($rt['nama_siswa']) ?></p>
                                    <p class="text-[10px] font-medium text-slate-400"><?= htmlspecialchars($rt['nama_kelas']) ?></p>
                                </td>
                                <td class="p-4 text-slate-600 font-medium text-[13px]">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($rt['nama_guru']) ?>
                                    </span>
                                </td>
                                <td class="p-4 text-right font-extrabold text-rose-600">
                                    +<?= $rt['total_poin'] ?? 0 ?>
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

</body>
</html>