<?php
/**
 * SITAPSI - Kelola Report dari Wali Kelas (UI GLOBAL PORTAL)
 * Admin menerima report, membaca alasan, lalu menyetujui atau menolak.
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$filter_status = $_GET['status'] ?? 'Pending';

// Query report dengan detail lengkap (DISESUAIKAN NO INDUK)
$sql = "
    SELECT 
        h.id_transaksi,
        h.tanggal,
        h.waktu,
        h.semester,
        h.status_revisi,
        h.alasan_revisi,
        s.no_induk,
        s.nama_siswa,
        k.nama_kelas,
        g.nama_guru as pelapor,
        g_wali.nama_guru as wali_kelas,
        ta.nama_tahun,
        GROUP_CONCAT(DISTINCT jp.nama_pelanggaran SEPARATOR ' | ') as pelanggaran,
        SUM(d.poin_saat_itu) as total_poin
    FROM tb_pelanggaran_header h
    JOIN tb_anggota_kelas a ON h.id_anggota = a.id_anggota
    JOIN tb_siswa s ON a.no_induk = s.no_induk
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    JOIN tb_guru g ON h.id_guru = g.id_guru
    LEFT JOIN tb_guru g_wali ON k.id_kelas = g_wali.id_kelas
    JOIN tb_tahun_ajaran ta ON h.id_tahun = ta.id_tahun
    LEFT JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    LEFT JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    WHERE h.status_revisi = :status
    GROUP BY h.id_transaksi
    ORDER BY h.tanggal DESC, h.waktu DESC
";

$reports = fetchAll($sql, ['status' => $filter_status]);

// Hitung jumlah pending (untuk badge tab)
$count_pending = fetchOne("SELECT COUNT(*) as total FROM tb_pelanggaran_header WHERE status_revisi = 'Pending'")['total'] ?? 0;

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// --- UI CONFIG VARIABLES ---
$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Report Wali Kelas - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 py-4 sticky top-0 z-30 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Kelola Report Data</h1>
                <p class="text-sm font-medium text-slate-500">Verifikasi pengajuan perbaikan/penghapusan data dari Wali Kelas</p>
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

            <div class="flex border-b border-[#E2E8F0] space-x-6">
                <a href="kelola_report.php?status=Pending" class="pb-3 text-sm font-bold transition-colors <?= $filter_status === 'Pending' ? 'text-[#000080] border-b-2 border-[#000080]' : 'text-slate-500 hover:text-slate-800' ?>">
                    Menunggu Verifikasi 
                    <?php if($count_pending > 0): ?>
                        <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-500 text-white"><?= $count_pending ?></span>
                    <?php endif; ?>
                </a>
                <a href="kelola_report.php?status=Disetujui" class="pb-3 text-sm font-bold transition-colors <?= $filter_status === 'Disetujui' ? 'text-[#000080] border-b-2 border-[#000080]' : 'text-slate-500 hover:text-slate-800' ?>">
                    Telah Disetujui
                </a>
                <a href="kelola_report.php?status=Ditolak" class="pb-3 text-sm font-bold transition-colors <?= $filter_status === 'Ditolak' ? 'text-[#000080] border-b-2 border-[#000080]' : 'text-slate-500 hover:text-slate-800' ?>">
                    Ditolak
                </a>
            </div>

            <div class="<?= $card_class ?> overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50/50 text-xs text-slate-500 uppercase border-b border-[#E2E8F0]">
                            <tr>
                                <th class="p-4 font-bold">ID & Waktu</th>
                                <th class="p-4 font-bold">Siswa & Kelas</th>
                                <th class="p-4 font-bold">Wali Kelas (Pelapor)</th>
                                <th class="p-4 font-bold">Detail Pelanggaran</th>
                                <th class="p-4 font-bold text-center">Status</th>
                                <th class="p-4 font-bold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php if(empty($reports)): ?>
                            <tr>
                                <td colspan="6" class="p-12 text-center text-slate-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <p class="font-medium">Tidak ada data report dengan status <strong><?= $filter_status ?></strong></p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($reports as $row): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <p class="font-bold text-slate-800">#<?= $row['id_transaksi'] ?></p>
                                    <p class="text-[10px] text-slate-500 font-medium"><?= date('d M Y', strtotime($row['tanggal'])) ?> • <?= substr($row['waktu'], 0, 5) ?></p>
                                </td>
                                <td class="p-4">
                                    <p class="font-bold text-slate-800 text-[13px]"><?= htmlspecialchars($row['nama_siswa']) ?></p>
                                    <p class="text-[10px] font-medium text-slate-500 bg-slate-100 inline-block px-1.5 py-0.5 rounded mt-0.5"><?= $row['nama_kelas'] ?> • <?= $row['no_induk'] ?></p>
                                </td>
                                <td class="p-4">
                                    <p class="font-bold text-slate-700 text-xs"><?= htmlspecialchars($row['wali_kelas'] ?? 'Tidak Diketahui') ?></p>
                                    <p class="text-[10px] text-slate-400">Guru Input: <?= htmlspecialchars($row['pelapor']) ?></p>
                                </td>
                                <td class="p-4 max-w-xs">
                                    <p class="text-xs text-slate-700 truncate" title="<?= htmlspecialchars($row['pelanggaran']) ?>"><?= htmlspecialchars($row['pelanggaran']) ?></p>
                                    <p class="text-[10px] font-bold text-red-600 mt-0.5">+<?= $row['total_poin'] ?> Poin</p>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if ($row['status_revisi'] === 'Pending'): ?>
                                        <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-md bg-amber-50 text-amber-600 border border-amber-200">Menunggu</span>
                                    <?php elseif ($row['status_revisi'] === 'Disetujui'): ?>
                                        <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-md bg-emerald-50 text-emerald-600 border border-emerald-200">Disetujui</span>
                                    <?php elseif ($row['status_revisi'] === 'Ditolak'): ?>
                                        <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-md bg-red-50 text-red-600 border border-red-200">Ditolak</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button onclick="showAlasan(`<?= htmlspecialchars(addslashes($row['alasan_revisi'])) ?>`)" 
                                                class="px-3 py-1.5 text-[11px] font-bold bg-[#000080]/10 text-[#000080] rounded-md hover:bg-[#000080]/20 transition-colors shadow-sm">
                                            Alasan
                                        </button>
                                        
                                        <?php if ($filter_status === 'Pending'): ?>
                                            <button onclick="setujuiReport(<?= $row['id_transaksi'] ?>, '<?= addslashes(htmlspecialchars($row['nama_siswa'])) ?>')" 
                                                    class="p-1.5 bg-white border border-emerald-200 text-emerald-600 rounded-md hover:bg-emerald-50 transition-colors shadow-sm" title="Setujui">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </button>
                                            <button onclick="tolakReport(<?= $row['id_transaksi'] ?>, '<?= addslashes(htmlspecialchars($row['nama_siswa'])) ?>')" 
                                                    class="p-1.5 bg-white border border-red-200 text-red-600 rounded-md hover:bg-red-50 transition-colors shadow-sm" title="Tolak">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        <?php endif; ?>
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

<div id="modal-alasan" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeAlasan()"></div>
    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full relative z-10 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                Pesan / Alasan Wali Kelas
            </h3>
            <button onclick="closeAlasan()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="p-6">
            <div class="bg-slate-50 border border-[#E2E8F0] p-4 rounded-xl">
                <p id="teks-alasan" class="text-slate-700 whitespace-pre-wrap leading-relaxed text-sm font-medium"></p>
            </div>
        </div>
        <div class="p-5 border-t border-[#E2E8F0] flex justify-end">
            <button onclick="closeAlasan()" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-lg font-bold text-sm hover:bg-slate-200 transition-colors">Tutup</button>
        </div>
    </div>
</div>

<script>
function showAlasan(alasan) {
    document.getElementById('teks-alasan').textContent = alasan;
    document.getElementById('modal-alasan').classList.remove('hidden');
}

function closeAlasan() {
    document.getElementById('modal-alasan').classList.add('hidden');
}

function setujuiReport(id, nama) {
    if (confirm(`✅ MENYETUJUI LAPORAN\n\nAnda akan menandai keluhan Wali Kelas untuk siswa "${nama}" sebagai DITERIMA.\n\nNote: Sistem TIDAK akan menghapus transaksi secara otomatis. Anda harus mengedit/menghapusnya secara manual di menu Audit Harian.\n\nLanjutkan?`)) {
        window.location.href = `../../actions/proses_report.php?action=setujui&id=${id}`;
    }
}

function tolakReport(id, nama) {
    const alasan = prompt(`❌ MENOLAK LAPORAN\n\nMasukkan alasan penolakan ke Wali Kelas untuk siswa "${nama}":`);
    if (alasan !== null) {
        if(alasan.trim() === '') {
            alert('Alasan penolakan tidak boleh kosong!');
            return;
        }
        window.location.href = `../../actions/proses_report.php?action=tolak&id=${id}&alasan_admin=${encodeURIComponent(alasan)}`;
    }
}
</script>

</body>
</html>