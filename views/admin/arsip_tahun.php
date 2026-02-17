<?php
/**
 * SITAPSI - Arsip Tahun Ajaran (COMPLETE FILTER)
 * Fitur: Filter Kelas, Pencarian Siswa, Filter Kategori
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_tahun = $_GET['tahun'] ?? null;

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
        <div class="bg-white shadow-sm border-b px-6 py-4 sticky top-0 z-30 flex items-center space-x-4">
            <a href="pengaturan_akademik.php" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Arsip Tahun Ajaran</h1>
                <p class="text-sm text-gray-500">Pilih tahun ajaran untuk melihat data arsip</p>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <div class="bg-gradient-to-r from-gray-600 to-gray-800 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">📦 Arsip Data Pelanggaran</h2>
                        <p class="text-gray-200">Data historis yang tidak dapat diubah lagi.</p>
                    </div>
                    <div class="text-6xl">🗄️</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($tahun_arsip)): ?>
                <div class="col-span-full bg-white rounded-xl shadow-sm p-12 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                    <p class="text-gray-500 font-medium">Belum ada tahun ajaran yang diarsipkan</p>
                </div>
                <?php else: ?>
                <?php foreach ($tahun_arsip as $t): 
                    // Statistik Preview
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
                    <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-all transform hover:-translate-y-1 border-l-4 border-gray-500">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800 group-hover:text-gray-600"><?= $t['nama_tahun'] ?></h3>
                                <p class="text-sm text-gray-500">Semester <?= $t['semester_aktif'] ?></p>
                            </div>
                            <div class="text-4xl">📁</div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            <div class="bg-gray-50 p-2 rounded text-center">
                                <p class="text-xs text-gray-600">Siswa</p>
                                <p class="text-lg font-bold text-gray-800"><?= $stats['total_siswa'] ?></p>
                            </div>
                            <div class="bg-gray-50 p-2 rounded text-center">
                                <p class="text-xs text-gray-600">Kejadian</p>
                                <p class="text-lg font-bold text-gray-800"><?= $stats['total_pelanggaran'] ?></p>
                            </div>
                            <div class="bg-gray-50 p-2 rounded text-center">
                                <p class="text-xs text-gray-600">Poin</p>
                                <p class="text-lg font-bold text-gray-800"><?= number_format((float)$stats['total_poin']) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center text-gray-600 group-hover:text-gray-800 font-medium text-sm">
                            <span>Buka Arsip</span>
                            <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
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
$filter_kategori = $_GET['kategori'] ?? 'all'; // New: Filter Kategori

// 3. Ambil Daftar Kelas (Dropdown)
// Hanya kelas yang ada di tahun ajaran tersebut
$kelas_list = fetchAll("
    SELECT DISTINCT k.id_kelas, k.nama_kelas 
    FROM tb_kelas k
    JOIN tb_anggota_kelas a ON k.id_kelas = a.id_kelas
    WHERE a.id_tahun = :id_tahun
    ORDER BY k.nama_kelas
", ['id_tahun' => $id_tahun]);

// 4. Ambil Daftar Kategori (Dropdown)
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

// Apply Filter Kelas
if ($filter_kelas !== 'all') {
    $sql .= " AND k.id_kelas = :kelas";
    $params['kelas'] = $filter_kelas;
}

// Apply Filter Pencarian (Nama Siswa atau NIS)
if (!empty($filter_search)) {
    $sql .= " AND (s.nama_siswa LIKE :search OR s.nis LIKE :search)";
    $params['search'] = "%$filter_search%";
}

// Apply Filter Kategori
if ($filter_kategori !== 'all') {
    $sql .= " AND jp.id_kategori = :kategori";
    $params['kategori'] = $filter_kategori;
}

// Finalize Query
$sql .= " GROUP BY h.id_transaksi ORDER BY h.tanggal DESC, h.waktu DESC LIMIT 200";

$pelanggaran_list = fetchAll($sql, $params);

// 6. Hitung Statistik Ringkas (Sesuai Filter)
// Note: Statistik ini global per tahun, tidak ikut filter agar admin tetap tau total konteksnya.
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
        
        <div class="bg-white shadow-sm border-b px-6 py-4 sticky top-0 z-30 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="arsip_tahun.php" class="text-gray-400 hover:text-navy transition-colors flex items-center">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span class="text-sm font-medium">Kembali</span>
                </a>
                <div class="h-6 w-px bg-gray-300"></div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">Arsip: <?= $tahun_info['nama_tahun'] ?></h1>
                    <p class="text-xs text-gray-500">Semester <?= $tahun_info['semester_aktif'] ?></p>
                </div>
            </div>
            
            <div class="hidden md:flex space-x-2">
                <span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold border border-blue-100">Total Poin: <?= number_format((float)$stats['total_poin']) ?></span>
                <span class="px-3 py-1 bg-red-50 text-red-700 rounded-full text-xs font-bold border border-red-100">Kasus: <?= $stats['total_pelanggaran'] ?></span>
            </div>
        </div>

        <div class="p-6 space-y-6">

            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h3 class="text-sm font-bold text-gray-700 uppercase mb-4 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    Filter Data Arsip
                </h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <input type="hidden" name="tahun" value="<?= $id_tahun ?>">
                    
                    <div class="md:col-span-4">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Cari Siswa (Nama/NIS)</label>
                        <div class="relative">
                            <input type="text" name="search" value="<?= htmlspecialchars($filter_search) ?>" placeholder="Contoh: Budi atau 12345" 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent text-sm">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Kelas Pada Tahun Ini</label>
                        <select name="kelas" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent text-sm">
                            <option value="all">Semua Kelas</option>
                            <?php foreach ($kelas_list as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>" <?= $filter_kelas == $k['id_kelas'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kelas']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Kategori Pelanggaran</label>
                        <select name="kategori" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent text-sm">
                            <option value="all">Semua Kategori</option>
                            <?php foreach ($kategori_list as $kat): ?>
                            <option value="<?= $kat['id_kategori'] ?>" <?= $filter_kategori == $kat['id_kategori'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-2 flex space-x-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-navy hover:bg-blue-900 text-white font-semibold rounded-lg transition-colors text-sm flex justify-center items-center">
                            Filter
                        </button>
                        <a href="?tahun=<?= $id_tahun ?>" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition-colors text-sm flex justify-center items-center" title="Reset Filter">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                    <span class="font-bold text-gray-700 text-sm">📋 Hasil Pencarian Arsip</span>
                    <span class="text-xs text-gray-500 italic">Menampilkan maks 200 data</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-100 text-xs text-gray-600 uppercase tracking-wider">
                            <tr>
                                <th class="p-4 font-semibold">Tanggal & Waktu</th>
                                <th class="p-4 font-semibold">Identitas Siswa</th>
                                <th class="p-4 font-semibold">Detail Pelanggaran</th>
                                <th class="p-4 font-semibold text-center">Poin</th>
                                <th class="p-4 font-semibold text-center">Tipe</th>
                                <th class="p-4 font-semibold">Pelapor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if(empty($pelanggaran_list)): ?>
                            <tr>
                                <td colspan="6" class="p-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <p>Tidak ada data pelanggaran yang sesuai filter.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($pelanggaran_list as $log): ?>
                            <tr class="hover:bg-blue-50 transition-colors">
                                <td class="p-4 whitespace-nowrap text-gray-700">
                                    <div class="font-medium"><?= date('d M Y', strtotime($log['tanggal'])) ?></div>
                                    <div class="text-xs text-gray-500"><?= substr($log['waktu'], 0, 5) ?> WIB</div>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-navy"><?= htmlspecialchars($log['nama_siswa']) ?></div>
                                    <div class="text-xs text-gray-500"><?= $log['nama_kelas'] ?> • <?= $log['nis'] ?></div>
                                </td>
                                <td class="p-4 text-gray-700 max-w-xs">
                                    <div class="text-sm"><?= htmlspecialchars($log['pelanggaran_list'] ?: '-') ?></div>
                                </td>
                                <td class="p-4 whitespace-nowrap text-center">
                                    <span class="px-2 py-1 text-xs font-bold rounded bg-red-100 text-red-800 border border-red-200">
                                        +<?= $log['total_poin'] ?>
                                    </span>
                                </td>
                                <td class="p-4 whitespace-nowrap text-center">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?= $log['tipe_form'] === 'Piket' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' ?>">
                                        <?= $log['tipe_form'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-gray-600 text-sm"><?= htmlspecialchars($log['nama_guru']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-center p-4 bg-yellow-50 text-yellow-800 rounded-lg border border-yellow-200 text-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                <span>Mode Arsip: Data hanya dapat dibaca (Read-Only) dan tidak dapat diedit atau dihapus.</span>
            </div>

        </div>
    </div>
</div>

</body>
</html>