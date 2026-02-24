<?php
/**
 * SITAPSI - Rekap Kelas untuk Guru (UI GLOBAL PORTAL)
 * Fitur: Matriks Poin, Reward Badge, Navigasi tanpa Sidebar
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireGuru();

$id_kelas = $_GET['kelas'] ?? null;

// Ambil info guru yang login secara spesifik
$id_guru_login = $_SESSION['user_id'];
$guru = fetchOne("SELECT id_guru, nama_guru, id_kelas FROM tb_guru WHERE id_guru = :id", ['id' => $id_guru_login]);

$id_kelas_wali = $guru['id_kelas'] ?? null;

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

$siswa_kelas = [];
if ($id_kelas) {
    $kelas_info = fetchOne("SELECT * FROM tb_kelas WHERE id_kelas = :id", ['id' => $id_kelas]);
    
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
            a.status_sp_kerapian
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

// UI CONFIG
$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Kelas - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC] pb-24 md:pb-8"> <?php include '../../includes/navbar_guru.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Rekapitulasi Poin Kelas</h1>
                <p class="text-sm font-medium text-slate-500">Lihat total pelanggaran dan status SP siswa</p>
            </div>
            
            <?php if ($id_kelas): ?>
            <a href="export_rekap.php?kelas=<?= $id_kelas ?>" target="_blank"
               class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-bold flex items-center justify-center transition-colors shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                Export Excel
            </a>
            <?php endif; ?>
        </div>

        <div class="space-y-6">

            <div class="<?= $card_class ?> p-5 bg-slate-50/50">
                <form method="GET" class="flex flex-col sm:flex-row gap-4 items-start sm:items-end max-w-md">
                    <div class="w-full">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Pilih Kelas</label>
                        <select name="kelas" onchange="this.form.submit()" class="w-full px-4 py-2.5 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm font-bold text-slate-700 bg-white">
                            <?php foreach ($kelas_list as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>" <?= $id_kelas == $k['id_kelas'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kelas']) ?> 
                                <?= ($k['id_kelas'] == $id_kelas_wali) ? '(Wali Kelas)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>

            <?php if ($id_kelas && isset($kelas_info)): ?>
            
            <div class="bg-[#000080] text-white rounded-xl shadow-md p-6 relative overflow-hidden">
                <svg class="absolute right-0 top-0 text-white/5 w-48 h-48 transform translate-x-8 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-extrabold mb-1">Kelas <?= htmlspecialchars($kelas_info['nama_kelas']) ?></h2>
                        <p class="text-blue-200 font-medium text-sm">Tahun Ajaran <?= $tahun_aktif['nama_tahun'] ?></p>
                        <?php if($id_kelas_wali == $id_kelas): ?>
                            <span class="inline-block mt-3 px-3 py-1 bg-amber-400 text-amber-900 rounded-md text-[10px] font-extrabold uppercase tracking-wider shadow-sm">
                                👑 Kelas Anda (Wali Kelas)
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <p class="text-blue-200 text-xs font-bold uppercase tracking-wider mb-1">Total Siswa</p>
                        <p class="text-4xl font-extrabold"><?= count($siswa_kelas) ?></p>
                    </div>
                </div>
            </div>

            <div class="<?= $card_class ?> overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50/50 text-[10px] text-slate-500 uppercase tracking-wider sticky top-0 border-b border-[#E2E8F0]">
                            <tr>
                                <th class="p-3 text-center sticky left-0 bg-slate-50/90 backdrop-blur z-20 border-r border-[#E2E8F0]">No</th>
                                <th class="p-3 text-left sticky left-10 bg-slate-50/90 backdrop-blur z-20 border-r border-[#E2E8F0]" style="min-width: 200px;">Nama Siswa</th>
                                
                                <th class="p-3 text-center bg-red-50/80 font-extrabold text-red-700">
                                    <div class="flex flex-col items-center"><svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg>Kelakuan</div>
                                </th>
                                <th class="p-3 text-center bg-blue-50/80 font-extrabold text-blue-700">
                                    <div class="flex flex-col items-center"><svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path></svg>Kerajinan</div>
                                </th>
                                <th class="p-3 text-center bg-yellow-50/80 font-extrabold text-yellow-700">
                                    <div class="flex flex-col items-center"><svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line></svg>Kerapian</div>
                                </th>
                                
                                <th class="p-3 text-center bg-slate-100 font-extrabold text-slate-700 border-l border-[#E2E8F0]">Total<br><span class="text-[9px] opacity-75">Poin</span></th>
                                <th class="p-3 text-center bg-slate-800 text-white border-l border-[#E2E8F0]">SP<br><span class="text-[9px] opacity-75">Max</span></th>
                                <th class="p-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php if (empty($siswa_kelas)): ?>
                            <tr>
                                <td colspan=\"8\" class="p-12 text-center text-slate-400 font-medium text-sm">Tidak ada data siswa.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($siswa_kelas as $idx => $siswa): 
                                $is_bersih = ($siswa['total_poin_umum'] == 0);
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
                                    <span class="px-2 py-1 <?= $siswa['poin_kelakuan'] > 0 ? 'bg-red-100 text-red-700' : 'text-slate-400' ?> rounded-md text-[11px] font-extrabold border <?= $siswa['poin_kelakuan'] > 0 ? 'border-red-200 shadow-sm' : 'border-transparent' ?>"><?= $siswa['poin_kelakuan'] ?></span>
                                </td>
                                <td class="p-3 text-center bg-blue-50/30">
                                    <span class="px-2 py-1 <?= $siswa['poin_kerajinan'] > 0 ? 'bg-blue-100 text-blue-700' : 'text-slate-400' ?> rounded-md text-[11px] font-extrabold border <?= $siswa['poin_kerajinan'] > 0 ? 'border-blue-200 shadow-sm' : 'border-transparent' ?>"><?= $siswa['poin_kerajinan'] ?></span>
                                </td>
                                <td class="p-3 text-center bg-yellow-50/30">
                                    <span class="px-2 py-1 <?= $siswa['poin_kerapian'] > 0 ? 'bg-yellow-100 text-yellow-700' : 'text-slate-400' ?> rounded-md text-[11px] font-extrabold border <?= $siswa['poin_kerapian'] > 0 ? 'border-yellow-200 shadow-sm' : 'border-transparent' ?>"><?= $siswa['poin_kerapian'] ?></span>
                                </td>
                                
                                <td class="p-3 text-center bg-slate-50 border-l border-[#E2E8F0]">
                                    <span class="px-2.5 py-1 bg-slate-800 text-white rounded-md text-[11px] font-extrabold shadow-sm"><?= $siswa['total_poin_umum'] ?></span>
                                </td>
                                
                                <td class="p-3 text-center border-l border-[#E2E8F0]">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-extrabold shadow-sm <?= $siswa['status_sp_terakhir'] === 'Aman' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-red-500 text-white' ?>">
                                        <?= $siswa['status_sp_terakhir'] ?>
                                    </span>
                                </td>

                                <td class="p-3 text-center">
                                    <a href="detail_siswa.php?id=<?= $siswa['id_anggota'] ?>" class="px-3 py-1.5 bg-white border border-[#E2E8F0] text-slate-700 rounded-md hover:bg-slate-50 hover:text-[#000080] text-xs font-bold shadow-sm transition-colors inline-block">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 p-5 rounded-xl shadow-sm text-sm">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    <div>
                        <h4 class="font-extrabold text-[#000080] mb-2 uppercase tracking-wide">Panduan Singkat</h4>
                        <ul class="text-blue-800 space-y-1.5 font-medium text-xs">
                            <li class="flex items-center"><svg class="w-4 h-4 text-amber-500 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg> <strong>Kandidat Reward:</strong> Siswa berprestasi dengan poin bersih (0). Baris mereka akan disorot warna kuning pastel.</li>
                            <li class="flex items-center"><span class="w-1.5 h-1.5 bg-[#000080] rounded-full mr-2 ml-1"></span> <strong>SP Max (Tertinggi):</strong> Adalah tingkat SP terparah yang saat ini dikenakan pada siswa.</li>
                            <li class="flex items-center"><span class="w-1.5 h-1.5 bg-[#000080] rounded-full mr-2 ml-1"></span> <strong>Klik Detail:</strong> Untuk melihat rincian riwayat pelanggaran dan tombol Laporan Wali Kelas.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php endif; ?>

        </div>
    </main>

</body>
</html>