<?php
/**
 * SITAPSI - Manajemen Aturan (UI GLOBAL PORTAL)
 * Tab 1: Aturan Pelanggaran & SP
 * Tab 2: Referensi Sanksi
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$active_tab = $_GET['tab'] ?? 'pelanggaran';
$filter_kategori = $_GET['kategori'] ?? 'all';

$kategori_list = fetchAll("SELECT id_kategori, nama_kategori FROM tb_kategori_pelanggaran ORDER BY id_kategori");

$aturan_sp = fetchAll("
    SELECT 
        sp.id_aturan_sp,
        sp.level_sp,
        sp.batas_bawah_poin,
        k.nama_kategori,
        k.id_kategori
    FROM tb_aturan_sp sp
    JOIN tb_kategori_pelanggaran k ON sp.id_kategori = k.id_kategori
    ORDER BY k.id_kategori, sp.batas_bawah_poin
");

$sql_pelanggaran = "
    SELECT 
        jp.id_jenis,
        jp.nama_pelanggaran,
        jp.poin_default,
        jp.sanksi_default,
        k.nama_kategori,
        k.id_kategori,
        jp.sub_kategori
    FROM tb_jenis_pelanggaran jp
    JOIN tb_kategori_pelanggaran k ON jp.id_kategori = k.id_kategori
    WHERE 1=1
";
$params = [];
if ($filter_kategori !== 'all') {
    $sql_pelanggaran .= " AND k.id_kategori = :kategori";
    $params['kategori'] = $filter_kategori;
}
$sql_pelanggaran .= " ORDER BY k.id_kategori, jp.sub_kategori, jp.nama_pelanggaran";
$pelanggaran_list = fetchAll($sql_pelanggaran, $params);

$sanksi_list = fetchAll("SELECT * FROM tb_sanksi_ref ORDER BY CAST(kode_sanksi AS UNSIGNED)");

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$btn_primary = "px-4 py-2.5 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-md shadow-blue-900/10 hover:bg-blue-900 transition-all flex items-center justify-center";
$btn_outline = "px-4 py-2.5 bg-white border border-[#E2E8F0] text-slate-700 text-sm font-semibold rounded-lg shadow-sm hover:bg-slate-50 transition-all flex items-center justify-center";
$input_class = "w-full px-4 py-2.5 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm text-slate-700 bg-white transition-all";
$label_class = "block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wide";
$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Aturan - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 pl-16 lg:pl-6 py-4 sticky top-0 z-30 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Manajemen Aturan</h1>
                <p class="text-sm font-medium text-slate-500">Pengaturan bobot poin pelanggaran & sanksi</p>
            </div>
            
            <div class="flex space-x-2 bg-slate-100 p-1 rounded-lg">
                <a href="?tab=pelanggaran" 
                   class="px-4 py-1.5 rounded-md text-sm font-bold transition-all <?= $active_tab === 'pelanggaran' ? 'bg-white text-[#000080] shadow-sm' : 'text-slate-500 hover:text-slate-700' ?>">
                    Pelanggaran & SP
                </a>
                <a href="?tab=sanksi" 
                   class="px-4 py-1.5 rounded-md text-sm font-bold transition-all <?= $active_tab === 'sanksi' ? 'bg-white text-[#000080] shadow-sm' : 'text-slate-500 hover:text-slate-700' ?>">
                    Referensi Sanksi
                </a>
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

            <?php if ($active_tab === 'pelanggaran'): ?>
            <div class="<?= $card_class ?> overflow-hidden">
                <div class="p-4 border-b border-[#E2E8F0] bg-slate-50/50 flex justify-between items-center">
                    <span class="font-bold text-slate-800 text-sm flex items-center">
                        <svg class="w-4 h-4 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path><line x1="12" y1="11" x2="12" y2="17"></line><line x1="9" y1="14" x2="15" y2="14"></line></svg>
                        Ambang Batas SP Per Kategori
                    </span>
                    <button onclick="document.getElementById('modal-info-sp').classList.remove('hidden')" class="text-xs font-bold text-[#000080] hover:underline flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        Info System
                    </button>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php 
                        $grouped_sp = [];
                        foreach ($aturan_sp as $a) { $grouped_sp[$a['nama_kategori']][] = $a; }
                        $colors = [
                            'KELAKUAN' => ['bg' => 'bg-red-50/50', 'border' => 'border-red-200', 'text' => 'text-red-700', 'badge' => 'bg-red-100 text-red-700'],
                            'KERAJINAN' => ['bg' => 'bg-blue-50/50', 'border' => 'border-blue-200', 'text' => 'text-blue-700', 'badge' => 'bg-blue-100 text-blue-700'],
                            'KERAPIAN' => ['bg' => 'bg-yellow-50/50', 'border' => 'border-yellow-200', 'text' => 'text-yellow-700', 'badge' => 'bg-yellow-100 text-yellow-700']
                        ];
                        
                        foreach ($grouped_sp as $kategori => $aturan):
                            $color = $colors[$kategori] ?? ['bg' => 'bg-slate-50', 'border' => 'border-slate-200', 'text' => 'text-slate-700', 'badge' => 'bg-slate-200 text-slate-700'];
                        ?>
                        <div class="<?= $color['bg'] ?> border <?= $color['border'] ?> rounded-xl p-5 shadow-sm">
                            <h3 class="font-extrabold <?= $color['text'] ?> mb-4 flex items-center text-sm">
                                <span class="w-2 h-2 rounded-full mr-2 <?= $color['badge'] ?>"></span>
                                <?= $kategori ?>
                            </h3>
                            <div class="space-y-2.5">
                                <?php foreach ($aturan as $a): ?>
                                <div class="bg-white px-4 py-3 rounded-lg border border-[#E2E8F0] shadow-sm flex justify-between items-center group">
                                    <div>
                                        <span class="font-extrabold text-slate-800 text-sm"><?= $a['level_sp'] ?></span>
                                        <span class="text-xs font-bold text-slate-500 ml-2">&ge; <?= $a['batas_bawah_poin'] ?> pt</span>
                                    </div>
                                    <button onclick="editAturanSP(<?= $a['id_aturan_sp'] ?>, '<?= $kategori ?>', '<?= $a['level_sp'] ?>', <?= $a['batas_bawah_poin'] ?>)"
                                            class="text-slate-400 hover:text-[#000080] transition-colors" title="Edit Threshold">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="<?= $card_class ?> overflow-hidden">
                <div class="p-4 border-b border-[#E2E8F0] bg-slate-50/50 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                    <div class="flex items-center space-x-2 overflow-x-auto scrollbar-hide pb-2 sm:pb-0">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide mr-2">Filter Kategori:</span>
                        <a href="?tab=pelanggaran&kategori=all" class="px-3 py-1 rounded-md text-[11px] font-bold whitespace-nowrap transition-colors <?= $filter_kategori === 'all' ? 'bg-[#000080] text-white' : 'bg-white border border-[#E2E8F0] text-slate-600 hover:bg-slate-50' ?>">Semua</a>
                        <?php foreach ($kategori_list as $k): ?>
                            <a href="?tab=pelanggaran&kategori=<?= $k['id_kategori'] ?>" class="px-3 py-1 rounded-md text-[11px] font-bold whitespace-nowrap transition-colors <?= $filter_kategori == $k['id_kategori'] ? 'bg-[#000080] text-white' : 'bg-white border border-[#E2E8F0] text-slate-600 hover:bg-slate-50' ?>"><?= $k['nama_kategori'] ?></a>
                        <?php endforeach; ?>
                    </div>
                    <button onclick="document.getElementById('modal-tambah-pelanggaran').classList.remove('hidden')" class="<?= $btn_primary ?> h-8 text-[11px] px-3 w-full sm:w-auto">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Tambah Data
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-white text-xs text-slate-500 uppercase border-b border-[#E2E8F0]">
                            <tr>
                                <th class="p-4 font-bold">Kategori</th>
                                <th class="p-4 font-bold">Sub Kategori</th>
                                <th class="p-4 font-bold">Nama Pelanggaran</th>
                                <th class="p-4 font-bold text-center">Poin</th>
                                <th class="p-4 font-bold text-center">Sanksi Ref.</th>
                                <th class="p-4 font-bold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php if (empty($pelanggaran_list)): ?>
                            <tr><td colspan="6" class="p-8 text-center text-slate-400 font-medium text-sm">Tidak ada data pelanggaran</td></tr>
                            <?php else: ?>
                            <?php foreach($pelanggaran_list as $p): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                        <?= $p['nama_kategori'] === 'KELAKUAN' ? 'bg-red-50 text-red-600 border border-red-200' : '' ?>
                                        <?= $p['nama_kategori'] === 'KERAJINAN' ? 'bg-blue-50 text-blue-600 border border-blue-200' : '' ?>
                                        <?= $p['nama_kategori'] === 'KERAPIAN' ? 'bg-yellow-50 text-yellow-600 border border-yellow-200' : '' ?>">
                                        <?= $p['nama_kategori'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-xs font-bold text-slate-500"><?= htmlspecialchars($p['sub_kategori']) ?></td>
                                <td class="p-4 font-bold text-slate-800 text-[13px] whitespace-normal min-w-[200px]"><?= htmlspecialchars($p['nama_pelanggaran']) ?></td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-1 bg-red-50 text-red-600 border border-red-200 rounded-md font-bold text-[11px] shadow-sm"><?= $p['poin_default'] ?></span>
                                </td>
                                <td class="p-4 text-center font-mono text-xs font-bold text-slate-500"><?= htmlspecialchars($p['sanksi_default']) ?></td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button onclick='editPelanggaran(<?= json_encode($p) ?>)' class="p-1.5 bg-white border border-[#E2E8F0] text-slate-600 rounded-md hover:bg-slate-50 hover:text-[#000080] transition-colors shadow-sm" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        </button>
                                        <button onclick="hapusPelanggaran(<?= $p['id_jenis'] ?>)" class="p-1.5 bg-white border border-red-200 text-red-600 rounded-md hover:bg-red-50 transition-colors shadow-sm" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
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

            <?php else: ?>
            <div class="flex flex-col sm:flex-row gap-6">
                
                <div class="w-full sm:w-1/3">
                    <div class="bg-blue-50 border border-blue-200 p-5 rounded-xl shadow-sm text-sm">
                        <h4 class="font-extrabold text-[#000080] mb-2 flex items-center"><svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg> Referensi Kode Sanksi</h4>
                        <p class="text-blue-800 font-medium leading-relaxed">Sanksi ini digunakan sebagai referensi pada tabel pelanggaran. Anda bisa memasukkan lebih dari 1 kode sanksi (dipisah koma) pada kolom "Sanksi Default" di form pelanggaran.</p>
                    </div>
                </div>

                <div class="w-full sm:w-2/3 <?= $card_class ?> overflow-hidden">
                    <div class="p-4 border-b border-[#E2E8F0] bg-slate-50/50 flex justify-between items-center">
                        <span class="font-bold text-slate-800 text-sm">Daftar Kode Sanksi</span>
                        <button onclick="document.getElementById('modal-tambah-sanksi').classList.remove('hidden')" class="<?= $btn_primary ?> h-8 text-[11px] px-3">Tambah Sanksi</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-white text-xs text-slate-500 uppercase border-b border-[#E2E8F0]">
                                <tr>
                                    <th class="p-4 font-bold text-center w-24">Kode</th>
                                    <th class="p-4 font-bold">Deskripsi Tindakan Sanksi</th>
                                    <th class="p-4 font-bold text-center w-24">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E2E8F0]">
                                <?php if (empty($sanksi_list)): ?>
                                <tr><td colspan="3" class="p-8 text-center text-slate-400">Belum ada data sanksi</td></tr>
                                <?php else: ?>
                                <?php foreach($sanksi_list as $s): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="p-4 text-center">
                                        <span class="px-2.5 py-1 bg-[#000080]/10 text-[#000080] rounded-md font-extrabold text-[11px] border border-[#000080]/20 shadow-sm"><?= htmlspecialchars($s['kode_sanksi']) ?></span>
                                    </td>
                                    <td class="p-4 font-bold text-slate-700 whitespace-normal text-xs leading-relaxed"><?= htmlspecialchars($s['deskripsi']) ?></td>
                                    <td class="p-4 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <button onclick='editSanksi(<?= json_encode($s) ?>)' class="p-1.5 bg-white border border-[#E2E8F0] text-slate-600 rounded-md hover:bg-slate-50 hover:text-[#000080] transition-colors shadow-sm" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>
                                            <button onclick="hapusSanksi(<?= $s['id_sanksi_ref'] ?>)" class="p-1.5 bg-white border border-red-200 text-red-600 rounded-md hover:bg-red-50 transition-colors shadow-sm" title="Hapus"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
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
            <?php endif; ?>

        </div>
    </div>
</div>

<div id="modal-tambah-pelanggaran" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModal('modal-tambah-pelanggaran')"></div>
    <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full relative z-10 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800">Tambah Jenis Pelanggaran</h3>
            <button onclick="closeModal('modal-tambah-pelanggaran')" class="text-slate-400 hover:text-slate-600 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <form action="../../actions/tambah_aturan.php" method="POST" class="p-6 space-y-5">
            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="<?= $label_class ?>">Kategori *</label>
                    <select name="id_kategori" required class="<?= $input_class ?>">
                        <option value="">Pilih Kategori</option>
                        <?php foreach ($kategori_list as $k): ?>
                        <option value="<?= $k['id_kategori'] ?>"><?= $k['nama_kategori'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="<?= $label_class ?>">Sub Kategori *</label>
                    <input type="text" name="sub_kategori" required placeholder="Contoh: 02. Sikap & Moral" class="<?= $input_class ?>">
                </div>
            </div>
            <div>
                <label class="<?= $label_class ?>">Nama Pelanggaran *</label>
                <textarea name="nama_pelanggaran" rows="2" required placeholder="Contoh: Berkata tidak sopan/kasar" class="<?= $input_class ?>"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="<?= $label_class ?>">Poin Default *</label>
                    <input type="number" name="poin_default" min="1" required placeholder="100" class="<?= $input_class ?>">
                </div>
                <div>
                    <label class="<?= $label_class ?>">Kode Sanksi Default *</label>
                    <input type="text" name="sanksi_default" required placeholder="1,5,7" class="<?= $input_class ?>">
                    <p class="text-[10px] text-slate-500 mt-1 font-medium">Pisahkan dengan koma jika lebih dari satu</p>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('modal-tambah-pelanggaran')" class="<?= $btn_outline ?> flex-1">Batal</button>
                <button type="submit" class="<?= $btn_primary ?> flex-1">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit-pelanggaran" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModal('modal-edit-pelanggaran')"></div>
    <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full relative z-10 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800">Edit Jenis Pelanggaran</h3>
            <button onclick="closeModal('modal-edit-pelanggaran')" class="text-slate-400 hover:text-slate-600 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <form action="../../actions/edit_aturan_pelanggaran.php" method="POST" class="p-6 space-y-5">
            <input type="hidden" name="id_jenis" id="edit-id-jenis">
            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="<?= $label_class ?>">Kategori *</label>
                    <select name="id_kategori" id="edit-id-kategori" required class="<?= $input_class ?>">
                        <?php foreach ($kategori_list as $k): ?>
                        <option value="<?= $k['id_kategori'] ?>"><?= $k['nama_kategori'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="<?= $label_class ?>">Sub Kategori *</label>
                    <input type="text" name="sub_kategori" id="edit-sub-kategori" required class="<?= $input_class ?>">
                </div>
            </div>
            <div>
                <label class="<?= $label_class ?>">Nama Pelanggaran *</label>
                <textarea name="nama_pelanggaran" id="edit-nama-pelanggaran" rows="2" required class="<?= $input_class ?>"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="<?= $label_class ?>">Poin Default *</label>
                    <input type="number" name="poin_default" id="edit-poin-default" min="1" required class="<?= $input_class ?>">
                </div>
                <div>
                    <label class="<?= $label_class ?>">Kode Sanksi Default *</label>
                    <input type="text" name="sanksi_default" id="edit-sanksi-default" required class="<?= $input_class ?>">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('modal-edit-pelanggaran')" class="<?= $btn_outline ?> flex-1">Batal</button>
                <button type="submit" class="<?= $btn_primary ?> flex-1">Update Data</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit-sp" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModal('modal-edit-sp')"></div>
    <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full relative z-10 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800">Edit Batas SP</h3>
            <button onclick="closeModal('modal-edit-sp')" class="text-slate-400 hover:text-slate-600 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <form action="../../actions/edit_aturan_sp.php" method="POST" class="p-6 space-y-5">
            <input type="hidden" name="id_aturan_sp" id="sp-id">
            <div>
                <label class="<?= $label_class ?>">Kategori</label>
                <input type="text" id="sp-kategori" readonly class="<?= $input_class ?> bg-slate-50 font-bold text-slate-500 cursor-not-allowed">
            </div>
            <div>
                <label class="<?= $label_class ?>">Level Surat Peringatan (SP)</label>
                <input type="text" id="sp-level" readonly class="<?= $input_class ?> bg-slate-50 font-bold text-slate-500 cursor-not-allowed">
            </div>
            <div>
                <label class="<?= $label_class ?>">Ambang Batas Poin (Minimal) *</label>
                <input type="number" name="batas_bawah_poin" id="sp-batas" min="0" required class="<?= $input_class ?> border-[#000080]/30 focus:border-[#000080]">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('modal-edit-sp')" class="<?= $btn_outline ?> flex-1">Batal</button>
                <button type="submit" class="<?= $btn_primary ?> flex-1">Update</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-info-sp" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModal('modal-info-sp')"></div>
    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full relative z-10 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800 flex items-center"><svg class="w-5 h-5 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg> Informasi Sistem SP</h3>
            <button onclick="closeModal('modal-info-sp')" class="text-slate-400 hover:text-slate-600 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <div class="p-6">
            <div class="text-sm text-slate-700 leading-relaxed mb-4">
                <p class="mb-2">Sistem Surat Peringatan (SP) di SITAPSI bekerja secara otomatis (Trigger) berdasarkan akumulasi poin siswa di <strong>masing-masing kategori</strong>.</p>
                <ul class="list-disc pl-5 space-y-1 font-bold text-slate-800">
                    <li>SP1 : Peringatan Tahap 1</li>
                    <li>SP2 : Peringatan Tahap 2</li>
                    <li>SP3 : Peringatan Tahap Akhir</li>
                    <li class="text-red-600">Dikeluarkan : Sanksi Maksimal</li>
                </ul>
            </div>
            <div class="bg-amber-50 border border-amber-200 p-4 rounded-xl shadow-sm">
                <p class="text-[11px] text-amber-800 font-medium"><strong>Catatan Penting:</strong> Jika seorang siswa mencapai ambang batas SP2 di kategori Kelakuan, maka ia akan menerima SP2 meskipun di kategori Kerajinan poinnya masih 0.</p>
            </div>
        </div>
        <div class="p-5 border-t border-[#E2E8F0] flex justify-end">
            <button onclick="closeModal('modal-info-sp')" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-lg font-bold hover:bg-slate-200 transition-colors text-sm">Paham</button>
        </div>
    </div>
</div>

<div id="modal-tambah-sanksi" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModal('modal-tambah-sanksi')"></div>
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full relative z-10 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800">Tambah Sanksi Baru</h3>
            <button onclick="closeModal('modal-tambah-sanksi')" class="text-slate-400 hover:text-slate-600 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <form action="../../actions/tambah_sanksi.php" method="POST" class="p-6 space-y-5">
            <div>
                <label class="<?= $label_class ?>">Kode Sanksi (Angka) *</label>
                <input type="text" name="kode_sanksi" required placeholder="Contoh: 11" class="<?= $input_class ?>">
            </div>
            <div>
                <label class="<?= $label_class ?>">Deskripsi Tindakan Sanksi *</label>
                <textarea name="deskripsi" rows="3" required placeholder="Contoh: Menulis surat pernyataan bermaterai" class="<?= $input_class ?>"></textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('modal-tambah-sanksi')" class="<?= $btn_outline ?> flex-1">Batal</button>
                <button type="submit" class="<?= $btn_primary ?> flex-1">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit-sanksi" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModal('modal-edit-sanksi')"></div>
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full relative z-10 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800">Edit Data Sanksi</h3>
            <button onclick="closeModal('modal-edit-sanksi')" class="text-slate-400 hover:text-slate-600 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <form action="../../actions/edit_sanksi.php" method="POST" class="p-6 space-y-5">
            <input type="hidden" name="id_sanksi_ref" id="sanksi-id">
            <div>
                <label class="<?= $label_class ?>">Kode Sanksi *</label>
                <input type="text" name="kode_sanksi" id="sanksi-kode" required class="<?= $input_class ?>">
            </div>
            <div>
                <label class="<?= $label_class ?>">Deskripsi Tindakan Sanksi *</label>
                <textarea name="deskripsi" id="sanksi-deskripsi" rows="3" required class="<?= $input_class ?>"></textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('modal-edit-sanksi')" class="<?= $btn_outline ?> flex-1">Batal</button>
                <button type="submit" class="<?= $btn_primary ?> flex-1">Update Data</button>
            </div>
        </form>
    </div>
</div>

<script>
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

// Pelanggaran & SP
function editPelanggaran(data) {
    document.getElementById('edit-id-jenis').value = data.id_jenis;
    document.getElementById('edit-id-kategori').value = data.id_kategori;
    document.getElementById('edit-sub-kategori').value = data.sub_kategori;
    document.getElementById('edit-nama-pelanggaran').value = data.nama_pelanggaran;
    document.getElementById('edit-poin-default').value = data.poin_default;
    document.getElementById('edit-sanksi-default').value = data.sanksi_default;
    document.getElementById('modal-edit-pelanggaran').classList.remove('hidden');
}

function hapusPelanggaran(id) {
    if (confirm('⚠️ Yakin ingin menghapus jenis pelanggaran ini?\n\nData transaksi lama yang menggunakan pelanggaran ini akan tetap aman/tersimpan.')) {
        window.location.href = `../../actions/hapus_aturan.php?id=${id}`;
    }
}

function editAturanSP(id, kategori, level, batas) {
    document.getElementById('sp-id').value = id;
    document.getElementById('sp-kategori').value = kategori;
    document.getElementById('sp-level').value = level;
    document.getElementById('sp-batas').value = batas;
    document.getElementById('modal-edit-sp').classList.remove('hidden');
}

// Sanksi
function editSanksi(data) {
    document.getElementById('sanksi-id').value = data.id_sanksi_ref;
    document.getElementById('sanksi-kode').value = data.kode_sanksi;
    document.getElementById('sanksi-deskripsi').value = data.deskripsi;
    document.getElementById('modal-edit-sanksi').classList.remove('hidden');
}

function hapusSanksi(id) {
    if (confirm('⚠️ Yakin ingin menghapus sanksi ini?\n\nPastikan tidak ada pelanggaran yang masih bergantung pada kode sanksi ini.')) {
        window.location.href = `../../actions/hapus_sanksi.php?id=${id}`;
    }
}
</script>

</body>
</html>