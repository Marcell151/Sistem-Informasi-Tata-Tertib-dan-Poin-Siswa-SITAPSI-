<?php
/**
 * SITAPSI - Pengaturan Akademik
 * Tutup Tahun, Kenaikan Kelas, Kelulusan
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

// Ambil tahun ajaran aktif
$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// Ambil semua tahun ajaran
$tahun_list = fetchAll("SELECT id_tahun, nama_tahun, semester_aktif, status FROM tb_tahun_ajaran ORDER BY id_tahun DESC");

// Statistik tahun aktif
$stats = fetchOne("
    SELECT 
        COUNT(DISTINCT a.nis) as total_siswa,
        COUNT(DISTINCT CASE WHEN a.status_sp_terakhir != 'Aman' THEN a.nis END) as siswa_sp,
        SUM(a.total_poin_umum) as total_poin
    FROM tb_anggota_kelas a
    WHERE a.id_tahun = :id_tahun
", ['id_tahun' => $tahun_aktif['id_tahun']]);

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
            <p class="text-sm text-gray-500">Tutup tahun, kenaikan kelas, & kelulusan</p>
        </div>

        <div class="p-6 space-y-6">

            <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <p class="text-green-700 font-medium"><?= htmlspecialchars($success) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <p class="text-red-700 font-medium"><?= htmlspecialchars($error) ?></p>
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
                        <div class="grid grid-cols-3 gap-4">
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
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aksi Utama -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Ganti Semester -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-100 rounded-full p-3 mr-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Ganti Semester</h3>
                            <p class="text-sm text-gray-600">Pindah dari Ganjil ke Genap (atau sebaliknya)</p>
                        </div>
                    </div>
                    <form action="../../actions/ganti_semester.php" method="POST" onsubmit="return confirm('⚠️ Yakin ganti semester?\n\nPoin akan tetap akumulasi, hanya tampilan yang direset.')">
                        <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                            🔄 Ganti ke Semester <?= $tahun_aktif['semester_aktif'] === 'Ganjil' ? 'Genap' : 'Ganjil' ?>
                        </button>
                    </form>
                </div>

                <!-- Tutup Tahun Ajaran -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-yellow-100 rounded-full p-3 mr-4">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Tutup Tahun Ajaran</h3>
                            <p class="text-sm text-gray-600">Arsipkan tahun ini & buat tahun baru</p>
                        </div>
                    </div>
                    <button onclick="document.getElementById('modal-tutup-tahun').classList.remove('hidden')" 
                            class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                        🔒 Tutup Tahun Ajaran
                    </button>
                </div>

                <!-- Proses Kelulusan -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-green-100 rounded-full p-3 mr-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Proses Kelulusan</h3>
                            <p class="text-sm text-gray-600">Set status siswa kelas 9 jadi Lulus</p>
                        </div>
                    </div>
                    <form action="../../actions/proses_kelulusan.php" method="POST" onsubmit="return confirm('⚠️ Yakin proses kelulusan?\n\nSiswa kelas 9 akan di-set status Lulus.')">
                        <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                            🎓 Proses Kelulusan Kelas 9
                        </button>
                    </form>
                </div>

                <!-- Kenaikan Kelas -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-purple-100 rounded-full p-3 mr-4">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Kenaikan Kelas</h3>
                            <p class="text-sm text-gray-600">Naikkan kelas 7→8, 8→9</p>
                        </div>
                    </div>
                    <button onclick="alert('Fitur kenaikan kelas akan dikembangkan lebih lanjut')" 
                            class="w-full bg-purple-500 hover:bg-purple-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                        📈 Proses Kenaikan Kelas
                    </button>
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
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
            </div>
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                <p class="text-sm text-yellow-700">
                    <strong>⚠️ Peringatan:</strong><br>
                    • Tahun <?= $tahun_aktif['nama_tahun'] ?> akan diarsipkan<br>
                    • Tahun baru akan dibuat & diaktifkan<br>
                    • Data poin akan direset untuk tahun baru
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

</body>
</html>