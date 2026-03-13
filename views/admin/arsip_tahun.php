<?php
/**
 * PORTAL SEKOLAH - Arsip Global Super-App
 * Menampilkan data historis Read-Only dari ke-6 Sistem Terintegrasi
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_tahun = $_GET['tahun'] ?? null;
$tab_aktif = $_GET['tab'] ?? 'tatib'; // Default tab adalah Tatib

// --- UI CONFIG VARIABLES ---
$btn_primary = "px-4 py-2.5 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-md hover:bg-blue-900 transition-all";
$input_class = "w-full px-4 py-2.5 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 text-sm bg-white";
$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm";

// ========================================
// STATE 1: PILIH TAHUN ARSIP
// ========================================
if (!$id_tahun) {
    $tahun_arsip = fetchAll("SELECT * FROM tb_tahun_ajaran WHERE status = 'Arsip' ORDER BY id_tahun DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arsip Global - Portal Sekolah</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">
<div class="flex h-screen overflow-hidden">
    <?php include '../../includes/sidebar_admin.php'; ?>
    <div class="flex-1 overflow-auto lg:ml-64">
        <div class="bg-white border-b border-[#E2E8F0] px-6 py-4 sticky top-0 z-30">
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Arsip Global Sekolah</h1>
            <p class="text-sm font-medium text-slate-500">Pusat data historis terintegrasi (Read-Only)</p>
        </div>

        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($tahun_arsip)): ?>
                <div class="col-span-full bg-white rounded-xl border border-dashed border-[#E2E8F0] p-12 text-center">
                    <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M21 8v13H3V8"></path><path d="M1 3h22v5H1z"></path></svg>
                    <p class="text-slate-500 font-bold text-lg">Belum ada tahun ajaran yang diarsipkan.</p>
                </div>
                <?php else: ?>
                <?php foreach ($tahun_arsip as $t): ?>
                <a href="?tahun=<?= $t['id_tahun'] ?>" class="block group">
                    <div class="<?= $card_class ?> p-6 hover:shadow-lg hover:border-[#000080]/50 transition-all transform hover:-translate-y-1">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-xl font-extrabold text-slate-800 group-hover:text-[#000080]"><?= $t['nama_tahun'] ?></h3>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Semester <?= $t['semester_aktif'] ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-[#000080] group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                            </div>
                        </div>
                        <div class="flex items-center text-slate-500 group-hover:text-[#000080] font-bold text-xs uppercase">
                            <span>Buka Data Arsip 6 Sistem</span>
                            <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
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
// STATE 2: DETAIL ARSIP BERDASARKAN TAHUN
// ========================================
$tahun_info = fetchOne("SELECT * FROM tb_tahun_ajaran WHERE id_tahun = :id", ['id' => $id_tahun]);
if (!$tahun_info) { header('Location: arsip_tahun.php'); exit; }

// --- QUERY STATISTIK ADVANCED UNTUK TAB TATIB ---
$stats_tatib = fetchOne("
    SELECT 
        COUNT(DISTINCT a.no_induk) as total_siswa,
        COUNT(DISTINCT h.id_transaksi) as total_pelanggaran,
        SUM(a.total_poin_umum) as total_poin
    FROM tb_anggota_kelas a
    LEFT JOIN tb_pelanggaran_header h ON a.id_anggota = h.id_anggota AND h.id_tahun = :id1
    WHERE a.id_tahun = :id2
", ['id1' => $id_tahun, 'id2' => $id_tahun]);

$top_siswa = fetchOne("
    SELECT s.nama_siswa, k.nama_kelas, a.total_poin_umum 
    FROM tb_anggota_kelas a
    JOIN tb_siswa s ON a.no_induk = s.no_induk
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE a.id_tahun = :id AND a.total_poin_umum > 0
    ORDER BY a.total_poin_umum DESC LIMIT 1
", ['id' => $id_tahun]);

$top_kelas = fetchOne("
    SELECT k.nama_kelas, SUM(a.total_poin_umum) as total_poin_kelas 
    FROM tb_anggota_kelas a
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE a.id_tahun = :id
    GROUP BY k.id_kelas
    ORDER BY total_poin_kelas DESC LIMIT 1
", ['id' => $id_tahun]);

$total_sp = fetchOne("
    SELECT COUNT(id_sp) as jml_sp 
    FROM tb_riwayat_sp r
    JOIN tb_anggota_kelas a ON r.id_anggota = a.id_anggota
    WHERE a.id_tahun = :id
", ['id' => $id_tahun]);

// --- QUERY DATA TABEL TATIB ---
$filter_search = $_GET['search'] ?? '';
$sql_table = "
    SELECT h.tanggal, s.nama_siswa, k.nama_kelas, d.poin_saat_itu, jp.nama_pelanggaran
    FROM tb_pelanggaran_header h
    JOIN tb_anggota_kelas a ON h.id_anggota = a.id_anggota
    JOIN tb_siswa s ON a.no_induk = s.no_induk
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    WHERE h.id_tahun = :id
";
$params_table = ['id' => $id_tahun];

if (!empty($filter_search)) {
    $sql_table .= " AND (s.nama_siswa LIKE :search1 OR s.no_induk LIKE :search2)";
    $params_table['search1'] = "%$filter_search%";
    $params_table['search2'] = "%$filter_search%";
}
$sql_table .= " ORDER BY h.tanggal DESC LIMIT 50";
$list_pelanggaran = fetchAll($sql_table, $params_table);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Arsip <?= $tahun_info['nama_tahun'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">
<div class="flex h-screen overflow-hidden">
    <?php include '../../includes/sidebar_admin.php'; ?>
    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 py-4 sticky top-0 z-30 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="arsip_tahun.php" class="p-2 bg-slate-100 rounded-lg text-slate-600 hover:bg-slate-200"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg></a>
                <div>
                    <h1 class="text-xl font-extrabold text-slate-800">Arsip: <?= htmlspecialchars($tahun_info['nama_tahun']) ?></h1>
                    <p class="text-xs font-bold text-slate-500 uppercase">Mode Read-Only Aktif</p>
                </div>
            </div>
        </div>

        <div class="p-6 max-w-7xl mx-auto space-y-6">
            
            <div class="bg-white p-2 rounded-xl border border-[#E2E8F0] shadow-sm flex overflow-x-auto scrollbar-hide space-x-2">
                <a href="?tahun=<?= $id_tahun ?>&tab=lab" class="px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap transition-colors <?= $tab_aktif === 'lab' ? 'bg-purple-600 text-white' : 'text-slate-500 hover:bg-slate-100' ?>">1. Inv. Lab</a>
                <a href="?tahun=<?= $id_tahun ?>&tab=nonlab" class="px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap transition-colors <?= $tab_aktif === 'nonlab' ? 'bg-pink-600 text-white' : 'text-slate-500 hover:bg-slate-100' ?>">2. Inv. Non-Lab</a>
                <a href="?tahun=<?= $id_tahun ?>&tab=absen" class="px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap transition-colors <?= $tab_aktif === 'absen' ? 'bg-emerald-600 text-white' : 'text-slate-500 hover:bg-slate-100' ?>">3. E-Absensi</a>
                <a href="?tahun=<?= $id_tahun ?>&tab=ekstra" class="px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap transition-colors <?= $tab_aktif === 'ekstra' ? 'bg-amber-500 text-white' : 'text-slate-500 hover:bg-slate-100' ?>">4. Ekstrakurikuler</a>
                <a href="?tahun=<?= $id_tahun ?>&tab=tatib" class="px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap transition-colors <?= $tab_aktif === 'tatib' ? 'bg-[#000080] text-white' : 'text-slate-500 hover:bg-slate-100' ?>">5. SITAPSI (Tatib)</a>
                <a href="?tahun=<?= $id_tahun ?>&tab=perpus" class="px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap transition-colors <?= $tab_aktif === 'perpus' ? 'bg-teal-600 text-white' : 'text-slate-500 hover:bg-slate-100' ?>">6. Perpustakaan</a>
            </div>

            <?php if ($tab_aktif === 'tatib'): ?>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-xl border border-[#E2E8F0] shadow-sm border-l-4 border-l-red-500">
                    <p class="text-[10px] font-bold text-slate-500 uppercase">Siswa Poin Tertinggi</p>
                    <p class="text-sm font-extrabold text-slate-800 mt-1 truncate"><?= $top_siswa ? htmlspecialchars($top_siswa['nama_siswa']) : 'Tidak Ada' ?></p>
                    <p class="text-xs text-red-600 font-bold mt-1"><?= $top_siswa ? $top_siswa['total_poin_umum'] . ' Poin (' . $top_siswa['nama_kelas'] . ')' : '-' ?></p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-[#E2E8F0] shadow-sm border-l-4 border-l-amber-500">
                    <p class="text-[10px] font-bold text-slate-500 uppercase">Kelas Paling Rawan</p>
                    <p class="text-lg font-extrabold text-slate-800 mt-1"><?= $top_kelas ? $top_kelas['nama_kelas'] : '-' ?></p>
                    <p class="text-xs text-amber-600 font-bold mt-1">Total: <?= $top_kelas ? $top_kelas['total_poin_kelas'] : '0' ?> Poin</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-[#E2E8F0] shadow-sm border-l-4 border-l-blue-500">
                    <p class="text-[10px] font-bold text-slate-500 uppercase">Total SP Dikeluarkan</p>
                    <p class="text-2xl font-extrabold text-slate-800 mt-1"><?= number_format($total_sp['jml_sp']) ?></p>
                    <p class="text-xs text-slate-500 font-medium mt-1">Surat Peringatan</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-[#E2E8F0] shadow-sm border-l-4 border-l-slate-500">
                    <p class="text-[10px] font-bold text-slate-500 uppercase">Total Kejadian</p>
                    <p class="text-2xl font-extrabold text-slate-800 mt-1"><?= number_format($stats_tatib['total_pelanggaran']) ?></p>
                    <p class="text-xs text-slate-500 font-medium mt-1">Kasus Tercatat</p>
                </div>
            </div>

            <div class="<?= $card_class ?> overflow-hidden mt-6">
                <div class="p-4 border-b border-[#E2E8F0] bg-slate-50/50 flex justify-between items-center">
                    <span class="font-bold text-slate-800 text-sm">Riwayat Kejadian Terakhir (Maks 50)</span>
                    <form method="GET" class="flex gap-2">
                        <input type="hidden" name="tahun" value="<?= $id_tahun ?>">
                        <input type="hidden" name="tab" value="tatib">
                        <input type="text" name="search" value="<?= htmlspecialchars($filter_search) ?>" placeholder="Cari Siswa..." class="px-3 py-1.5 text-xs border rounded-md focus:outline-none focus:border-[#000080]">
                        <button type="submit" class="bg-[#000080] text-white px-3 py-1.5 rounded-md text-xs font-bold">Cari</button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-white text-xs text-slate-500 uppercase border-b border-[#E2E8F0]">
                            <tr>
                                <th class="p-3 font-bold">Tanggal</th>
                                <th class="p-3 font-bold">Siswa & Kelas</th>
                                <th class="p-3 font-bold">Pelanggaran</th>
                                <th class="p-3 font-bold text-center">Poin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php foreach($list_pelanggaran as $log): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="p-3 text-xs font-bold text-slate-600"><?= date('d M Y', strtotime($log['tanggal'])) ?></td>
                                <td class="p-3">
                                    <p class="font-bold text-slate-800 text-xs"><?= htmlspecialchars($log['nama_siswa']) ?></p>
                                    <p class="text-[10px] text-slate-500"><?= $log['nama_kelas'] ?></p>
                                </td>
                                <td class="p-3 text-xs text-slate-600 truncate max-w-[200px]"><?= htmlspecialchars($log['nama_pelanggaran']) ?></td>
                                <td class="p-3 text-center"><span class="px-2 py-1 bg-red-50 text-red-600 font-bold text-[10px] rounded">+<?= $log['poin_saat_itu'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($list_pelanggaran)) echo '<tr><td colspan="4" class="p-6 text-center text-sm text-slate-500">Data tidak ditemukan.</td></tr>'; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php else: ?>
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-16 text-center shadow-sm">
                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
                <h3 class="text-xl font-extrabold text-slate-800 mb-2">Modul Sedang Dalam Pengembangan</h3>
                <p class="text-sm text-slate-500 max-w-md mx-auto">Database untuk sistem <strong class="uppercase"><?= htmlspecialchars($tab_aktif) ?></strong> belum terintegrasi ke Portal Induk. Teman tim Anda akan menyambungkan datanya ke halaman ini nantinya.</p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>