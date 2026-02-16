<?php
/**
 * SITAPSI - Edit Transaksi Pelanggaran
 * Edit pelanggaran dari Audit Harian dengan sinkronisasi poin otomatis
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_transaksi = $_GET['id'] ?? null;

if (!$id_transaksi) {
    $_SESSION['error_message'] = '❌ ID transaksi tidak valid';
    header('Location: audit_harian.php');
    exit;
}

// Ambil data transaksi header
$transaksi = fetchOne("
    SELECT 
        h.*,
        s.nis,
        s.nama_siswa,
        k.nama_kelas,
        a.id_anggota
    FROM tb_pelanggaran_header h
    JOIN tb_anggota_kelas a ON h.id_anggota = a.id_anggota
    JOIN tb_siswa s ON a.nis = s.nis
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE h.id_transaksi = :id
", ['id' => $id_transaksi]);

if (!$transaksi) {
    $_SESSION['error_message'] = '❌ Transaksi tidak ditemukan';
    header('Location: audit_harian.php');
    exit;
}

// Ambil detail pelanggaran yang sudah dipilih
$detail_pelanggaran = fetchAll("
    SELECT d.id_jenis, d.poin_saat_itu
    FROM tb_pelanggaran_detail d
    WHERE d.id_transaksi = :id
", ['id' => $id_transaksi]);

$selected_pelanggaran = array_column($detail_pelanggaran, 'id_jenis');

// Ambil sanksi yang sudah dipilih
$detail_sanksi = fetchAll("
    SELECT s.id_sanksi_ref
    FROM tb_pelanggaran_sanksi s
    WHERE s.id_transaksi = :id
", ['id' => $id_transaksi]);

$selected_sanksi = array_column($detail_sanksi, 'id_sanksi_ref');

// Ambil daftar pelanggaran per kategori
$pelanggaran_kelakuan = fetchAll("
    SELECT id_jenis, sub_kategori, nama_pelanggaran, poin_default, sanksi_default
    FROM tb_jenis_pelanggaran
    WHERE id_kategori = 1
    ORDER BY sub_kategori, nama_pelanggaran
");

$pelanggaran_kerajinan = fetchAll("
    SELECT id_jenis, sub_kategori, nama_pelanggaran, poin_default, sanksi_default
    FROM tb_jenis_pelanggaran
    WHERE id_kategori = 2
    ORDER BY sub_kategori, nama_pelanggaran
");

$pelanggaran_kerapian = fetchAll("
    SELECT id_jenis, sub_kategori, nama_pelanggaran, poin_default, sanksi_default
    FROM tb_jenis_pelanggaran
    WHERE id_kategori = 3
    ORDER BY sub_kategori, nama_pelanggaran
");

$sanksi_list = fetchAll("SELECT * FROM tb_sanksi_ref ORDER BY CAST(kode_sanksi AS UNSIGNED)");
$sanksi_json = json_encode($sanksi_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pelanggaran - SITAPSI</title>
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
        
        <div class="bg-white shadow-sm border-b px-6 py-4 sticky top-0 z-30 flex items-center space-x-4">
            <a href="audit_harian.php" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Edit Transaksi Pelanggaran</h1>
                <p class="text-sm text-gray-500">ID Transaksi: #<?= $id_transaksi ?></p>
            </div>
        </div>

        <div class="p-6">
            
            <!-- Info Siswa -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border-l-4 border-navy">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-navy rounded-full flex items-center justify-center text-white font-bold text-2xl">
                        <?= strtoupper(substr($transaksi['nama_siswa'], 0, 1)) ?>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($transaksi['nama_siswa']) ?></h3>
                        <p class="text-sm text-gray-600"><?= $transaksi['nama_kelas'] ?> • <?= $transaksi['nis'] ?></p>
                        <p class="text-xs text-gray-500 mt-1">
                            <?= date('d F Y', strtotime($transaksi['tanggal'])) ?> - <?= substr($transaksi['waktu'], 0, 5) ?> 
                            • <?= $transaksi['tipe_form'] ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Form Edit -->
            <form action="../../actions/update_pelanggaran.php" method="POST" class="space-y-6">
                <input type="hidden" name="id_transaksi" value="<?= $id_transaksi ?>">
                <input type="hidden" name="id_anggota" value="<?= $transaksi['id_anggota'] ?>">
                
                <!-- Tab Kategori -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="flex border-b border-gray-200 overflow-x-auto">
                        <button type="button" onclick="switchCategory('kelakuan')" id="tab-kelakuan" 
                                class="category-tab flex-1 py-4 px-4 font-bold text-sm text-center transition-colors bg-red-600 text-white">
                            🚨 KELAKUAN
                        </button>
                        <button type="button" onclick="switchCategory('kerajinan')" id="tab-kerajinan" 
                                class="category-tab flex-1 py-4 px-4 font-bold text-sm text-center transition-colors bg-gray-100 text-gray-600 hover:bg-gray-200">
                            📘 KERAJINAN
                        </button>
                        <button type="button" onclick="switchCategory('kerapian')" id="tab-kerapian" 
                                class="category-tab flex-1 py-4 px-4 font-bold text-sm text-center transition-colors bg-gray-100 text-gray-600 hover:bg-gray-200">
                            👔 KERAPIAN
                        </button>
                    </div>

                    <!-- Content Kelakuan -->
                    <div id="content-kelakuan" class="category-content p-6">
                        <?php $current_sub = ''; foreach ($pelanggaran_kelakuan as $p): 
                            if ($current_sub !== $p['sub_kategori']): 
                                if ($current_sub !== '') echo '</div>'; 
                                $current_sub = $p['sub_kategori']; 
                        ?>
                        <div class="mb-5"><h5 class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide border-b pb-1"><?= htmlspecialchars($p['sub_kategori']) ?></h5>
                        <?php endif; ?>
                            <label class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-red-50 cursor-pointer mb-2 transition-all hover:border-red-600 group">
                                <input type="checkbox" name="pelanggaran[]" value="<?= $p['id_jenis'] ?>" 
                                       data-poin="<?= $p['poin_default'] ?>" 
                                       data-sanksi="<?= htmlspecialchars($p['sanksi_default']) ?>" 
                                       onchange="updateSanksi()"
                                       <?= in_array($p['id_jenis'], $selected_pelanggaran) ? 'checked' : '' ?>
                                       class="w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-red-600 mt-1">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-800 text-sm group-hover:text-red-600"><?= htmlspecialchars($p['nama_pelanggaran']) ?></p>
                                    <p class="text-xs text-red-600 font-bold mt-1"><?= $p['poin_default'] ?> Poin</p>
                                </div>
                            </label>
                        <?php endforeach; if ($current_sub !== '') echo '</div>'; ?>
                    </div>

                    <!-- Content Kerajinan -->
                    <div id="content-kerajinan" class="category-content p-6 hidden">
                        <?php $current_sub = ''; foreach ($pelanggaran_kerajinan as $p): 
                            if ($current_sub !== $p['sub_kategori']): 
                                if ($current_sub !== '') echo '</div>'; 
                                $current_sub = $p['sub_kategori']; 
                        ?>
                        <div class="mb-5"><h5 class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide border-b pb-1"><?= htmlspecialchars($p['sub_kategori']) ?></h5>
                        <?php endif; ?>
                            <label class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-blue-50 cursor-pointer mb-2 transition-all hover:border-blue-600 group">
                                <input type="checkbox" name="pelanggaran[]" value="<?= $p['id_jenis'] ?>" 
                                       data-poin="<?= $p['poin_default'] ?>" 
                                       data-sanksi="<?= htmlspecialchars($p['sanksi_default']) ?>" 
                                       onchange="updateSanksi()"
                                       <?= in_array($p['id_jenis'], $selected_pelanggaran) ? 'checked' : '' ?>
                                       class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-600 mt-1">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-800 text-sm group-hover:text-blue-600"><?= htmlspecialchars($p['nama_pelanggaran']) ?></p>
                                    <p class="text-xs text-blue-600 font-bold mt-1"><?= $p['poin_default'] ?> Poin</p>
                                </div>
                            </label>
                        <?php endforeach; if ($current_sub !== '') echo '</div>'; ?>
                    </div>

                    <!-- Content Kerapian -->
                    <div id="content-kerapian" class="category-content p-6 hidden">
                        <?php $current_sub = ''; foreach ($pelanggaran_kerapian as $p): 
                            if ($current_sub !== $p['sub_kategori']): 
                                if ($current_sub !== '') echo '</div>'; 
                                $current_sub = $p['sub_kategori']; 
                        ?>
                        <div class="mb-5"><h5 class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide border-b pb-1"><?= htmlspecialchars($p['sub_kategori']) ?></h5>
                        <?php endif; ?>
                            <label class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-yellow-50 cursor-pointer mb-2 transition-all hover:border-yellow-600 group">
                                <input type="checkbox" name="pelanggaran[]" value="<?= $p['id_jenis'] ?>" 
                                       data-poin="<?= $p['poin_default'] ?>" 
                                       data-sanksi="<?= htmlspecialchars($p['sanksi_default']) ?>" 
                                       onchange="updateSanksi()"
                                       <?= in_array($p['id_jenis'], $selected_pelanggaran) ? 'checked' : '' ?>
                                       class="w-5 h-5 text-yellow-600 border-gray-300 rounded focus:ring-yellow-600 mt-1">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-800 text-sm group-hover:text-yellow-600"><?= htmlspecialchars($p['nama_pelanggaran']) ?></p>
                                    <p class="text-xs text-yellow-600 font-bold mt-1"><?= $p['poin_default'] ?> Poin</p>
                                </div>
                            </label>
                        <?php endforeach; if ($current_sub !== '') echo '</div>'; ?>
                    </div>
                </div>

                <!-- Sanksi -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h4 class="font-bold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Sanksi yang Diberikan:
                    </h4>
                    <div id="sanksi-container" class="space-y-2"></div>
                    <p id="sanksi-empty" class="text-center text-gray-400 py-4 italic bg-gray-50 rounded-lg" style="display: none;">Pilih pelanggaran untuk melihat sanksi</p>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-4">
                    <a href="audit_harian.php" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium text-center">
                        Batal
                    </a>
                    <button type="submit" class="flex-1 px-6 py-3 bg-navy text-white rounded-lg hover:bg-blue-900 font-medium">
                        💾 Simpan Perubahan
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>

<script>
const sanksiData = <?= $sanksi_json ?>;
const selectedSanksiDefault = <?= json_encode($selected_sanksi) ?>;

function switchCategory(category) {
    document.querySelectorAll('.category-content').forEach(content => content.classList.add('hidden'));
    document.querySelectorAll('.category-tab').forEach(tab => {
        tab.classList.remove('bg-red-600', 'bg-blue-600', 'bg-yellow-600', 'text-white');
        tab.classList.add('bg-gray-100', 'text-gray-600');
    });
    
    document.getElementById('content-' + category).classList.remove('hidden');
    const activeTab = document.getElementById('tab-' + category);
    activeTab.classList.remove('bg-gray-100', 'text-gray-600');
    activeTab.classList.add('text-white');
    
    if (category === 'kelakuan') activeTab.classList.add('bg-red-600');
    else if (category === 'kerajinan') activeTab.classList.add('bg-blue-600');
    else activeTab.classList.add('bg-yellow-600');
}

function updateSanksi() {
    const checked = document.querySelectorAll('input[name="pelanggaran[]"]:checked');
    const container = document.getElementById('sanksi-container');
    const empty = document.getElementById('sanksi-empty');
    let codes = new Set();
    
    checked.forEach(chk => { 
        if(chk.dataset.sanksi) {
            chk.dataset.sanksi.split(',').forEach(c => codes.add(c.trim())); 
        }
    });
    
    container.innerHTML = '';
    
    if (codes.size === 0) { 
        empty.style.display = 'block'; 
        return; 
    }
    
    empty.style.display = 'none';
    
    sanksiData.forEach(s => {
        if (codes.has(s.kode_sanksi)) {
            const isChecked = selectedSanksiDefault.includes(s.id_sanksi_ref);
            container.innerHTML += `
                <label class="flex items-start space-x-3 p-3 border-2 border-navy bg-blue-50 rounded-lg cursor-pointer">
                    <input type="checkbox" name="sanksi[]" value="${s.id_sanksi_ref}" ${isChecked ? 'checked' : ''} 
                           class="w-5 h-5 text-navy border-gray-300 rounded focus:ring-navy mt-1">
                    <div class="flex-1">
                        <p class="font-medium text-gray-800 text-sm">${s.kode_sanksi}. ${s.deskripsi}</p>
                    </div>
                </label>
            `;
        }
    });
}

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    updateSanksi();
});
</script>

</body>
</html>