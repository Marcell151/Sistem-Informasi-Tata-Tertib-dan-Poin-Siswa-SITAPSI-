<?php
/**
 * SITAPSI - Rapor Karakter (UI GLOBAL PORTAL)
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
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 pl-16 lg:pl-6 py-4 sticky top-0 z-30 flex items-center space-x-4">
            <a href="pelaporan_rekap.php" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            </a>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Rapor Karakter Siswa</h1>
                <p class="text-sm font-medium text-slate-500">Hasil konversi nilai karakter (A/B/C/D) berdasarkan poin</p>
            </div>
        </div>

        <div class="p-6 space-y-6 max-w-7xl mx-auto">

            <div class="bg-[#000080] text-white rounded-xl shadow-md shadow-blue-900/10 p-6 relative overflow-hidden">
                <svg class="absolute right-0 top-0 text-white/5 w-48 h-48 transform translate-x-8 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-extrabold mb-1">Pilih Kelas untuk Rapor</h2>
                        <p class="text-blue-200 font-medium text-sm">Tahun Ajaran <?= $tahun_aktif['nama_tahun'] ?> • Semester <?= $tahun_aktif['semester_aktif'] ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php 
                // Grouping layout UI Global
                foreach ($kelas_list as $kelas): 
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
                    <div class="bg-white border border-[#E2E8F0] hover:border-[#000080] rounded-xl shadow-sm hover:shadow-md p-6 transition-all transform group-hover:-translate-y-1 relative overflow-hidden flex flex-col items-center justify-center h-full min-h-[160px]">
                        <div class="w-1.5 h-full bg-slate-200 group-hover:bg-[#000080] absolute left-0 top-0 transition-colors"></div>
                        
                        <div class="w-12 h-12 bg-slate-50 group-hover:bg-blue-50 text-[#000080] rounded-full flex items-center justify-center mb-3 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        </div>
                        
                        <h3 class="text-2xl font-extrabold text-slate-800 mb-1"><?= $kelas['nama_kelas'] ?></h3>
                        
                        <div class="flex items-center text-[11px] font-bold text-slate-400 group-hover:text-blue-500 uppercase tracking-wider">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            <span><?= $jumlah_siswa ?> Siswa</span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($kelas_list)): ?>
            <div class="bg-white rounded-xl shadow-sm p-12 text-center border border-dashed border-[#E2E8F0]">
                <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <p class="text-slate-500 font-bold text-lg">Belum ada kelas yang terdaftar</p>
            </div>
            <?php endif; ?>

        </div>

    </div>

</div>

</body>
</html>