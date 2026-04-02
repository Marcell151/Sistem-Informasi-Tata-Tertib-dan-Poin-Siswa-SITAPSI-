<?php
/**
 * SITAPSI - Navbar Guru (Terintegrasi UI Global)
 * Desain translasi dari React/Next.js ke PHP Native
 * PENYESUAIAN: Penambahan Fitur Ganti PIN Guru
 */

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user_name = $_SESSION['nama_lengkap'] ?? ($user['nama_lengkap'] ?? 'Guru');
$initial = strtoupper(substr($user_name, 0, 1));

// Tangkap Notifikasi (Success/Error) dari proses ganti PIN
$pin_success_msg = $_SESSION['pin_success_message'] ?? '';
$pin_error_msg = $_SESSION['pin_error_message'] ?? '';
unset($_SESSION['pin_success_message'], $_SESSION['pin_error_message']);
?>

<header class="bg-white border-b border-[#E2E8F0] sticky top-0 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                    <img src="../../assets/img/logo.png" alt="Logo Santa Maria" class="w-full h-full object-contain">
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

            <div class="flex items-center gap-2 sm:gap-4">
                <div class="hidden md:flex items-center gap-3 border-r border-[#E2E8F0] pr-4">
                    <div class="text-right">
                        <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($user_name) ?></p>
                        <p class="text-[10px] font-medium text-slate-500 uppercase">Guru SMPK 2</p>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-[#000080] flex items-center justify-center text-sm font-bold text-white shadow-sm">
                        <?= $initial ?>
                    </div>
                </div>
                
                <button onclick="openPinModal()" class="p-2 text-slate-500 hover:text-[#000080] hover:bg-blue-50 rounded-lg transition-colors flex items-center justify-center" title="Ganti PIN Akses">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </button>

                <a href="../../actions/logout.php" onclick="return confirm('Keluar dari Portal Guru?')" 
                   class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors flex items-center justify-center" title="Keluar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </a>
            </div>

        </div>
    </div>
</header>

<?php if ($pin_success_msg || $pin_error_msg): ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
    <?php if ($pin_success_msg): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <p class="font-medium text-sm"><?= htmlspecialchars($pin_success_msg) ?></p>
        </div>
    <?php endif; ?>
    <?php if ($pin_error_msg): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            <p class="font-medium text-sm"><?= htmlspecialchars($pin_error_msg) ?></p>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

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

<div id="modal-pin" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closePinModal()"></div>
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full relative z-10 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex justify-between items-center">
            <h3 class="font-extrabold text-slate-800 flex items-center text-sm">
                <svg class="w-5 h-5 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                Ubah PIN Akses
            </h3>
            <button onclick="closePinModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        
        <form action="../../actions/update_pin_guru.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="current_page" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wide">PIN Lama (Saat Ini)</label>
                <input type="password" name="old_pin" maxlength="6" required placeholder="•••" class="w-full px-4 py-2.5 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] font-mono tracking-widest text-center text-lg text-slate-800 transition-all bg-slate-50 focus:bg-white">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wide">PIN Baru (Angka)</label>
                <input type="password" name="new_pin" maxlength="6" required placeholder="•••" class="w-full px-4 py-2.5 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] font-mono tracking-widest text-center text-lg text-slate-800 transition-all bg-slate-50 focus:bg-white">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wide">Ketik Ulang PIN Baru</label>
                <input type="password" name="confirm_pin" maxlength="6" required placeholder="•••" class="w-full px-4 py-2.5 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] font-mono tracking-widest text-center text-lg text-slate-800 transition-all bg-slate-50 focus:bg-white">
            </div>
            
            <div class="pt-3 flex gap-3">
                <button type="button" onclick="closePinModal()" class="flex-1 py-2.5 bg-white border border-[#E2E8F0] text-slate-700 font-bold rounded-lg hover:bg-slate-50 transition-colors text-sm">Batal</button>
                <button type="submit" class="flex-1 py-2.5 bg-[#000080] text-white font-bold rounded-lg shadow-md hover:bg-blue-900 transition-colors text-sm">Simpan PIN</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openPinModal() { document.getElementById('modal-pin').classList.remove('hidden'); }
    function closePinModal() { document.getElementById('modal-pin').classList.add('hidden'); }
</script>