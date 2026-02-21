<?php
/**
 * SITAPSI - Kelola Report dari Wali Kelas (MANUAL SYSTEM)
 * Admin menerima report, membaca alasan, lalu menyetujui (Tandai selesai) atau menolak.
 * Eksekusi penghapusan transaksi dilakukan manual oleh Admin di menu Audit.
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$filter_status = $_GET['status'] ?? 'Pending';

// Query report dengan detail lengkap
$sql = "
    SELECT 
        h.id_transaksi,
        h.tanggal,
        h.waktu,
        h.semester,
        h.status_revisi,
        h.alasan_revisi,
        s.nis,
        s.nama_siswa,
        k.nama_kelas,
        g.nama_guru as pelapor,
        g_wali.nama_guru as wali_kelas,
        ta.nama_tahun,
        GROUP_CONCAT(DISTINCT jp.nama_pelanggaran SEPARATOR ' | ') as pelanggaran,
        SUM(d.poin_saat_itu) as total_poin
    FROM tb_pelanggaran_header h
    JOIN tb_anggota_kelas a ON h.id_anggota = a.id_anggota
    JOIN tb_siswa s ON a.nis = s.nis
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    JOIN tb_guru g ON h.id_guru = g.id_guru
    LEFT JOIN tb_guru g_wali ON k.id_kelas = g_wali.id_kelas
    JOIN tb_tahun_ajaran ta ON h.id_tahun = ta.id_tahun
    JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    WHERE h.status_revisi = :status
    GROUP BY h.id_transaksi
    ORDER BY h.tanggal DESC, h.waktu DESC
";

$report_list = fetchAll($sql, ['status' => $filter_status]);

// Hitung total report pending
$count_pending = fetchOne("
    SELECT COUNT(*) as total 
    FROM tb_pelanggaran_header 
    WHERE status_revisi = 'Pending'
")['total'];

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Report - SITAPSI</title>
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
        
        <div class="bg-white shadow-sm border-b px-6 py-4 sticky top-0 z-30">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Kelola Report Wali Kelas</h1>
                    <p class="text-sm text-gray-500">Verifikasi laporan kesalahan input dari wali kelas</p>
                </div>
                <?php if ($count_pending > 0): ?>
                <div class="px-4 py-2 bg-red-100 text-red-800 rounded-full font-bold flex items-center shadow-sm">
                    <span class="w-2.5 h-2.5 bg-red-600 rounded-full mr-2 animate-pulse"></span>
                    <?= $count_pending ?> Report Menunggu
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-6 space-y-6">

            <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded shadow-sm">
                <p class="text-green-700 font-medium"><?= htmlspecialchars($success) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
                <p class="text-red-700 font-medium"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <div class="bg-gradient-to-r from-orange-500 to-orange-700 text-white rounded-xl shadow-lg p-6 relative overflow-hidden">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"></div>
                <div class="flex items-center justify-between relative z-10">
                    <div>
                        <h2 class="text-2xl font-bold mb-2 flex items-center">
                            <span class="text-3xl mr-2">🚩</span> Report Kesalahan Data
                        </h2>
                        <p class="text-orange-100">Daftar laporan dari Wali Kelas untuk perbaikan/penghapusan data pelanggaran.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm font-medium text-gray-700">Filter Status:</span>
                    <a href="?status=Pending" 
                       class="px-4 py-2 rounded-lg font-medium transition-all text-sm <?= $filter_status === 'Pending' ? 'bg-yellow-500 text-white shadow-md transform scale-105' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        ⏳ Pending (<?= $count_pending ?>)
                    </a>
                    <a href="?status=Disetujui" 
                       class="px-4 py-2 rounded-lg font-medium transition-all text-sm <?= $filter_status === 'Disetujui' ? 'bg-green-500 text-white shadow-md transform scale-105' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        ✅ Disetujui
                    </a>
                    <a href="?status=Ditolak" 
                       class="px-4 py-2 rounded-lg font-medium transition-all text-sm <?= $filter_status === 'Ditolak' ? 'bg-red-500 text-white shadow-md transform scale-105' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        ❌ Ditolak
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                    <span class="font-bold text-gray-700">📜 Daftar Report (Status: <?= $filter_status ?>)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-white text-xs text-gray-500 uppercase border-b">
                            <tr>
                                <th class="p-4">Tgl Transaksi</th>
                                <th class="p-4">Siswa</th>
                                <th class="p-4">Kelas</th>
                                <th class="p-4">Pelanggaran</th>
                                <th class="p-4 text-center">Poin</th>
                                <th class="p-4">Wali Kelas (Pelapor)</th>
                                <th class="p-4 text-center">Alasan</th>
                                <?php if ($filter_status === 'Pending'): ?>
                                <th class="p-4 text-center bg-gray-50 border-l">Aksi Verifikasi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($report_list)): ?>
                            <tr>
                                <td colspan="<?= $filter_status === 'Pending' ? '8' : '7' ?>" class="p-12 text-center text-gray-500">
                                    <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="font-medium text-gray-400">Tidak ada report dengan status ini</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($report_list as $r): ?>
                            <tr class="hover:bg-orange-50/30 transition-colors">
                                <td class="p-4">
                                    <div class="font-medium text-gray-800"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></div>
                                    <div class="text-xs text-gray-500"><?= substr($r['waktu'], 0, 5) ?> WIB</div>
                                </td>
                                <td class="p-4">
                                    <p class="font-bold text-navy text-[13px]"><?= htmlspecialchars($r['nama_siswa']) ?></p>
                                    <p class="text-[10px] text-gray-500"><?= $r['nis'] ?></p>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-bold border border-gray-200">
                                        <?= $r['nama_kelas'] ?>
                                    </span>
                                </td>
                                <td class="p-4 max-w-[200px] truncate" title="<?= htmlspecialchars($r['pelanggaran']) ?>">
                                    <span class="text-sm font-medium text-gray-800">
                                        <?= htmlspecialchars($r['pelanggaran']) ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2.5 py-1 bg-red-100 text-red-800 rounded-lg font-bold text-xs">
                                        +<?= $r['total_poin'] ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-800 flex items-center">
                                            <svg class="w-3.5 h-3.5 text-orange-500 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"></path></svg>
                                            <?= htmlspecialchars($r['wali_kelas'] ?: 'Tidak diketahui') ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <button onclick='showAlasan(`<?= htmlspecialchars($r['alasan_revisi'], ENT_QUOTES) ?>`)' 
                                            class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-md text-xs font-bold transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        Buka Pesan
                                    </button>
                                </td>
                                
                                <?php if ($filter_status === 'Pending'): ?>
                                <td class="p-4 bg-gray-50/50 border-l border-gray-100">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button onclick="setujuiReport(<?= $r['id_transaksi'] ?>, '<?= htmlspecialchars($r['nama_siswa'], ENT_QUOTES) ?>')"
                                                class="flex items-center px-3 py-1.5 bg-green-500 text-white rounded-md hover:bg-green-600 shadow-sm text-xs font-bold transition-colors" title="Tandai Diterima">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            Setujui
                                        </button>
                                        <button onclick="tolakReport(<?= $r['id_transaksi'] ?>, '<?= htmlspecialchars($r['nama_siswa'], ENT_QUOTES) ?>')"
                                                class="flex items-center px-3 py-1.5 bg-white border border-red-200 text-red-600 rounded-md hover:bg-red-50 text-xs font-bold transition-colors" title="Tolak">
                                            Tolak
                                        </button>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-xl shadow-sm text-sm">
                <div class="flex items-start">
                    <span class="text-lg mr-3">💡</span>
                    <div>
                        <h4 class="font-bold text-blue-800 mb-1">Panduan Pengelolaan Report</h4>
                        <ul class="text-blue-700 space-y-1.5">
                            <li>• <strong>Setujui:</strong> Menandakan bahwa pesan/keluhan dari Wali Kelas telah Anda terima. Status akan berubah menjadi "Disetujui". Anda bisa mencari siswa tersebut di menu <strong>Audit Harian</strong> lalu menghapus/mengedit datanya secara manual.</li>
                            <li>• <strong>Tolak:</strong> Jika data yang diinput dirasa sudah benar. Wali kelas akan melihat balasan penolakan dari Anda.</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="modal-alasan" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-blue-50">
            <h3 class="text-lg font-bold text-blue-800 flex items-center">
                <span class="mr-2">✉️</span> Pesan Wali Kelas
            </h3>
            <button onclick="closeAlasan()" class="text-gray-400 hover:text-gray-600 bg-white rounded-full p-1 shadow-sm transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-6">
            <p id="teks-alasan" class="text-gray-700 whitespace-pre-wrap leading-relaxed text-sm"></p>
        </div>
        <div class="p-4 bg-gray-50 border-t flex justify-end">
            <button onclick="closeAlasan()" class="px-5 py-2 bg-gray-800 text-white rounded-lg font-medium text-sm hover:bg-gray-700 transition-colors">Tutup</button>
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
    const alasan = prompt(`❌ MENOLAK LAPORAN\n\nMasukkan alasan penolakan kepada Wali Kelas siswa "${nama}":\n(Misal: "Data sudah dicek dan memang benar siswa ybs melanggar")`);
    if (alasan && alasan.trim() !== '') {
        window.location.href = `../../actions/proses_report.php?action=tolak&id=${id}&alasan=${encodeURIComponent(alasan)}`;
    }
}
</script>

</body>
</html>