<?php
/**
 * SITAPSI - Input Pelanggaran (Multi-Upload Cumulative Fix)
 * Fitur: Input Poin, Smart Sanction, Upload Foto Bertahap (Tidak Reset)
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireGuru();

$user = getCurrentUser();
$mode = $_GET['mode'] ?? 'piket';

// Ambil tahun ajaran aktif
$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

if (!$tahun_aktif) {
    die("Error: Tidak ada tahun ajaran aktif. Hubungi admin.");
}

// Ambil daftar siswa
$siswa_list = fetchAll("
    SELECT s.nis, s.nama_siswa, s.foto_profil, k.nama_kelas, a.id_anggota
    FROM tb_siswa s
    JOIN tb_anggota_kelas a ON s.nis = a.nis
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE s.status_aktif = 'Aktif' 
    AND a.id_tahun = :id_tahun
    ORDER BY k.nama_kelas, s.nama_siswa
", ['id_tahun' => $tahun_aktif['id_tahun']]);

// Ambil daftar jenis pelanggaran per kategori
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

$sanksi_list = fetchAll("SELECT id_sanksi_ref, kode_sanksi, deskripsi FROM tb_sanksi_ref ORDER BY CAST(kode_sanksi AS UNSIGNED)");
$sanksi_json = json_encode($sanksi_list);

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Pelanggaran - SITAPSI</title>
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
<body class="bg-gray-50 pb-24 md:pb-10">

<?php include '../../includes/navbar_guru.php'; ?>

<div class="container mx-auto px-4 py-6">
    
    <?php if ($success): ?>
    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded shadow-sm flex items-center">
        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        <p class="text-green-700 font-medium"><?= htmlspecialchars($success) ?></p>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm flex items-center">
        <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
        <p class="text-red-700 font-medium"><?= htmlspecialchars($error) ?></p>
    </div>
    <?php endif; ?>

    <div class="mb-6">
        <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-bold text-gray-800">Mode Input</h2>
                <span class="text-xs px-2 py-1 bg-blue-50 text-navy rounded-md font-medium border border-blue-100">
                    <?= $tahun_aktif['nama_tahun'] ?>
                </span>
            </div>
            <div class="flex space-x-2">
                <a href="?mode=piket" class="flex-1 py-3 px-4 text-center font-semibold rounded-lg transition-all border <?= $mode === 'piket' ? 'bg-navy text-white border-navy shadow-md' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' ?>">📋 Mode Piket</a>
                <a href="?mode=kelas" class="flex-1 py-3 px-4 text-center font-semibold rounded-lg transition-all border <?= $mode === 'kelas' ? 'bg-navy text-white border-navy shadow-md' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' ?>">📚 Mode Kelas</a>
            </div>
        </div>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        
        <div class="w-full md:w-4/12">
            <div class="bg-white rounded-lg shadow sticky top-24 border border-gray-200">
                <div class="p-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                    <h3 class="font-bold text-gray-800 mb-2">🔍 Cari Siswa</h3>
                    <div class="relative">
                        <input type="text" id="search-siswa" placeholder="Ketik nama atau kelas..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent outline-none">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
                
                <div id="siswa-list" class="overflow-y-auto bg-white rounded-b-lg" style="max-height: 500px;">
                    <?php foreach ($siswa_list as $siswa): ?>
                    <div class="siswa-item p-4 border-b border-gray-100 hover:bg-blue-50 cursor-pointer transition-colors group"
                         data-id="<?= $siswa['id_anggota'] ?>"
                         data-nis="<?= $siswa['nis'] ?>"
                         data-nama="<?= htmlspecialchars($siswa['nama_siswa']) ?>"
                         data-kelas="<?= htmlspecialchars($siswa['nama_kelas']) ?>"
                         onclick="selectSiswa(this)">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center overflow-hidden border border-gray-300 group-hover:border-navy">
                                <?php if($siswa['foto_profil']): ?>
                                    <img src="../../assets/uploads/siswa/<?= $siswa['foto_profil'] ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="text-gray-500 font-bold text-sm"><?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800 text-sm group-hover:text-navy"><?= htmlspecialchars($siswa['nama_siswa']) ?></p>
                                <p class="text-xs text-gray-500"><?= $siswa['nama_kelas'] ?> • <?= $siswa['nis'] ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="w-full md:w-8/12">
            <form id="form-pelanggaran" action="../../actions/simpan_pelanggaran.php" method="POST" enctype="multipart/form-data">
                
                <input type="hidden" name="id_anggota" id="input-id-anggota">
                <input type="hidden" name="tipe_form" value="<?= $mode === 'piket' ? 'Piket' : 'Kelas' ?>">
                <input type="hidden" name="id_tahun" value="<?= $tahun_aktif['id_tahun'] ?>">
                <input type="hidden" name="semester" value="<?= $tahun_aktif['semester_aktif'] ?>">
                
                <div id="selected-siswa-info" class="bg-white rounded-lg shadow p-4 md:p-6 mb-6 hidden border-l-4 border-navy">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center border-2 border-white shadow">
                            <span id="selected-initial" class="text-gray-600 font-bold text-2xl"></span>
                        </div>
                        <div class="flex-1">
                            <h3 id="selected-nama" class="text-lg md:text-xl font-bold text-gray-800"></h3>
                            <p id="selected-detail" class="text-sm text-gray-600"></p>
                        </div>
                        <button type="button" onclick="clearSelection()" class="p-2 bg-red-50 text-red-500 rounded-lg hover:bg-red-100 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </div>

                <div id="form-content" class="hidden space-y-6">
                    
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="flex border-b border-gray-200 overflow-x-auto">
                            <button type="button" onclick="switchCategory('kelakuan')" id="tab-kelakuan" class="category-tab flex-1 py-4 px-4 font-bold text-sm md:text-base text-center transition-colors bg-kelakuan text-white whitespace-nowrap">🚨 KELAKUAN</button>
                            <button type="button" onclick="switchCategory('kerajinan')" id="tab-kerajinan" class="category-tab flex-1 py-4 px-4 font-bold text-sm md:text-base text-center transition-colors bg-gray-100 text-gray-600 whitespace-nowrap hover:bg-gray-200">📘 KERAJINAN</button>
                            <button type="button" onclick="switchCategory('kerapian')" id="tab-kerapian" class="category-tab flex-1 py-4 px-4 font-bold text-sm md:text-base text-center transition-colors bg-gray-100 text-gray-600 whitespace-nowrap hover:bg-gray-200">👔 KERAPIAN</button>
                        </div>

                        <div id="content-kelakuan" class="category-content p-4 md:p-6">
                            <?php $current_sub = ''; foreach ($pelanggaran_kelakuan as $p): 
                                if ($current_sub !== $p['sub_kategori']): if ($current_sub !== '') echo '</div>'; $current_sub = $p['sub_kategori']; ?>
                            <div class="mb-5"><h5 class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide border-b pb-1"><?= htmlspecialchars($p['sub_kategori']) ?></h5>
                            <?php endif; ?>
                                <label class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-red-50 cursor-pointer mb-2 transition-all hover:border-kelakuan group">
                                    <input type="checkbox" name="pelanggaran[]" value="<?= $p['id_jenis'] ?>" data-poin="<?= $p['poin_default'] ?>" data-sanksi="<?= htmlspecialchars($p['sanksi_default']) ?>" onchange="updateSanksi()" class="w-5 h-5 text-kelakuan border-gray-300 rounded focus:ring-kelakuan mt-1">
                                    <div class="flex-1"><p class="font-medium text-gray-800 text-sm md:text-base group-hover:text-kelakuan"><?= htmlspecialchars($p['nama_pelanggaran']) ?></p><p class="text-xs text-red-600 font-bold mt-1"><?= $p['poin_default'] ?> Poin</p></div>
                                </label>
                            <?php endforeach; if ($current_sub !== '') echo '</div>'; ?>
                        </div>

                        <div id="content-kerajinan" class="category-content p-4 md:p-6 hidden">
                            <?php $current_sub = ''; foreach ($pelanggaran_kerajinan as $p): 
                                if ($current_sub !== $p['sub_kategori']): if ($current_sub !== '') echo '</div>'; $current_sub = $p['sub_kategori']; ?>
                            <div class="mb-5"><h5 class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide border-b pb-1"><?= htmlspecialchars($p['sub_kategori']) ?></h5>
                            <?php endif; ?>
                                <label class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-blue-50 cursor-pointer mb-2 transition-all hover:border-kerajinan group">
                                    <input type="checkbox" name="pelanggaran[]" value="<?= $p['id_jenis'] ?>" data-poin="<?= $p['poin_default'] ?>" data-sanksi="<?= htmlspecialchars($p['sanksi_default']) ?>" onchange="updateSanksi()" class="w-5 h-5 text-kerajinan border-gray-300 rounded focus:ring-kerajinan mt-1">
                                    <div class="flex-1"><p class="font-medium text-gray-800 text-sm md:text-base group-hover:text-kerajinan"><?= htmlspecialchars($p['nama_pelanggaran']) ?></p><p class="text-xs text-blue-600 font-bold mt-1"><?= $p['poin_default'] ?> Poin</p></div>
                                </label>
                            <?php endforeach; if ($current_sub !== '') echo '</div>'; ?>
                        </div>

                        <div id="content-kerapian" class="category-content p-4 md:p-6 hidden">
                            <?php $current_sub = ''; foreach ($pelanggaran_kerapian as $p): 
                                if ($current_sub !== $p['sub_kategori']): if ($current_sub !== '') echo '</div>'; $current_sub = $p['sub_kategori']; ?>
                            <div class="mb-5"><h5 class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide border-b pb-1"><?= htmlspecialchars($p['sub_kategori']) ?></h5>
                            <?php endif; ?>
                                <label class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-yellow-50 cursor-pointer mb-2 transition-all hover:border-kerapian group">
                                    <input type="checkbox" name="pelanggaran[]" value="<?= $p['id_jenis'] ?>" data-poin="<?= $p['poin_default'] ?>" data-sanksi="<?= htmlspecialchars($p['sanksi_default']) ?>" onchange="updateSanksi()" class="w-5 h-5 text-kerapian border-gray-300 rounded focus:ring-kerapian mt-1">
                                    <div class="flex-1"><p class="font-medium text-gray-800 text-sm md:text-base group-hover:text-kerapian"><?= htmlspecialchars($p['nama_pelanggaran']) ?></p><p class="text-xs text-yellow-600 font-bold mt-1"><?= $p['poin_default'] ?> Poin</p></div>
                                </label>
                            <?php endforeach; if ($current_sub !== '') echo '</div>'; ?>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4 md:p-6">
                        <h4 class="font-bold text-gray-800 mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            Sanksi yang Diberikan:
                        </h4>
                        <div id="sanksi-container" class="space-y-2"></div>
                        <p id="sanksi-empty" class="text-center text-gray-400 py-4 italic bg-gray-50 rounded-lg">Pilih pelanggaran di atas untuk melihat sanksi</p>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4 md:p-6 mb-20 md:mb-0">
                        <h4 class="font-bold text-gray-800 mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Bukti Foto (Bisa Banyak)
                        </h4>
                        
                        <label class="block w-full cursor-pointer group">
                            <div class="flex items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg group-hover:bg-blue-50 group-hover:border-navy transition-colors">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-3 text-gray-400 group-hover:text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                    <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Klik untuk Tambah Foto</span></p>
                                    <p class="text-xs text-gray-400">Pilih foto satu-satu atau sekaligus</p>
                                </div>
                            </div>
                            <input type="file" id="file-upload" name="bukti_foto[]" accept="image/*" class="hidden" multiple onchange="handleFileSelect(this)">
                        </label>
                        
                        <div id="file-list" class="mt-4 space-y-2 hidden"></div>
                    </div>

                    <div class="fixed md:relative bottom-16 md:bottom-0 left-0 right-0 p-4 md:p-0 bg-white/90 backdrop-blur-sm md:bg-transparent shadow-t-lg md:shadow-none z-30">
                        <button type="submit" class="w-full bg-navy hover:bg-blue-900 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition-transform active:scale-95 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                            Simpan Data
                        </button>
                    </div>

                </div>

                <div id="empty-state" class="bg-white rounded-lg shadow p-12 text-center border border-gray-200">
                    <svg class="w-20 h-20 mx-auto text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Pilih Siswa Terlebih Dahulu</h3>
                    <p class="text-gray-500">Cari nama siswa di panel sebelah kiri untuk memulai.</p>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
const sanksiData = <?= $sanksi_json ?>;

// === LOGIKA UPLOAD FOTO (FIXED: AKUMULATIF) ===
// Kita gunakan DataTransfer sebagai "Keranjang" file
const fileStorage = new DataTransfer();

function handleFileSelect(input) {
    const files = input.files;
    
    // 1. Masukkan file baru ke keranjang
    for (let i = 0; i < files.length; i++) {
        fileStorage.items.add(files[i]);
    }

    // 2. Update input asli dengan isi keranjang
    input.files = fileStorage.files;

    // 3. Tampilkan list terbaru
    renderFileList();
}

function renderFileList() {
    const fileList = document.getElementById('file-list');
    const input = document.getElementById('file-upload');
    
    fileList.innerHTML = ''; // Reset tampilan

    if (fileStorage.files.length > 0) {
        fileList.classList.remove('hidden');

        // Loop isi keranjang untuk ditampilkan
        for (let i = 0; i < fileStorage.files.length; i++) {
            const file = fileStorage.files[i];
            
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between text-sm bg-blue-50 p-3 rounded border border-blue-100';
            
            div.innerHTML = `
                <div class="flex items-center overflow-hidden">
                    <svg class="w-5 h-5 mr-2 text-navy flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span class="font-medium text-navy truncate">${file.name}</span>
                </div>
                <button type="button" onclick="removeFile(${i})" class="text-red-500 hover:text-red-700 ml-2 p-1 rounded hover:bg-red-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            `;
            fileList.appendChild(div);
        }
    } else {
        fileList.classList.add('hidden');
    }
}

function removeFile(index) {
    // Hapus file dari keranjang berdasarkan index
    fileStorage.items.remove(index);
    
    // Update input asli
    document.getElementById('file-upload').files = fileStorage.files;
    
    // Render ulang
    renderFileList();
}
// ============================================

document.getElementById('search-siswa').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const items = document.querySelectorAll('.siswa-item');
    items.forEach(item => {
        const nama = item.dataset.nama.toLowerCase();
        const kelas = item.dataset.kelas.toLowerCase();
        item.style.display = (nama.includes(search) || kelas.includes(search)) ? 'block' : 'none';
    });
});

function selectSiswa(element) {
    const id = element.dataset.id;
    const nis = element.dataset.nis;
    const nama = element.dataset.nama;
    const kelas = element.dataset.kelas;
    
    document.getElementById('input-id-anggota').value = id;
    document.getElementById('selected-nama').textContent = nama;
    document.getElementById('selected-detail').textContent = `${kelas} • NIS: ${nis}`;
    document.getElementById('selected-initial').textContent = nama.charAt(0).toUpperCase();
    
    document.getElementById('selected-siswa-info').classList.remove('hidden');
    document.getElementById('form-content').classList.remove('hidden');
    document.getElementById('empty-state').classList.add('hidden');
    
    document.querySelectorAll('.siswa-item').forEach(item => { item.classList.remove('bg-blue-100', 'border-l-4', 'border-navy'); });
    element.classList.add('bg-blue-100', 'border-l-4', 'border-navy');
    
    if (window.innerWidth < 768) { document.getElementById('selected-siswa-info').scrollIntoView({ behavior: 'smooth' }); }
}

function clearSelection() {
    document.getElementById('input-id-anggota').value = '';
    document.getElementById('selected-siswa-info').classList.add('hidden');
    document.getElementById('form-content').classList.add('hidden');
    document.getElementById('empty-state').classList.remove('hidden');
    document.querySelectorAll('.siswa-item').forEach(item => { item.classList.remove('bg-blue-100', 'border-l-4', 'border-navy'); });
    
    document.getElementById('form-pelanggaran').reset();
    
    // Reset juga file storage
    fileStorage.items.clear();
    renderFileList();
    
    updateSanksi(); 
}

function switchCategory(category) {
    document.querySelectorAll('.category-content').forEach(content => { content.classList.add('hidden'); });
    document.querySelectorAll('.category-tab').forEach(tab => { tab.classList.remove('bg-kelakuan', 'bg-kerajinan', 'bg-kerapian', 'text-white'); tab.classList.add('bg-gray-100', 'text-gray-600'); });
    
    document.getElementById('content-' + category).classList.remove('hidden');
    const activeTab = document.getElementById('tab-' + category);
    activeTab.classList.remove('bg-gray-100', 'text-gray-600');
    activeTab.classList.add('text-white');
    
    if (category === 'kelakuan') activeTab.classList.add('bg-kelakuan');
    else if (category === 'kerajinan') activeTab.classList.add('bg-kerajinan');
    else activeTab.classList.add('bg-kerapian');
}

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
            container.innerHTML += `<label class="flex items-start space-x-3 p-3 border-2 border-navy bg-blue-50 rounded-lg cursor-pointer"><input type="checkbox" name="sanksi[]" value="${s.id_sanksi_ref}" checked class="w-5 h-5 text-navy border-gray-300 rounded focus:ring-navy mt-1"><div class="flex-1"><p class="font-medium text-gray-800 text-sm">${s.kode_sanksi}. ${s.deskripsi}</p></div></label>`;
        }
    });
}

document.getElementById('form-pelanggaran').addEventListener('submit', function(e) {
    if (!document.getElementById('input-id-anggota').value) { e.preventDefault(); alert('⚠️ Pilih siswa dulu!'); return false; }
    if (document.querySelectorAll('input[name="pelanggaran[]"]:checked').length === 0) { e.preventDefault(); alert('⚠️ Pilih minimal 1 pelanggaran!'); return false; }
    return confirm('✅ Simpan data?');
});

document.addEventListener('DOMContentLoaded', function() { updateSanksi(); });
</script>

</body>
</html>