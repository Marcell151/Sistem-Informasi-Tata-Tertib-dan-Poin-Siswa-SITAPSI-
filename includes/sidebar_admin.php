<?php
/**
 * SITAPSI - Sidebar Admin (FIXED LAYOUT + KELOLA REPORT)
 * Perbaikan: Sidebar tidak lagi menimpa konten pada mode Desktop
 */

$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Menghitung jumlah report/revisi yang berstatus Pending
$count_report = 0;
if (function_exists('fetchOne')) {
    $report_data = fetchOne("SELECT COUNT(*) as total FROM tb_pelanggaran_header WHERE status_revisi = 'Pending'");
    $count_report = $report_data['total'] ?? 0;
}
?>

<div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-navy transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col h-screen">
    
    <div class="flex items-center justify-center h-20 border-b border-blue-800 flex-shrink-0">
        <div class="text-center">
            <h1 class="text-xl font-bold text-white tracking-wider">SITAPSI</h1>
            <p class="text-xs text-blue-300">Admin Panel</p>
        </div>
    </div>

    <nav class="flex-1 px-4 py-6 overflow-y-auto scrollbar-hide">
        
        <a href="dashboard.php" class="flex items-center px-4 py-3 mb-2 rounded-lg transition-colors group <?= $current_page === 'dashboard' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            <span class="font-medium">Dashboard</span>
        </a>

        <div class="border-t border-blue-800 my-4 opacity-50"></div>
        <p class="px-4 text-xs font-bold text-blue-400 uppercase mb-2 tracking-wider">Data & Input</p>

        <a href="audit_harian.php" class="flex items-center px-4 py-3 mb-2 rounded-lg transition-colors <?= $current_page === 'audit_harian' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            <span class="font-medium">Audit Harian</span>
        </a>

        <a href="kelola_report.php" class="flex items-center px-4 py-3 mb-2 rounded-lg transition-colors <?= $current_page === 'kelola_report' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path>
            </svg>
            <span class="font-medium flex-1">Kelola Report</span>
            <?php if ($count_report > 0): ?>
            <span class="px-2 py-0.5 bg-red-500 text-white rounded-full text-xs font-bold shadow-sm">
                <?= $count_report ?>
            </span>
            <?php endif; ?>
        </a>

        <a href="monitoring_siswa.php" class="flex items-center px-4 py-3 mb-2 rounded-lg transition-colors <?= in_array($current_page, ['monitoring_siswa', 'monitoring_siswa_list', 'detail_siswa']) ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            <span class="font-medium">Monitoring Siswa</span>
        </a>

        <a href="pelaporan_rekap.php" class="flex items-center px-4 py-3 mb-2 rounded-lg transition-colors <?= in_array($current_page, ['pelaporan_rekap', 'rekapitulasi_kelas', 'rapor_karakter']) ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span class="font-medium">Pelaporan & Rekap</span>
        </a>

        <div class="border-t border-blue-800 my-4 opacity-50"></div>
        <p class="px-4 text-xs font-bold text-blue-400 uppercase mb-2 tracking-wider">Surat Peringatan</p>

        <a href="manajemen_sp.php" class="flex items-center px-4 py-3 mb-2 rounded-lg transition-colors <?= $current_page === 'manajemen_sp' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span class="font-medium">Manajemen SP</span>
        </a>

        <div class="border-t border-blue-800 my-4 opacity-50"></div>
        <p class="px-4 text-xs font-bold text-blue-400 uppercase mb-2 tracking-wider">Master Data</p>

        <a href="data_siswa.php" class="flex items-center px-4 py-3 mb-2 rounded-lg transition-colors <?= $current_page === 'data_siswa' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <span class="font-medium">Data Siswa</span>
        </a>

        <a href="data_guru.php" class="flex items-center px-4 py-3 mb-2 rounded-lg transition-colors <?= $current_page === 'data_guru' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            <span class="font-medium">Data Guru</span>
        </a>

        <a href="manajemen_aturan.php" class="flex items-center px-4 py-3 mb-2 rounded-lg transition-colors <?= $current_page === 'manajemen_aturan' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span class="font-medium">Manajemen Aturan</span>
        </a>

        <div class="border-t border-blue-800 my-4 opacity-50"></div>
        <p class="px-4 text-xs font-bold text-blue-400 uppercase mb-2 tracking-wider">Sistem</p>

        <a href="pengaturan_akademik.php" class="flex items-center px-4 py-3 mb-2 rounded-lg transition-colors <?= $current_page === 'pengaturan_akademik' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            <span class="font-medium">Pengaturan Akademik</span>
        </a>

        <div class="h-24"></div>
    </nav>

    <div class="border-t border-blue-800 bg-navy p-4 flex-shrink-0">
        <div class="flex items-center mb-3">
            <div class="w-10 h-10 bg-blue-700 rounded-full flex items-center justify-center mr-3 border border-blue-600 shadow-sm">
                <span class="font-bold text-white text-lg"><?= substr($user['nama_lengkap'] ?? 'A', 0, 1) ?></span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate"><?= $user['nama_lengkap'] ?? 'Admin' ?></p>
                <p class="text-xs text-blue-300">Super Admin</p>
            </div>
        </div>
        <a href="../../actions/logout.php" onclick="return confirm('Yakin ingin logout?')" class="flex items-center justify-center w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors shadow-sm text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Logout
        </a>
    </div>

</div>

<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden glass-effect"></div>

<button id="sidebar-toggle" class="fixed top-4 left-4 z-50 md:hidden bg-navy text-white p-2 rounded-lg shadow-lg hover:bg-blue-900 transition-colors">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
</button>

<script>
// Mobile menu toggle logic
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebar-overlay');
const sidebarToggle = document.getElementById('sidebar-toggle');

// Toggle buka/tutup
if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('-translate-x-full');
        sidebarOverlay.classList.toggle('hidden');
    });
}

// Tutup saat klik overlay (background gelap)
if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.add('-translate-x-full');
        sidebarOverlay.classList.add('hidden');
    });
}
</script>