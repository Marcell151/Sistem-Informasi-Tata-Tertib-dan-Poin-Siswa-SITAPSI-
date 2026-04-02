<?php
/**
 * SITAPSI - Manajemen SP (UI GLOBAL PORTAL)
 * Menampilkan riwayat SP dengan filter Semester Pintar
 * Penyesuaian: Sembunyikan tombol Cetak untuk 'Sanksi oleh Sekolah'
 * FITUR BARU: Buku Penghubung (Pesan Admin ke Ortu) & Lihat Balasan
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

$filter_kelas = $_GET['kelas'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$filter_semester = $_GET['semester'] ?? $tahun_aktif['semester_aktif'];

$kelas_list = fetchAll("SELECT * FROM tb_kelas ORDER BY tingkat, nama_kelas");

// [MODIFIKASI FINAL] Query riwayat SP dengan Indikator Balasan Ortu
$sql = "
    SELECT 
        s.no_induk, s.nama_siswa,
        k.nama_kelas,
        a.total_poin_umum, a.status_sp_terakhir, a.status_sp_kelakuan, a.status_sp_kerajinan, a.status_sp_kerapian,
        a.poin_kelakuan, a.poin_kerajinan, a.poin_kerapian,
        sp.id_sp, sp.tingkat_sp, sp.kategori_pemicu, sp.tanggal_terbit, sp.tanggal_validasi, sp.status, sp.catatan_admin,
        (SELECT COUNT(id_feedback) FROM tb_feedback_ortu fb WHERE fb.id_sp = sp.id_sp) as jml_balasan,
        (SELECT COUNT(id_feedback) FROM tb_feedback_ortu fb WHERE fb.id_sp = sp.id_sp AND fb.status_baca = 'Belum Dibaca') as balasan_baru,
        (SELECT isi_feedback FROM tb_feedback_ortu fb WHERE fb.id_sp = sp.id_sp ORDER BY tanggal_kirim DESC LIMIT 1) as balasan_terakhir,
        (SELECT id_feedback FROM tb_feedback_ortu fb WHERE fb.id_sp = sp.id_sp ORDER BY tanggal_kirim DESC LIMIT 1) as id_feedback
    FROM tb_riwayat_sp sp
    JOIN tb_anggota_kelas a ON sp.id_anggota = a.id_anggota
    JOIN tb_siswa s ON a.no_induk = s.no_induk
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE a.id_tahun = :id_tahun
";

$params = ['id_tahun' => $tahun_aktif['id_tahun']];

if ($filter_kelas !== 'all') {
    $sql .= " AND k.id_kelas = :kelas";
    $params['kelas'] = $filter_kelas;
}

if ($filter_status !== 'all') {
    $sql .= " AND sp.status = :status";
    $params['status'] = $filter_status;
}

if ($filter_semester === 'Ganjil') {
    $sql .= " AND MONTH(sp.tanggal_terbit) >= 7";
} elseif ($filter_semester === 'Genap') {
    $sql .= " AND MONTH(sp.tanggal_terbit) <= 6";
}

$sql .= " ORDER BY sp.tanggal_terbit DESC, sp.id_sp DESC";
$riwayat_sp = fetchAll($sql, $params);

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// --- UI CONFIG VARIABLES ---
$btn_primary = "px-4 py-2.5 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-md shadow-blue-900/10 hover:bg-blue-900 transition-all flex items-center justify-center";
$input_class = "w-full px-4 py-2 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm text-slate-700 bg-white transition-all";
$label_class = "block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wide";
$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen SP - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64 relative">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 pl-16 lg:pl-6 py-4 sticky top-0 z-30">
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Manajemen Surat Peringatan</h1>
            <p class="text-sm font-medium text-slate-500">Kelola dan validasi SP per kategori siswa</p>
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

            <div class="bg-red-600 text-white rounded-xl shadow-md p-6 relative overflow-hidden">
                <svg class="absolute right-0 top-0 text-white/10 w-48 h-48 transform translate-x-8 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm0 3.83L19.82 19H4.18L12 5.83zM11 16h2v2h-2v-2zm0-7h2v5h-2V9z"></path></svg>
                <div class="relative z-10 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-extrabold mb-1 flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            Riwayat Surat Peringatan (SP)
                        </h2>
                        <p class="text-red-100 font-medium text-sm">Tahun Ajaran: <?= $tahun_aktif['nama_tahun'] ?></p>
                    </div>
                </div>
            </div>

            <div class="<?= $card_class ?> p-5 bg-slate-50/30">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="<?= $label_class ?>">Filter Semester</label>
                        <select name="semester" class="<?= $input_class ?> font-bold text-[#000080]">
                            <option value="all" <?= $filter_semester === 'all' ? 'selected' : '' ?>>Semua Semester</option>
                            <option value="Ganjil" <?= $filter_semester === 'Ganjil' ? 'selected' : '' ?>>Semester Ganjil</option>
                            <option value="Genap" <?= $filter_semester === 'Genap' ? 'selected' : '' ?>>Semester Genap</option>
                        </select>
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Filter Kelas</label>
                        <select name="kelas" class="<?= $input_class ?>">
                            <option value="all">Semua Kelas</option>
                            <?php foreach ($kelas_list as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>" <?= $filter_kelas == $k['id_kelas'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Status SP</label>
                        <select name="status" class="<?= $input_class ?>">
                            <option value="all">Semua Status</option>
                            <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Menunggu TTD</option>
                            <option value="Selesai" <?= $filter_status === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="<?= $btn_primary ?> w-full h-[38px]">Terapkan Filter</button>
                    </div>
                </form>
            </div>

            <div class="<?= $card_class ?> overflow-hidden">
                <div class="p-4 border-b border-[#E2E8F0] bg-white flex justify-between items-center">
                    <span class="font-bold text-slate-800 text-sm">Daftar Surat Peringatan</span>
                    <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2 py-1 rounded-md">Total: <?= count($riwayat_sp) ?> Surat</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50/50 text-xs text-slate-500 uppercase border-b border-[#E2E8F0]">
                            <tr>
                                <th class="p-4 font-bold">Siswa</th>
                                <th class="p-4 font-bold text-center">Tingkat SP</th>
                                <th class="p-4 font-bold text-center">Status 3 Silo</th>
                                <th class="p-4 font-bold text-center">Tgl Terbit</th>
                                <th class="p-4 font-bold text-center">Status & Ortu</th>
                                <th class="p-4 font-bold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php if (empty($riwayat_sp)): ?>
                            <tr>
                                <td colspan="6" class="p-12 text-center text-slate-400">Tidak ada riwayat SP</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($riwayat_sp as $sp): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-[#000080] rounded-xl flex items-center justify-center overflow-hidden flex-shrink-0 shadow-sm text-white font-extrabold">
                                            <?= strtoupper(substr($sp['nama_siswa'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800 text-[13px]"><?= htmlspecialchars($sp['nama_siswa']) ?></p>
                                            <p class="text-[10px] font-medium text-slate-500"><?= $sp['nama_kelas'] ?> • No Induk: <?= $sp['no_induk'] ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2.5 py-1 bg-red-50 text-red-600 border border-red-200 rounded-md font-bold text-xs shadow-sm">
                                        <?= $sp['tingkat_sp'] ?> <span class="font-normal">(<?= $sp['kategori_pemicu'] ?>)</span>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex gap-1 justify-center">
                                        <?php
                                        $sp_kategori = [
                                            ['label' => 'KL', 'status' => $sp['status_sp_kelakuan'], 'color' => 'red'],
                                            ['label' => 'KJ', 'status' => $sp['status_sp_kerajinan'], 'color' => 'blue'],
                                            ['label' => 'KP', 'status' => $sp['status_sp_kerapian'], 'color' => 'yellow']
                                        ];
                                        foreach ($sp_kategori as $kat):
                                            if ($kat['status'] !== 'Aman'):
                                        ?>
                                        <span class="px-1.5 py-0.5 border border-<?= $kat['color'] ?>-200 bg-<?= $kat['color'] ?>-50 text-<?= $kat['color'] ?>-700 rounded text-[9px] font-bold">
                                            <?= $kat['label'] ?>:<?= $kat['status'] ?>
                                        </span>
                                        <?php endif; endforeach; ?>
                                    </div>
                                </td>
                                <td class="p-4 text-center font-bold text-xs text-slate-600">
                                    <?= date('d M Y', strtotime($sp['tanggal_terbit'])) ?>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold shadow-sm block mb-1.5 mx-auto w-fit
                                        <?= $sp['status'] === 'Pending' ? 'bg-amber-50 text-amber-600 border border-amber-200' : 'bg-emerald-50 text-emerald-600 border border-emerald-200' ?>">
                                        <?= $sp['status'] ?>
                                    </span>
                                    
                                    <?php if ($sp['balasan_baru'] > 0): ?>
                                        <button onclick="lihatBalasan('<?= $sp['id_feedback'] ?>', '<?= htmlspecialchars($sp['nama_siswa'], ENT_QUOTES) ?>', '<?= htmlspecialchars($sp['balasan_terakhir'], ENT_QUOTES) ?>')" class="inline-flex items-center text-[10px] font-extrabold text-white bg-rose-500 px-2 py-0.5 rounded border border-rose-600 hover:bg-rose-600 transition-colors cursor-pointer animate-pulse shadow-sm" title="Ada Balasan Baru dari Ortu!">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                            Pesan Baru (<?= $sp['balasan_baru'] ?>)
                                        </button>
                                    <?php elseif ($sp['jml_balasan'] > 0): ?>
                                        <button onclick="lihatBalasan('<?= $sp['id_feedback'] ?>', '<?= htmlspecialchars($sp['nama_siswa'], ENT_QUOTES) ?>', '<?= htmlspecialchars($sp['balasan_terakhir'], ENT_QUOTES) ?>')" class="inline-flex items-center text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-200 hover:bg-blue-100 transition-colors cursor-pointer" title="Lihat Balasan Sebelumnya">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            Sudah Dibaca
                                        </button>
                                    <?php else: ?>
                                        <span class="inline-flex items-center text-[9px] font-medium text-slate-400">Belum ada balasan</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex gap-2 justify-center">
                                        <?php if ($sp['tingkat_sp'] !== 'Sanksi oleh Sekolah'): ?>
                                        <a href="cetak_sp.php?id=<?= $sp['id_sp'] ?>" target="_blank" class="p-1.5 bg-white border border-[#E2E8F0] text-[#000080] rounded-md hover:bg-blue-50 transition-colors shadow-sm" title="Cetak Surat Resmi">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                                        </a>
                                        <?php endif; ?>

                                        <?php if ($sp['status'] === 'Pending'): ?>
                                            <button onclick="bukaModalPesan('<?= $sp['id_sp'] ?>', '<?= htmlspecialchars($sp['nama_siswa'], ENT_QUOTES) ?>', '<?= htmlspecialchars($sp['catatan_admin'] ?? '', ENT_QUOTES) ?>')"
                                               class="p-1.5 bg-amber-50 border border-amber-200 text-amber-600 rounded-md hover:bg-amber-100 transition-colors shadow-sm inline-block" title="Tulis Pesan ke Ortu">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>

                                            <a href="../../actions/validasi_sp.php?id=<?= $sp['id_sp'] ?>" 
                                            onclick="return confirm('PENTING: Validasi SP ini HANYA jika Anda sudah benar-benar bertemu/menghubungi Orang Tua dan SP sudah ditandatangani. Lanjutkan?')"
                                            class="p-1.5 bg-emerald-50 border border-emerald-200 text-emerald-600 rounded-md hover:bg-emerald-100 transition-colors shadow-sm inline-block" title="Validasi & Selesai">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            </a>
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

    <div id="modal-pesan" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="tutupModalPesan()"></div>
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full relative z-10 overflow-hidden transform transition-all">
            <div class="p-5 border-b border-slate-200 bg-[#000080] flex justify-between items-center">
                <h3 class="font-extrabold text-white flex items-center text-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Pesan untuk Wali Murid
                </h3>
                <button type="button" onclick="tutupModalPesan()" class="text-blue-200 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            
            <form action="../../actions/update_catatan_admin.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id_sp" id="input_id_sp" value="">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wide">Siswa</label>
                    <input type="text" id="input_nama_siswa" readonly class="w-full px-4 py-2 border border-slate-200 rounded-lg bg-slate-100 text-sm font-bold text-slate-700 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wide">Pesan / Instruksi untuk Orang Tua <span class="text-red-500">*</span></label>
                    <textarea name="catatan_admin" id="input_catatan_admin" required rows="5" placeholder="Tuliskan pesan untuk orang tua di sini..." class="w-full px-4 py-3 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm font-medium text-slate-800 transition-all resize-none"></textarea>
                </div>
                <div class="pt-3 flex gap-3">
                    <button type="button" onclick="tutupModalPesan()" class="flex-1 py-2.5 bg-white border border-[#E2E8F0] text-slate-700 font-bold rounded-lg hover:bg-slate-50 transition-colors text-sm">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-[#000080] text-white font-bold rounded-lg shadow-md hover:bg-blue-900 transition-colors text-sm">Kirim Pesan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-baca-balasan" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4" data-need-reload="false">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="tutupModalBalasan()"></div>
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full relative z-10 overflow-hidden transform transition-all border border-blue-100">
            <div class="p-5 border-b border-blue-100 bg-blue-50 flex justify-between items-center">
                <h3 class="font-extrabold text-[#000080] flex items-center text-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    Balasan / Konfirmasi Wali Murid
                </h3>
                <button type="button" onclick="tutupModalBalasan()" class="text-blue-400 hover:text-[#000080] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="p-6">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Tanggapan Untuk Siswa:</p>
                <p id="baca_nama_siswa" class="font-extrabold text-slate-800 text-lg mb-4"></p>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 relative">
                    <div class="absolute -left-2 top-4 w-4 h-4 bg-slate-50 border-l border-t border-slate-200 transform -rotate-45"></div>
                    <p id="baca_isi_balasan" class="text-sm font-medium text-slate-700 leading-relaxed relative z-10 whitespace-pre-wrap"></p>
                </div>
            </div>
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                <button type="button" onclick="tutupModalBalasan()" class="w-full py-2.5 bg-white border border-slate-300 text-slate-700 font-bold rounded-lg shadow-sm hover:bg-slate-100 transition-colors text-sm">Tutup & Mengerti</button>
            </div>
        </div>
    </div>

</div>

<script>
    function bukaModalPesan(id_sp, nama, catatan) {
        document.getElementById('input_id_sp').value = id_sp;
        document.getElementById('input_nama_siswa').value = nama;
        document.getElementById('input_catatan_admin').value = catatan; 
        document.getElementById('modal-pesan').classList.remove('hidden');
    }

    function tutupModalPesan() {
        document.getElementById('modal-pesan').classList.add('hidden');
    }

    // [BARU] Logika AJAX untuk menandai pesan sudah dibaca
    function lihatBalasan(id_feedback, nama, balasan) {
        document.getElementById('baca_nama_siswa').innerText = nama;
        document.getElementById('baca_isi_balasan').innerText = balasan;
        document.getElementById('modal-baca-balasan').classList.remove('hidden');

        // Panggil action background untuk mengubah status menjadi "Sudah Dibaca"
        fetch('../../actions/tandai_dibaca.php?id=' + id_feedback)
            .then(response => response.text())
            .then(data => {
                if(data.trim() === 'success') {
                    // Tandai bahwa halaman perlu direfresh saat modal ditutup
                    document.getElementById('modal-baca-balasan').setAttribute('data-need-reload', 'true');
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function tutupModalBalasan() {
        document.getElementById('modal-baca-balasan').classList.add('hidden');
        // Jika statusnya tadi adalah pesan baru, refresh halaman agar indikator merahnya hilang
        if (document.getElementById('modal-baca-balasan').getAttribute('data-need-reload') === 'true') {
            window.location.reload();
        }
    }
</script>

</body>
</html>