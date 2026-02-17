<?php
/**
 * SITAPSI - Manajemen Kelas
 * CRUD lengkap untuk data kelas
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

// Ambil daftar kelas
$kelas_list = fetchAll("SELECT * FROM tb_kelas ORDER BY tingkat, nama_kelas");

// Group by tingkat
$kelas_by_tingkat = [];
foreach ($kelas_list as $k) {
    $kelas_by_tingkat[$k['tingkat']][] = $k;
}

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kelas - SITAPSI</title>
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
        
        <div class="bg-white shadow-sm border-b px-6 py-4 sticky top-0 z-30 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="pengaturan_akademik.php" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Manajemen Kelas</h1>
                    <p class="text-sm text-gray-500">Kelola data kelas (CRUD)</p>
                </div>
            </div>
            <button onclick="document.getElementById('modal-tambah').classList.remove('hidden')" 
                    class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium flex items-center space-x-2 transition-colors shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Tambah Kelas</span>
            </button>
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

            <!-- Info Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">🏫 Data Kelas</h2>
                        <p class="text-indigo-200">Kelola semua kelas di sekolah</p>
                    </div>
                    <div class="text-right">
                        <p class="text-indigo-200 text-sm">Total Kelas</p>
                        <p class="text-5xl font-bold"><?= count($kelas_list) ?></p>
                    </div>
                </div>
            </div>

            <!-- Kelas by Tingkat -->
            <?php foreach ($kelas_by_tingkat as $tingkat => $kelas_items): ?>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b font-bold text-gray-700 bg-gray-50 flex items-center">
                    <span class="bg-indigo-600 text-white px-4 py-1 rounded-full mr-3">Tingkat <?= $tingkat ?></span>
                    <span class="text-gray-500 text-sm font-normal">(<?= count($kelas_items) ?> kelas)</span>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <?php foreach ($kelas_items as $k): 
                            // Hitung jumlah siswa
                            $tahun_aktif = fetchOne("SELECT id_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
                            $jumlah_siswa = fetchOne("
                                SELECT COUNT(*) as total 
                                FROM tb_anggota_kelas 
                                WHERE id_kelas = :id 
                                AND id_tahun = :tahun
                            ", [
                                'id' => $k['id_kelas'],
                                'tahun' => $tahun_aktif['id_tahun']
                            ])['total'] ?? 0;
                        ?>
                        <div class="bg-gray-50 border-2 border-gray-200 rounded-xl p-4 hover:border-indigo-500 hover:shadow-md transition-all group">
                            <div class="text-center mb-3">
                                <h3 class="text-2xl font-bold text-gray-800 group-hover:text-indigo-600"><?= htmlspecialchars($k['nama_kelas']) ?></h3>
                                <p class="text-xs text-gray-500"><?= $jumlah_siswa ?> siswa</p>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick='editKelas(<?= json_encode($k) ?>)' 
                                        class="flex-1 p-2 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition-colors" title="Edit">
                                    <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button onclick="hapusKelas(<?= $k['id_kelas'] ?>, '<?= htmlspecialchars($k['nama_kelas']) ?>', <?= $jumlah_siswa ?>)" 
                                        class="flex-1 p-2 bg-red-50 text-red-600 rounded hover:bg-red-100 transition-colors" title="Hapus">
                                    <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($kelas_list)): ?>
            <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <p class="text-gray-500 font-medium">Belum ada kelas yang terdaftar</p>
            </div>
            <?php endif; ?>

        </div>

    </div>

</div>

<!-- Modal Tambah Kelas -->
<div id="modal-tambah" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Tambah Kelas Baru</h3>
            <button onclick="document.getElementById('modal-tambah').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="../../actions/tambah_kelas.php" method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tingkat *</label>
                <select name="tingkat" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-600">
                    <option value="">Pilih Tingkat</option>
                    <option value="7">7 (Tujuh)</option>
                    <option value="8">8 (Delapan)</option>
                    <option value="9">9 (Sembilan)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kelas *</label>
                <input type="text" name="nama_kelas" required placeholder="Contoh: 7A, 8B, 9C"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-600">
                <p class="text-xs text-gray-500 mt-1">Format: [Tingkat][Nama], misal: 7A, 8B</p>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="document.getElementById('modal-tambah').classList.add('hidden')" 
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Batal
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kelas -->
<div id="modal-edit" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Edit Kelas</h3>
            <button onclick="document.getElementById('modal-edit').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="../../actions/edit_kelas.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="id_kelas" id="edit-id-kelas">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tingkat *</label>
                <select name="tingkat" id="edit-tingkat" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-600">
                    <option value="7">7 (Tujuh)</option>
                    <option value="8">8 (Delapan)</option>
                    <option value="9">9 (Sembilan)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kelas *</label>
                <input type="text" name="nama_kelas" id="edit-nama-kelas" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-600">
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')" 
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Batal
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editKelas(data) {
    document.getElementById('edit-id-kelas').value = data.id_kelas;
    document.getElementById('edit-tingkat').value = data.tingkat;
    document.getElementById('edit-nama-kelas').value = data.nama_kelas;
    document.getElementById('modal-edit').classList.remove('hidden');
}

function hapusKelas(id, nama, jumlahSiswa) {
    if (jumlahSiswa > 0) {
        alert(`⚠️ Tidak dapat menghapus kelas ${nama}!\n\nMasih ada ${jumlahSiswa} siswa di kelas ini.\nPindahkan siswa terlebih dahulu.`);
        return;
    }
    
    if (confirm(`⚠️ Yakin ingin menghapus kelas ${nama}?`)) {
        window.location.href = `../../actions/hapus_kelas.php?id=${id}`;
    }
}
</script>

</body>
</html>