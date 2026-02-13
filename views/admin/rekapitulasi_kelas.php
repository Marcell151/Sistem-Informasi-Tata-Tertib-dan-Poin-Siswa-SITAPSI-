<?php
/**
 * SITAPSI - Rekapitulasi Kelas (Leger)
 * Matrix lengkap per kelas dengan export Excel
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

// Ambil tahun ajaran aktif
$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// Ambil daftar kelas
$kelas_list = fetchAll("SELECT id_kelas, nama_kelas FROM tb_kelas ORDER BY nama_kelas");

// Filter kelas
$selected_kelas = $_GET['kelas'] ?? ($kelas_list[0]['id_kelas'] ?? null);

// Ambil data siswa dalam kelas
$siswa_kelas = [];
if ($selected_kelas) {
    $siswa_kelas = fetchAll("
        SELECT 
            s.nis,
            s.nama_siswa,
            a.poin_kelakuan,
            a.poin_kerajinan,
            a.poin_kerapian,
            a.total_poin_umum,
            a.status_sp_terakhir
        FROM tb_siswa s
        JOIN tb_anggota_kelas a ON s.nis = a.nis
        WHERE s.status_aktif = 'Aktif' 
        AND a.id_tahun = :id_tahun
        AND a.id_kelas = :id_kelas
        ORDER BY s.nama_siswa
    ", [
        'id_tahun' => $tahun_aktif['id_tahun'],
        'id_kelas' => $selected_kelas
    ]);
}

// Get nama kelas
$nama_kelas_terpilih = '';
foreach ($kelas_list as $k) {
    if ($k['id_kelas'] == $selected_kelas) {
        $nama_kelas_terpilih = $k['nama_kelas'];
        break;
    }
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
                extend: {
                    colors: {
                        'navy': '#000080',
                        'kelakuan': '#DC2626',
                        'kerajinan': '#2563EB',
                        'kerapian': '#D97706'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto">
        
        <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-6 py-4">
                <h1 class="text-2xl font-bold text-gray-800">Rekapitulasi Kelas</h1>
                <p class="text-sm text-gray-600 mt-1">Leger & matrix poin siswa</p>
            </div>
        </div>

        <div class="p-6">

            <!-- Filter & Export -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Filter Kelas</h3>
                        <p class="text-sm text-gray-600"><?= $tahun_aktif['nama_tahun'] ?> - Semester <?= $tahun_aktif['semester_aktif'] ?></p>
                    </div>
                    <a href="../../actions/export_rekap_admin.php?kelas=<?= $selected_kelas ?>" 
                       class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium flex items-center space-x-2 transition-colors shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Export Excel</span>
                    </a>
                </div>

                <div class="flex space-x-2 overflow-x-auto pb-2">
                    <?php foreach ($kelas_list as $kelas): ?>
                    <a href="?kelas=<?= $kelas['id_kelas'] ?>" 
                       class="flex-shrink-0 px-6 py-3 rounded-lg font-semibold transition-colors border <?= $kelas['id_kelas'] == $selected_kelas ? 'bg-navy text-white border-navy shadow-md' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' ?>">
                        <?= htmlspecialchars($kelas['nama_kelas']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tabel Rekapitulasi -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-800">Kelas <?= htmlspecialchars($nama_kelas_terpilih) ?></h3>
                    <p class="text-sm text-gray-600 mt-1">Total Siswa: <?= count($siswa_kelas) ?></p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b-2 border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase sticky left-0 bg-gray-50 z-10 border-r">No</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase sticky left-12 bg-gray-50 z-10 border-r min-w-[200px]">Nama Siswa</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-red-700 uppercase border-r">Kelakuan</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-blue-700 uppercase border-r">Kerajinan</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-yellow-700 uppercase border-r">Kerapian</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase border-r">Total Poin</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if(empty($siswa_kelas)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    Tidak ada data siswa di kelas ini
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php $no = 1; foreach($siswa_kelas as $siswa): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-center font-medium text-gray-700 sticky left-0 bg-white border-r"><?= $no++ ?></td>
                                <td class="px-4 py-3 font-medium text-gray-800 sticky left-12 bg-white border-r">
                                    <?= htmlspecialchars($siswa['nama_siswa']) ?>
                                    <div class="text-xs text-gray-500"><?= $siswa['nis'] ?></div>
                                </td>
                                <td class="px-4 py-3 text-center border-r">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-red-50 text-red-700">
                                        <?= $siswa['poin_kelakuan'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center border-r">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-blue-50 text-blue-700">
                                        <?= $siswa['poin_kerajinan'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center border-r">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-yellow-50 text-yellow-700">
                                        <?= $siswa['poin_kerapian'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center border-r">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-gray-100 text-gray-800">
                                        <?= $siswa['total_poin_umum'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($siswa['status_sp_terakhir'] !== 'Aman'): ?>
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">
                                        <?= $siswa['status_sp_terakhir'] ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">
                                        Aman
                                    </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

</div>

</body>
</html>