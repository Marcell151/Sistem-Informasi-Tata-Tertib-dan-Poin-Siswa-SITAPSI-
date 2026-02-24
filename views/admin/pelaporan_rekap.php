<?php
/**
 * SITAPSI - Pelaporan & Rekap (UI GLOBAL PORTAL)
 * Hub untuk memilih: Rekapitulasi atau Rapor Karakter
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelaporan & Rekap - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 pl-16 lg:pl-6 py-4 sticky top-0 z-30">
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Pelaporan & Rekap</h1>
            <p class="text-sm font-medium text-slate-500">Pusat unduh laporan rekapitulasi poin dan rapor karakter</p>
        </div>

        <div class="p-6 space-y-6 max-w-5xl mx-auto">
            
            <div class="bg-[#000080] text-white rounded-xl shadow-md shadow-blue-900/10 p-6 relative overflow-hidden">
                <svg class="absolute right-0 top-0 text-white/5 w-48 h-48 transform translate-x-8 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <div class="relative z-10">
                    <h2 class="text-2xl font-extrabold mb-1">Sistem Pelaporan</h2>
                    <p class="text-blue-200 font-medium text-sm">Tahun Ajaran <?= $tahun_aktif['nama_tahun'] ?> • Semester <?= $tahun_aktif['semester_aktif'] ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <a href="rekapitulasi_kelas.php" class="block group">
                    <div class="bg-white rounded-xl shadow-sm border border-[#E2E8F0] p-8 transition-all transform hover:-translate-y-1 hover:shadow-md hover:border-[#000080] h-full flex flex-col">
                        <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-[#000080] group-hover:text-white transition-colors">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-800 mb-3 group-hover:text-[#000080] transition-colors">Rekapitulasi Kelas (Leger)</h3>
                        <p class="text-sm text-slate-500 mb-6 flex-1 leading-relaxed">
                            Laporan poin pelanggaran siswa per kelas dalam bentuk matriks. Menampilkan total poin 3 kategori dan status SP.
                        </p>
                        <div class="space-y-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-6">
                            <p class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Tabel Matriks Per Kelas</p>
                            <p class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Export ke Excel</p>
                        </div>
                        <div class="flex items-center text-[#000080] font-bold text-sm">
                            <span>Buka Menu</span>
                            <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        </div>
                    </div>
                </a>

                <a href="rapor_karakter.php" class="block group">
                    <div class="bg-white rounded-xl shadow-sm border border-[#E2E8F0] p-8 transition-all transform hover:-translate-y-1 hover:shadow-md hover:border-[#000080] h-full flex flex-col">
                        <div class="w-16 h-16 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-800 mb-3 group-hover:text-purple-600 transition-colors">Rapor Karakter Akhir</h3>
                        <p class="text-sm text-slate-500 mb-6 flex-1 leading-relaxed">
                            Laporan akhir semester dengan konversi poin menjadi nilai predikat (A/B/C/D) untuk 3 aspek karakter siswa.
                        </p>
                        <div class="space-y-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-6">
                            <p class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Konversi Nilai A/B/C/D</p>
                            <p class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Export Format PDF Resmi</p>
                        </div>
                        <div class="flex items-center text-purple-600 font-bold text-sm">
                            <span>Buka Menu</span>
                            <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        </div>
                    </div>
                </a>

            </div>

        </div>
    </div>
</div>

</body>
</html>