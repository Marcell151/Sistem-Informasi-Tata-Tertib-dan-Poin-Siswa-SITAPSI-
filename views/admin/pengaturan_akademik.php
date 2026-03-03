<?php
/**
 * SITAPSI - Pengaturan Akademik (UI GLOBAL PORTAL)
 * FITUR BARU: Proses Kelulusan, Kenaikan Kelas (Lock/Unlock System)
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
$cek_transaksi = fetchOne("SELECT COUNT(*) as total FROM tb_pelanggaran_header WHERE id_tahun = :id_tahun", ['id_tahun' => $tahun_aktif['id_tahun']]);
$ada_transaksi = $cek_transaksi['total'] > 0;
$kenaikan_kelas_locked = $ada_transaksi;

$tahun_list = fetchAll("SELECT id_tahun, nama_tahun, semester_aktif, status FROM tb_tahun_ajaran ORDER BY id_tahun DESC");
// DISESUAIKAN NO INDUK PADA QUERY STATISTIK
$stats = fetchOne("
    SELECT 
        COUNT(DISTINCT a.no_induk) as total_siswa,
        COUNT(DISTINCT CASE WHEN a.status_sp_terakhir != 'Aman' THEN a.no_induk END) as siswa_sp,
        SUM(a.total_poin_umum) as total_poin
    FROM tb_anggota_kelas a
    WHERE a.id_tahun = :id_tahun
", ['id_tahun' => $tahun_aktif['id_tahun']]);
$total_kelas = fetchOne("SELECT COUNT(*) as total FROM tb_kelas")['total'] ?? 0;

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// --- UI CONFIG VARIABLES ---
$btn_primary = "w-full py-3 px-4 bg-[#000080] text-white text-sm font-bold rounded-xl shadow-md hover:bg-blue-900 transition-colors flex items-center justify-center";
$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akademik - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 pl-16 lg:pl-6 py-4 sticky top-0 z-30">
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Pengaturan Akademik Sistem</h1>
            <p class="text-sm font-medium text-slate-500">Kelola master tahun ajaran, semester, kelas & kelulusan</p>
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

            <div class="bg-[#000080] text-white rounded-xl shadow-md p-6 relative overflow-hidden">
                <svg class="absolute right-0 top-0 text-white/5 w-48 h-48 transform translate-x-8 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="text-center md:text-left">
                        <p class="text-blue-200 text-xs font-bold uppercase tracking-wider mb-1">Tahun Ajaran Aktif</p>
                        <h2 class="text-3xl font-extrabold mb-1"><?= $tahun_aktif['nama_tahun'] ?></h2>
                        <p class="font-medium text-blue-100">Semester <?= $tahun_aktif['semester_aktif'] ?></p>
                    </div>
                    <div class="grid grid-cols-4 gap-3 md:gap-4 text-center">
                        <div class="bg-white/10 border border-white/20 backdrop-blur rounded-xl p-3">
                            <p class="text-[10px] text-blue-200 uppercase font-bold tracking-wider mb-1">Siswa</p>
                            <p class="text-xl font-extrabold"><?= $stats['total_siswa'] ?></p>
                        </div>
                        <div class="bg-white/10 border border-white/20 backdrop-blur rounded-xl p-3">
                            <p class="text-[10px] text-blue-200 uppercase font-bold tracking-wider mb-1">Di-SP</p>
                            <p class="text-xl font-extrabold"><?= $stats['siswa_sp'] ?></p>
                        </div>
                        <div class="bg-white/10 border border-white/20 backdrop-blur rounded-xl p-3">
                            <p class="text-[10px] text-blue-200 uppercase font-bold tracking-wider mb-1">Tot. Poin</p>
                            <p class="text-xl font-extrabold"><?= number_format((float)$stats['total_poin']) ?></p>
                        </div>
                        <div class="bg-white/10 border border-white/20 backdrop-blur rounded-xl p-3">
                            <p class="text-[10px] text-blue-200 uppercase font-bold tracking-wider mb-1">Kelas</p>
                            <p class="text-xl font-extrabold"><?= $total_kelas ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <div class="<?= $card_class ?> p-6 flex flex-col h-full group hover:border-blue-400 transition-colors">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-50 text-blue-600 rounded-xl p-3 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-slate-800">Ganti Semester</h3>
                            <p class="text-[11px] font-bold text-slate-400 uppercase">Pindah Semester</p>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed flex-1 mb-4">Poin pelanggaran akan tetap terakumulasi tanpa reset. Hanya form input guru yang akan menyesuaikan.</p>
                    <form action="../../actions/ganti_semester.php" method="POST" onsubmit="return confirm('⚠️ Yakin ganti semester?\nPoin akan tetap terakumulasi.')">
                        <button type="submit" class="w-full py-2.5 bg-white border-2 border-blue-600 text-blue-700 hover:bg-blue-50 font-bold rounded-lg transition-colors text-sm">
                            Ganti ke Semester <?= $tahun_aktif['semester_aktif'] === 'Ganjil' ? 'Genap' : 'Ganjil' ?>
                        </button>
                    </form>
                </div>

                <div class="<?= $card_class ?> p-6 flex flex-col h-full group hover:border-amber-400 transition-colors">
                    <div class="flex items-center mb-4">
                        <div class="bg-amber-50 text-amber-600 rounded-xl p-3 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-slate-800">Tutup Tahun</h3>
                            <p class="text-[11px] font-bold text-slate-400 uppercase">Arsip & Reset Poin</p>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed flex-1 mb-4">Data saat ini diarsipkan, poin siswa di-reset ke 0, dan kelas 9 lulus otomatis.</p>
                    <button onclick="document.getElementById('modal-tutup-tahun').classList.remove('hidden')" class="w-full py-2.5 bg-white border-2 border-amber-500 text-amber-600 hover:bg-amber-50 font-bold rounded-lg transition-colors text-sm">
                        Tutup Tahun Ajaran
                    </button>
                </div>

                <div class="<?= $card_class ?> p-6 flex flex-col h-full group hover:border-emerald-400 transition-colors">
                    <div class="flex items-center mb-4">
                        <div class="bg-emerald-50 text-emerald-600 rounded-xl p-3 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-slate-800">Proses Kelulusan</h3>
                            <p class="text-[11px] font-bold text-slate-400 uppercase">Khusus Kelas 9</p>
                        </div>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 flex-1 mb-4 flex items-start">
                        <svg class="w-4 h-4 text-emerald-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        <p class="text-[10px] text-emerald-800 font-medium leading-tight">Tombol ini hanya <strong class="font-bold">fitur backup darurat</strong> jika sistem gagal otomatis meluluskan saat proses tutup tahun.</p>
                    </div>
                    <form action="../../actions/proses_kelulusan.php" method="POST" onsubmit="return confirmKelulusan()">
                        <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg transition-colors text-sm shadow-sm">
                            Proses Kelulusan
                        </button>
                    </form>
                </div>

                <div class="<?= $card_class ?> p-6 flex flex-col h-full <?= $kenaikan_kelas_locked ? 'bg-slate-50' : 'group hover:border-purple-400' ?> transition-colors">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="bg-purple-50 text-purple-600 rounded-xl p-3 mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-slate-800">Kenaikan Kelas</h3>
                                <p class="text-[11px] font-bold text-slate-400 uppercase">Naik Kelas 7→8, 8→9</p>
                            </div>
                        </div>
                        <?php if ($kenaikan_kelas_locked): ?>
                            <span class="px-2 py-0.5 bg-red-100 text-red-600 text-[10px] rounded-md font-extrabold shadow-sm border border-red-200">LOCKED</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($kenaikan_kelas_locked): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 flex-1 mb-4">
                        <p class="text-[10px] text-red-700 font-medium leading-tight mb-2">Hanya bisa diakses awal tahun ajaran. Saat ini sudah ada <strong><?= $cek_transaksi['total'] ?> transaksi</strong>.</p>
                    </div>
                    <button onclick="unlockKenaikanKelas()" class="w-full py-2.5 bg-white border-2 border-red-500 text-red-600 hover:bg-red-50 font-bold rounded-lg transition-colors text-sm">
                        <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Unlock Darurat
                    </button>
                    <?php else: ?>
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 flex-1 mb-4 flex items-start">
                        <svg class="w-4 h-4 text-purple-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        <p class="text-[10px] text-purple-800 font-medium leading-tight">Status <strong>Aman/Unlocked</strong>. Belum ada transaksi, aman untuk memindahkan siswa kelas.</p>
                    </div>
                    <a href="kenaikan_kelas.php" class="block w-full py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-center font-bold rounded-lg transition-colors text-sm shadow-sm">
                        Mulai Kenaikan Kelas
                    </a>
                    <?php endif; ?>
                </div>

                <div class="<?= $card_class ?> p-6 flex flex-col h-full group hover:border-[#000080] transition-colors">
                    <div class="flex items-center mb-4">
                        <div class="bg-indigo-50 text-indigo-600 rounded-xl p-3 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-slate-800">Data Kelas</h3>
                            <p class="text-[11px] font-bold text-slate-400 uppercase">Master Data Ruangan</p>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed flex-1 mb-4">Tambah, edit, hapus kelas yang terdaftar di sekolah.</p>
                    <a href="manajemen_kelas.php" class="block w-full py-2.5 bg-white border border-[#E2E8F0] text-slate-700 hover:bg-slate-50 text-center font-bold rounded-lg transition-colors text-sm shadow-sm">
                        Kelola Kelas
                    </a>
                </div>

                <div class="<?= $card_class ?> p-6 flex flex-col h-full group hover:border-slate-400 transition-colors">
                    <div class="flex items-center mb-4">
                        <div class="bg-slate-100 text-slate-600 rounded-xl p-3 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-slate-800">Arsip Historis</h3>
                            <p class="text-[11px] font-bold text-slate-400 uppercase">Lihat Data Lampau</p>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed flex-1 mb-4">Akses data pelanggaran pada tahun ajaran yang sudah ditutup (Read Only).</p>
                    <a href="arsip_tahun.php" class="block w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-center font-bold rounded-lg transition-colors text-sm">
                        Buka Folder Arsip
                    </a>
                </div>

            </div>

            <div class="<?= $card_class ?> overflow-hidden mt-8">
                <div class="p-4 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span class="font-extrabold text-slate-800 text-sm">Riwayat Tahun Ajaran</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-white text-xs text-slate-500 uppercase border-b border-[#E2E8F0]">
                            <tr>
                                <th class="p-4 font-bold">Tahun Ajaran</th>
                                <th class="p-4 font-bold">Semester Terakhir</th>
                                <th class="p-4 font-bold text-center">Status</th>
                                <th class="p-4 font-bold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php foreach($tahun_list as $t): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-4 font-extrabold text-slate-800"><?= $t['nama_tahun'] ?></td>
                                <td class="p-4 text-slate-600 font-medium"><?= $t['semester_aktif'] ?></td>
                                <td class="p-4 text-center">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold shadow-sm <?= $t['status'] === 'Aktif' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' ?>">
                                        <?= $t['status'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if ($t['status'] === 'Arsip'): ?>
                                    <a href="arsip_tahun.php?tahun=<?= $t['id_tahun'] ?>" class="inline-flex items-center text-[#000080] hover:text-blue-900 font-bold text-[11px] bg-[#000080]/10 px-3 py-1.5 rounded-lg transition-colors">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        Buka Arsip
                                    </a>
                                    <?php else: ?>
                                    <span class="text-slate-400 text-[11px] font-bold italic">- Sedang Berjalan -</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="modal-tutup-tahun" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="document.getElementById('modal-tutup-tahun').classList.add('hidden')"></div>
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full relative z-10 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                Tutup Tahun Ajaran
            </h3>
            <button type="button" onclick="document.getElementById('modal-tutup-tahun').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <form action="../../actions/tutup_tahun.php" method="POST" class="p-6 space-y-5">
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wide">Nama Tahun Ajaran Baru *</label>
                <input type="text" name="nama_tahun_baru" required placeholder="Contoh: 2025/2026" class="w-full px-4 py-2.5 border border-[#E2E8F0] rounded-lg focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-sm">
            </div>
            <div class="bg-amber-50 border border-amber-200 p-4 rounded-xl shadow-sm">
                <p class="text-xs text-amber-800 font-medium leading-relaxed">
                    <strong class="font-extrabold">⚠️ Peringatan Aksi Permanen:</strong><br>
                    • Tahun <strong><?= $tahun_aktif['nama_tahun'] ?></strong> akan diarsipkan.<br>
                    • Poin seluruh siswa akan direset (0).<br>
                    • Siswa kelas 9 akan otomatis diluluskan.<br>
                    • Anda wajib melakukan Kenaikan Kelas (7→8, 8→9) setelahnya.
                </p>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('modal-tutup-tahun').classList.add('hidden')" class="px-4 py-2.5 bg-white border border-[#E2E8F0] text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-50 flex-1">Batal</button>
                <button type="submit" class="px-4 py-2.5 bg-amber-500 text-white text-sm font-bold rounded-lg hover:bg-amber-600 flex-1 shadow-sm">Tutup & Buat Baru</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmKelulusan() {
    return confirm('⚠️ PERHATIAN\n\n' +
        'Fitur ini adalah BACKUP manual jika sistem gagal otomatis meluluskan saat tutup tahun.\n\n' +
        'Siswa kelas 9 saat ini akan diubah statusnya menjadi "Lulus".\n\n' +
        'Lanjutkan?');
}

function unlockKenaikanKelas() {
    const password = prompt('🔐 UNLOCK DARURAT - KENAIKAN KELAS\n\n' +
        'Fitur ini sengaja dikunci untuk menghindari error database saat tahun ajaran sedang berjalan.\n\n' +
        'Masukkan password darurat untuk unlock:\n' +
        '(Password: NAIKKELAS2025)');
    
    if (password === 'NAIKKELAS2025') {
        if (confirm('✅ Password Benar!\n\nKenaikan kelas akan dibuka.\n\n⚠️ PERINGATAN:\n' +
            'Memindahkan kelas di tengah tahun ajaran berpotensi membuat laporan poin menjadi tidak konsisten.\n\n' +
            'Yakin ingin melanjutkan?')) {
            window.location.href = 'kenaikan_kelas.php?unlock=1';
        }
    } else if (password !== null) {
        alert('❌ Password salah! Akses tetap ditolak.');
    }
}
</script>

</body>
</html>