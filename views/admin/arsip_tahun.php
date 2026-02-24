<?php
/**
 * SITAPSI - Arsip Tahun Ajaran (UI GLOBAL PORTAL)
 * Fitur: Filter Kelas, Pencarian Siswa, Filter Kategori
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_tahun = $_GET['tahun'] ?? null;

// --- UI CONFIG VARIABLES ---
$btn_primary = "px-4 py-2.5 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-md shadow-blue-900/10 hover:bg-blue-900 transition-all flex items-center justify-center";
$btn_outline = "px-4 py-2.5 bg-white border border-[#E2E8F0] text-slate-700 text-sm font-semibold rounded-lg shadow-sm hover:bg-slate-50 transition-all flex items-center justify-center";
$input_class = "w-full px-4 py-2.5 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm text-slate-700 bg-white transition-all";
$label_class = "block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wide";
$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm";

// ========================================
// STATE 1: PILIH TAHUN (JIKA BELUM ADA TAHUN DIPILIH)
// ========================================
if (!$id_tahun) {
    $tahun_arsip = fetchAll("
        SELECT * FROM tb_tahun_ajaran 
        WHERE status = 'Arsip' 
        ORDER BY id_tahun DESC
    ");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arsip Tahun Ajaran - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        <div class="bg-white border-b border-[#E2E8F0] px-6 py-4 sticky top-0 z-30 flex items-center space-x-4">
            <a href="dashboard.php" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            </a>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Arsip Sistem</h1>
                <p class="text-sm font-medium text-slate-500">Pilih tahun ajaran untuk melihat data historis</p>
            </div>
        </div>

        <div class="p-6 space-y-6 max-w-7xl mx-auto">
            
            <div class="bg-[#000080] text-white rounded-xl shadow-md shadow-blue-900/10 p-6 relative overflow-hidden">
                <div class="relative z-10">
                    <h2 class="text-2xl font-extrabold mb-2 flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 8v13H3V8"></path><path d="M1 3h22v5H1z"></path><path d="M10 12h4"></path></svg>
                        Arsip Data Pelanggaran
                    </h2>
                    <p class="text-blue-100 font-medium">Data historis pada tahun ajaran yang telah ditutup (Read-Only).</p>
                </div>
                <svg class="absolute right-0 top-0 text-white/5 w-48 h-48 transform translate-x-8 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"></path></svg>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($tahun_arsip)): ?>
                <div class="col-span-full bg-white rounded-xl border border-dashed border-[#E2E8F0] p-12 text-center">
                    <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M21 8v13H3V8"></path><path d="M1 3h22v5H1z"></path><path d="M10 12h4"></path></svg>
                    <p class="text-slate-500 font-bold text-lg">Belum ada tahun ajaran yang diarsipkan</p>
                </div>
                <?php else: ?>
                <?php foreach ($tahun_arsip as $t): 
                    $stats = fetchOne("
                        SELECT 
                            COUNT(DISTINCT a.nis) as total_siswa,
                            COUNT(DISTINCT h.id_transaksi) as total_pelanggaran,
                            SUM(a.total_poin_umum) as total_poin
                        FROM tb_anggota_kelas a
                        LEFT JOIN tb_pelanggaran_header h ON a.id_anggota = h.id_anggota AND h.id_tahun = :id_tahun1
                        WHERE a.id_tahun = :id_tahun2
                    ", ['id_tahun1' => $t['id_tahun'], 'id_tahun2' => $t['id_tahun']]);
                ?>
                <a href="?tahun=<?= $t['id_tahun'] ?>" class="block group">
                    <div class="<?= $card_class ?> p-6 hover:shadow-lg hover:border-[#000080]/30 transition-all transform hover:-translate-y-1 relative overflow-hidden">
                        <div class="w-1.5 h-full bg-slate-300 group-hover:bg-[#000080] absolute left-0 top-0 transition-colors"></div>
                        <div class="flex items-center justify-between mb-5 pl-2">
                            <div>
                                <h3 class="text-xl font-extrabold text-slate-800 group-hover:text-[#000080] transition-colors"><?= $t['nama_tahun'] ?></h3>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Semester <?= $t['semester_aktif'] ?></p>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-[#000080]/10 group-hover:text-[#000080] transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-3 mb-4 pl-2">
                            <div class="bg-slate-50 border border-[#E2E8F0] p-2.5 rounded-lg text-center">
                                <p class="text-[10px] font-bold text-slate-500 uppercase">Siswa</p>
                                <p class="text-sm font-extrabold text-slate-800"><?= number_format((float)$stats['total_siswa']) ?></p>
                            </div>
                            <div class="bg-slate-50 border border-[#E2E8F0] p-2.5 rounded-lg text-center">
                                <p class="text-[10px] font-bold text-slate-500 uppercase">Kejadian</p>
                                <p class="text-sm font-extrabold text-slate-800"><?= number_format((float)$stats['total_pelanggaran']) ?></p>
                            </div>
                            <div class="bg-red-50 border border-red-100 p-2.5 rounded-lg text-center">
                                <p class="text-[10px] font-bold text-red-600 uppercase">Poin</p>
                                <p class="text-sm font-extrabold text-red-700"><?= number_format((float)$stats['total_poin']) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center text-slate-500 group-hover:text-[#000080] font-bold text-[11px] uppercase tracking-wider pl-2 mt-4">
                            <span>Buka Arsip</span>
                            <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php
    exit;
}

// ========================================
// STATE 2: DETAIL TAHUN (JIKA TAHUN SUDAH DIPILIH)
// ========================================

// 1. Validasi Tahun
$tahun_info = fetchOne("SELECT * FROM tb_tahun_ajaran WHERE id_tahun = :id", ['id' => $id_tahun]);
if (!$tahun_info || $tahun_info['status'] !== 'Arsip') {
    $_SESSION['error_message'] = '❌ Tahun ajaran tidak valid atau bukan arsip';
    header('Location: arsip_tahun.php');
    exit;
}

// 2. Ambil Parameter Filter
$filter_kelas    = $_GET['kelas'] ?? 'all';
$filter_search   = $_GET['search'] ?? '';
$filter_kategori = $_GET['kategori'] ?? 'all';

// 3. Ambil Daftar Kelas
$kelas_list = fetchAll("
    SELECT DISTINCT k.id_kelas, k.nama_kelas 
    FROM tb_kelas k
    JOIN tb_anggota_kelas a ON k.id_kelas = a.id_kelas
    WHERE a.id_tahun = :id_tahun
    ORDER BY k.nama_kelas
", ['id_tahun' => $id_tahun]);

// 4. Ambil Daftar Kategori
$kategori_list = fetchAll("SELECT * FROM tb_kategori_pelanggaran");

// 5. Build Dynamic SQL Query
$sql = "
    SELECT 
        h.id_transaksi, h.tanggal, h.waktu, h.tipe_form,
        s.nis, s.nama_siswa,
        k.nama_kelas,
        g.nama_guru,
        GROUP_CONCAT(DISTINCT jp.nama_pelanggaran SEPARATOR ', ') as pelanggaran_list,
        SUM(d.poin_saat_itu) as total_poin
    FROM tb_pelanggaran_header h
    JOIN tb_anggota_kelas a ON h.id_anggota = a.id_anggota
    JOIN tb_siswa s ON a.nis = s.nis
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    JOIN tb_guru g ON h.id_guru = g.id_guru
    LEFT JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    LEFT JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    WHERE h.id_tahun = :id_tahun
";

$params = ['id_tahun' => $id_tahun];

if ($filter_kelas !== 'all') {
    $sql .= " AND k.id_kelas = :kelas";
    $params['kelas'] = $filter_kelas;
}
if (!empty($filter_search)) {
    $sql .= " AND (s.nama_siswa LIKE :search OR s.nis LIKE :search)";
    $params['search'] = "%$filter_search%";
}
if ($filter_kategori !== 'all') {
    $sql .= " AND jp.id_kategori = :kategori";
    $params['kategori'] = $filter_kategori;
}

$sql .= " GROUP BY h.id_transaksi ORDER BY h.tanggal DESC, h.waktu DESC LIMIT 200";
$pelanggaran_list = fetchAll($sql, $params);

// 6. Hitung Statistik
$stats = fetchOne("
    SELECT 
        COUNT(DISTINCT a.nis) as total_siswa,
        COUNT(DISTINCT h.id_transaksi) as total_pelanggaran,
        SUM(a.total_poin_umum) as total_poin
    FROM tb_anggota_kelas a
    LEFT JOIN tb_pelanggaran_header h ON a.id_anggota = h.id_anggota AND h.id_tahun = :id_tahun1
    WHERE a.id_tahun = :id_tahun2
", ['id_tahun1' => $id_tahun, 'id_tahun2' => $id_tahun]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arsip <?= $tahun_info['nama_tahun'] ?> - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 py-4 sticky top-0 z-30 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="arsip_tahun.php" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                </a>
                <div class="h-6 w-px bg-[#E2E8F0]"></div>
                <div>
                    <h1 class="text-xl font-extrabold text-slate-800">Arsip: <?= htmlspecialchars($tahun_info['nama_tahun']) ?></h1>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Semester <?= htmlspecialchars($tahun_info['semester_aktif']) ?></p>
                </div>
            </div>
            
            <div class="hidden md:flex space-x-3">
                <div class="flex items-center px-3 py-1.5 bg-blue-50 border border-blue-100 rounded-lg">
                    <svg class="w-4 h-4 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <span class="text-xs font-bold text-blue-800">Total Kejadian: <?= number_format($stats['total_pelanggaran']) ?></span>
                </div>
                <div class="flex items-center px-3 py-1.5 bg-red-50 border border-red-100 rounded-lg">
                    <svg class="w-4 h-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span class="text-xs font-bold text-red-800">Total Poin: <?= number_format((float)$stats['total_poin']) ?></span>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6 max-w-7xl mx-auto">

            <div class="<?= $card_class ?> p-5 bg-slate-50/30">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <input type="hidden" name="tahun" value="<?= $id_tahun ?>">
                    
                    <div class="md:col-span-4">
                        <label class="<?= $label_class ?>">Cari Siswa (Nama/NIS)</label>
                        <div class="relative">
                            <input type="text" name="search" value="<?= htmlspecialchars($filter_search) ?>" placeholder="Contoh: Budi / 12345" class="<?= $input_class ?> pl-9">
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </div>
                    </div>

                    <div class="md:col-span-3">
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

                    <div class="md:col-span-3">
                        <label class="<?= $label_class ?>">Kategori</label>
                        <select name="kategori" class="<?= $input_class ?>">
                            <option value="all">Semua Kategori</option>
                            <?php foreach ($kategori_list as $kat): ?>
                            <option value="<?= $kat['id_kategori'] ?>" <?= $filter_kategori == $kat['id_kategori'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-2 flex space-x-2">
                        <button type="submit" class="<?= $btn_primary ?> flex-1 h-[38px]">Filter</button>
                        <a href="?tahun=<?= $id_tahun ?>" class="<?= $btn_outline ?> px-3 h-[38px]" title="Reset">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
                        </a>
                    </div>
                </form>
            </div>

            <div class="<?= $card_class ?> overflow-hidden">
                <div class="p-4 border-b border-[#E2E8F0] flex justify-between items-center bg-white">
                    <span class="font-bold text-slate-800 text-sm flex items-center">
                        <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        Hasil Pencarian Arsip
                    </span>
                    <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2 py-1 rounded-md">Maks 200 data</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50/50 text-xs text-slate-500 uppercase border-b border-[#E2E8F0]">
                            <tr>
                                <th class="p-4 font-bold">Waktu</th>
                                <th class="p-4 font-bold">Identitas Siswa</th>
                                <th class="p-4 font-bold">Detail Pelanggaran</th>
                                <th class="p-4 font-bold text-center">Poin</th>
                                <th class="p-4 font-bold text-center">Tipe</th>
                                <th class="p-4 font-bold">Pelapor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php if(empty($pelanggaran_list)): ?>
                            <tr>
                                <td colspan="6" class="p-12 text-center text-slate-400">
                                    <svg class="w-12 h-12 mb-3 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    <p class="font-medium text-sm">Tidak ada data yang sesuai dengan filter.</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($pelanggaran_list as $log): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-4 text-slate-800">
                                    <span class="font-bold text-xs"><?= date('d M Y', strtotime($log['tanggal'])) ?></span><br>
                                    <span class="text-[10px] font-medium text-slate-500"><?= substr($log['waktu'], 0, 5) ?> WIB</span>
                                </td>
                                <td class="p-4">
                                    <p class="font-bold text-slate-800 text-[13px]"><?= htmlspecialchars($log['nama_siswa']) ?></p>
                                    <p class="text-[10px] font-medium text-slate-500 bg-slate-100 inline-block px-1.5 py-0.5 rounded mt-0.5"><?= $log['nama_kelas'] ?> • <?= $log['nis'] ?></p>
                                </td>
                                <td class="p-4 max-w-xs">
                                    <p class="text-xs text-slate-700 truncate" title="<?= htmlspecialchars($log['pelanggaran_list'] ?: '-') ?>"><?= htmlspecialchars($log['pelanggaran_list'] ?: '-') ?></p>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-1 text-[11px] font-bold rounded-md bg-red-50 text-red-600 border border-red-200 shadow-sm">+<?= $log['total_poin'] ?></span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-1 text-[10px] font-bold uppercase rounded-md <?= $log['tipe_form'] === 'Piket' ? 'bg-[#000080]/10 text-[#000080] border border-[#000080]/20' : 'bg-purple-50 text-purple-700 border border-purple-200' ?>">
                                        <?= $log['tipe_form'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-xs font-medium text-slate-700"><?= htmlspecialchars($log['nama_guru']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-center p-4 bg-amber-50 text-amber-800 rounded-xl border border-amber-200 text-sm shadow-sm font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                <span><strong>Mode Arsip:</strong> Data hanya dapat dibaca (Read-Only) dan tidak dapat diedit atau dihapus.</span>
            </div>

        </div>
    </div>
</div>

</body>
</html>