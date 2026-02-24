<?php
/**
 * SITAPSI - Navbar Guru (Terintegrasi UI Global)
 * Desain translasi dari React/Next.js ke PHP Native
 */

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user_name = $_SESSION['nama_lengkap'] ?? ($user['nama_lengkap'] ?? 'Guru');
$initial = strtoupper(substr($user_name, 0, 1));
?>

<header class="bg-white border-b border-[#E2E8F0] sticky top-0 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-[#000080] shadow-md shadow-blue-900/10 flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                    </svg>
                </div>
                <div class="flex flex-col justify-center">
                    <h1 class="text-sm font-extrabold text-[#000080] tracking-tight leading-tight">SITAPSI</h1>
                    <p class="text-[10px] font-bold text-slate-500 truncate max-w-[140px] leading-tight">Portal Guru</p>
                </div>
            </div>

            <nav class="hidden md:flex space-x-2 items-center">
                <a href="input_pelanggaran.php" 
                   class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $current_page === 'input_pelanggaran' ? 'bg-[#000080] text-white shadow-md shadow-blue-900/10' : 'text-slate-600 hover:text-[#000080] hover:bg-slate-50' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                    Input Pelanggaran
                </a>
                
                <a href="rekap_kelas.php" 
                   class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= in_array($current_page, ['rekap_kelas', 'detail_siswa']) ? 'bg-[#000080] text-white shadow-md shadow-blue-900/10' : 'text-slate-600 hover:text-[#000080] hover:bg-slate-50' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="M12 11h4"></path><path d="M12 16h4"></path><path d="M8 11h.01"></path><path d="M8 16h.01"></path></svg>
                    Rekap Kelas
                </a>
            </nav>

            <div class="flex items-center gap-4">
                <div class="hidden md:flex items-center gap-3 border-r border-[#E2E8F0] pr-4">
                    <div class="text-right">
                        <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($user_name) ?></p>
                        <p class="text-[10px] font-medium text-slate-500 uppercase">Guru SMPK 2</p>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-[#000080] flex items-center justify-center text-sm font-bold text-white shadow-sm">
                        <?= $initial ?>
                    </div>
                </div>
                
                <a href="../../actions/logout.php" onclick="return confirm('Keluar dari Portal Guru?')" 
                   class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors flex items-center justify-center" title="Keluar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </a>
            </div>

        </div>
    </div>
</header>

<nav class="md:hidden fixed bottom-0 w-full bg-white border-t border-[#E2E8F0] z-50 pb-safe shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
    <div class="flex justify-around items-center h-16 px-2">
        
        <a href="input_pelanggaran.php" class="flex flex-col items-center justify-center w-full h-full space-y-1 transition-colors <?= $current_page === 'input_pelanggaran' ? 'text-[#000080]' : 'text-slate-400 hover:text-slate-600' ?>">
            <div class="<?= $current_page === 'input_pelanggaran' ? 'bg-blue-50 p-1.5 rounded-lg' : 'p-1.5' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
            </div>
            <span class="text-[10px] font-bold">Input Poin</span>
        </a>

        <a href="rekap_kelas.php" class="flex flex-col items-center justify-center w-full h-full space-y-1 transition-colors <?= in_array($current_page, ['rekap_kelas', 'detail_siswa']) ? 'text-[#000080]' : 'text-slate-400 hover:text-slate-600' ?>">
            <div class="<?= in_array($current_page, ['rekap_kelas', 'detail_siswa']) ? 'bg-blue-50 p-1.5 rounded-lg' : 'p-1.5' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="M12 11h4"></path><path d="M12 16h4"></path><path d="M8 11h.01"></path><path d="M8 16h.01"></path></svg>
            </div>
            <span class="text-[10px] font-bold">Rekap Kelas</span>
        </a>

    </div>
</nav>