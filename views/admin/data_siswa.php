<?php
/**
 * SITAPSI - Data Siswa (UI ALIGNED WITH GLOBAL PORTAL)
 * [FIXED]: XSS Vulnerability pada tombol Edit & Hapus
 * [PENYESUAIAN BARU]: Relasi id_ortu dengan Dropdown TomSelect
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

// AMBIL DATA ORANG TUA UNTUK DROPDOWN
$ortu_list = fetchAll("SELECT id_ortu, nik_ortu, nama_ayah, nama_ibu FROM tb_orang_tua ORDER BY nama_ayah ASC");

// [PENYESUAIAN] Tambah JOIN ke tb_orang_tua untuk memanggil NIK ortu
$sql = "
    SELECT 
        s.no_induk,
        s.nama_siswa,
        s.jenis_kelamin,
        s.nama_ayah,
        s.nama_ibu,
        s.no_hp_ortu,
        s.id_ortu,
        o.nik_ortu,
        s.status_aktif,
        k.nama_kelas,
        k.id_kelas,
        a.id_anggota
    FROM tb_siswa s
    LEFT JOIN tb_anggota_kelas a ON (
        a.no_induk = s.no_induk 
        AND a.id_tahun = :id_tahun
        AND a.id_anggota = (
            SELECT MAX(a2.id_anggota) 
            FROM tb_anggota_kelas a2 
            WHERE a2.no_induk = s.no_induk 
            AND a2.id_tahun = a.id_tahun
        )
    )
    LEFT JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    LEFT JOIN tb_orang_tua o ON s.id_ortu = o.id_ortu
    WHERE s.status_aktif = :status
";

$params = [
    'id_tahun' => $tahun_aktif['id_tahun'],
    'status' => $filter_status
];

if (!empty($search)) {
    // Kita bedakan nama tempatnya: :search_nama dan :search_induk
    $sql .= " AND (s.nama_siswa LIKE :search_nama OR s.no_induk LIKE :search_induk)";
    
    // Kita kirimkan datanya untuk masing-masing tempat
    $params['search_nama'] = "%$search%";
    $params['search_induk'] = "%$search%";
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

// --- UI CONFIG VARIABLES ---
$btn_primary = "px-4 py-2.5 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-md shadow-blue-900/10 hover:bg-blue-900 transition-all flex items-center justify-center";
$btn_outline = "px-4 py-2.5 bg-white border border-[#E2E8F0] text-slate-700 text-sm font-semibold rounded-lg shadow-sm hover:bg-slate-50 transition-all flex items-center justify-center";
$btn_success = "px-4 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-lg shadow-md shadow-emerald-900/10 hover:bg-emerald-700 transition-all flex items-center justify-center";
$input_class = "w-full px-4 py-2.5 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm text-slate-700 bg-white transition-all";
$label_class = "block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wide";
$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <style>
        /* Styling TomSelect agar mirip Tailwind Input */
        .ts-control { border-radius: 0.5rem !important; padding: 0.625rem 1rem !important; border-color: #E2E8F0 !important; font-size: 0.875rem !important;}
        .ts-control.focus { border-color: #000080 !important; box-shadow: 0 0 0 2px rgba(0,0,128,0.1) !important; }
    </style>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">

    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">

        <div class="bg-white border-b border-[#E2E8F0] px-6 py-4 sticky top-0 z-30 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Data Siswa</h1>
                <p class="text-sm font-medium text-slate-500">Manajemen data master siswa & import Excel</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button onclick="downloadTemplate()" class="<?= $btn_success ?>">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Template
                </button>
                <button onclick="openModalImport()" class="<?= $btn_outline ?>">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    Import
                </button>
                <button onclick="openModalTambah()" class="<?= $btn_primary ?>">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Tambah Siswa
                </button>
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

            <div class="<?= $card_class ?> p-5">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="<?= $label_class ?>">Pencarian</label>
                        <div class="relative">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nama atau No Induk..." class="<?= $input_class ?> pl-10">
                            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </div>
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Status</label>
                        <select name="status" class="<?= $input_class ?>">
                            <option value="Aktif" <?= $filter_status === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Lulus" <?= $filter_status === 'Lulus' ? 'selected' : '' ?>>Lulus</option>
                            <option value="Keluar" <?= $filter_status === 'Keluar' ? 'selected' : '' ?>>Keluar</option>
                            <option value="Dikeluarkan" <?= $filter_status === 'Dikeluarkan' ? 'selected' : '' ?>>Dikeluarkan</option>
                        </select>
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Kelas</label>
                        <select name="kelas" class="<?= $input_class ?>">
                            <option value="all">Semua Kelas</option>
                            <?php foreach ($kelas_list as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>" <?= $filter_kelas == $k['id_kelas'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kelas']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="<?= $btn_primary ?> w-full">Filter Data</button>
                    </div>
                </form>
            </div>

            <div class="<?= $card_class ?> overflow-hidden">
                <div class="p-4 border-b border-[#E2E8F0] bg-slate-50/50 flex justify-between items-center">
                    <span class="font-bold text-slate-800 text-sm">Daftar Siswa <span class="text-slate-400 font-medium ml-1">(Total: <?= count($siswa_list) ?>)</span></span>
                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider
                        <?= $filter_status === 'Aktif' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
                        <?= $filter_status ?>
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-white text-xs text-slate-500 uppercase border-b border-[#E2E8F0]">
                            <tr>
                                <th class="p-4 font-bold">Siswa</th>
                                <th class="p-4 font-bold">Kelas</th>
                                <th class="p-4 font-bold">Data Orang Tua</th>
                                <th class="p-4 font-bold text-center">Akses Wali</th>
                                <th class="p-4 font-bold text-center">Status</th>
                                <th class="p-4 font-bold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php if(empty($siswa_list)): ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400 text-sm font-medium">Tidak ada data siswa ditemukan</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($siswa_list as $siswa): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-[#000080] rounded-xl flex items-center justify-center overflow-hidden flex-shrink-0 shadow-sm">
                                            <span class="text-white font-bold text-sm"><?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?></span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800 text-[13px]"><?= htmlspecialchars($siswa['nama_siswa']) ?></p>
                                            <p class="text-[10px] font-medium text-slate-500">No Induk: <?= htmlspecialchars($siswa['no_induk']) ?> • <?= $siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-slate-600 font-medium">
                                    <?= $siswa['nama_kelas'] ? htmlspecialchars($siswa['nama_kelas']) : '<span class="text-slate-400">-</span>' ?>
                                </td>
                                <td class="p-4">
                                    <p class="font-medium text-slate-700 text-xs">Ayah: <?= htmlspecialchars($siswa['nama_ayah'] ?? '-') ?></p>
                                    <p class="font-medium text-slate-700 text-xs">Ibu: <?= htmlspecialchars($siswa['nama_ibu'] ?? '-') ?></p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">HP: <?= htmlspecialchars($siswa['no_hp_ortu'] ?? '-') ?></p>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if(!empty($siswa['id_ortu'])): ?>
                                        <span class="inline-flex flex-col items-center px-2.5 py-1 rounded bg-blue-50 text-blue-700 border border-blue-200" title="Terhubung dengan NIK: <?= htmlspecialchars($siswa['nik_ortu']) ?>">
                                            <svg class="w-4 h-4 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                                            <span class="text-[9px] font-extrabold uppercase">Terkait</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex flex-col items-center px-2.5 py-1 rounded bg-slate-100 text-slate-500 border border-slate-200" title="Akun orang tua belum disetting">
                                            <svg class="w-4 h-4 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                            <span class="text-[9px] font-extrabold uppercase">Belum</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold 
                                        <?= $siswa['status_aktif'] === 'Aktif' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
                                        <?= $siswa['status_aktif'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <?php 
                                        // [KEAMANAN XSS & DOM BREAK FIX] Ditambah data id_ortu
                                        $edit_data = json_encode([
                                            "no_induk" => $siswa["no_induk"],
                                            "id_anggota" => $siswa["id_anggota"],
                                            "nama_siswa" => $siswa["nama_siswa"],
                                            "jenis_kelamin" => $siswa["jenis_kelamin"],
                                            "status_aktif" => $siswa["status_aktif"],
                                            "nama_ayah" => $siswa["nama_ayah"] ?? "",
                                            "nama_ibu" => $siswa["nama_ibu"] ?? "",
                                            "no_hp_ortu" => $siswa["no_hp_ortu"] ?? "",
                                            "id_kelas" => $siswa["id_kelas"],
                                            "id_ortu" => $siswa["id_ortu"] // <--- BARU
                                        ]);
                                        $safe_edit_data = htmlspecialchars($edit_data, ENT_QUOTES, 'UTF-8');
                                        
                                        $safe_js_id = htmlspecialchars(json_encode($siswa['no_induk']), ENT_QUOTES, 'UTF-8');
                                        $safe_js_nama = htmlspecialchars(json_encode($siswa['nama_siswa']), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <div class="flex items-center justify-center space-x-2">
                                        <button onclick="editSiswa(<?= $safe_edit_data ?>)"
                                                class="p-1.5 bg-white border border-[#E2E8F0] text-slate-600 rounded-md hover:bg-slate-50 transition-colors shadow-sm" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        </button>
                                        <button onclick="hapusSiswa(<?= $safe_js_id ?>, <?= $safe_js_nama ?>)"
                                                class="p-1.5 bg-white border border-red-200 text-red-600 rounded-md hover:bg-red-50 transition-colors shadow-sm" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
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

<div id="modal-import" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModalImport()"></div>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md relative z-10 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800 flex items-center"><svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg> Import Excel</h3>
            <button type="button" onclick="closeModalImport()" class="text-slate-400 hover:text-slate-600 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <form action="../../actions/import_siswa.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
            <div>
                <label class="<?= $label_class ?>">File (.csv)</label>
                <input type="file" name="file_excel" accept=".xlsx,.xls,.csv" required class="<?= $input_class ?> text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-[#000080] hover:file:bg-blue-100 cursor-pointer">
            </div>
            <div class="bg-blue-50/50 border border-blue-100 p-4 rounded-lg">
                <p class="text-xs text-blue-800 leading-relaxed font-medium">
                    Format Kolom Excel yang dibutuhkan:<br>
                    <span class="font-mono text-slate-600 mt-1 block">No Induk | Nama | JK | Tempat Lahir | Tgl Lahir | Alamat | Nama Ayah | Nama Ibu | No HP | NIK Orang Tua | Kelas</span>
                </p>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModalImport()" class="<?= $btn_outline ?> flex-1">Batal</button>
                <button type="submit" class="<?= $btn_primary ?> flex-1">Upload File</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-tambah" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModalTambah()"></div>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl relative z-10 overflow-hidden transform transition-all flex flex-col max-h-[90vh]">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between flex-shrink-0">
            <h3 class="font-extrabold text-slate-800 flex items-center"><svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg> Tambah Siswa Baru</h3>
            <button type="button" onclick="closeModalTambah()" class="text-slate-400 hover:text-slate-600 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <div class="overflow-y-auto p-6">
            <form action="../../actions/tambah_siswa.php" method="POST" id="formTambah" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="<?= $label_class ?>">No Induk *</label>
                        <input type="text" name="no_induk" required class="<?= $input_class ?>" placeholder="Nomor Induk Siswa">
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Nama Lengkap *</label>
                        <input type="text" name="nama_siswa" required class="<?= $input_class ?>" placeholder="Nama Siswa">
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Jenis Kelamin *</label>
                        <select name="jenis_kelamin" required class="<?= $input_class ?>">
                            <option value="">Pilih JK...</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Kelas *</label>
                        <select name="id_kelas" required class="<?= $input_class ?>">
                            <option value="">Pilih Kelas...</option>
                            <?php foreach ($kelas_list as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="sm:col-span-2 pt-4 border-t border-[#E2E8F0]">
                        <h4 class="text-sm font-bold text-slate-800 mb-2">Informasi Orang Tua / Wali</h4>
                        <p class="text-xs text-slate-500 mb-4">Cari dari data yang sudah ada, atau kosongi jika akan diisi manual di form bawahnya.</p>
                        
                        <div class="mb-4">
                            <label class="<?= $label_class ?>">Hubungkan dengan Akun Orang Tua (Opsional)</label>
                            <select name="id_ortu" id="select-ortu-tambah" placeholder="Cari NIK atau Nama Ayah/Ibu...">
                                <option value="">-- Tidak Dihubungkan / Belum Terdaftar --</option>
                                <?php foreach($ortu_list as $o): ?>
                                    <option value="<?= $o['id_ortu'] ?>"><?= $o['nik_ortu'] ?> - Ayah: <?= htmlspecialchars($o['nama_ayah']) ?> | Ibu: <?= htmlspecialchars($o['nama_ibu']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Nama Ayah *</label>
                        <input type="text" name="nama_ayah" required class="<?= $input_class ?>" placeholder="Nama lengkap Ayah">
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Nama Ibu *</label>
                        <input type="text" name="nama_ibu" required class="<?= $input_class ?>" placeholder="Nama lengkap Ibu">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="<?= $label_class ?>">No. HP (WhatsApp) *</label>
                        <input type="text" name="no_hp_ortu" required class="<?= $input_class ?>" placeholder="Contoh: 08123...">
                    </div>
                </div>
            </form>
        </div>
        <div class="p-5 border-t border-[#E2E8F0] bg-white flex gap-3 flex-shrink-0">
            <button type="button" onclick="closeModalTambah()" class="<?= $btn_outline ?> flex-1">Batal</button>
            <button type="submit" form="formTambah" class="<?= $btn_primary ?> flex-1">Simpan Data</button>
        </div>
    </div>
</div>

<div id="modal-edit" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModalEdit()"></div>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl relative z-10 overflow-hidden transform transition-all flex flex-col max-h-[90vh]">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between flex-shrink-0">
            <h3 class="font-extrabold text-slate-800 flex items-center"><svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Edit Data Siswa</h3>
            <button type="button" onclick="closeModalEdit()" class="text-slate-400 hover:text-slate-600 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <div class="overflow-y-auto p-6">
            <form action="../../actions/edit_siswa.php" method="POST" id="formEdit" class="space-y-5">
                <input type="hidden" name="no_induk" id="edit-no-induk">
                <input type="hidden" name="id_anggota" id="edit-id-anggota">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="<?= $label_class ?>">No Induk (Read Only)</label>
                        <input type="text" id="edit-no-induk-display" readonly class="<?= $input_class ?> bg-slate-50 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Nama Lengkap *</label>
                        <input type="text" name="nama_siswa" id="edit-nama-siswa" required class="<?= $input_class ?>">
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Jenis Kelamin *</label>
                        <select name="jenis_kelamin" id="edit-jenis-kelamin" required class="<?= $input_class ?>">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Status Keaktifan *</label>
                        <select name="status_aktif" id="edit-status-aktif" required class="<?= $input_class ?>">
                            <option value="Aktif">Aktif</option>
                            <option value="Lulus">Lulus</option>
                            <option value="Keluar">Keluar</option>
                            <option value="Dikeluarkan">Dikeluarkan</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="<?= $label_class ?>">Pindah/Set Kelas</label>
                        <select name="id_kelas" id="edit-id-kelas" class="<?= $input_class ?>">
                            <option value="">-- Tidak ada kelas / Lulus --</option>
                            <?php foreach ($kelas_list as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-[11px] text-orange-600 mt-1.5 flex items-center font-medium">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Mengubah kelas akan memindahkan siswa pada Tahun Ajaran yang sedang Aktif.
                        </p>
                    </div>
                    <div class="sm:col-span-2 pt-4 border-t border-[#E2E8F0]">
                        <h4 class="text-sm font-bold text-slate-800 mb-2">Informasi Orang Tua / Wali</h4>
                        <p class="text-xs text-slate-500 mb-4">Ikat siswa ini dengan akun login Portal Orang Tua.</p>
                        
                        <div class="mb-4">
                            <label class="<?= $label_class ?>">Akun Login Orang Tua Terhubung</label>
                            <select name="id_ortu" id="select-ortu-edit" placeholder="Cari NIK atau Nama Ayah/Ibu...">
                                <option value="">-- Belum Terhubung (Kosong) --</option>
                                <?php foreach($ortu_list as $o): ?>
                                    <option value="<?= $o['id_ortu'] ?>"><?= $o['nik_ortu'] ?> - Ayah: <?= htmlspecialchars($o['nama_ayah']) ?> | Ibu: <?= htmlspecialchars($o['nama_ibu']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Nama Ayah *</label>
                        <input type="text" name="nama_ayah" id="edit-nama-ayah" required class="<?= $input_class ?>">
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Nama Ibu *</label>
                        <input type="text" name="nama_ibu" id="edit-nama-ibu" required class="<?= $input_class ?>">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="<?= $label_class ?>">No. HP (WhatsApp) *</label>
                        <input type="text" name="no_hp_ortu" id="edit-no-hp-ortu" required class="<?= $input_class ?>">
                    </div>
                </div>
            </form>
        </div>
        <div class="p-5 border-t border-[#E2E8F0] bg-white flex gap-3 flex-shrink-0">
            <button type="button" onclick="closeModalEdit()" class="<?= $btn_outline ?> flex-1">Batal</button>
            <button type="submit" form="formEdit" class="<?= $btn_primary ?> flex-1">Update Data</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<script>
// Init TomSelect
let tsTambah, tsEdit;
document.addEventListener("DOMContentLoaded", function() {
    tsTambah = new TomSelect("#select-ortu-tambah", { create: false, sortField: { field: "text", direction: "asc" } });
    tsEdit = new TomSelect("#select-ortu-edit", { create: false, sortField: { field: "text", direction: "asc" } });
});

// Modal Logic Toggle
function openModalImport() { document.getElementById('modal-import').classList.remove('hidden'); }
function closeModalImport() { document.getElementById('modal-import').classList.add('hidden'); }
function openModalTambah() { 
    document.getElementById('modal-tambah').classList.remove('hidden');
    if(tsTambah) tsTambah.clear(); // Reset pilihan ortu
}
function closeModalTambah() { document.getElementById('modal-tambah').classList.add('hidden'); }
function closeModalEdit() { document.getElementById('modal-edit').classList.add('hidden'); }

function editSiswa(data) {
    document.getElementById('edit-no-induk').value = data.no_induk;
    document.getElementById('edit-id-anggota').value = data.id_anggota || '';
    document.getElementById('edit-no-induk-display').value = data.no_induk;
    document.getElementById('edit-nama-siswa').value = data.nama_siswa;
    document.getElementById('edit-jenis-kelamin').value = data.jenis_kelamin;
    document.getElementById('edit-status-aktif').value = data.status_aktif;
    document.getElementById('edit-nama-ayah').value = data.nama_ayah;
    document.getElementById('edit-nama-ibu').value = data.nama_ibu;
    document.getElementById('edit-no-hp-ortu').value = data.no_hp_ortu;
    document.getElementById('edit-id-kelas').value = data.id_kelas || '';
    
    // Set Nilai TomSelect Ortu
    if(tsEdit) {
        if(data.id_ortu) {
            tsEdit.setValue(data.id_ortu);
        } else {
            tsEdit.clear();
        }
    }
    
    document.getElementById('modal-edit').classList.remove('hidden');
}

function hapusSiswa(no_induk, nama) {
    if (confirm(`⚠️ Hapus siswa: ${nama} (${no_induk})?\n\nSiswa yang memiliki riwayat pelanggaran tidak dapat dihapus.\nDisarankan ubah status menjadi Lulus/Keluar.`)) {
        window.location.href = '../../actions/hapus_siswa.php?no_induk=' + encodeURIComponent(no_induk);
    }
}

function downloadTemplate() {
    window.location.href = '../../actions/download_template_siswa.php';
}
</script>

</body>
</html>