<?php
/**
 * SITAPSI - Audit Harian
 * Halaman untuk melihat, edit, dan hapus log input pelanggaran
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$user = getCurrentUser();

// Ambil tahun ajaran aktif
$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// Filter
$filter_tipe = $_GET['tipe'] ?? 'all';
$filter_tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// Query log transaksi
$sql = "
    SELECT 
        h.id_transaksi,
        h.tanggal,
        h.waktu,
        h.tipe_form,
        h.bukti_foto,
        s.nama_siswa,
        k.nama_kelas,
        g.nama_guru,
        GROUP_CONCAT(jp.nama_pelanggaran SEPARATOR ', ') as pelanggaran_list,
        SUM(d.poin_saat_itu) as total_poin
    FROM tb_pelanggaran_header h
    JOIN tb_anggota_kelas a ON h.id_anggota = a.id_anggota
    JOIN tb_siswa s ON a.nis = s.nis
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    JOIN tb_guru g ON h.id_guru = g.id_guru
    LEFT JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    LEFT JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    WHERE h.id_tahun = :id_tahun
    AND h.tanggal = :tanggal
";

$params = [
    'id_tahun' => $tahun_aktif['id_tahun'],
    'tanggal' => $filter_tanggal
];

if ($filter_tipe !== 'all') {
    $sql .= " AND h.tipe_form = :tipe";
    $params['tipe'] = ucfirst($filter_tipe);
}

$sql .= " GROUP BY h.id_transaksi ORDER BY h.waktu DESC";

$log_transaksi = fetchAll($sql, $params);

// Success/Error message
$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Harian - SITAPSI</title>
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
                <h1 class="text-2xl font-bold text-gray-800">Audit Harian</h1>
                <p class="text-sm text-gray-600 mt-1">Log & jejak input pelanggaran</p>
            </div>
        </div>

        <div class="p-6">

            <?php if ($success): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <p class="text-green-700 font-medium"><?= htmlspecialchars($success) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <p class="text-red-700 font-medium"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <!-- Filter Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                        <input type="date" name="tanggal" value="<?= $filter_tanggal ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Form</label>
                        <select name="tipe" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                            <option value="all" <?= $filter_tipe === 'all' ? 'selected' : '' ?>>Semua Tipe</option>
                            <option value="piket" <?= $filter_tipe === 'piket' ? 'selected' : '' ?>>Mode Piket</option>
                            <option value="kelas" <?= $filter_tipe === 'kelas' ? 'selected' : '' ?>>Mode Kelas</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-navy hover:bg-blue-900 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                            🔍 Filter Data
                        </button>
                    </div>

                </form>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-800">
                        Log Transaksi - <?= date('d F Y', strtotime($filter_tanggal)) ?>
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">Total: <?= count($log_transaksi) ?> transaksi</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Siswa</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pelanggaran</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Poin</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pelapor</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if(empty($log_transaksi)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="font-medium">Tidak ada data untuk tanggal ini</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($log_transaksi as $log): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= substr($log['waktu'], 0, 5) ?>
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($log['nama_siswa']) ?></p>
                                    <p class="text-xs text-gray-500"><?= $log['nama_kelas'] ?></p>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700 max-w-xs">
                                    <div class="line-clamp-2"><?= htmlspecialchars($log['pelanggaran_list'] ?: '-') ?></div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800">
                                        +<?= $log['total_poin'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?= $log['tipe_form'] === 'Piket' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' ?>">
                                        <?= $log['tipe_form'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700"><?= htmlspecialchars($log['nama_guru']) ?></td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                    <div class="flex space-x-2">
                                        <button onclick="viewDetail(<?= $log['id_transaksi'] ?>)" 
                                                class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors" 
                                                title="Lihat Detail">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                        <button onclick="deleteTransaction(<?= $log['id_transaksi'] ?>)" 
                                                class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors" 
                                                title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
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

<!-- Modal Detail (Hidden by default) -->
<div id="modal-detail" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white">
            <h3 class="text-lg font-bold text-gray-800">Detail Transaksi</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="modal-content" class="p-6">
            <!-- Content will be loaded via AJAX -->
        </div>
    </div>
</div>

<script>
function viewDetail(id) {
    document.getElementById('modal-detail').classList.remove('hidden');
    document.getElementById('modal-content').innerHTML = '<p class="text-center text-gray-500">Loading...</p>';
    
    // You can implement AJAX here to fetch detail
    // For now, just show placeholder
    setTimeout(() => {
        document.getElementById('modal-content').innerHTML = `
            <p class="text-gray-600">Detail transaksi ID: ${id}</p>
            <p class="text-sm text-gray-500 mt-2">Fitur detail akan dikembangkan lebih lanjut</p>
        `;
    }, 500);
}

function closeModal() {
    document.getElementById('modal-detail').classList.add('hidden');
}

function deleteTransaction(id) {
    if (confirm('⚠️ PERINGATAN!\n\nMenghapus transaksi akan:\n• Mengurangi poin siswa\n• Menghapus riwayat pelanggaran\n• Tidak dapat dikembalikan\n\nYakin ingin menghapus?')) {
        window.location.href = `../../actions/hapus_transaksi.php?id=${id}&redirect=audit`;
    }
}
</script>

</body>
</html>