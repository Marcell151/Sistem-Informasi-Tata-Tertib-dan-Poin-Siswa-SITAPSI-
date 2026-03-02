<?php
/**
 * SITAPSI - Rekapitulasi Kelas (UI GLOBAL PORTAL)
 * Menampilkan matriks poin dan SP per kategori
 * FIX LOGIKA: Kandidat Reward mengecek poin 1 Tahun Penuh
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_kelas = $_GET['kelas'] ?? null;

$tahun_aktif = fetchOne("
    SELECT id_tahun, nama_tahun 
    FROM tb_tahun_ajaran 
    WHERE status = 'Aktif' 
    LIMIT 1
");

$kelas_list = fetchAll("SELECT * FROM tb_kelas ORDER BY tingkat, nama_kelas");

if (!$id_kelas && !empty($kelas_list)) {
    $id_kelas = $kelas_list[0]['id_kelas'];
}

if ($id_kelas) {
    $kelas_info = fetchOne("SELECT * FROM tb_kelas WHERE id_kelas = :id", ['id' => $id_kelas]);
    
    // LOGIKA BARU: Tambah sub-query total_tahunan
    $siswa_kelas = fetchAll("
        SELECT 
            s.nis,
            s.nama_siswa,
            a.id_anggota,
            a.poin_kelakuan,
            a.poin_kerajinan,
            a.poin_kerapian,
            a.total_poin_umum,
            a.status_sp_terakhir,
            a.status_sp_kelakuan,
            a.status_sp_kerajinan,
            a.status_sp_kerapian,
            (SELECT COALESCE(SUM(d.poin_saat_itu), 0) 
             FROM tb_pelanggaran_header h 
             JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi 
             WHERE h.id_anggota = a.id_anggota AND h.id_tahun = a.id_tahun) as total_tahunan
        FROM tb_siswa s
        JOIN tb_anggota_kelas a ON s.nis = a.nis
        WHERE s.status_aktif = 'Aktif' 
        AND a.id_tahun = :id_tahun
        AND a.id_kelas = :id_kelas
        ORDER BY s.nama_siswa
    ", [
        'id_tahun' => $tahun_aktif['id_tahun'],
        'id_kelas' => $id_kelas
    ]);
}

$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Kelas - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 pl-16 lg:pl-6 py-4 sticky top-0 z-30 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Rekapitulasi Kelas</h1>
                <p class="text-sm font-medium text-slate-500">Matriks poin dan SP per kategori</p>
            </div>
            <?php if ($id_kelas): ?>
            <a href="../../actions/export_rekap_admin.php?kelas=<?= $id_kelas ?>" target="_blank"
               class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center space-x-2 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line>
                </svg>
                <span class="hidden sm:inline">Export Excel</span>
            </a>
            <?php endif; ?>
        </div>

        <div class="p-6 space-y-6 max-w-full mx-auto">

            <div class="<?= $card_class ?> p-5 bg-slate-50/50">
                <form method="GET" class="flex flex-col sm:flex-row items-start sm:items-end gap-4 max-w-md">
                    <div class="w-full">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Pilih Kelas</label>
                        <select name="kelas" onchange="this.form.submit()" 
                                class="w-full px-4 py-2.5 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm font-bold text-slate-700 bg-white">
                            <?php foreach ($kelas_list as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>" <?= $id_kelas == $k['id_kelas'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kelas']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>

            <?php if ($id_kelas && isset($kelas_info)): ?>

            <div class="bg-[#000080] text-white rounded-xl shadow-md shadow-blue-900/10 p-6 relative overflow-hidden">
                <svg class="absolute right-0 top-0 text-white/5 w-48 h-48 transform translate-x-8 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-extrabold mb-1">Rekapitulasi Kelas <?= htmlspecialchars($kelas_info['nama_kelas']) ?></h2>
                        <p class="text-blue-200 font-medium text-sm">Tahun Ajaran: <?= $tahun_aktif['nama_tahun'] ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-blue-200 text-xs font-bold uppercase tracking-wider mb-1">Total Siswa</p>
                        <p class="text-4xl font-extrabold"><?= count($siswa_kelas) ?></p>
                    </div>
                </div>
            </div>

            <div class="<?= $card_class ?> overflow-hidden">
                <div class="p-4 border-b border-[#E2E8F0] bg-white flex items-center">
                    <svg class="w-5 h-5 text-slate-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                    <span class="font-extrabold text-slate-800 text-sm">Matriks Poin dan Status SP Per Kategori</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50/50 text-[10px] text-slate-500 uppercase tracking-wider sticky top-0 border-b border-[#E2E8F0]">
                            <tr>
                                <th class="p-3 text-center sticky left-0 bg-slate-50/90 backdrop-blur z-20 border-r border-[#E2E8F0]">No</th>
                                <th class="p-3 text-left sticky left-10 bg-slate-50/90 backdrop-blur z-20 border-r border-[#E2E8F0]" style="min-width: 200px;">Nama Siswa</th>
                                
                                <th class="p-3 text-center bg-red-50/80 font-extrabold text-red-700">
                                    <div class="flex flex-col items-center"><svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>Kelakuan<br><span class="text-[9px] opacity-75">(Poin)</span></div>
                                </th>
                                <th class="p-3 text-center bg-blue-50/80 font-extrabold text-blue-700">
                                    <div class="flex flex-col items-center"><svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path></svg>Kerajinan<br><span class="text-[9px] opacity-75">(Poin)</span></div>
                                </th>
                                <th class="p-3 text-center bg-yellow-50/80 font-extrabold text-yellow-700">
                                    <div class="flex flex-col items-center"><svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>Kerapian<br><span class="text-[9px] opacity-75">(Poin)</span></div>
                                </th>
                                
                                <th class="p-3 text-center bg-slate-100 font-extrabold text-slate-700 border-l border-[#E2E8F0]">Total<br><span class="text-[9px] opacity-75">Poin Smt</span></th>
                                
                                <th class="p-3 text-center bg-red-50/80 font-extrabold text-red-700 border-l border-[#E2E8F0]">SP<br><span class="text-[9px] opacity-75">Kelakuan</span></th>
                                <th class="p-3 text-center bg-blue-50/80 font-extrabold text-blue-700">SP<br><span class="text-[9px] opacity-75">Kerajinan</span></th>
                                <th class="p-3 text-center bg-yellow-50/80 font-extrabold text-yellow-700">SP<br><span class="text-[9px] opacity-75">Kerapian</span></th>
                                
                                <th class="p-3 text-center bg-slate-800 text-white border-l border-[#E2E8F0]">SP<br><span class="text-[9px] opacity-75">Tertinggi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php if (empty($siswa_kelas)): ?>
                            <tr>
                                <td colspan="10" class="p-12 text-center text-slate-400 font-medium">
                                    Tidak ada siswa di kelas ini
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($siswa_kelas as $idx => $siswa): 
                                // LOGIKA BARU: Cek dari total_tahunan
                                $is_bersih = ($siswa['total_tahunan'] == 0);
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors group <?= $is_bersih ? 'bg-amber-50/30' : '' ?>">
                                <td class="p-3 text-center sticky left-0 bg-white group-hover:bg-slate-50 <?= $is_bersih ? 'bg-amber-50/30 group-hover:bg-amber-50/50' : '' ?> border-r border-[#E2E8F0] font-bold text-slate-500 text-xs"><?= $idx + 1 ?></td>
                                <td class="p-3 sticky left-10 bg-white group-hover:bg-slate-50 <?= $is_bersih ? 'bg-amber-50/30 group-hover:bg-amber-50/50' : '' ?> border-r border-[#E2E8F0] font-bold text-[#000080] text-xs" style="min-width: 200px;">
                                    <div class="flex items-center">
                                        <?= htmlspecialchars($siswa['nama_siswa']) ?>
                                        <?php if ($is_bersih): ?>
                                            <span title="Kandidat Reward" class="ml-2 text-amber-500"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="p-3 text-center bg-red-50/30">
                                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded-md text-[11px] font-extrabold border border-red-200 shadow-sm"><?= $siswa['poin_kelakuan'] ?></span>
                                </td>
                                <td class="p-3 text-center bg-blue-50/30">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-md text-[11px] font-extrabold border border-blue-200 shadow-sm"><?= $siswa['poin_kerajinan'] ?></span>
                                </td>
                                <td class="p-3 text-center bg-yellow-50/30">
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-md text-[11px] font-extrabold border border-yellow-200 shadow-sm"><?= $siswa['poin_kerapian'] ?></span>
                                </td>
                                
                                <td class="p-3 text-center bg-slate-50 border-l border-[#E2E8F0]">
                                    <span class="px-2.5 py-1 bg-slate-800 text-white rounded-md text-[11px] font-extrabold shadow-sm"><?= $siswa['total_poin_umum'] ?></span>
                                </td>
                                
                                <td class="p-3 text-center border-l border-[#E2E8F0]">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $siswa['status_sp_kelakuan'] === 'Aman' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-red-50 text-red-600 border border-red-200' ?>">
                                        <?= $siswa['status_sp_kelakuan'] ?>
                                    </span>
                                </td>
                                <td class="p-3 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $siswa['status_sp_kerajinan'] === 'Aman' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-blue-50 text-blue-600 border border-blue-200' ?>">
                                        <?= $siswa['status_sp_kerajinan'] ?>
                                    </span>
                                </td>
                                <td class="p-3 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $siswa['status_sp_kerapian'] === 'Aman' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-yellow-50 text-yellow-600 border border-yellow-200' ?>">
                                        <?= $siswa['status_sp_kerapian'] ?>
                                    </span>
                                </td>
                                
                                <td class="p-3 text-center border-l border-[#E2E8F0]">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-extrabold shadow-sm <?= $siswa['status_sp_terakhir'] === 'Aman' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white' ?>">
                                        <?= $siswa['status_sp_terakhir'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 p-5 rounded-xl shadow-sm">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    <div>
                        <h4 class="font-extrabold text-[#000080] text-sm mb-2 uppercase tracking-wide">Keterangan Matriks</h4>
                        <ul class="text-xs text-blue-800 space-y-1.5 font-medium">
                            <li class="flex items-center"><span class="w-1.5 h-1.5 bg-[#000080] rounded-full mr-2"></span> <strong>Poin</strong>: Akumulasi poin pelanggaran per kategori (Kelakuan, Kerajinan, Kerapian).</li>
                            <li class="flex items-center"><span class="w-1.5 h-1.5 bg-[#000080] rounded-full mr-2"></span> <strong>SP Per Kategori</strong>: Status SP yang dihitung per kategori secara terpisah.</li>
                            <li class="flex items-center"><span class="w-1.5 h-1.5 bg-[#000080] rounded-full mr-2"></span> <strong>SP Tertinggi</strong>: Status SP maksimal dari ketiga kategori (sebagai kesimpulan laporan akhir).</li>
                            <li class="flex items-center"><span class="w-1.5 h-1.5 bg-[#000080] rounded-full mr-2"></span> Siswa dapat memiliki SP yang berbeda di setiap kategori (Misal: SP1 Kelakuan, namun Aman di Kerapian).</li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>