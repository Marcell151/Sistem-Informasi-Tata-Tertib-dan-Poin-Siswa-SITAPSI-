<?php
/**
 * SITAPSI - Dashboard Hub Orang Tua (SUPER-APP)
 * Fitur: Menampilkan daftar anak (Relasi Kakak-Adik) & Tombol Modul Sistem
 * PENYESUAIAN: Penambahan Fitur Ganti Password di Navbar
 */

session_start();
require_once '../../config/database.php';

// Validasi Akses: Tendang jika bukan Ortu
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Ortu') {
    header("Location: login.php");
    exit;
}

$id_ortu = $_SESSION['ortu_id'];
$nama_ortu = $_SESSION['nama_user'];

// Tangkap Notifikasi (Success/Error) dari proses ganti password
$success_msg = $_SESSION['success_message'] ?? '';
$error_msg = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Ambil tahun ajaran aktif untuk memastikan kita mengambil data kelas tahun ini
$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// Query Cerdas: Ambil SEMUA anak yang terikat dengan id_ortu ini
$anak_list = [];
if ($tahun_aktif) {
    $anak_list = fetchAll("
        SELECT 
            s.no_induk, 
            s.nama_siswa, 
            s.jenis_kelamin, 
            k.nama_kelas
        FROM tb_siswa s
        LEFT JOIN tb_anggota_kelas a ON s.no_induk = a.no_induk AND a.id_tahun = :id_tahun
        LEFT JOIN tb_kelas k ON a.id_kelas = k.id_kelas
        WHERE s.id_ortu = :id_ortu AND s.status_aktif = 'Aktif'
        ORDER BY s.tanggal_lahir ASC -- Urutkan dari kakak (paling tua) ke adik
    ", [
        'id_tahun' => $tahun_aktif['id_tahun'],
        'id_ortu' => $id_ortu
    ]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Wali Murid - SMPK Santa Maria 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-[#000080] rounded-xl flex items-center justify-center text-white shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-extrabold text-slate-800 leading-tight">Portal Terpadu</h1>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider hidden sm:block">SMPK Santa Maria 2</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <button onclick="openPasswordModal()" class="flex items-center px-3 py-2 text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                        <svg class="w-4 h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        <span class="hidden sm:inline">Ganti Sandi</span>
                    </button>
                    <a href="../../actions/logout_ortu.php" onclick="return confirm('Yakin ingin keluar dari portal?')" class="flex items-center px-3 py-2 text-sm font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                        <svg class="w-4 h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        <span class="hidden sm:inline">Keluar</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 max-w-5xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
        
        <?php if ($success_msg): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center animate-pulse">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <p class="font-bold text-sm"><?= htmlspecialchars($success_msg) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                <p class="font-bold text-sm"><?= htmlspecialchars($error_msg) ?></p>
            </div>
        <?php endif; ?>

        <div class="mb-8">
            <h2 class="text-3xl font-extrabold text-slate-800 tracking-tight">Selamat datang, <?= htmlspecialchars($nama_ortu) ?>! 👋</h2>
            <p class="text-slate-500 font-medium mt-2 text-sm lg:text-base">Silakan pilih profil putra/putri Anda untuk memantau perkembangan kedisiplinan dan akademik mereka.</p>
        </div>

        <?php if (empty($anak_list)): ?>
            <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center shadow-sm">
                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <h3 class="text-lg font-extrabold text-slate-800 mb-2">Belum Ada Data Anak</h3>
                <p class="text-slate-500 text-sm">Akun Anda belum terhubung dengan data siswa mana pun. Silakan hubungi Wali Kelas atau Admin Tata Usaha sekolah untuk menautkan data.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($anak_list as $anak): ?>
                <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                    
                    <div class="absolute top-0 right-0 w-32 h-32 bg-slate-50 rounded-full transform translate-x-10 -translate-y-10 group-hover:scale-150 transition-transform duration-500 ease-out -z-10"></div>
                    
                    <div class="flex items-center space-x-4 mb-6 relative z-10">
                        <div class="w-16 h-16 bg-[#000080]/10 border-2 border-[#000080]/20 rounded-full flex items-center justify-center text-[#000080] font-extrabold text-2xl shadow-inner flex-shrink-0 overflow-hidden">
                            <?= strtoupper(substr($anak['nama_siswa'], 0, 1)) ?>
                        </div>
                        <div>
                            <h3 class="text-xl font-extrabold text-slate-800 leading-tight"><?= htmlspecialchars($anak['nama_siswa']) ?></h3>
                            <div class="flex items-center mt-1 space-x-2">
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-[10px] font-bold uppercase tracking-wide border border-slate-200">NIS: <?= htmlspecialchars($anak['no_induk']) ?></span>
                                <span class="px-2 py-0.5 <?= $anak['nama_kelas'] ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-red-50 text-red-600 border-red-200' ?> rounded text-[10px] font-bold uppercase tracking-wide border">
                                    <?= $anak['nama_kelas'] ? 'Kelas ' . htmlspecialchars($anak['nama_kelas']) : 'Belum Ada Kelas' ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 relative z-10">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pilih Modul Laporan:</p>
                        
                        <a href="tatib/detail_anak.php?induk=<?= urlencode($anak['no_induk']) ?>" class="flex items-center p-3.5 bg-white border border-slate-200 hover:border-[#000080] hover:shadow-md rounded-2xl transition-all group/btn">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#000080] flex items-center justify-center mr-4 group-hover/btn:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-extrabold text-slate-800">Kedisiplinan & Karakter</h4>
                                <p class="text-[11px] font-medium text-slate-500">Poin, SP, dan riwayat pelanggaran</p>
                            </div>
                            <svg class="w-5 h-5 text-slate-300 group-hover/btn:text-[#000080] transform group-hover/btn:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                        </a>

                        <a href="#" onclick="alert('Fitur Absensi sedang dikembangkan oleh tim.')" class="flex items-center p-3.5 bg-white border border-slate-200 hover:border-emerald-500 hover:shadow-md rounded-2xl transition-all group/btn opacity-80">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mr-4 group-hover/btn:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-extrabold text-slate-800">Kehadiran (Absensi)</h4>
                                <p class="text-[11px] font-medium text-slate-500">Sakit, Izin, Alpa semester ini</p>
                            </div>
                            <svg class="w-5 h-5 text-slate-300 group-hover/btn:text-emerald-500 transform group-hover/btn:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                        </a>

                        <a href="#" onclick="alert('Fitur Ekstrakurikuler sedang dikembangkan oleh tim.')" class="flex items-center p-3.5 bg-white border border-slate-200 hover:border-amber-500 hover:shadow-md rounded-2xl transition-all group/btn opacity-80">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mr-4 group-hover/btn:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-extrabold text-slate-800">Ekstrakurikuler</h4>
                                <p class="text-[11px] font-medium text-slate-500">Kegiatan dan nilai ekstra anak</p>
                            </div>
                            <svg class="w-5 h-5 text-slate-300 group-hover/btn:text-amber-500 transform group-hover/btn:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                        </a>
                        
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <footer class="bg-white border-t border-slate-200 py-6 mt-auto">
        <div class="max-w-5xl mx-auto px-4 text-center">
            <p class="text-xs text-slate-500 font-medium">&copy; <?= date('Y') ?> SITAPSI & Sistem Terpadu SMPK Santa Maria 2.</p>
        </div>
    </footer>

    <div id="modal-password" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closePasswordModal()"></div>
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full relative z-10 overflow-hidden transform transition-all">
            <div class="p-6 border-b border-slate-200 bg-slate-50/50 flex justify-between items-center">
                <h3 class="font-extrabold text-slate-800 flex items-center text-lg">
                    <svg class="w-6 h-6 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    Ubah Kata Sandi
                </h3>
                <button onclick="closePasswordModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            
            <form action="../../actions/update_pass_ortu.php" method="POST" class="p-6 space-y-5">
                <div>
                    <label class="block text-xs font-extrabold text-slate-500 mb-2 uppercase tracking-wide">Kata Sandi Lama</label>
                    <input type="password" name="old_password" required placeholder="Masukkan sandi saat ini" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm text-slate-800 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-extrabold text-slate-500 mb-2 uppercase tracking-wide">Kata Sandi Baru</label>
                    <input type="password" name="new_password" required placeholder="Minimal 6 karakter" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm text-slate-800 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-extrabold text-slate-500 mb-2 uppercase tracking-wide">Konfirmasi Kata Sandi Baru</label>
                    <input type="password" name="confirm_password" required placeholder="Ketik ulang sandi baru" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm text-slate-800 transition-all">
                </div>
                
                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="closePasswordModal()" class="flex-1 py-3 bg-white border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition-colors">Batal</button>
                    <button type="submit" class="flex-1 py-3 bg-[#000080] text-white font-bold rounded-xl shadow-lg hover:bg-blue-900 transition-colors">Simpan Sandi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPasswordModal() { document.getElementById('modal-password').classList.remove('hidden'); }
        function closePasswordModal() { document.getElementById('modal-password').classList.add('hidden'); }
    </script>
</body>
</html>