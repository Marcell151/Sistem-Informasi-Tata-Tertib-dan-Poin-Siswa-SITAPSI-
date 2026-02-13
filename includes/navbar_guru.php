<?php
/**
 * SITAPSI - Navbar Guru (Responsive Final)
 * Mobile: Sticky Bottom Navigation (2 Menu Utama)
 * Desktop: Top Horizontal Navigation
 * Header: Menampilkan Nama Lengkap (Konsisten)
 */

$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Ambil nama lengkap dari session
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Guru';
?>

<nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 md:hidden z-50 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]">
    <div class="flex justify-around items-center h-16">
        
        <a href="../guru/input_pelanggaran.php" 
           class="flex flex-col items-center justify-center flex-1 h-full transition-colors <?= $current_page === 'input_pelanggaran' ? 'text-navy bg-blue-50 border-t-2 border-navy' : 'text-gray-500 hover:text-navy hover:bg-gray-50' ?>">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            <span class="text-xs font-medium">Input Poin</span>
        </a>

        <a href="../guru/rekap_kelas.php"
           class="flex flex-col items-center justify-center flex-1 h-full transition-colors <?= $current_page === 'rekap_kelas' ? 'text-navy bg-blue-50 border-t-2 border-navy' : 'text-gray-500 hover:text-navy hover:bg-gray-50' ?>">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span class="text-xs font-medium">Rekap Kelas</span>
        </a>

    </div>
</nav>


<header class="bg-navy text-white shadow-lg sticky top-0 z-40">
    <div class="container mx-auto px-4 py-3 flex items-center justify-between">
        
        <div class="flex items-center space-x-3">
            <div class="bg-white rounded-full p-1.5 shadow-sm">
                <svg class="w-6 h-6 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-lg font-bold tracking-wide leading-tight">SITAPSI</h1>
                <p class="text-[10px] text-blue-200 hidden sm:block">Sistem Tata Tertib Siswa</p>
            </div>
        </div>

        <nav class="hidden md:flex items-center space-x-6">
            <a href="../guru/input_pelanggaran.php" 
               class="text-sm font-semibold transition-colors flex items-center space-x-2 <?= $current_page === 'input_pelanggaran' ? 'text-white border-b-2 border-white pb-1' : 'text-blue-200 hover:text-white pb-1' ?>">
                <span>Input Pelanggaran</span>
            </a>

            <a href="../guru/rekap_kelas.php" 
               class="text-sm font-semibold transition-colors flex items-center space-x-2 <?= $current_page === 'rekap_kelas' ? 'text-white border-b-2 border-white pb-1' : 'text-blue-200 hover:text-white pb-1' ?>">
                <span>Rekap Kelas</span>
            </a>
        </nav>

        <div class="flex items-center space-x-3">
            
            <div class="text-right">
                <p class="text-sm font-semibold text-white max-w-[120px] md:max-w-none truncate md:overflow-visible">
                    <?= htmlspecialchars($nama_lengkap) ?>
                </p>
                
                <div class="flex items-center justify-end space-x-1">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                    <p class="text-[10px] text-blue-200">Online</p>
                </div>
            </div>
            
            <a href="../../actions/logout.php" 
               onclick="return confirm('Apakah Anda yakin ingin keluar?');"
               class="bg-red-500/90 hover:bg-red-600 text-white p-2 md:px-3 md:py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center shadow-md border border-red-400">
                <svg class="w-5 h-5 md:mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span class="hidden md:inline">Keluar</span>
            </a>
        </div>

    </div>
</header>

<div class="h-20 md:hidden"></div>