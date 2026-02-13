<?php
/**
 * SITAPSI - Sidebar Admin (Desktop Navigation)
 * Fixed sidebar untuk admin dengan menu lengkap
 */

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Admin';
$role = $_SESSION['role'] ?? 'Admin';
?>

<!-- Sidebar Desktop -->
<aside class="hidden md:flex md:flex-shrink-0">
    <div class="flex flex-col w-64 bg-navy">
        
        <!-- Logo & Brand -->
        <div class="flex items-center justify-center h-16 px-4 bg-blue-900 border-b border-blue-800">
            <div class="flex items-center space-x-3">
                <div class="bg-white rounded-full p-1.5">
                    <svg class="w-6 h-6 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-white font-bold text-lg">SITAPSI</h1>
                    <p class="text-blue-200 text-[10px]">Admin Panel</p>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            
            <!-- Dashboard -->
            <a href="dashboard.php" 
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors <?= $current_page === 'dashboard' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Dashboard
            </a>

            <!-- Divider -->
            <div class="px-4 pt-4 pb-2">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Data & Input</p>
            </div>

            <!-- Audit Harian -->
            <a href="audit_harian.php" 
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors <?= $current_page === 'audit_harian' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                Audit Harian
            </a>

            <!-- Monitoring Siswa -->
            <a href="monitoring_siswa.php" 
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors <?= $current_page === 'monitoring_siswa' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                Monitoring Siswa
            </a>

            <!-- Rekapitulasi Kelas -->
            <a href="rekapitulasi_kelas.php" 
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors <?= $current_page === 'rekapitulasi_kelas' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Rekapitulasi Kelas
            </a>

            <!-- Divider -->
            <div class="px-4 pt-4 pb-2">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Surat Peringatan</p>
            </div>

            <!-- Manajemen SP -->
            <a href="manajemen_sp.php" 
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors <?= $current_page === 'manajemen_sp' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                Manajemen SP
            </a>

            <!-- Divider -->
            <div class="px-4 pt-4 pb-2">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Master Data</p>
            </div>

            <!-- Data Siswa -->
            <a href="data_siswa.php" 
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors <?= $current_page === 'data_siswa' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Data Siswa
            </a>

            <!-- Data Guru -->
            <a href="data_guru.php" 
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors <?= $current_page === 'data_guru' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                Data Guru
            </a>

            <!-- Manajemen Aturan -->
            <a href="manajemen_aturan.php" 
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors <?= $current_page === 'manajemen_aturan' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Manajemen Aturan
            </a>

            <!-- Divider -->
            <div class="px-4 pt-4 pb-2">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Sistem</p>
            </div>

            <!-- Pengaturan Akademik -->
            <a href="pengaturan_akademik.php" 
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors <?= $current_page === 'pengaturan_akademik' ? 'bg-blue-800 text-white shadow-md' : 'text-blue-100 hover:bg-blue-800 hover:text-white' ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Pengaturan Akademik
            </a>

        </nav>

        <!-- User Info -->
        <div class="flex-shrink-0 flex border-t border-blue-800 p-4">
            <div class="flex items-center w-full">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-blue-800 rounded-full flex items-center justify-center border-2 border-blue-600">
                        <span class="text-white font-bold text-sm"><?= strtoupper(substr($nama_lengkap, 0, 1)) ?></span>
                    </div>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($nama_lengkap) ?></p>
                    <p class="text-xs text-blue-200"><?= htmlspecialchars($role) ?></p>
                </div>
                <a href="../../actions/logout.php" 
                   onclick="return confirm('Yakin ingin keluar?');"
                   class="ml-2 p-2 bg-red-500 rounded-lg hover:bg-red-600 transition-colors" 
                   title="Logout">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </a>
            </div>
        </div>

    </div>
</aside>

<!-- Mobile Header (Hamburger Menu) -->
<div class="md:hidden bg-navy sticky top-0 z-50">
    <div class="flex items-center justify-between px-4 py-3">
        <div class="flex items-center space-x-3">
            <button id="mobile-menu-button" class="text-white p-2 rounded-lg hover:bg-blue-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <div>
                <h1 class="text-white font-bold text-lg">SITAPSI</h1>
                <p class="text-blue-200 text-[10px]">Admin Panel</p>
            </div>
        </div>
        <a href="../../actions/logout.php" 
           onclick="return confirm('Yakin ingin keluar?');"
           class="p-2 bg-red-500 rounded-lg hover:bg-red-600 transition-colors">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
        </a>
    </div>
</div>

<!-- Mobile Sidebar Overlay -->
<div id="mobile-menu-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden"></div>

<!-- Mobile Sidebar -->
<div id="mobile-menu" class="hidden fixed inset-y-0 left-0 w-64 bg-navy z-50 md:hidden transform -translate-x-full transition-transform duration-300">
    <div class="flex flex-col h-full">
        <div class="flex items-center justify-between px-4 py-3 border-b border-blue-800">
            <h2 class="text-white font-bold">Menu</h2>
            <button id="mobile-menu-close" class="text-white p-2 rounded-lg hover:bg-blue-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <!-- Same menu items as desktop -->
            <a href="dashboard.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-blue-100 hover:bg-blue-800 hover:text-white">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Dashboard
            </a>
            <!-- Add other menu items... -->
        </nav>
    </div>
</div>

<script>
// Mobile menu toggle
const menuButton = document.getElementById('mobile-menu-button');
const menuClose = document.getElementById('mobile-menu-close');
const menu = document.getElementById('mobile-menu');
const overlay = document.getElementById('mobile-menu-overlay');

menuButton?.addEventListener('click', () => {
    menu.classList.remove('hidden');
    overlay.classList.remove('hidden');
    setTimeout(() => menu.classList.remove('-translate-x-full'), 10);
});

menuClose?.addEventListener('click', () => {
    menu.classList.add('-translate-x-full');
    setTimeout(() => {
        menu.classList.add('hidden');
        overlay.classList.add('hidden');
    }, 300);
});

overlay?.addEventListener('click', () => {
    menu.classList.add('-translate-x-full');
    setTimeout(() => {
        menu.classList.add('hidden');
        overlay.classList.add('hidden');
    }, 300);
});
</script>