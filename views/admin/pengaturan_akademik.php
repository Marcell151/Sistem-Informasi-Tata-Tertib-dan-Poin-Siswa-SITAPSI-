<?php
/**
 * SITAPSI - Pengaturan Akademik (ENHANCED SAFETY)
 * 
 * FITUR BARU:
 * 1. Proses Kelulusan: Tambah warning hanya untuk backup (otomatis di tutup tahun)
 * 2. Kenaikan Kelas: Lock otomatis jika sudah ada transaksi (unlock dengan password darurat)
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

// Ambil tahun ajaran aktif
$tahun_aktif = fetchOne("
    SELECT id_tahun, nama_tahun, semester_aktif 
    FROM tb_tahun_ajaran 
    WHERE status = 'Aktif' 
    LIMIT 1
");

// Cek apakah ada transaksi pelanggaran di tahun ini
$cek_transaksi = fetchOne("
    SELECT COUNT(*) as total 
    FROM tb_pelanggaran_header 
    WHERE id_tahun = :id_tahun
", ['id_tahun' => $tahun_aktif['id_tahun']]);

$ada_transaksi = $cek_transaksi['total'] > 0;

// Status kenaikan kelas: locked jika ada transaksi
$kenaikan_kelas_locked = $ada_transaksi;

// Ambil semua tahun ajaran
$tahun_list = fetchAll("
    SELECT id_tahun, nama_tahun, semester_aktif, status 
    FROM tb_tahun_ajaran 
    ORDER BY id_tahun DESC
");

// Statistik tahun aktif
$stats = fetchOne("
    SELECT 
        COUNT(DISTINCT a.nis) as total_siswa,
        COUNT(DISTINCT CASE WHEN a.status_sp_terakhir != 'Aman' THEN a.nis END) as siswa_sp,
        SUM(a.total_poin_umum) as total_poin
    FROM tb_anggota_kelas a
    WHERE a.id_tahun = :id_tahun
", ['id_tahun' => $tahun_aktif['id_tahun']]);

// Hitung total kelas
$total_kelas = fetchOne("SELECT COUNT(*) as total FROM tb_kelas")['total'] ?? 0;

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akademik - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 'navy': '#000080' }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto bg-gray-100">
        
        <div class="bg-white shadow-sm border-b px-6 py-4 sticky top-0 z-30">
            <h1 class="text-2xl font-bold text-gray-800">Pengaturan Akademik</h1>
            <p class="text-sm text-gray-500">Kelola tahun ajaran, semester, kelas, kelulusan & kenaikan kelas</p>
        </div>

        <div class="p-6 space-y-6">

            <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <p class="text-green-700 font-medium"><?= $success ?></p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <p class="text-red-700 font-medium"><?= $error ?></p>
            </div>
            <?php endif; ?>

            <!-- Info Tahun Aktif -->
            <div class="bg-gradient-to-r from-navy to-blue-800 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">📅 Tahun Ajaran Aktif</h2>
                        <p class="text-3xl font-bold mb-1"><?= $tahun_aktif['nama_tahun'] ?></p>
                        <p class="text-blue-200">Semester: <?= $tahun_aktif['semester_aktif'] ?></p>
                    </div>
                    <div class="text-right">
                        <div class="grid grid-cols-4 gap-4">
                            <div class="bg-white/10 backdrop-blur rounded-lg p-3">
                                <p class="text-xs text-blue-200">Total Siswa</p>
                                <p class="text-2xl font-bold"><?= $stats['total_siswa'] ?></p>
                            </div>
                            <div class="bg-white/10 backdrop-blur rounded-lg p-3">
                                <p class="text-xs text-blue-200">Siswa SP</p>
                                <p class="text-2xl font-bold"><?= $stats['siswa_sp'] ?></p>
                            </div>
                            <div class="bg-white/10 backdrop-blur rounded-lg p-3">
                                <p class="text-xs text-blue-200">Total Poin</p>
                                <p class="text-2xl font-bold"><?= number_format($stats['total_poin']) ?></p>
                            </div>
                            <div class="bg-white/10 backdrop-blur rounded-lg p-3">
                                <p class="text-xs text-blue-200">Total Kelas</p>
                                <p class="text-2xl font-bold"><?= $total_kelas ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aksi Utama -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Ganti Semester -->
                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-100 rounded-full p-3 mr-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Ganti Semester</h3>
                            <p class="text-sm text-gray-600">Pindah semester</p>
                        </div>
                    </div>
                    <form action="../../actions/ganti_semester.php" method="POST" onsubmit="return confirm('⚠️ Yakin ganti semester?\n\nPoin akan tetap akumulasi.')">
                        <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                            🔄 Ganti ke Semester <?= $tahun_aktif['semester_aktif'] === 'Ganjil' ? 'Genap' : 'Ganjil' ?>
                        </button>
                    </form>
                </div>

                <!-- Tutup Tahun Ajaran -->
                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="bg-yellow-100 rounded-full p-3 mr-4">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Tutup Tahun Ajaran</h3>
                            <p class="text-sm text-gray-600">Arsip & buat tahun baru</p>
                        </div>
                    </div>
                    <button onclick="document.getElementById('modal-tutup-tahun').classList.remove('hidden')" 
                            class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                        🔒 Tutup Tahun Ajaran
                    </button>
                </div>

                <!-- Proses Kelulusan - DENGAN WARNING -->
                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="bg-green-100 rounded-full p-3 mr-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Proses Kelulusan</h3>
                            <p class="text-sm text-gray-600">Set siswa kelas 9 lulus</p>
                        </div>
                    </div>
                    
                    <!-- WARNING TAMBAHAN -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-3">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="text-xs font-bold text-yellow-800 mb-1">⚠️ PERHATIAN</p>
                                <p class="text-xs text-yellow-700">
                                    Tombol ini <strong>hanya untuk backup</strong> jika sistem <u>tidak otomatis meluluskan</u> saat tutup tahun ajaran. 
                                    Normalnya kelulusan kelas 9 sudah otomatis.
                                </p>
                            </div>
                        </div>
                    </div>

                    <form action="../../actions/proses_kelulusan.php" method="POST" onsubmit="return confirmKelulusan()">
                        <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                            🎓 Proses Kelulusan Kelas 9
                        </button>
                    </form>
                </div>

                <!-- Kenaikan Kelas - DENGAN LOCK SYSTEM -->
                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow <?= $kenaikan_kelas_locked ? 'opacity-75' : '' ?>">
                    <div class="flex items-center mb-4">
                        <div class="bg-purple-100 rounded-full p-3 mr-4">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 flex items-center">
                                Kenaikan Kelas
                                <?php if ($kenaikan_kelas_locked): ?>
                                <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-600 text-xs rounded-full font-bold">
                                    🔒 LOCKED
                                </span>
                                <?php endif; ?>
                            </h3>
                            <p class="text-sm text-gray-600">Naik kelas 7→8, 8→9</p>
                        </div>
                    </div>

                    <?php if ($kenaikan_kelas_locked): ?>
                    <!-- STATUS LOCKED -->
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-3">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-red-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="text-sm font-bold text-red-800 mb-1">Fitur Terkunci</p>
                                <p class="text-xs text-red-700 mb-2">
                                    Kenaikan kelas hanya bisa diakses <strong>di awal tahun ajaran baru</strong> (sebelum ada transaksi pelanggaran).
                                    <br>Saat ini sudah ada <strong><?= $cek_transaksi['total'] ?> transaksi</strong> di tahun ajaran ini.
                                </p>
                                <p class="text-xs text-red-600 font-semibold">
                                    💡 Untuk membuka: Klik tombol "Unlock Darurat" di bawah
                                </p>
                            </div>
                        </div>
                    </div>

                    <button onclick="unlockKenaikanKelas()" 
                            class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors mb-2">
                        🔓 Unlock Darurat (Password)
                    </button>

                    <?php else: ?>
                    <!-- STATUS UNLOCKED -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-3">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="text-xs font-bold text-green-800 mb-1">✅ Tersedia</p>
                                <p class="text-xs text-green-700">
                                    Tahun ajaran baru. Belum ada transaksi pelanggaran. Kenaikan kelas dapat diakses.
                                </p>
                            </div>
                        </div>
                    </div>

                    <a href="kenaikan_kelas.php" class="block w-full bg-purple-500 hover:bg-purple-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors text-center">
                        📈 Proses Kenaikan Kelas
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Manajemen Kelas -->
                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="bg-indigo-100 rounded-full p-3 mr-4">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Manajemen Kelas</h3>
                            <p class="text-sm text-gray-600">Tambah, edit, hapus kelas</p>
                        </div>
                    </div>
                    <a href="manajemen_kelas.php" class="block w-full bg-indigo-500 hover:bg-indigo-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors text-center">
                        🏫 Kelola Kelas
                    </a>
                </div>

                <!-- Lihat Arsip -->
                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="bg-gray-100 rounded-full p-3 mr-4">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Lihat Arsip</h3>
                            <p class="text-sm text-gray-600">Data tahun lampau</p>
                        </div>
                    </div>
                    <a href="arsip_tahun.php" class="block w-full bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors text-center">
                        📦 Buka Arsip
                    </a>
                </div>

            </div>

            <!-- Riwayat Tahun Ajaran -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b font-bold text-gray-700">
                    📚 Riwayat Tahun Ajaran
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="p-4">Tahun Ajaran</th>
                                <th class="p-4">Semester Terakhir</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach($tahun_list as $t): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4 font-bold text-gray-800"><?= $t['nama_tahun'] ?></td>
                                <td class="p-4 text-gray-600"><?= $t['semester_aktif'] ?></td>
                                <td class="p-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?= $t['status'] === 'Aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' ?>">
                                        <?= $t['status'] ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <?php if ($t['status'] === 'Arsip'): ?>
                                    <a href="arsip_tahun.php?tahun=<?= $t['id_tahun'] ?>" 
                                       class="text-blue-600 hover:text-blue-800 font-medium text-sm flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Lihat Data
                                    </a>
                                    <?php else: ?>
                                    <span class="text-gray-400 text-sm">Tahun Aktif</span>
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

<!-- Modal Tutup Tahun -->
<div id="modal-tutup-tahun" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Tutup Tahun Ajaran</h3>
            <button onclick="document.getElementById('modal-tutup-tahun').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="../../actions/tutup_tahun.php" method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Tahun Ajaran Baru *</label>
                <input type="text" name="nama_tahun_baru" required placeholder="Contoh: 2025/2026"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
            </div>
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                <p class="text-sm text-yellow-700">
                    <strong>⚠️ Peringatan:</strong><br>
                    • Tahun <?= $tahun_aktif['nama_tahun'] ?> akan diarsipkan<br>
                    • Tahun baru akan dibuat & diaktifkan<br>
                    • Data poin akan direset untuk tahun baru<br>
                    • Siswa kelas 9 akan otomatis lulus
                </p>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="document.getElementById('modal-tutup-tahun').classList.add('hidden')" 
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Batal
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 font-medium">
                    Tutup & Buat Tahun Baru
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Konfirmasi kelulusan dengan warning tambahan
function confirmKelulusan() {
    return confirm('⚠️ PERHATIAN\n\n' +
        'Fitur ini adalah BACKUP jika sistem tidak otomatis meluluskan saat tutup tahun.\n\n' +
        'Siswa kelas 9 akan diubah statusnya menjadi "Lulus".\n\n' +
        'Lanjutkan?');
}

// Unlock kenaikan kelas dengan password darurat
function unlockKenaikanKelas() {
    const password = prompt('🔐 UNLOCK DARURAT - KENAIKAN KELAS\n\n' +
        'Fitur ini terkunci untuk menghindari error saat tahun ajaran sedang berjalan.\n\n' +
        'Masukkan password darurat untuk unlock:\n' +
        '(Password: NAIKKELAS2025)');
    
    if (password === 'NAIKKELAS2025') {
        if (confirm('✅ Password benar!\n\nKenaikan kelas akan dibuka.\n\n⚠️ PERINGATAN:\n' +
            'Pastikan Anda paham konsekuensinya. Kenaikan kelas di tengah tahun ajaran dapat menyebabkan:\n' +
            '- Poin siswa tetap terbawa\n' +
            '- Data pelanggaran tetap tercatat di kelas lama\n' +
            '- Laporan mungkin tidak konsisten\n\n' +
            'Lanjutkan?')) {
            window.location.href = 'kenaikan_kelas.php?unlock=1';
        }
    } else if (password !== null) {
        alert('❌ Password salah!\n\nKenaikan kelas tetap terkunci.');
    }
}
</script>

</body>
</html>