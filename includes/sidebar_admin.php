<?php
/**
 * SITAPSI - Sidebar Admin (Versi Standalone / Belum Integrasi)
 * Menggunakan UI Modern (Translasi dari React/Next.js ke PHP Native)
 * PENYESUAIAN: Tambah Menu Data Wali Murid (Orang Tua) & Arsip Global
 */

$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Menghitung jumlah report/revisi yang berstatus Pending
$count_report = 0;
if (function_exists('fetchOne')) {
    $report_data = fetchOne("SELECT COUNT(*) as total FROM tb_pelanggaran_header WHERE status_revisi = 'Pending'");
    $count_report = $report_data['total'] ?? 0;
}

// Data user untuk footer sidebar
$user_name = $_SESSION['nama_lengkap'] ?? ($user['nama_lengkap'] ?? 'Admin Tatib');
$initial = strtoupper(substr($user_name, 0, 1));

// Helper untuk CSS Class menu aktif
function getNavClass($isActive) {
    $base = "flex items-center justify-between px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 border border-transparent";
    if ($isActive) {
        return "$base bg-[#000080] text-white shadow-md shadow-blue-900/10";
    }
    return "$base text-slate-600 hover:text-[#000080] hover:bg-slate-50 hover:border-slate-200";
}
?>

<button id="mobile-menu-btn" class="lg:hidden fixed top-4 left-4 z-50 p-2 rounded-lg bg-[#000080] text-white shadow-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="4" y1="12" x2="20" y2="12"></line>
        <line x1="4" y1="6" x2="20" y2="6"></line>
        <line x1="4" y1="18" x2="20" y2="18"></line>
    </svg>
</button>

<div id="sidebar-overlay" class="lg:hidden fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 hidden transition-opacity"></div>

<aside id="sidebar" class="fixed left-0 top-0 h-screen w-64 bg-white border-r border-[#E2E8F0] flex flex-col z-50 transition-transform duration-300 -translate-x-full lg:translate-x-0 shadow-sm">
    
    <div class="p-6 border-b border-[#F1F5F9]">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-md bg-[#000080] shadow-blue-900/10 flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-sm font-extrabold text-[#000080] tracking-tight">SITAPSI</h1>
                    <p class="text-[10px] font-bold text-slate-500 truncate max-w-[140px]">SMPK SANTA MARIA 2</p>
                </div>
            </div>
            <button id="close-sidebar-btn" class="lg:hidden text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    </div>

    <nav class="flex-1 p-4 space-y-1 overflow-y-auto scrollbar-hide">
        
        <a href="dashboard.php" class="<?= getNavClass($current_page === 'dashboard') ?>">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                Dashboard
            </div>
        </a>

        <div class="pt-4 pb-1">
            <p class="px-4 text-[10px] font-bold tracking-wider text-slate-400 uppercase">Master Data</p>
        </div>

        <a href="data_siswa.php" class="<?= getNavClass($current_page === 'data_siswa') ?>">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Data Siswa
            </div>
        </a>

        <a href="data_ortu.php" class="<?= getNavClass($current_page === 'data_ortu') ?>">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Data Wali Murid
            </div>
        </a>

        <a href="data_guru.php" class="<?= getNavClass($current_page === 'data_guru') ?>">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                Data Guru
            </div>
        </a>

        <div class="pt-4 pb-1">
            <p class="px-4 text-[10px] font-bold tracking-wider text-slate-400 uppercase">Tatib & Poin</p>
        </div>

        <a href="audit_harian.php" class="<?= getNavClass($current_page === 'audit_harian') ?>">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Audit Harian
            </div>
        </a>

        <a href="kelola_report.php" class="<?= getNavClass($current_page === 'kelola_report') ?>">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                Kelola Report
            </div>
            <?php if ($count_report > 0): ?>
                <span class="px-2 py-0.5 bg-red-500 text-white rounded-full text-[10px] font-bold shadow-sm">
                    <?= $count_report ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="monitoring_siswa.php" class="<?= getNavClass(in_array($current_page, ['monitoring_siswa', 'monitoring_siswa_list'])) ?>">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                Monitoring Siswa
            </div>
        </a>

        <a href="pelaporan_rekap.php" class="<?= getNavClass(in_array($current_page, ['pelaporan_rekap', 'rekapitulasi_kelas', 'rapor_karakter'])) ?>">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Pelaporan & Rekap
            </div>
        </a>

        <a href="peringkat_siswa.php" class="<?= getNavClass($current_page === 'peringkat_siswa') ?>">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                Statistik Kedisiplinan
            </div>
        </a>

        <a href="manajemen_sp.php" class="<?= getNavClass($current_page === 'manajemen_sp') ?>">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path><line x1="12" y1="11" x2="12" y2="17"></line><line x1="9" y1="14" x2="15" y2="14"></line></svg>
                Manajemen SP
            </div>
        </a>

        <a href="manajemen_aturan.php" class="<?= getNavClass($current_page === 'manajemen_aturan') ?>">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                Manajemen Aturan
            </div>
        </a>

        <div class="pt-4 pb-1">
            <p class="px-4 text-[10px] font-bold tracking-wider text-slate-400 uppercase">Sistem Akademik</p>
        </div>

        <a href="pengaturan_akademik.php" class="<?= getNavClass(in_array($current_page, ['pengaturan_akademik', 'kenaikan_kelas'])) ?>">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                Pengaturan Akademik
            </div>
        </a>

        <div class="pt-4 pb-1">
            <p class="px-4 text-[10px] font-bold tracking-wider text-slate-400 uppercase">Pusat Data Lampau</p>
        </div>

        <a href="arsip_tahun.php" class="<?= getNavClass($current_page === 'arsip_tahun') ?>">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"></path><path d="M1 3h22v5H1z"></path><path d="M10 12h4"></path></svg>
                Arsip Global
            </div>
        </a>

    </nav>

    <div class="p-4 border-t border-[#F1F5F9] bg-slate-50/50">
        <div class="flex items-center gap-3 mb-4 px-2">
            <div class="w-10 h-10 rounded-full bg-[#000080] flex items-center justify-center text-sm font-bold text-white shadow-sm">
                <?= $initial ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($user_name) ?></p>
                <p class="text-[10px] font-medium text-slate-500 uppercase">Administrator</p>
            </div>
        </div>
        <a href="../../actions/logout.php" onclick="return confirm('Keluar dari aplikasi?')" class="flex w-full items-center justify-start text-sm font-medium px-4 py-2.5 rounded-md text-slate-600 hover:text-red-600 hover:bg-red-50 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Keluar
        </a>
    </div>
</aside>

<script>
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const closeBtn = document.getElementById('close-sidebar-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    function toggleSidebar() {
        const isClosed = sidebar.classList.contains('-translate-x-full');
        if (isClosed) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }

    if (mobileBtn) mobileBtn.addEventListener('click', toggleSidebar);
    if (closeBtn) closeBtn.addEventListener('click', toggleSidebar);
    if (overlay) overlay.addEventListener('click', toggleSidebar);
</script>