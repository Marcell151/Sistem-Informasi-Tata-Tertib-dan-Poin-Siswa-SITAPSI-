<?php
/**
 * SITAPSI - Rekapitulasi Kelas (COMPLETE - SP PER KATEGORI)
 * Menampilkan matriks poin dan SP per kategori
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_kelas = $_GET['kelas'] ?? null;

$tahun_aktif = fetchOne("
    SELECT id_tahun, nama_tahun 
    FROM tb_tahun_ajaran 
    WHERE status = 'Aktif' 
    LIMIT 1
");

$kelas_list = fetchAll("SELECT * FROM tb_kelas ORDER BY tingkat, nama_kelas");

if (!$id_kelas && !empty($kelas_list)) {
    $id_kelas = $kelas_list[0]['id_kelas'];
}

if ($id_kelas) {
    $kelas_info = fetchOne("SELECT * FROM tb_kelas WHERE id_kelas = :id", ['id' => $id_kelas]);
    
    // Query siswa dengan SP per kategori
    $siswa_kelas = fetchAll("
        SELECT 
            s.nis,
            s.nama_siswa,
            a.poin_kelakuan,
            a.poin_kerajinan,
            a.poin_kerapian,
            a.total_poin_umum,
            a.status_sp_terakhir,
            a.status_sp_kelakuan,
            a.status_sp_kerajinan,
            a.status_sp_kerapian
        FROM tb_siswa s
        JOIN tb_anggota_kelas a ON s.nis = a.nis
        WHERE s.status_aktif = 'Aktif' 
        AND a.id_tahun = :id_tahun
        AND a.id_kelas = :id_kelas
        ORDER BY s.nama_siswa
    ", [
        'id_tahun' => $tahun_aktif['id_tahun'],
        'id_kelas' => $id_kelas
    ]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Kelas - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: { colors: { 'navy': '#000080' } }
            }
        }
    </script>
</head>
<body class="bg-gray-50">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto bg-gray-100">
        
        <div class="bg-white shadow-sm border-b px-6 py-4 sticky top-0 z-30 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Rekapitulasi Kelas</h1>
                <p class="text-sm text-gray-500">Matriks poin dan SP per kategori</p>
            </div>
            <?php if ($id_kelas): ?>
            <a href="export_rekap.php?kelas=<?= $id_kelas ?>" target="_blank"
               class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium flex items-center space-x-2 transition-colors shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Export Excel</span>
            </a>
            <?php endif; ?>
        </div>

        <div class="p-6 space-y-6">

            <!-- Pilih Kelas -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <form method="GET" class="flex items-end gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Kelas</label>
                        <select name="kelas" onchange="this.form.submit()" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                            <?php foreach ($kelas_list as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>" <?= $id_kelas == $k['id_kelas'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kelas']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>

            <?php if ($id_kelas && isset($kelas_info)): ?>

            <!-- Info Kelas -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">📊 Rekapitulasi Kelas <?= $kelas_info['nama_kelas'] ?></h2>
                        <p class="text-indigo-200">Tahun Ajaran: <?= $tahun_aktif['nama_tahun'] ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-indigo-200 text-sm">Total Siswa</p>
                        <p class="text-5xl font-bold"><?= count($siswa_kelas) ?></p>
                    </div>
                </div>
            </div>

            <!-- Tabel Rekapitulasi dengan SP Per Kategori -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b font-bold text-gray-700">
                    📋 Matriks Poin dan Status SP Per Kategori
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase sticky top-0">
                            <tr>
                                <th class="p-3 text-center sticky left-0 bg-gray-50 z-10">No</th>
                                <th class="p-3 text-left sticky left-12 bg-gray-50 z-10" style="min-width: 200px;">Nama Siswa</th>
                                <th class="p-3 text-center bg-red-50">🚨 Kelakuan<br><span class="text-xs">(Poin)</span></th>
                                <th class="p-3 text-center bg-blue-50">📘 Kerajinan<br><span class="text-xs">(Poin)</span></th>
                                <th class="p-3 text-center bg-yellow-50">👔 Kerapian<br><span class="text-xs">(Poin)</span></th>
                                <th class="p-3 text-center bg-gray-100">Total<br><span class="text-xs">Poin</span></th>
                                <th class="p-3 text-center bg-red-50">SP<br><span class="text-xs">Kelakuan</span></th>
                                <th class="p-3 text-center bg-blue-50">SP<br><span class="text-xs">Kerajinan</span></th>
                                <th class="p-3 text-center bg-yellow-50">SP<br><span class="text-xs">Kerapian</span></th>
                                <th class="p-3 text-center bg-gray-800 text-white">SP<br><span class="text-xs">Tertinggi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($siswa_kelas)): ?>
                            <tr>
                                <td colspan="10" class="p-12 text-center text-gray-500">
                                    Tidak ada siswa di kelas ini
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($siswa_kelas as $idx => $siswa): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 text-center sticky left-0 bg-white font-bold text-gray-700"><?= $idx + 1 ?></td>
                                <td class="p-3 sticky left-12 bg-white font-bold text-navy" style="min-width: 200px;">
                                    <?= htmlspecialchars($siswa['nama_siswa']) ?>
                                </td>
                                <td class="p-3 text-center bg-red-50">
                                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-bold">
                                        <?= $siswa['poin_kelakuan'] ?>
                                    </span>
                                </td>
                                <td class="p-3 text-center bg-blue-50">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-bold">
                                        <?= $siswa['poin_kerajinan'] ?>
                                    </span>
                                </td>
                                <td class="p-3 text-center bg-yellow-50">
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-bold">
                                        <?= $siswa['poin_kerapian'] ?>
                                    </span>
                                </td>
                                <td class="p-3 text-center bg-gray-100">
                                    <span class="px-3 py-1 bg-gray-800 text-white rounded-full text-xs font-bold">
                                        <?= $siswa['total_poin_umum'] ?>
                                    </span>
                                </td>
                                <!-- SP Per Kategori -->
                                <td class="p-3 text-center bg-red-50">
                                    <span class="px-2 py-1 rounded text-xs font-bold <?= $siswa['status_sp_kelakuan'] === 'Aman' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $siswa['status_sp_kelakuan'] ?>
                                    </span>
                                </td>
                                <td class="p-3 text-center bg-blue-50">
                                    <span class="px-2 py-1 rounded text-xs font-bold <?= $siswa['status_sp_kerajinan'] === 'Aman' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                                        <?= $siswa['status_sp_kerajinan'] ?>
                                    </span>
                                </td>
                                <td class="p-3 text-center bg-yellow-50">
                                    <span class="px-2 py-1 rounded text-xs font-bold <?= $siswa['status_sp_kerapian'] === 'Aman' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                        <?= $siswa['status_sp_kerapian'] ?>
                                    </span>
                                </td>
                                <td class="p-3 text-center bg-gray-800">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $siswa['status_sp_terakhir'] === 'Aman' ? 'bg-green-500 text-white' : 'bg-red-500 text-white' ?>">
                                        <?= $siswa['status_sp_terakhir'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Keterangan -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h4 class="font-bold text-blue-800 mb-1">ℹ️ Keterangan</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li>• <strong>Poin</strong>: Akumulasi poin pelanggaran per kategori (Kelakuan, Kerajinan, Kerapian)</li>
                            <li>• <strong>SP Per Kategori</strong>: Status SP yang dihitung per kategori secara terpisah</li>
                            <li>• <strong>SP Tertinggi</strong>: Status SP maksimal dari ketiga kategori (untuk keperluan laporan umum)</li>
                            <li>• Siswa dapat memiliki SP berbeda di setiap kategori (misal: SP1 Kelakuan, SP2 Kerajinan, Aman Kerapian)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>