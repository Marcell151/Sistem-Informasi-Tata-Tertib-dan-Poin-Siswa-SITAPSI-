<?php
/**
 * SITAPSI - Input Pelanggaran (UI GLOBAL PORTAL)
 * FIX: Filter 2 Tahap (Pilih Kelas -> Pilih Siswa), Waktu Live
 * PENYESUAIAN: Fitur Upload diperluas (Gambar, PDF, Word, atau Link Eksternal)
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireGuru();

$user = getCurrentUser();
$mode = $_GET['mode'] ?? 'piket';

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

if (!$tahun_aktif) {
    die("Error: Tidak ada tahun ajaran aktif. Hubungi admin.");
}

// 1. Ambil daftar kelas
$kelas_list = fetchAll("SELECT id_kelas, nama_kelas, tingkat FROM tb_kelas ORDER BY tingkat, nama_kelas");

// 2. Ambil daftar siswa dan kelompokkan berdasarkan id_kelas menggunakan PHP Array (nis diubah ke no_induk)
$siswa_raw = fetchAll("
    SELECT s.no_induk, s.nama_siswa, a.id_kelas, a.id_anggota
    FROM tb_siswa s
    JOIN tb_anggota_kelas a ON s.no_induk = a.no_induk
    WHERE s.status_aktif = 'Aktif' 
    AND a.id_tahun = :id_tahun
    ORDER BY s.nama_siswa
", ['id_tahun' => $tahun_aktif['id_tahun']]);

$siswa_by_kelas = [];
foreach ($siswa_raw as $s) {
    $siswa_by_kelas[$s['id_kelas']][] = [
        'id_anggota' => $s['id_anggota'],
        'no_induk' => $s['no_induk'],
        'nama_siswa' => $s['nama_siswa']
    ];
}
// Encode ke JSON agar bisa dibaca oleh JavaScript
$siswa_json = json_encode($siswa_by_kelas);

// Ambil daftar jenis pelanggaran per kategori
$pelanggaran_kelakuan = fetchAll("SELECT * FROM tb_jenis_pelanggaran WHERE id_kategori = 1 ORDER BY sub_kategori, nama_pelanggaran");
$pelanggaran_kerajinan = fetchAll("SELECT * FROM tb_jenis_pelanggaran WHERE id_kategori = 2 ORDER BY sub_kategori, nama_pelanggaran");
$pelanggaran_kerapian = fetchAll("SELECT * FROM tb_jenis_pelanggaran WHERE id_kategori = 3 ORDER BY sub_kategori, nama_pelanggaran");

// Ambil data referensi sanksi untuk JS
$sanksi_list = fetchAll("SELECT * FROM tb_sanksi_ref ORDER BY CAST(kode_sanksi AS UNSIGNED)");
$sanksi_json = json_encode($sanksi_list);

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// --- UI CONFIG VARIABLES ---
$btn_primary = "w-full px-6 py-3.5 bg-[#000080] text-white text-sm font-extrabold rounded-xl shadow-md shadow-blue-900/10 hover:bg-blue-900 transition-all flex items-center justify-center";
$input_class = "w-full px-4 py-3 border border-[#E2E8F0] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm text-slate-700 bg-white transition-all";
$label_class = "block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wide";
$card_class = "bg-white border border-[#E2E8F0] rounded-2xl shadow-sm";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Pelanggaran - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <style>
        .ts-control { border-radius: 0.75rem !important; padding: 0.75rem 1rem !important; border-color: #E2E8F0 !important; }
        .ts-control.focus { border-color: #000080 !important; box-shadow: 0 0 0 2px rgba(0,0,128,0.1) !important; }
        .ts-control.disabled { background-color: #F8FAFC !important; opacity: 0.7; cursor: not-allowed; }
    </style>
</head>
<body class="bg-[#F8FAFC] pb-24 md:pb-8">

    <?php include '../../includes/navbar_guru.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Input Pelanggaran</h1>
                <p class="text-sm font-medium text-slate-500">Tahun Ajaran <?= $tahun_aktif['nama_tahun'] ?> - Semester <?= $tahun_aktif['semester_aktif'] ?></p>
            </div>
            
            <div class="bg-white p-1 rounded-xl border border-[#E2E8F0] shadow-sm flex inline-flex w-full md:w-auto">
                <a href="?mode=piket" class="flex-1 md:flex-none text-center px-6 py-2 text-sm font-bold rounded-lg transition-colors <?= $mode === 'piket' ? 'bg-[#000080] text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>">Guru Piket</a>
                <a href="?mode=kelas" class="flex-1 md:flex-none text-center px-6 py-2 text-sm font-bold rounded-lg transition-colors <?= $mode === 'kelas' ? 'bg-[#000080] text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>">Dalam Kelas</a>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl shadow-sm flex items-center mb-6">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <p class="font-medium text-sm"><?= htmlspecialchars($success) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl shadow-sm flex items-center mb-6">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            <p class="font-medium text-sm"><?= htmlspecialchars($error) ?></p>
        </div>
        <?php endif; ?>

        <form action="../../actions/simpan_pelanggaran.php" method="POST" enctype="multipart/form-data" id="form-pelanggaran">
            <input type="hidden" name="tipe_form" value="<?= $mode === 'piket' ? 'Piket' : 'Kelas' ?>">
            
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <div class="lg:col-span-4 space-y-6">
                    <div class="<?= $card_class ?> p-6 border-t-4 border-t-[#000080]">
                        <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wide mb-5 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                            Identitas & Waktu
                        </h2>
                        
                        <div class="space-y-5">
                            
                            <div>
                                <label class="<?= $label_class ?>">1. Pilih Kelas *</label>
                                <select id="select-kelas" required placeholder="Pilih Kelas...">
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php foreach ($kelas_list as $k): ?>
                                        <option value="<?= $k['id_kelas'] ?>"><?= $k['nama_kelas'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="<?= $label_class ?>">2. Cari Siswa *</label>
                                <select id="select-siswa" name="id_anggota" required placeholder="Pilih kelas terlebih dahulu...">
                                    <option value="">-- Pilih Siswa --</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="<?= $label_class ?>">Tanggal Masuk</label>
                                    <div class="<?= $input_class ?> bg-slate-100 text-slate-600 font-bold flex items-center cursor-not-allowed opacity-80" title="Tanggal otomatis dikunci oleh sistem">
                                        <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                        <?= date('d M Y') ?>
                                    </div>
                                    <input type="hidden" name="tanggal" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div>
                                    <label class="<?= $label_class ?>">Waktu (Live)</label>
                                    <div class="<?= $input_class ?> bg-slate-100 text-slate-600 font-bold flex items-center cursor-not-allowed opacity-80" title="Waktu otomatis mengikuti jam saat ini">
                                        <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        <span id="live-clock"><?= date('H:i') ?></span>
                                    </div>
                                    <input type="hidden" name="waktu" id="waktu-input" value="<?= date('H:i') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="<?= $card_class ?> p-6">
                        <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wide mb-5 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"></path></svg>
                            Lampiran Bukti (Opsional)
                        </h2>
                        
                        <div class="flex bg-slate-100 p-1 rounded-xl mb-4 shadow-inner">
                            <button type="button" id="btn-mode-file" onclick="switchAttachmentMode('file')" class="flex-1 py-2 text-[11px] font-extrabold rounded-lg bg-white text-[#000080] shadow-sm transition-all uppercase tracking-wider">
                                Upload File
                            </button>
                            <button type="button" id="btn-mode-link" onclick="switchAttachmentMode('link')" class="flex-1 py-2 text-[11px] font-bold rounded-lg text-slate-500 hover:text-slate-700 transition-all uppercase tracking-wider">
                                Link Drive
                            </button>
                        </div>

                        <div id="area-file" class="space-y-4">
                            <button type="button" onclick="document.getElementById('input-foto').click()" 
                                    class="w-full border-2 border-dashed border-[#000080]/30 hover:border-[#000080] hover:bg-blue-50 text-[#000080] bg-white rounded-xl p-6 text-center transition-all cursor-pointer">
                                <svg class="w-8 h-8 mx-auto mb-2 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                <span class="font-bold text-sm">Klik untuk tambah File</span>
                                <p class="text-[10px] font-medium text-slate-500 mt-1">Maks 2MB per file (Foto, PDF, Word)</p>
                            </button>
                            
                            <input type="file" id="input-foto" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" multiple class="hidden">
                            <div id="file-list-container" class="space-y-2 empty:hidden"></div>
                        </div>

                        <div id="area-link" class="hidden space-y-3">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Tautkan Link (Google Drive, dll)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                </div>
                                <input type="url" name="lampiran_link" id="input-link" placeholder="https://drive.google.com/..." class="w-full pl-9 pr-4 py-3 border border-[#E2E8F0] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm text-slate-700 bg-white transition-all">
                            </div>
                            <p class="text-[10px] text-amber-600 font-medium leading-relaxed bg-amber-50 p-2 rounded-lg border border-amber-100">
                                ⚠️ <strong>Pastikan:</strong> Akses link telah dibuka (Public) agar tim Disiplin dapat melihat buktinya.
                            </p>
                        </div>

                    </div>
                </div>

                <div class="lg:col-span-8 space-y-6">
                    <div class="<?= $card_class ?> overflow-hidden flex flex-col">
                        
                        <div class="flex border-b border-[#E2E8F0] overflow-x-auto bg-slate-50/50 scrollbar-hide">
                            <button type="button" onclick="switchTab('kelakuan')" id="tab-kelakuan" class="tab-btn flex-1 py-4 px-4 font-extrabold text-sm text-center transition-colors bg-red-600 text-white border-b-2 border-red-700 whitespace-nowrap">
                                🚨 KELAKUAN
                            </button>
                            <button type="button" onclick="switchTab('kerajinan')" id="tab-kerajinan" class="tab-btn flex-1 py-4 px-4 font-bold text-sm text-center transition-colors text-slate-500 hover:text-slate-800 border-b-2 border-transparent hover:bg-slate-100 whitespace-nowrap">
                                📘 KERAJINAN
                            </button>
                            <button type="button" onclick="switchTab('kerapian')" id="tab-kerapian" class="tab-btn flex-1 py-4 px-4 font-bold text-sm text-center transition-colors text-slate-500 hover:text-slate-800 border-b-2 border-transparent hover:bg-slate-100 whitespace-nowrap">
                                👔 KERAPIAN
                            </button>
                        </div>

                        <div id="content-kelakuan" class="tab-content p-6 overflow-y-auto h-[400px] md:h-[500px]">
                            <?php $current_sub = ''; foreach ($pelanggaran_kelakuan as $p): 
                                if ($current_sub !== $p['sub_kategori']): 
                                    if ($current_sub !== '') echo '</div>'; $current_sub = $p['sub_kategori']; 
                            ?>
                            <div class="mb-6"><h5 class="text-[11px] font-extrabold text-slate-400 mb-3 uppercase tracking-wider border-b border-[#E2E8F0] pb-2"><?= htmlspecialchars($p['sub_kategori']) ?></h5>
                            <?php endif; ?>
                                <label class="flex items-start space-x-3 p-3.5 border border-[#E2E8F0] rounded-xl hover:bg-red-50 cursor-pointer mb-2.5 transition-all hover:border-red-300 group">
                                    <input type="checkbox" name="pelanggaran[]" value="<?= $p['id_jenis'] ?>" data-poin="<?= $p['poin_default'] ?>" data-sanksi="<?= htmlspecialchars($p['sanksi_default']) ?>" onchange="updateSanksi()" class="w-5 h-5 text-red-600 border-slate-300 rounded focus:ring-red-500 mt-0.5">
                                    <div class="flex-1">
                                        <p class="font-bold text-slate-700 text-sm group-hover:text-red-700 leading-snug"><?= htmlspecialchars($p['nama_pelanggaran']) ?></p>
                                        <p class="text-xs text-red-600 font-extrabold mt-1"><?= $p['poin_default'] ?> Poin</p>
                                    </div>
                                </label>
                            <?php endforeach; if ($current_sub !== '') echo '</div>'; ?>
                        </div>

                        <div id="content-kerajinan" class="tab-content p-6 overflow-y-auto h-[400px] md:h-[500px] hidden">
                            <?php $current_sub = ''; foreach ($pelanggaran_kerajinan as $p): 
                                if ($current_sub !== $p['sub_kategori']): 
                                    if ($current_sub !== '') echo '</div>'; $current_sub = $p['sub_kategori']; 
                            ?>
                            <div class="mb-6"><h5 class="text-[11px] font-extrabold text-slate-400 mb-3 uppercase tracking-wider border-b border-[#E2E8F0] pb-2"><?= htmlspecialchars($p['sub_kategori']) ?></h5>
                            <?php endif; ?>
                                <label class="flex items-start space-x-3 p-3.5 border border-[#E2E8F0] rounded-xl hover:bg-blue-50 cursor-pointer mb-2.5 transition-all hover:border-blue-300 group">
                                    <input type="checkbox" name="pelanggaran[]" value="<?= $p['id_jenis'] ?>" data-poin="<?= $p['poin_default'] ?>" data-sanksi="<?= htmlspecialchars($p['sanksi_default']) ?>" onchange="updateSanksi()" class="w-5 h-5 text-blue-600 border-slate-300 rounded focus:ring-blue-500 mt-0.5">
                                    <div class="flex-1">
                                        <p class="font-bold text-slate-700 text-sm group-hover:text-blue-700 leading-snug"><?= htmlspecialchars($p['nama_pelanggaran']) ?></p>
                                        <p class="text-xs text-blue-600 font-extrabold mt-1"><?= $p['poin_default'] ?> Poin</p>
                                    </div>
                                </label>
                            <?php endforeach; if ($current_sub !== '') echo '</div>'; ?>
                        </div>

                        <div id="content-kerapian" class="tab-content p-6 overflow-y-auto h-[400px] md:h-[500px] hidden">
                            <?php $current_sub = ''; foreach ($pelanggaran_kerapian as $p): 
                                if ($current_sub !== $p['sub_kategori']): 
                                    if ($current_sub !== '') echo '</div>'; $current_sub = $p['sub_kategori']; 
                            ?>
                            <div class="mb-6"><h5 class="text-[11px] font-extrabold text-slate-400 mb-3 uppercase tracking-wider border-b border-[#E2E8F0] pb-2"><?= htmlspecialchars($p['sub_kategori']) ?></h5>
                            <?php endif; ?>
                                <label class="flex items-start space-x-3 p-3.5 border border-[#E2E8F0] rounded-xl hover:bg-yellow-50 cursor-pointer mb-2.5 transition-all hover:border-yellow-400 group">
                                    <input type="checkbox" name="pelanggaran[]" value="<?= $p['id_jenis'] ?>" data-poin="<?= $p['poin_default'] ?>" data-sanksi="<?= htmlspecialchars($p['sanksi_default']) ?>" onchange="updateSanksi()" class="w-5 h-5 text-yellow-500 border-slate-300 rounded focus:ring-yellow-500 mt-0.5">
                                    <div class="flex-1">
                                        <p class="font-bold text-slate-700 text-sm group-hover:text-yellow-700 leading-snug"><?= htmlspecialchars($p['nama_pelanggaran']) ?></p>
                                        <p class="text-xs text-yellow-600 font-extrabold mt-1"><?= $p['poin_default'] ?> Poin</p>
                                    </div>
                                </label>
                            <?php endforeach; if ($current_sub !== '') echo '</div>'; ?>
                        </div>
                    </div>

                    <div class="<?= $card_class ?> p-6 bg-slate-50/50">
                        <h4 class="font-extrabold text-slate-800 mb-4 flex items-center text-sm">
                            <svg class="w-5 h-5 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            Sanksi Tindakan
                        </h4>
                        <div id="sanksi-container" class="space-y-3"></div>
                        <p id="sanksi-empty" class="text-center text-slate-400 py-6 text-sm font-medium border border-dashed border-[#E2E8F0] rounded-xl bg-white">Pilih jenis pelanggaran di atas untuk memunculkan sanksi</p>
                    </div>

                    <button type="submit" class="<?= $btn_primary ?>">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Simpan Pelanggaran Siswa
                    </button>
                </div>
            </div>
        </form>
    </main>

<script>
// JSON Data Siswa dari PHP
const siswaData = <?= $siswa_json ?>;

// Init TomSelect Kelas
const tsKelas = new TomSelect("#select-kelas", {
    create: false,
    sortField: { field: "text", direction: "asc" }
});

// Init TomSelect Siswa (Awalnya kosong & disabled)
const tsSiswa = new TomSelect("#select-siswa", {
    create: false,
    valueField: 'id',
    labelField: 'title',
    searchField: 'title',
    sortField: { field: "title", direction: "asc" },
    placeholder: "Pilih kelas terlebih dahulu..."
});

// Logic Trigger Pilih Kelas -> Load Siswa
tsKelas.on('change', function(val) {
    tsSiswa.clear();
    tsSiswa.clearOptions();
    
    if (val && siswaData[val]) {
        const options = siswaData[val].map(s => ({
            id: s.id_anggota,
            title: s.nama_siswa + ' (' + s.no_induk + ')'
        }));
        
        tsSiswa.addOptions(options);
        tsSiswa.enable();
        tsSiswa.settings.placeholder = "Ketik Nama / No Induk Siswa...";
        tsSiswa.control_input.placeholder = "Ketik Nama / No Induk Siswa...";
    } else {
        tsSiswa.disable();
        tsSiswa.settings.placeholder = "Pilih kelas terlebih dahulu...";
        tsSiswa.control_input.placeholder = "Pilih kelas terlebih dahulu...";
    }
});

// Disable select siswa saat pertama kali load
tsSiswa.disable();

// JAM LIVE OTOMATIS (Mencegah input manual dari guru)
setInterval(() => {
    const d = new Date();
    const hrs = d.getHours().toString().padStart(2, '0');
    const mins = d.getMinutes().toString().padStart(2, '0');
    const timeStr = hrs + ':' + mins;
    
    document.getElementById('live-clock').textContent = timeStr;
    document.getElementById('waktu-input').value = timeStr;
}, 10000);

// UI TABS LOGIC
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('bg-red-600', 'bg-blue-600', 'bg-yellow-500', 'text-white', 'border-red-700', 'border-blue-700', 'border-yellow-600', 'font-extrabold');
        b.classList.add('text-slate-500', 'border-transparent', 'font-bold');
    });
    
    document.getElementById('content-' + tab).classList.remove('hidden');
    const activeBtn = document.getElementById('tab-' + tab);
    
    activeBtn.classList.remove('text-slate-500', 'border-transparent', 'font-bold');
    activeBtn.classList.add('text-white', 'font-extrabold');
    
    if(tab === 'kelakuan') activeBtn.classList.add('bg-red-600', 'border-red-700');
    if(tab === 'kerajinan') activeBtn.classList.add('bg-blue-600', 'border-blue-700');
    if(tab === 'kerapian') activeBtn.classList.add('bg-yellow-500', 'border-yellow-600');
}

// LOGIKA TAB LAMPIRAN (FILE vs LINK)
function switchAttachmentMode(mode) {
    const btnFile = document.getElementById('btn-mode-file');
    const btnLink = document.getElementById('btn-mode-link');
    const areaFile = document.getElementById('area-file');
    const areaLink = document.getElementById('area-link');
    const inputLink = document.getElementById('input-link');

    if (mode === 'file') {
        btnFile.classList.replace('text-slate-500', 'text-[#000080]');
        btnFile.classList.replace('font-bold', 'font-extrabold');
        btnFile.classList.add('bg-white', 'shadow-sm');
        
        btnLink.classList.replace('text-[#000080]', 'text-slate-500');
        btnLink.classList.replace('font-extrabold', 'font-bold');
        btnLink.classList.remove('bg-white', 'shadow-sm');

        areaFile.classList.remove('hidden');
        areaLink.classList.add('hidden');
        inputLink.value = ''; 
    } else {
        btnLink.classList.replace('text-slate-500', 'text-[#000080]');
        btnLink.classList.replace('font-bold', 'font-extrabold');
        btnLink.classList.add('bg-white', 'shadow-sm');
        
        btnFile.classList.replace('text-[#000080]', 'text-slate-500');
        btnFile.classList.replace('font-extrabold', 'font-bold');
        btnFile.classList.remove('bg-white', 'shadow-sm');

        areaLink.classList.remove('hidden');
        areaFile.classList.add('hidden');
        
        dt.items.clear();
        inputFoto.files = dt.files;
        updateFileDisplay();
    }
}

// SANCTION LOGIC
const sanksiData = <?= $sanksi_json ?>;

function updateSanksi() {
    const checked = document.querySelectorAll('input[name="pelanggaran[]"]:checked');
    const container = document.getElementById('sanksi-container');
    const empty = document.getElementById('sanksi-empty');
    let codes = new Set();
    
    checked.forEach(chk => { if(chk.dataset.sanksi) chk.dataset.sanksi.split(',').forEach(c => codes.add(c.trim())); });
    container.innerHTML = '';
    
    if (codes.size === 0) { empty.style.display = 'block'; return; }
    empty.style.display = 'none';
    
    sanksiData.forEach(s => {
        if (codes.has(s.kode_sanksi)) {
            container.innerHTML += `<label class="flex items-start space-x-3 p-3.5 border-2 border-[#000080]/20 bg-[#000080]/5 rounded-xl cursor-pointer hover:border-[#000080]/40 transition-colors"><input type="checkbox" name="sanksi[]" value="${s.id_sanksi_ref}" checked class="w-5 h-5 text-[#000080] border-slate-300 rounded focus:ring-[#000080] mt-0.5"><div class="flex-1"><p class="font-bold text-slate-800 text-sm leading-snug"><span class="text-[#000080] mr-1">${s.kode_sanksi}.</span> ${s.deskripsi}</p></div></label>`;
        }
    });
}

// MULTIPLE FILE UPLOAD LOGIC (KUMULATIF & DETEKSI EXT)
const dt = new DataTransfer(); 
const inputFoto = document.getElementById('input-foto');
const fileListContainer = document.getElementById('file-list-container');

inputFoto.addEventListener('change', function(e) {
    for(let i = 0; i < this.files.length; i++){
        if (this.files[i].size > 2 * 1024 * 1024) {
            alert(`File "${this.files[i].name}" terlalu besar! Maksimal 2MB.`);
            continue;
        }
        dt.items.add(this.files[i]);
    }
    this.files = dt.files; 
    updateFileDisplay();
});

function updateFileDisplay() {
    fileListContainer.innerHTML = '';
    const files = dt.files;
    for(let i = 0; i < files.length; i++) {
        const file = files[i];
        
        // Ganti Icon Berdasarkan Tipe File
        let iconHtml = '<svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>';
        
        if(file.type === 'application/pdf') {
            iconHtml = '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';
        } else if(file.type === 'application/msword' || file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            iconHtml = '<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';
        }

        fileListContainer.innerHTML += `
            <div class="flex items-center justify-between p-3 bg-white border border-[#E2E8F0] rounded-xl shadow-sm">
                <div class="flex items-center space-x-3 overflow-hidden">
                    <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        ${iconHtml}
                    </div>
                    <p class="text-xs font-bold text-slate-700 truncate">${file.name}</p>
                </div>
                <button type="button" onclick="removeFile(${i})" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors ml-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
        `;
    }
    
    const formFile = document.createElement('input');
    formFile.type = 'file';
    formFile.name = 'bukti_foto[]';
    formFile.multiple = true;
    formFile.files = dt.files;
    formFile.className = 'hidden';
    
    document.querySelectorAll('input[name="bukti_foto[]"]').forEach(el => el.remove());
    document.getElementById('form-pelanggaran').appendChild(formFile);
}

function removeFile(index) {
    dt.items.remove(index);
    inputFoto.files = dt.files;
    updateFileDisplay();
}

document.getElementById('form-pelanggaran').addEventListener('submit', function(e) {
    if (!document.getElementById('select-siswa').value) { e.preventDefault(); alert('⚠️ Pilih siswa dulu!'); return; }
    const checked = document.querySelectorAll('input[name="pelanggaran[]"]:checked');
    if (checked.length === 0) { e.preventDefault(); alert('⚠️ Pilih minimal 1 jenis pelanggaran!'); return; }
});
</script>

</body>
</html>