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
    <title>Arsip Global</title>
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

// --- QUERY STATISTIK KARTU (Global Setahun) ---
$stats_global = fetchOne("
    SELECT 
        COUNT(DISTINCT h.id_anggota) as total_siswa_melanggar,
        COUNT(DISTINCT h.id_transaksi) as total_pelanggaran
    FROM tb_pelanggaran_header h
    WHERE h.id_tahun = :id
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


// --- PERSIAPAN FILTER PENCARIAN ---
$filter_search   = $_GET['search'] ?? '';
$filter_semester = $_GET['semester'] ?? 'all';
$filter_kelas    = $_GET['kelas'] ?? 'all';

$kelas_list = fetchAll("SELECT id_kelas, nama_kelas FROM tb_kelas ORDER BY tingkat, nama_kelas");

// --- QUERY REKAPITULASI SISWA (Filtered) ---
$sql_rekap = "
    SELECT 
        a.id_anggota, s.no_induk, s.nama_siswa, k.nama_kelas,
        a.status_sp_terakhir,
        COUNT(DISTINCT h.id_transaksi) as jml_kejadian,
        COALESCE(SUM(d.poin_saat_itu), 0) as total_poin
    FROM tb_anggota_kelas a
    JOIN tb_siswa s ON a.no_induk = s.no_induk
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    JOIN tb_pelanggaran_header h ON a.id_anggota = h.id_anggota
    LEFT JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    WHERE h.id_tahun = :id_tahun
";
$params_rekap = ['id_tahun' => $id_tahun];

if ($filter_semester !== 'all') {
    $sql_rekap .= " AND h.semester = :semester";
    $params_rekap['semester'] = $filter_semester;
}
if ($filter_kelas !== 'all') {
    $sql_rekap .= " AND a.id_kelas = :kelas";
    $params_rekap['kelas'] = $filter_kelas;
}
if (!empty($filter_search)) {
    $sql_rekap .= " AND (s.nama_siswa LIKE :s1 OR s.no_induk LIKE :s2)";
    $params_rekap['s1'] = "%$filter_search%";
    $params_rekap['s2'] = "%$filter_search%";
}

$sql_rekap .= " GROUP BY a.id_anggota ORDER BY total_poin DESC LIMIT 100";
$list_siswa_bermasalah = fetchAll($sql_rekap, $params_rekap);

// --- PERSIAPAN DATA UNTUK MODAL DETAIL ---
// Kita ambil semua detail transaksi untuk siswa-siswa yang tampil di layar
$details_by_anggota = [];
if (!empty($list_siswa_bermasalah)) {
    $id_anggota_list = array_column($list_siswa_bermasalah, 'id_anggota');
    $placeholders = implode(',', array_fill(0, count($id_anggota_list), '?'));
    
    $sql_details = "
        SELECT h.id_anggota, h.tanggal, h.semester, d.poin_saat_itu, jp.nama_pelanggaran
        FROM tb_pelanggaran_header h
        JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
        JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
        WHERE h.id_tahun = ? AND h.id_anggota IN ($placeholders)
    ";
    
// Siapkan parameter (ID Tahun + Array ID Anggota)
    $params_details = array_merge([$id_tahun], $id_anggota_list);
    
    // MENGGUNAKAN FUNGSI fetchAll() BAWAAN SISTEM ANDA
    $raw_details = fetchAll($sql_details, $params_details);

    if ($raw_details) {
        foreach($raw_details as $row) {
            $details_by_anggota[$row['id_anggota']][] = $row;
        }
    }
}
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
    <div class="flex-1 overflow-auto lg:ml-64 relative">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 py-4 sticky top-0 z-30 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="arsip_tahun.php" class="p-2 bg-slate-100 rounded-lg text-slate-600 hover:bg-slate-200"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg></a>
                <div>
                    <h1 class="text-xl font-extrabold text-slate-800">Arsip: <?= htmlspecialchars($tahun_info['nama_tahun']) ?></h1>
                    <p class="text-xs font-bold text-slate-500 uppercase">Rekam Jejak Historis (Read-Only)</p>
                </div>
            </div>
        </div>

        <div class="p-6 max-w-7xl mx-auto space-y-6 pb-20">
            
            <?php if ($tab_aktif === 'tatib'): ?>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-xl border border-[#E2E8F0] shadow-sm border-l-4 border-l-red-500">
                    <p class="text-[10px] font-bold text-slate-500 uppercase">Siswa Melanggar</p>
                    <p class="text-2xl font-extrabold text-slate-800 mt-1"><?= number_format($stats_global['total_siswa_melanggar']) ?></p>
                    <p class="text-xs text-red-600 font-medium mt-1">Orang tercatat di arsip</p>
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
                    <p class="text-2xl font-extrabold text-slate-800 mt-1"><?= number_format($stats_global['total_pelanggaran']) ?></p>
                    <p class="text-xs text-slate-500 font-medium mt-1">Kasus Tercatat</p>
                </div>
            </div>

            <div class="<?= $card_class ?> p-4 bg-slate-50/50">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <input type="hidden" name="tahun" value="<?= $id_tahun ?>">
                    <input type="hidden" name="tab" value="tatib">
                    
                    <div class="md:col-span-4">
                        <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Cari Nama/NIK</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($filter_search) ?>" placeholder="Masukkan nama..." class="<?= $input_class ?>">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Semester</label>
                        <select name="semester" class="<?= $input_class ?>">
                            <option value="all">Semua Semester</option>
                            <option value="Ganjil" <?= $filter_semester === 'Ganjil' ? 'selected' : '' ?>>Semester Ganjil</option>
                            <option value="Genap" <?= $filter_semester === 'Genap' ? 'selected' : '' ?>>Semester Genap</option>
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Kelas</label>
                        <select name="kelas" class="<?= $input_class ?>">
                            <option value="all">Semua Kelas</option>
                            <?php foreach ($kelas_list as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>" <?= $filter_kelas == $k['id_kelas'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2 flex space-x-2">
                        <button type="submit" class="<?= $btn_primary ?> flex-1">Filter</button>
                        <a href="?tahun=<?= $id_tahun ?>&tab=tatib" class="px-3 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50" title="Reset">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
                        </a>
                    </div>
                </form>
            </div>

            <div class="<?= $card_class ?> overflow-hidden">
                <div class="p-4 border-b border-[#E2E8F0] bg-white flex justify-between items-center">
                    <span class="font-bold text-slate-800 text-sm">Rekam Jejak Siswa (Maks 100)</span>
                    <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-1 rounded font-bold uppercase">Diurutkan berdasar poin tertinggi</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 text-xs text-slate-500 uppercase border-b border-[#E2E8F0]">
                            <tr>
                                <th class="p-3 font-bold">Identitas Siswa</th>
                                <th class="p-3 font-bold text-center">Total Kejadian</th>
                                <th class="p-3 font-bold text-center">Total Poin</th>
                                <th class="p-3 font-bold text-center">Status Akhir</th>
                                <th class="p-3 font-bold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php foreach($list_siswa_bermasalah as $siswa): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="p-3">
                                    <p class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($siswa['nama_siswa']) ?></p>
                                    <p class="text-[10px] text-slate-500 font-medium"><?= $siswa['nama_kelas'] ?> • <?= $siswa['no_induk'] ?></p>
                                </td>
                                <td class="p-3 text-center">
                                    <span class="font-bold text-slate-700"><?= $siswa['jml_kejadian'] ?></span>x Kasus
                                </td>
                                <td class="p-3 text-center">
                                    <span class="px-2 py-1 bg-red-50 text-red-600 font-extrabold text-[11px] rounded shadow-sm"><?= $siswa['total_poin'] ?> Pts</span>
                                </td>
                                <td class="p-3 text-center">
                                    <?php 
                                        $sp_class = $siswa['status_sp_terakhir'] === 'Aman' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200';
                                    ?>
                                    <span class="px-2 py-1 <?= $sp_class ?> border font-bold text-[10px] rounded-md"><?= $siswa['status_sp_terakhir'] ?></span>
                                </td>
                                <td class="p-3 text-center">
                                    <button onclick="openDetailModal('<?= $siswa['id_anggota'] ?>', '<?= htmlspecialchars(addslashes($siswa['nama_siswa'])) ?>')" class="inline-flex items-center text-[#000080] hover:text-white hover:bg-[#000080] border border-[#000080] font-bold text-[10px] px-3 py-1.5 rounded-lg transition-colors">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        Lihat Riwayat
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($list_siswa_bermasalah)) echo '<tr><td colspan="5" class="p-8 text-center text-sm font-bold text-slate-400">Tidak ada rekam jejak pelanggaran sesuai filter.</td></tr>'; ?>
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

<div id="modal-detail" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeDetailModal()"></div>
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full relative z-10 overflow-hidden flex flex-col max-h-[85vh]">
        <div class="p-5 border-b border-slate-200 bg-slate-50/80 flex justify-between items-center">
            <div>
                <h3 class="font-extrabold text-slate-800 text-lg" id="modal-siswa-nama">Nama Siswa</h3>
                <p class="text-xs font-bold text-slate-500 uppercase">Rekam Jejak Historis</p>
            </div>
            <button onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600 transition-colors p-2 bg-white rounded-lg shadow-sm border border-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        
        <div class="p-0 overflow-y-auto flex-1 bg-slate-50">
            <div id="modal-content-area" class="divide-y divide-[#E2E8F0]">
                </div>
        </div>
    </div>
</div>

<script>
    // Mengonversi data detail PHP ke format JSON JavaScript agar bisa dipanggil saat modal dibuka
    const riwayatData = <?= json_encode($details_by_anggota) ?>;

    function openDetailModal(idAnggota, namaSiswa) {
        document.getElementById('modal-siswa-nama').innerText = namaSiswa;
        const contentArea = document.getElementById('modal-content-area');
        contentArea.innerHTML = ''; // Clear previous

        const riwayat = riwayatData[idAnggota];

        if (!riwayat || riwayat.length === 0) {
            contentArea.innerHTML = '<div class="p-8 text-center text-slate-400 font-bold text-sm">Tidak ada rincian kejadian.</div>';
        } else {
            let html = '';
            riwayat.forEach(item => {
                // Format Tanggal
                const dateObj = new Date(item.tanggal);
                const formattedDate = dateObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                
                html += `
                <div class="p-4 bg-white flex items-start gap-4 hover:bg-slate-50 transition-colors">
                    <div class="w-12 flex-shrink-0 text-center">
                        <div class="text-[10px] font-bold text-slate-400 uppercase leading-tight mb-1">Semester</div>
                        <div class="text-xs font-extrabold text-[#000080] bg-blue-50 py-1 rounded">${item.semester}</div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-500 mb-0.5">${formattedDate}</p>
                        <p class="text-sm font-bold text-slate-800 leading-snug">${item.nama_pelanggaran}</p>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="px-2.5 py-1 bg-red-50 text-red-600 font-extrabold text-xs rounded-lg shadow-sm border border-red-100">+${item.poin_saat_itu} Pts</span>
                    </div>
                </div>
                `;
            });
            contentArea.innerHTML = html;
        }

        document.getElementById('modal-detail').classList.remove('hidden');
    }

    function closeDetailModal() {
        document.getElementById('modal-detail').classList.add('hidden');
    }
</script>

</body>
</html>