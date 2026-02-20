<?php
/**
 * SITAPSI - Manajemen Aturan (COMPLETE WITH SANKSI)
 * Tab 1: Aturan Pelanggaran & SP
 * Tab 2: Referensi Sanksi
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

// Tab aktif
$active_tab = $_GET['tab'] ?? 'pelanggaran';

// Filter kategori (untuk tab pelanggaran)
$filter_kategori = $_GET['kategori'] ?? 'all';

// Ambil daftar kategori
$kategori_list = fetchAll("SELECT id_kategori, nama_kategori FROM tb_kategori_pelanggaran ORDER BY id_kategori");

// Ambil aturan SP
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

// Query jenis pelanggaran dengan filter
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

// Ambil daftar sanksi
$sanksi_list = fetchAll("SELECT * FROM tb_sanksi_ref ORDER BY CAST(kode_sanksi AS UNSIGNED)");

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Aturan - SITAPSI</title>
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
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Manajemen Aturan</h1>
                    <p class="text-sm text-gray-500">Pengaturan pelanggaran, sanksi & threshold SP</p>
                </div>
                
                <!-- Tab Switcher -->
                <div class="flex space-x-2">
                    <a href="?tab=pelanggaran" 
                       class="px-4 py-2 rounded-lg font-medium transition-colors <?= $active_tab === 'pelanggaran' ? 'bg-navy text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        📋 Pelanggaran
                    </a>
                    <a href="?tab=sanksi" 
                       class="px-4 py-2 rounded-lg font-medium transition-colors <?= $active_tab === 'sanksi' ? 'bg-navy text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        ⚖️ Sanksi
                    </a>
                </div>
            </div>
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

            <?php if ($active_tab === 'pelanggaran'): ?>
            <!-- ============================================ -->
            <!-- TAB 1: PELANGGARAN & SP THRESHOLD -->
            <!-- ============================================ -->
            
            <!-- Aturan SP -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b font-bold text-gray-700 flex justify-between items-center">
                    <span>⚖️ Aturan Threshold Surat Peringatan</span>
                    <button onclick="document.getElementById('modal-info-sp').classList.remove('hidden')" 
                            class="text-xs bg-blue-50 text-blue-600 px-3 py-1 rounded-lg hover:bg-blue-100">
                        ℹ️ Info SP
                    </button>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php 
                        $grouped_sp = [];
                        foreach ($aturan_sp as $a) {
                            $grouped_sp[$a['nama_kategori']][] = $a;
                        }
                        
                        $colors = [
                            'KELAKUAN' => ['bg' => 'bg-red-50', 'border' => 'border-red-500', 'text' => 'text-red-700'],
                            'KERAJINAN' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-500', 'text' => 'text-blue-700'],
                            'KERAPIAN' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-500', 'text' => 'text-yellow-700']
                        ];
                        
                        foreach ($grouped_sp as $kategori => $aturan):
                            $color = $colors[$kategori] ?? ['bg' => 'bg-gray-50', 'border' => 'border-gray-500', 'text' => 'text-gray-700'];
                        ?>
                        <div class="<?= $color['bg'] ?> border-l-4 <?= $color['border'] ?> p-4 rounded-lg">
                            <h3 class="font-bold <?= $color['text'] ?> mb-3"><?= $kategori ?></h3>
                            <div class="space-y-2">
                                <?php foreach ($aturan as $a): ?>
                                <div class="bg-white p-3 rounded border flex justify-between items-center">
                                    <div>
                                        <span class="font-bold text-gray-800"><?= $a['level_sp'] ?></span>
                                        <span class="text-sm font-medium text-gray-600 ml-2">&ge; <?= $a['batas_bawah_poin'] ?> poin</span>
                                    </div>
                                    <button onclick="editAturanSP(<?= $a['id_aturan_sp'] ?>, '<?= $kategori ?>', '<?= $a['level_sp'] ?>', <?= $a['batas_bawah_poin'] ?>)"
                                            class="p-1 bg-blue-50 text-blue-600 rounded hover:bg-blue-100" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Header dengan Filter & Tombol Tambah -->
            <div class="bg-white rounded-xl shadow-sm p-6 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <label class="text-sm font-medium text-gray-700">Filter Kategori:</label>
                    <div class="flex space-x-2">
                        <a href="?tab=pelanggaran&kategori=all" 
                           class="px-4 py-2 rounded-lg font-medium transition-colors <?= $filter_kategori === 'all' ? 'bg-navy text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                            Semua
                        </a>
                        <?php foreach ($kategori_list as $k): ?>
                        <a href="?tab=pelanggaran&kategori=<?= $k['id_kategori'] ?>" 
                           class="px-4 py-2 rounded-lg font-medium transition-colors <?= $filter_kategori == $k['id_kategori'] ? 'bg-navy text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                            <?= $k['nama_kategori'] ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button onclick="document.getElementById('modal-tambah-pelanggaran').classList.remove('hidden')" 
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium flex items-center space-x-2 transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Tambah Pelanggaran</span>
                </button>
            </div>

            <!-- Daftar Jenis Pelanggaran -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b font-bold text-gray-700">
                    📋 Daftar Jenis Pelanggaran 
                    <?php if ($filter_kategori !== 'all'): ?>
                        <?php 
                        $nama_kat = '';
                        foreach ($kategori_list as $k) {
                            if ($k['id_kategori'] == $filter_kategori) {
                                $nama_kat = $k['nama_kategori'];
                                break;
                            }
                        }
                        ?>
                        <span class="text-sm text-gray-500">(Filter: <?= $nama_kat ?>)</span>
                    <?php endif; ?>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="p-4">Kategori</th>
                                <th class="p-4">Sub Kategori</th>
                                <th class="p-4">Nama Pelanggaran</th>
                                <th class="p-4">Poin</th>
                                <th class="p-4">Sanksi Default</th>
                                <th class="p-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($pelanggaran_list)): ?>
                            <tr>
                                <td colspan="6" class="p-12 text-center text-gray-500">Tidak ada data pelanggaran</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($pelanggaran_list as $p): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        <?= $p['nama_kategori'] === 'KELAKUAN' ? 'bg-red-100 text-red-800' : '' ?>
                                        <?= $p['nama_kategori'] === 'KERAJINAN' ? 'bg-blue-100 text-blue-800' : '' ?>
                                        <?= $p['nama_kategori'] === 'KERAPIAN' ? 'bg-yellow-100 text-yellow-800' : '' ?>">
                                        <?= $p['nama_kategori'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-gray-600"><?= htmlspecialchars($p['sub_kategori']) ?></td>
                                <td class="p-4 font-medium text-gray-800"><?= htmlspecialchars($p['nama_pelanggaran']) ?></td>
                                <td class="p-4">
                                    <span class="px-3 py-1 bg-gray-100 rounded-full font-bold text-gray-800">
                                        <?= $p['poin_default'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-gray-600 text-xs"><?= htmlspecialchars($p['sanksi_default']) ?></td>
                                <td class="p-4">
                                    <div class="flex space-x-2">
                                        <button onclick='editPelanggaran(<?= json_encode($p) ?>)'
                                                class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button onclick="hapusPelanggaran(<?= $p['id_jenis'] ?>)"
                                                class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100" title="Hapus">
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

            <?php else: ?>
            <!-- ============================================ -->
            <!-- TAB 2: REFERENSI SANKSI -->
            <!-- ============================================ -->
            
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b font-bold text-gray-700 flex justify-between items-center">
                    <span>⚖️ Daftar Referensi Sanksi</span>
                    <button onclick="document.getElementById('modal-tambah-sanksi').classList.remove('hidden')" 
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium flex items-center space-x-2 transition-colors shadow-sm text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Tambah Sanksi</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="p-4">Kode</th>
                                <th class="p-4">Deskripsi Sanksi</th>
                                <th class="p-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($sanksi_list)): ?>
                            <tr>
                                <td colspan="3" class="p-12 text-center text-gray-500">Belum ada data sanksi</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($sanksi_list as $s): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4">
                                    <span class="px-3 py-1 bg-navy text-white rounded-full font-bold text-sm">
                                        <?= htmlspecialchars($s['kode_sanksi']) ?>
                                    </span>
                                </td>
                                <td class="p-4 text-gray-800"><?= htmlspecialchars($s['deskripsi']) ?></td>
                                <td class="p-4">
                                    <div class="flex space-x-2">
                                        <button onclick='editSanksi(<?= json_encode($s) ?>)'
                                                class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button onclick="hapusSanksi(<?= $s['id_sanksi_ref'] ?>)"
                                                class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100" title="Hapus">
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

            <!-- Info Box -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h4 class="font-bold text-blue-800 mb-1">ℹ️ Tentang Sanksi</h4>
                        <p class="text-sm text-blue-700">
                            Sanksi digunakan sebagai referensi pada pelanggaran. Setiap pelanggaran dapat memiliki satu atau beberapa sanksi (dipisah koma pada kolom "Sanksi Default").
                            Contoh: Sanksi "1,5,7" berarti pelanggaran tersebut akan menggunakan sanksi kode 1, 5, dan 7.
                        </p>
                    </div>
                </div>
            </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<!-- ============================================ -->
<!-- MODALS: PELANGGARAN -->
<!-- ============================================ -->

<!-- Modal Tambah Pelanggaran -->
<div id="modal-tambah-pelanggaran" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-lg max-w-2xl w-full my-8">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Tambah Jenis Pelanggaran</h3>
            <button onclick="document.getElementById('modal-tambah-pelanggaran').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="../../actions/tambah_aturan.php" method="POST" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kategori *</label>
                    <select name="id_kategori" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                        <option value="">Pilih Kategori</option>
                        <?php foreach ($kategori_list as $k): ?>
                        <option value="<?= $k['id_kategori'] ?>"><?= $k['nama_kategori'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sub Kategori *</label>
                    <input type="text" name="sub_kategori" required 
                           placeholder="Contoh: 02. Sikap & Moral"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Pelanggaran *</label>
                <textarea name="nama_pelanggaran" rows="2" required
                          placeholder="Contoh: Berkata tidak sopan/kasar/jorok"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Poin Default *</label>
                    <input type="number" name="poin_default" min="1" required 
                           placeholder="Contoh: 100"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sanksi Default *</label>
                    <input type="text" name="sanksi_default" required 
                           placeholder="Contoh: 1,5,7"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                    <p class="text-xs text-gray-500 mt-1">Kode sanksi dipisah koma</p>
                </div>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="document.getElementById('modal-tambah-pelanggaran').classList.add('hidden')" 
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Batal
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-navy text-white rounded-lg hover:bg-blue-900 font-medium">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Pelanggaran - FIXED ACTION -->
<div id="modal-edit-pelanggaran" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-lg max-w-2xl w-full my-8">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Edit Jenis Pelanggaran</h3>
            <button onclick="document.getElementById('modal-edit-pelanggaran').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <!-- PERBAIKAN: Ganti action ke edit_aturan_pelanggaran.php (bukan edit_pelanggaran.php) -->
        <form action="../../actions/edit_aturan_pelanggaran.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="id_jenis" id="edit-id-jenis">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kategori *</label>
                    <select name="id_kategori" id="edit-id-kategori" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                        <?php foreach ($kategori_list as $k): ?>
                        <option value="<?= $k['id_kategori'] ?>"><?= $k['nama_kategori'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sub Kategori *</label>
                    <input type="text" name="sub_kategori" id="edit-sub-kategori" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Pelanggaran *</label>
                <textarea name="nama_pelanggaran" id="edit-nama-pelanggaran" rows="2" required
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Poin Default *</label>
                    <input type="number" name="poin_default" id="edit-poin-default" min="1" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sanksi Default *</label>
                    <input type="text" name="sanksi_default" id="edit-sanksi-default" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                </div>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="document.getElementById('modal-edit-pelanggaran').classList.add('hidden')" 
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Batal
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-navy text-white rounded-lg hover:bg-blue-900 font-medium">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Aturan SP -->
<div id="modal-edit-sp" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Edit Aturan SP</h3>
            <button onclick="document.getElementById('modal-edit-sp').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="../../actions/edit_aturan_sp.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="id_aturan_sp" id="sp-id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                <input type="text" id="sp-kategori" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Level SP</label>
                <input type="text" id="sp-level" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Batas Bawah Poin *</label>
                <input type="number" name="batas_bawah_poin" id="sp-batas" min="0" required 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="document.getElementById('modal-edit-sp').classList.add('hidden')" 
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Batal
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-navy text-white rounded-lg hover:bg-blue-900 font-medium">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- MODALS: SANKSI -->
<!-- ============================================ -->

<!-- Modal Tambah Sanksi -->
<div id="modal-tambah-sanksi" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Tambah Sanksi Baru</h3>
            <button onclick="document.getElementById('modal-tambah-sanksi').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="../../actions/tambah_sanksi.php" method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kode Sanksi *</label>
                <input type="text" name="kode_sanksi" required 
                       placeholder="Contoh: 11"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                <p class="text-xs text-gray-500 mt-1">Kode unik untuk referensi sanksi</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Sanksi *</label>
                <textarea name="deskripsi" rows="3" required
                          placeholder="Contoh: Menulis surat pernyataan bermaterai"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy"></textarea>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="document.getElementById('modal-tambah-sanksi').classList.add('hidden')" 
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Batal
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Sanksi -->
<div id="modal-edit-sanksi" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Edit Sanksi</h3>
            <button onclick="document.getElementById('modal-edit-sanksi').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="../../actions/edit_sanksi.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="id_sanksi_ref" id="sanksi-id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kode Sanksi *</label>
                <input type="text" name="kode_sanksi" id="sanksi-kode" required 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Sanksi *</label>
                <textarea name="deskripsi" id="sanksi-deskripsi" rows="3" required
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy"></textarea>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="document.getElementById('modal-edit-sanksi').classList.add('hidden')" 
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Batal
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-navy text-white rounded-lg hover:bg-blue-900 font-medium">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Info SP -->
<div id="modal-info-sp" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">ℹ️ Informasi Aturan SP</h3>
            <button onclick="document.getElementById('modal-info-sp').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <div class="prose prose-sm">
                <h4 class="font-bold text-gray-800">Cara Kerja Threshold SP:</h4>
                <ul class="list-disc pl-5 space-y-2 text-gray-700">
                    <li><strong>SP1:</strong> Siswa mendapat peringatan pertama saat poin mencapai batas bawah SP1</li>
                    <li><strong>SP2:</strong> Peringatan kedua dengan sanksi lebih berat</li>
                    <li><strong>SP3:</strong> Peringatan terakhir sebelum dikeluarkan</li>
                    <li><strong>Dikeluarkan:</strong> Siswa dikeluarkan dari sekolah</li>
                </ul>
                <div class="mt-4 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                    <p class="text-sm text-yellow-700">
                        <strong>Catatan:</strong> Setiap kategori (Kelakuan, Kerajinan, Kerapian) memiliki threshold SP yang berbeda. 
                        SP akan otomatis ter-trigger saat siswa mencapai batas poin yang ditentukan.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================
// FUNCTIONS: PELANGGARAN
// ============================================
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
    if (confirm('⚠️ Yakin ingin menghapus jenis pelanggaran ini?\n\nData transaksi yang menggunakan pelanggaran ini akan tetap tersimpan.')) {
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

// ============================================
// FUNCTIONS: SANKSI
// ============================================
function editSanksi(data) {
    document.getElementById('sanksi-id').value = data.id_sanksi_ref;
    document.getElementById('sanksi-kode').value = data.kode_sanksi;
    document.getElementById('sanksi-deskripsi').value = data.deskripsi;
    document.getElementById('modal-edit-sanksi').classList.remove('hidden');
}

function hapusSanksi(id) {
    if (confirm('⚠️ Yakin ingin menghapus sanksi ini?\n\nPastikan tidak ada pelanggaran yang masih menggunakan kode sanksi ini.')) {
        window.location.href = `../../actions/hapus_sanksi.php?id=${id}`;
    }
}
</script>

</body>
</html>