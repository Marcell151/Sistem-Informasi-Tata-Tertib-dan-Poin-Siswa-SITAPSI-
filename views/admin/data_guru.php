<?php
/**
 * SITAPSI - Data Guru (WITH WALI KELAS MANAGEMENT)
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

// Ambil daftar kelas untuk dropdown
$kelas_list = fetchAll("SELECT id_kelas, nama_kelas FROM tb_kelas ORDER BY tingkat, nama_kelas");

// Query guru dengan info wali kelas
$guru_list = fetchAll("
    SELECT 
        g.*,
        k.nama_kelas
    FROM tb_guru g
    LEFT JOIN tb_kelas k ON g.id_kelas = k.id_kelas
    ORDER BY g.nama_guru
");

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Guru - SITAPSI</title>
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
            <h1 class="text-2xl font-bold text-gray-800">Data Guru</h1>
            <p class="text-sm text-gray-500">Manajemen guru & wali kelas</p>
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

            <!-- Header dengan Tombol Tambah -->
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Daftar Guru</h2>
                    <p class="text-sm text-gray-500">Total: <?= count($guru_list) ?> guru</p>
                </div>
                <button onclick="openModalTambah()" 
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium flex items-center space-x-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Tambah Guru</span>
                </button>
            </div>

            <!-- Tabel Guru -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="p-4">No</th>
                                <th class="p-4">Nama Guru</th>
                                <th class="p-4">NIP</th>
                                <th class="p-4">PIN Login</th>
                                <th class="p-4">Wali Kelas</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($guru_list)): ?>
                            <tr>
                                <td colspan="7" class="p-12 text-center text-gray-500">Belum ada data guru</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($guru_list as $idx => $g): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4 font-medium text-gray-700"><?= $idx + 1 ?></td>
                                <td class="p-4">
                                    <p class="font-bold text-navy"><?= htmlspecialchars($g['nama_guru']) ?></p>
                                </td>
                                <td class="p-4 text-gray-600"><?= htmlspecialchars($g['nip'] ?: '-') ?></td>
                                <td class="p-4">
                                    <code class="px-2 py-1 bg-gray-100 rounded text-xs font-mono"><?= $g['pin_validasi'] ?></code>
                                </td>
                                <td class="p-4">
                                    <?php if ($g['id_kelas']): ?>
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-bold text-xs flex items-center w-fit">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"></path>
                                        </svg>
                                        <?= $g['nama_kelas'] ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="px-3 py-1 bg-gray-100 text-gray-500 rounded-full text-xs">
                                        Bukan Wali Kelas
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?= $g['status'] === 'Aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $g['status'] ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex space-x-2">
                                        <button onclick='editGuru(<?= json_encode($g) ?>)'
                                                class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button onclick="hapusGuru(<?= $g['id_guru'] ?>, '<?= htmlspecialchars($g['nama_guru'], ENT_QUOTES) ?>')"
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

        </div>
    </div>
</div>

<!-- Modal Tambah Guru -->
<div id="modal-tambah" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Tambah Guru Baru</h3>
            <button onclick="closeModalTambah()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="../../actions/tambah_guru.php" method="POST" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                    <input type="text" name="nama_guru" required 
                           placeholder="Contoh: Budi Santoso, S.Pd"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">NIP</label>
                    <input type="text" name="nip" 
                           placeholder="Opsional"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">PIN Login (6 digit) *</label>
                    <input type="text" name="pin_validasi" required maxlength="6" pattern="[0-9]{6}"
                           placeholder="123456"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">PIN untuk login guru</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jabatan Wali Kelas</label>
                    <select name="id_kelas" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                        <option value="">Bukan Wali Kelas</option>
                        <?php foreach ($kelas_list as $k): ?>
                        <option value="<?= $k['id_kelas'] ?>">Wali Kelas <?= $k['nama_kelas'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                        <option value="Aktif">Aktif</option>
                        <option value="Non-Aktif">Non-Aktif</option>
                    </select>
                </div>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="closeModalTambah()" 
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

<!-- Modal Edit Guru -->
<div id="modal-edit" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Edit Data Guru</h3>
            <button onclick="closeModalEdit()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="../../actions/edit_guru.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="id_guru" id="edit-id-guru">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                    <input type="text" name="nama_guru" id="edit-nama-guru" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">NIP</label>
                    <input type="text" name="nip" id="edit-nip"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">PIN Login (6 digit) *</label>
                    <input type="text" name="pin_validasi" id="edit-pin" required maxlength="6" pattern="[0-9]{6}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jabatan Wali Kelas</label>
                    <select name="id_kelas" id="edit-id-kelas" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                        <option value="">Bukan Wali Kelas</option>
                        <?php foreach ($kelas_list as $k): ?>
                        <option value="<?= $k['id_kelas'] ?>">Wali Kelas <?= $k['nama_kelas'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <select name="status" id="edit-status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                        <option value="Aktif">Aktif</option>
                        <option value="Non-Aktif">Non-Aktif</option>
                    </select>
                </div>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="closeModalEdit()" 
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Batal
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModalTambah() {
    document.getElementById('modal-tambah').classList.remove('hidden');
}
function closeModalTambah() {
    document.getElementById('modal-tambah').classList.add('hidden');
}

function editGuru(data) {
    document.getElementById('edit-id-guru').value = data.id_guru;
    document.getElementById('edit-nama-guru').value = data.nama_guru;
    document.getElementById('edit-nip').value = data.nip || '';
    document.getElementById('edit-pin').value = data.pin_validasi;
    document.getElementById('edit-id-kelas').value = data.id_kelas || '';
    document.getElementById('edit-status').value = data.status;
    document.getElementById('modal-edit').classList.remove('hidden');
}
function closeModalEdit() {
    document.getElementById('modal-edit').classList.add('hidden');
}

function hapusGuru(id, nama) {
    if (confirm(`⚠️ Yakin ingin menghapus guru:\n"${nama}"?`)) {
        window.location.href = `../../actions/hapus_guru.php?id=${id}`;
    }
}
</script>

</body>
</html>