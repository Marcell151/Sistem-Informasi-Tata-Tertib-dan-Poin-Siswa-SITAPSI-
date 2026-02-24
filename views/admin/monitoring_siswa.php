<?php
/**
 * SITAPSI - Monitoring Siswa (UI GLOBAL PORTAL)
 * Flow: Pilih Kelas → Pilih Siswa → Detail
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
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
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 pl-16 lg:pl-6 py-4 sticky top-0 z-30 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Monitoring Siswa</h1>
                <p class="text-sm font-medium text-slate-500">Pilih kelas untuk melihat daftar siswa & detail pelanggaran</p>
            </div>
        </div>

        <div class="p-6 space-y-6 max-w-7xl mx-auto">

            <?php if ($success): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <p class="font-medium text-sm"><?= htmlspecialchars($success) ?></p>
            </div>
            <?php endif; ?>

            <div class="bg-[#000080] text-white rounded-xl shadow-md shadow-blue-900/10 p-6 relative overflow-hidden">
                <svg class="absolute right-0 top-0 text-white/5 w-48 h-48 transform translate-x-8 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-extrabold mb-1">Pilih Kelas</h2>
                        <p class="text-blue-200 font-medium text-sm">Tahun Ajaran <?= $tahun_aktif['nama_tahun'] ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-blue-200 text-xs font-bold uppercase tracking-wider mb-1">Total Kelas</p>
                        <p class="text-4xl font-extrabold"><?= count($kelas_list) ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php foreach ($kelas_list as $kelas): 
                    $jumlah_siswa = fetchOne("
                        SELECT COUNT(*) as total 
                        FROM tb_anggota_kelas 
                        WHERE id_kelas = :id_kelas AND id_tahun = :id_tahun
                    ", ['id_kelas' => $kelas['id_kelas'], 'id_tahun' => $tahun_aktif['id_tahun']])['total'] ?? 0;
                ?>
                <a href="monitoring_siswa_list.php?kelas=<?= $kelas['id_kelas'] ?>" class="block group">
                    <div class="bg-white border border-[#E2E8F0] hover:border-[#000080] rounded-xl shadow-sm hover:shadow-md p-6 transition-all transform group-hover:-translate-y-1 relative overflow-hidden h-full flex flex-col justify-between">
                        <div class="w-1.5 h-full bg-slate-200 group-hover:bg-[#000080] absolute left-0 top-0 transition-colors"></div>
                        
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-slate-50 group-hover:bg-blue-50 text-[#000080] rounded-full flex items-center justify-center transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            </div>
                            <div class="text-right">
                                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Tingkat</p>
                                <p class="text-2xl font-extrabold text-slate-800"><?= $kelas['tingkat'] ?></p>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-extrabold text-slate-800 mb-3"><?= $kelas['nama_kelas'] ?></h3>
                        
                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-500 uppercase tracking-wider border-t border-[#E2E8F0] pt-3">
                            <span class="flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                                <?= $jumlah_siswa ?> Siswa
                            </span>
                            <svg class="w-4 h-4 text-[#000080] transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($kelas_list)): ?>
            <div class="bg-white rounded-xl shadow-sm p-12 text-center border border-dashed border-[#E2E8F0]">
                <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <p class="text-slate-500 font-bold text-lg">Belum ada kelas yang terdaftar</p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>