<?php
/**
 * SITAPSI - Data Siswa (COMPLETE WITH MODALS)
 * Fix: Semua modal dimasukkan langsung di file ini
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? 'Aktif';
$filter_kelas = $_GET['kelas'] ?? 'all';

$tahun_aktif = fetchOne("
    SELECT id_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1
");

$kelas_list = fetchAll("SELECT id_kelas, nama_kelas FROM tb_kelas ORDER BY tingkat, nama_kelas");

// Query siswa dengan subquery untuk ambil MAX id_anggota per NIS (hindari duplikat)
$sql = "
    SELECT 
        s.nis,
        s.nama_siswa,
        s.jenis_kelamin,
        s.nama_ortu,
        s.no_hp_ortu,
        s.status_aktif,
        s.foto_profil,
        k.nama_kelas,
        k.id_kelas,
        a.id_anggota
    FROM tb_siswa s
    LEFT JOIN tb_anggota_kelas a ON (
        a.nis = s.nis 
        AND a.id_tahun = :id_tahun
        AND a.id_anggota = (
            SELECT MAX(a2.id_anggota) 
            FROM tb_anggota_kelas a2 
            WHERE a2.nis = s.nis 
            AND a2.id_tahun = a.id_tahun
        )
    )
    LEFT JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE s.status_aktif = :status
";

$params = [
    'id_tahun' => $tahun_aktif['id_tahun'],
    'status' => $filter_status
];

if (!empty($search)) {
    $sql .= " AND (s.nama_siswa LIKE :search OR s.nis LIKE :search)";
    $params['search'] = "%$search%";
}

if ($filter_kelas !== 'all') {
    $sql .= " AND k.id_kelas = :kelas";
    $params['kelas'] = $filter_kelas;
}

$sql .= " ORDER BY s.nama_siswa";

$siswa_list = fetchAll($sql, $params);

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
                extend: { colors: { 'navy': '#000080' } }
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
            <div class="flex space-x-2">
                <button onclick="downloadTemplate()"
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium flex items-center space-x-2 transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Download Template</span>
                </button>
                <button onclick="openModalImport()"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium flex items-center space-x-2 transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <span>Import Excel</span>
                </button>
                <button onclick="openModalTambah()"
                        class="bg-navy hover:bg-blue-900 text-white px-4 py-2 rounded-lg font-medium flex items-center space-x-2 transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Tambah Siswa</span>
                </button>
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

            <!-- Filter -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cari Nama/NIS</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                               placeholder="Ketik nama atau NIS..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                            <option value="Aktif" <?= $filter_status === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Lulus" <?= $filter_status === 'Lulus' ? 'selected' : '' ?>>Lulus</option>
                            <option value="Keluar" <?= $filter_status === 'Keluar' ? 'selected' : '' ?>>Keluar</option>
                            <option value="Dikeluarkan" <?= $filter_status === 'Dikeluarkan' ? 'selected' : '' ?>>Dikeluarkan</option>
                        </select>
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

            <!-- Tabel Data Siswa -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b font-bold text-gray-700 flex justify-between items-center">
                    <span>Daftar Siswa (Total: <?= count($siswa_list) ?>)</span>
                    <span class="px-3 py-1 rounded-full text-xs font-medium
                        <?= $filter_status === 'Aktif' ? 'bg-green-100 text-green-800' : '' ?>
                        <?= $filter_status === 'Lulus' ? 'bg-blue-100 text-blue-800' : '' ?>
                        <?= $filter_status === 'Keluar' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                        <?= $filter_status === 'Dikeluarkan' ? 'bg-red-100 text-red-800' : '' ?>">
                        <?= $filter_status ?>
                    </span>
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
                                <td colspan="8" class="p-12 text-center text-gray-500">
                                    Tidak ada data siswa dengan filter ini
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($siswa_list as $siswa): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4 font-medium"><?= htmlspecialchars($siswa['nis']) ?></td>
                                <td class="p-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-navy rounded-full flex items-center justify-center overflow-hidden flex-shrink-0">
                                            <?php if($siswa['foto_profil']): ?>
                                                <img src="../../assets/uploads/siswa/<?= htmlspecialchars($siswa['foto_profil']) ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <span class="text-white font-bold text-sm"><?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="font-bold text-navy"><?= htmlspecialchars($siswa['nama_siswa']) ?></span>
                                    </div>
                                </td>
                                <td class="p-4"><?= $siswa['nama_kelas'] ? htmlspecialchars($siswa['nama_kelas']) : '<span class="text-gray-400">-</span>' ?></td>
                                <td class="p-4"><?= $siswa['jenis_kelamin'] === 'L' ? 'L' : 'P' ?></td>
                                <td class="p-4"><?= htmlspecialchars($siswa['nama_ortu'] ?? '-') ?></td>
                                <td class="p-4"><?= htmlspecialchars($siswa['no_hp_ortu'] ?? '-') ?></td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        <?= $siswa['status_aktif'] === 'Aktif' ? 'bg-green-100 text-green-800' : '' ?>
                                        <?= $siswa['status_aktif'] === 'Lulus' ? 'bg-blue-100 text-blue-800' : '' ?>
                                        <?= $siswa['status_aktif'] === 'Keluar' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                                        <?= $siswa['status_aktif'] === 'Dikeluarkan' ? 'bg-red-100 text-red-800' : '' ?>">
                                        <?= $siswa['status_aktif'] ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex space-x-2">
                                        <button onclick='editSiswa(<?= json_encode([
                                            "nis" => $siswa["nis"],
                                            "id_anggota" => $siswa["id_anggota"],
                                            "nama_siswa" => $siswa["nama_siswa"],
                                            "jenis_kelamin" => $siswa["jenis_kelamin"],
                                            "status_aktif" => $siswa["status_aktif"],
                                            "nama_ortu" => $siswa["nama_ortu"] ?? "",
                                            "no_hp_ortu" => $siswa["no_hp_ortu"] ?? "",
                                            "id_kelas" => $siswa["id_kelas"]
                                        ]) ?>)'
                                                class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button onclick="hapusSiswa('<?= htmlspecialchars($siswa['nis'], ENT_QUOTES) ?>', '<?= htmlspecialchars($siswa['nama_siswa'], ENT_QUOTES) ?>')"
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
            <button onclick="closeModalImport()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="../../actions/import_siswa.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">File Excel (.xlsx, .csv)</label>
                <input type="file" name="file_excel" accept=".xlsx,.xls,.csv" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
            </div>
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <p class="text-sm text-blue-700">
                    <strong>Format Excel:</strong><br>
                    NIS | Nama | JK | Tempat Lahir | Tanggal Lahir | Alamat | Nama Ortu | No HP | Kelas
                </p>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="closeModalImport()"
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Batal
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium">
                    Upload & Import
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Siswa -->
<div id="modal-tambah" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-lg max-w-2xl w-full my-8">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Tambah Siswa Baru</h3>
            <button onclick="closeModalTambah()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="../../actions/tambah_siswa.php" method="POST" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">NIS *</label>
                    <input type="text" name="nis" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                    <input type="text" name="nama_siswa" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Kelamin *</label>
                    <select name="jenis_kelamin" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                        <option value="">Pilih</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kelas *</label>
                    <select name="id_kelas" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                        <option value="">Pilih Kelas</option>
                        <?php foreach ($kelas_list as $k): ?>
                        <option value="<?= $k['id_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Orang Tua *</label>
                    <input type="text" name="nama_ortu" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">No. HP Orang Tua *</label>
                    <input type="text" name="no_hp_ortu" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="closeModalTambah()"
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

<!-- Modal Edit Siswa -->
<div id="modal-edit" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-lg max-w-2xl w-full my-8">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Edit Data Siswa</h3>
            <button onclick="closeModalEdit()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="../../actions/edit_siswa.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="nis" id="edit-nis">
            <input type="hidden" name="id_anggota" id="edit-id-anggota">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">NIS</label>
                    <input type="text" id="edit-nis-display" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                    <input type="text" name="nama_siswa" id="edit-nama-siswa" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Kelamin *</label>
                    <select name="jenis_kelamin" id="edit-jenis-kelamin" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <select name="status_aktif" id="edit-status-aktif" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                        <option value="Aktif">Aktif</option>
                        <option value="Lulus">Lulus</option>
                        <option value="Keluar">Keluar</option>
                        <option value="Dikeluarkan">Dikeluarkan</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kelas</label>
                    <select name="id_kelas" id="edit-id-kelas" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                        <option value="">-- Tidak ada kelas / Lulus --</option>
                        <?php foreach ($kelas_list as $k): ?>
                        <option value="<?= $k['id_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        ⚠️ Mengubah kelas akan memindahkan siswa ke kelas terpilih di tahun ajaran aktif
                    </p>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Orang Tua *</label>
                    <input type="text" name="nama_ortu" id="edit-nama-ortu" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">No. HP Orang Tua *</label>
                    <input type="text" name="no_hp_ortu" id="edit-no-hp-ortu" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                </div>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="closeModalEdit()"
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

<script>
// Modal Import
function openModalImport() {
    document.getElementById('modal-import').classList.remove('hidden');
}
function closeModalImport() {
    document.getElementById('modal-import').classList.add('hidden');
}

// Modal Tambah
function openModalTambah() {
    document.getElementById('modal-tambah').classList.remove('hidden');
}
function closeModalTambah() {
    document.getElementById('modal-tambah').classList.add('hidden');
}

// Modal Edit
function closeModalEdit() {
    document.getElementById('modal-edit').classList.add('hidden');
}

function editSiswa(data) {
    document.getElementById('edit-nis').value = data.nis;
    document.getElementById('edit-id-anggota').value = data.id_anggota || '';
    document.getElementById('edit-nis-display').value = data.nis;
    document.getElementById('edit-nama-siswa').value = data.nama_siswa;
    document.getElementById('edit-jenis-kelamin').value = data.jenis_kelamin;
    document.getElementById('edit-status-aktif').value = data.status_aktif;
    document.getElementById('edit-nama-ortu').value = data.nama_ortu;
    document.getElementById('edit-no-hp-ortu').value = data.no_hp_ortu;

    const selectKelas = document.getElementById('edit-id-kelas');
    if (data.id_kelas) {
        selectKelas.value = data.id_kelas;
    } else {
        selectKelas.value = '';
    }

    document.getElementById('modal-edit').classList.remove('hidden');
}

function hapusSiswa(nis, nama) {
    if (confirm('⚠️ Hapus siswa: ' + nama + ' (' + nis + ')?\n\nSiswa yang memiliki riwayat pelanggaran tidak dapat dihapus.\nDisarankan ubah status menjadi Lulus/Keluar.')) {
        window.location.href = '../../actions/hapus_siswa.php?nis=' + encodeURIComponent(nis);
    }
}

function downloadTemplate() {
    window.location.href = '../../actions/download_template_siswa.php';
}
</script>

</body>
</html>