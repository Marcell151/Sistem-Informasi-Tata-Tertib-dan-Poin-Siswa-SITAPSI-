<?php
/**
 * SITAPSI - Data Guru
 * CRUD Guru & PIN Management
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

// Ambil daftar guru
$guru_list = fetchAll("SELECT id_guru, nama_guru, pin, status FROM tb_guru ORDER BY nama_guru");

// Success/Error message
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
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Data Guru</h1>
                <p class="text-sm text-gray-500">Manajemen akun & PIN guru</p>
            </div>
            <button onclick="document.getElementById('modal-tambah').classList.remove('hidden')" 
                    class="bg-navy hover:bg-blue-900 text-white px-4 py-2 rounded-lg font-medium flex items-center space-x-2 transition-colors shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Tambah Guru</span>
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

            <!-- Data Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b font-bold text-gray-700">
                    Daftar Guru (Total: <?= count($guru_list) ?>)
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="p-4">ID Guru</th>
                                <th class="p-4">Nama Guru</th>
                                <th class="p-4">PIN Login</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if(empty($guru_list)): ?>
                            <tr>
                                <td colspan="5" class="p-12 text-center text-gray-500">Belum ada data guru</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($guru_list as $guru): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4 font-medium"><?= $guru['id_guru'] ?></td>
                                <td class="p-4 font-bold text-navy"><?= htmlspecialchars($guru['nama_guru']) ?></td>
                                <td class="p-4">
                                    <code class="bg-gray-100 px-3 py-1 rounded font-mono text-sm"><?= $guru['pin'] ?></code>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= $guru['status'] === 'Aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= $guru['status'] ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex space-x-2">
                                        <button onclick="resetPIN('<?= $guru['id_guru'] ?>', '<?= htmlspecialchars($guru['nama_guru']) ?>')" 
                                                class="p-2 bg-yellow-50 text-yellow-600 rounded-lg hover:bg-yellow-100" title="Reset PIN">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                            </svg>
                                        </button>
                                        <button onclick="toggleStatus('<?= $guru['id_guru'] ?>', '<?= $guru['status'] ?>')" 
                                                class="p-2 <?= $guru['status'] === 'Aktif' ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600' ?> rounded-lg hover:opacity-80" 
                                                title="<?= $guru['status'] === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                            </svg>
                                        </button>
                                        <button onclick="hapusGuru('<?= $guru['id_guru'] ?>')" 
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
    <div class="bg-white rounded-lg max-w-md w-full">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Tambah Guru Baru</h3>
            <button onclick="document.getElementById('modal-tambah').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="../../actions/tambah_guru.php" method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Guru *</label>
                <input type="text" name="nama_guru" required 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">PIN (6 Digit) *</label>
                <input type="text" name="pin" maxlength="6" pattern="[0-9]{6}" required 
                       placeholder="Contoh: 123456"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                <p class="text-xs text-gray-500 mt-1">PIN harus 6 digit angka</p>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="document.getElementById('modal-tambah').classList.add('hidden')" 
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

<script>
function resetPIN(id, nama) {
    const newPIN = prompt(`Reset PIN untuk: ${nama}\n\nMasukkan PIN baru (6 digit angka):`);
    if (newPIN && /^[0-9]{6}$/.test(newPIN)) {
        window.location.href = `../../actions/reset_pin_guru.php?id=${id}&pin=${newPIN}`;
    } else if (newPIN) {
        alert('PIN harus 6 digit angka!');
    }
}

function toggleStatus(id, currentStatus) {
    const action = currentStatus === 'Aktif' ? 'nonaktifkan' : 'aktifkan';
    if (confirm(`Yakin ingin ${action} guru ini?`)) {
        window.location.href = `../../actions/toggle_status_guru.php?id=${id}`;
    }
}

function hapusGuru(id) {
    if (confirm('⚠️ Yakin ingin menghapus guru ini?\n\nData pelaporan yang terkait akan tetap tersimpan.')) {
        window.location.href = `../../actions/hapus_guru.php?id=${id}`;
    }
}
</script>

</body>
</html>