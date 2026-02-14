<?php
/**
 * SITAPSI - Data Siswa
 * CRUD & Import Excel
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

// Ambil tahun ajaran aktif
$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// Filter
$search = $_GET['search'] ?? '';
$filter_kelas = $_GET['kelas'] ?? 'all';

// Ambil daftar kelas
$kelas_list = fetchAll("SELECT id_kelas, nama_kelas FROM tb_kelas ORDER BY nama_kelas");

// Query siswa
$sql = "
    SELECT 
        s.nis,
        s.nama_siswa,
        s.jenis_kelamin,
        s.nama_ortu,
        s.no_hp_ortu,
        s.status_aktif,
        k.nama_kelas,
        a.id_anggota
    FROM tb_siswa s
    LEFT JOIN tb_anggota_kelas a ON s.nis = a.nis AND a.id_tahun = :id_tahun
    LEFT JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE 1=1
";

$params = ['id_tahun' => $tahun_aktif['id_tahun']];

if (!empty($search)) {
    $sql .= " AND (s.nama_siswa LIKE :search OR s.nis LIKE :search)";
    $params['search'] = "%$search%";
}

if ($filter_kelas !== 'all') {
    $sql .= " AND k.id_kelas = :kelas";
    $params['kelas'] = $filter_kelas;
}

$sql .= " ORDER BY k.nama_kelas, s.nama_siswa";

$siswa_list = fetchAll($sql, $params);

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
    <title>Data Siswa - SITAPSI</title>
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
                <h1 class="text-2xl font-bold text-gray-800">Data Siswa</h1>
                <p class="text-sm text-gray-500">Manajemen data siswa & import Excel</p>
            </div>
            <button onclick="document.getElementById('modal-import').classList.remove('hidden')" 
                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium flex items-center space-x-2 transition-colors shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <span>Import Excel</span>
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

            <!-- Filter -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cari Nama/NIS</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Ketik nama atau NIS..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kelas</label>
                        <select name="kelas" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                            <option value="all">Semua Kelas</option>
                            <?php foreach ($kelas_list as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>" <?= $filter_kelas == $k['id_kelas'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kelas']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-navy hover:bg-blue-900 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                            🔍 Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b font-bold text-gray-700 flex justify-between items-center">
                    <span>Daftar Siswa (Total: <?= count($siswa_list) ?>)</span>
                    <button onclick="document.getElementById('modal-tambah').classList.remove('hidden')" 
                            class="bg-navy hover:bg-blue-900 text-white px-3 py-1 rounded-lg text-sm font-medium">
                        + Tambah Siswa
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="p-4">NIS</th>
                                <th class="p-4">Nama Siswa</th>
                                <th class="p-4">Kelas</th>
                                <th class="p-4">JK</th>
                                <th class="p-4">Orang Tua</th>
                                <th class="p-4">No. HP</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if(empty($siswa_list)): ?>
                            <tr>
                                <td colspan="8" class="p-12 text-center text-gray-500">Tidak ada data siswa</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($siswa_list as $siswa): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4 font-medium"><?= $siswa['nis'] ?></td>
                                <td class="p-4 font-bold text-navy"><?= htmlspecialchars($siswa['nama_siswa']) ?></td>
                                <td class="p-4"><?= $siswa['nama_kelas'] ?? '-' ?></td>
                                <td class="p-4"><?= $siswa['jenis_kelamin'] ?></td>
                                <td class="p-4"><?= htmlspecialchars($siswa['nama_ortu']) ?></td>
                                <td class="p-4"><?= $siswa['no_hp_ortu'] ?></td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= $siswa['status_aktif'] === 'Aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= $siswa['status_aktif'] ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex space-x-2">
                                        <button onclick="editSiswa('<?= $siswa['nis'] ?>')" 
                                                class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button onclick="hapusSiswa('<?= $siswa['nis'] ?>')" 
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

<!-- Modal Import Excel -->
<div id="modal-import" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Import Data Siswa (Excel)</h3>
            <button onclick="document.getElementById('modal-import').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="../../actions/import_siswa.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">File Excel (.xlsx, .csv)</label>
                <input type="file" name="file_excel" accept=".xlsx,.xls,.csv" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                <p class="text-xs text-gray-500 mt-2">
                    Format: NIS | Nama | JK | Tempat Lahir | Tanggal Lahir | Alamat | Nama Ortu | No HP | Kelas
                </p>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="document.getElementById('modal-import').classList.add('hidden')" 
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Batal
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium">
                    Upload & Import
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Siswa (Simple Form) -->
<div id="modal-tambah" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-lg max-w-2xl w-full my-8">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Tambah Siswa Baru</h3>
            <button onclick="document.getElementById('modal-tambah').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="../../actions/tambah_siswa.php" method="POST" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">NIS *</label>
                    <input type="text" name="nis" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                    <input type="text" name="nama_siswa" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Kelamin *</label>
                    <select name="jenis_kelamin" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                        <option value="">Pilih</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kelas *</label>
                    <select name="id_kelas" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                        <option value="">Pilih Kelas</option>
                        <?php foreach ($kelas_list as $k): ?>
                        <option value="<?= $k['id_kelas'] ?>"><?= $k['nama_kelas'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Orang Tua *</label>
                    <input type="text" name="nama_ortu" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">No. HP Orang Tua *</label>
                    <input type="text" name="no_hp_ortu" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy">
                </div>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="document.getElementById('modal-tambah').classList.add('hidden')" 
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Batal
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-navy text-white rounded-lg hover:bg-blue-900 font-medium">
                    Simpan Siswa
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editSiswa(nis) {
    alert('Fitur edit siswa akan dikembangkan. NIS: ' + nis);
}

function hapusSiswa(nis) {
    if (confirm('⚠️ Yakin ingin menghapus siswa ini?\n\nData pelanggaran dan SP juga akan terhapus!')) {
        window.location.href = `../../actions/hapus_siswa.php?nis=${nis}`;
    }
}
</script>

</body>
</html>