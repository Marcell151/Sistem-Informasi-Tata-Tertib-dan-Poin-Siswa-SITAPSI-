<?php
/**
 * SITAPSI - Audit Harian (LOGIKA ASLI + UI GLOBAL + FIX LAYOUT)
 * PENYESUAIAN: Pemanggilan kolom lampiran_link
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
$filter_dari = $_GET['dari'] ?? date('Y-m-01'); // Awal bulan ini
$filter_sampai = $_GET['sampai'] ?? date('Y-m-d'); // Hari ini

// Query log transaksi dengan RANGE DATE (LOGIKA ASLI) + lampiran_link
$sql = "
    SELECT 
        h.id_transaksi,
        h.tanggal,
        h.waktu,
        h.tipe_form,
        h.bukti_foto,
        h.lampiran_link,
        s.no_induk,
        s.nama_siswa,
        k.nama_kelas,
        g.nama_guru,
        a.id_anggota,
        GROUP_CONCAT(DISTINCT jp.nama_pelanggaran SEPARATOR ', ') as pelanggaran_list,
        SUM(d.poin_saat_itu) as total_poin
    FROM tb_pelanggaran_header h
    JOIN tb_anggota_kelas a ON h.id_anggota = a.id_anggota
    JOIN tb_siswa s ON a.no_induk = s.no_induk
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    JOIN tb_guru g ON h.id_guru = g.id_guru
    LEFT JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    LEFT JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    WHERE h.id_tahun = :id_tahun
    AND h.tanggal BETWEEN :dari AND :sampai
";

$params = [
    'id_tahun' => $tahun_aktif['id_tahun'],
    'dari' => $filter_dari,
    'sampai' => $filter_sampai
];

if ($filter_tipe !== 'all') {
    $sql .= " AND h.tipe_form = :tipe";
    $params['tipe'] = ucfirst($filter_tipe);
}

$sql .= " GROUP BY h.id_transaksi ORDER BY h.tanggal DESC, h.waktu DESC";

$log_transaksi = fetchAll($sql, $params);

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// --- UI CONFIG VARIABLES ---
$btn_primary = "px-4 py-2.5 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-md shadow-blue-900/10 hover:bg-blue-900 transition-all flex items-center justify-center";
$input_class = "w-full px-4 py-2 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm text-slate-700 bg-white transition-all";
$label_class = "block text-sm font-medium text-slate-700 mb-2";
$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Harian - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 pl-16 lg:pl-6 py-4 sticky top-0 z-30 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Audit Harian Pelanggaran</h1>
                <p class="text-sm font-medium text-slate-500">Log & jejak input pelanggaran (Default: 1 bulan terakhir)</p>
            </div>
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

            <div class="<?= $card_class ?> p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    
                    <div>
                        <label class="<?= $label_class ?>">Dari Tanggal</label>
                        <input type="date" name="dari" value="<?= htmlspecialchars($filter_dari) ?>" class="<?= $input_class ?>">
                    </div>

                    <div>
                        <label class="<?= $label_class ?>">Sampai Tanggal</label>
                        <input type="date" name="sampai" value="<?= htmlspecialchars($filter_sampai) ?>" class="<?= $input_class ?>">
                    </div>

                    <div>
                        <label class="<?= $label_class ?>">Tipe Form</label>
                        <select name="tipe" class="<?= $input_class ?>">
                            <option value="all" <?= $filter_tipe === 'all' ? 'selected' : '' ?>>Semua Tipe</option>
                            <option value="piket" <?= $filter_tipe === 'piket' ? 'selected' : '' ?>>Mode Piket</option>
                            <option value="kelas" <?= $filter_tipe === 'kelas' ? 'selected' : '' ?>>Mode Kelas</option>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="<?= $btn_primary ?> w-full h-[42px]">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            Filter Data
                        </button>
                    </div>

                </form>
            </div>

            <div class="<?= $card_class ?> overflow-hidden">
                <div class="p-4 border-b border-[#E2E8F0] bg-slate-50/50 flex justify-between items-center">
                    <span class="font-bold text-slate-800 text-sm">
                        Log Transaksi - <?= date('d M Y', strtotime($filter_dari)) ?> s.d. <?= date('d M Y', strtotime($filter_sampai)) ?>
                    </span>
                    <span class="px-2.5 py-1 bg-slate-200 text-slate-700 rounded-md text-[10px] font-bold">Total: <?= count($log_transaksi) ?> transaksi</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left whitespace-nowrap">
                        <thead class="bg-white text-xs text-slate-500 uppercase border-b border-[#E2E8F0]">
                            <tr>
                                <th class="p-4 font-bold">Waktu</th>
                                <th class="p-4 font-bold">Siswa</th>
                                <th class="p-4 font-bold">Pelanggaran</th>
                                <th class="p-4 font-bold text-center">Poin</th>
                                <th class="p-4 font-bold text-center">Tipe</th>
                                <th class="p-4 font-bold">Pelapor</th>
                                <th class="p-4 font-bold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php if(empty($log_transaksi)): ?>
                            <tr>
                                <td colspan="7" class="p-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="font-medium text-sm">Tidak ada data untuk periode ini</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($log_transaksi as $log): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-4 text-slate-800">
                                    <span class="font-bold"><?= date('d/m/Y', strtotime($log['tanggal'])) ?></span><br>
                                    <span class="text-[10px] font-medium text-slate-500"><?= substr($log['waktu'], 0, 5) ?></span>
                                </td>
                                <td class="p-4">
                                    <p class="font-bold text-slate-800 text-[13px]"><?= htmlspecialchars($log['nama_siswa']) ?></p>
                                    <p class="text-[10px] font-medium text-slate-500 bg-slate-100 inline-block px-1.5 py-0.5 rounded mt-0.5"><?= $log['nama_kelas'] ?> • <?= $log['no_induk'] ?></p>
                                </td>
                                <td class="p-4 text-slate-700 max-w-xs">
                                    <div class="truncate text-xs" title="<?= htmlspecialchars($log['pelanggaran_list'] ?: '-') ?>">
                                        <?= htmlspecialchars($log['pelanggaran_list'] ?: '-') ?>
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-1 text-[11px] font-bold rounded-md bg-red-50 text-red-600 border border-red-200">
                                        +<?= $log['total_poin'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-1 text-[10px] font-bold uppercase rounded-md <?= $log['tipe_form'] === 'Piket' ? 'bg-[#000080]/10 text-[#000080] border border-[#000080]/20' : 'bg-purple-50 text-purple-700 border border-purple-200' ?>">
                                        <?= $log['tipe_form'] ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="text-xs font-semibold text-slate-700"><?= htmlspecialchars($log['nama_guru']) ?></span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button onclick="viewDetail(<?= $log['id_transaksi'] ?>)" 
                                                class="p-1.5 bg-white border border-[#E2E8F0] text-blue-600 rounded-md hover:bg-blue-50 transition-colors shadow-sm" title="Lihat Bukti/Detail">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </button>
                                        <button onclick="editTransaction(<?= $log['id_transaksi'] ?>)" 
                                                class="p-1.5 bg-white border border-[#E2E8F0] text-amber-600 rounded-md hover:bg-amber-50 transition-colors shadow-sm" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        </button>
                                        <button onclick="deleteTransaction(<?= $log['id_transaksi'] ?>)" 
                                                class="p-1.5 bg-white border border-red-200 text-red-600 rounded-md hover:bg-red-50 transition-colors shadow-sm" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
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

<div id="modal-detail" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full relative z-10 overflow-hidden max-h-[90vh] flex flex-col">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between flex-shrink-0">
            <h3 class="font-extrabold text-slate-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Detail Transaksi
            </h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div id="modal-content" class="p-6 overflow-y-auto">
            <div class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-[#000080]"></div>
            </div>
        </div>
    </div>
</div>

<script>
function viewDetail(id) {
    document.getElementById('modal-detail').classList.remove('hidden');
    document.getElementById('modal-content').innerHTML = `
        <div class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-[#000080]"></div>
        </div>
    `;
    
    fetch(`detail_transaksi_ajax.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modal-content').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('modal-content').innerHTML = `
                <div class="text-center text-red-600 py-8">
                    <p class="font-bold">Error loading data</p>
                    <p class="text-sm">${error.message}</p>
                </div>
            `;
        });
}

function closeModal() {
    document.getElementById('modal-detail').classList.add('hidden');
}

function editTransaction(id) {
    window.location.href = `edit_pelanggaran.php?id=${id}`;
}

function deleteTransaction(id) {
    if (confirm('⚠️ PERINGATAN!\n\nMenghapus transaksi akan:\n• Mengurangi poin siswa secara otomatis\n• Menghapus riwayat pelanggaran\n• Tidak dapat dikembalikan\n\nYakin ingin menghapus?')) {
        window.location.href = `../../actions/hapus_transaksi.php?id=${id}&redirect=audit`;
    }
}
</script>

</body>
</html>