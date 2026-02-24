<?php
/**
 * SITAPSI - Manajemen SP (UI GLOBAL PORTAL)
 * Menampilkan riwayat SP dengan kategori pemicu
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

$filter_kelas = $_GET['kelas'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';

$kelas_list = fetchAll("SELECT * FROM tb_kelas ORDER BY tingkat, nama_kelas");

// Query riwayat SP
$sql = "
    SELECT 
        s.nis, s.nama_siswa, s.foto_profil,
        k.nama_kelas,
        a.total_poin_umum, a.status_sp_terakhir, a.status_sp_kelakuan, a.status_sp_kerajinan, a.status_sp_kerapian,
        a.poin_kelakuan, a.poin_kerajinan, a.poin_kerapian,
        sp.id_sp, sp.tingkat_sp, sp.kategori_pemicu, sp.tanggal_terbit, sp.tanggal_validasi, sp.status
    FROM tb_riwayat_sp sp
    JOIN tb_anggota_kelas a ON sp.id_anggota = a.id_anggota
    JOIN tb_siswa s ON a.nis = s.nis
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE a.id_tahun = :id_tahun
";

$params = ['id_tahun' => $tahun_aktif['id_tahun']];

if ($filter_kelas !== 'all') {
    $sql .= " AND k.id_kelas = :kelas";
    $params['kelas'] = $filter_kelas;
}
if ($filter_status !== 'all') {
    $sql .= " AND sp.status = :status";
    $params['status'] = $filter_status;
}

$sql .= " ORDER BY sp.tanggal_terbit DESC, sp.id_sp DESC";
$riwayat_sp = fetchAll($sql, $params);

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// --- UI CONFIG VARIABLES ---
$btn_primary = "px-4 py-2.5 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-md shadow-blue-900/10 hover:bg-blue-900 transition-all flex items-center justify-center";
$input_class = "w-full px-4 py-2 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm text-slate-700 bg-white transition-all";
$label_class = "block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wide";
$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen SP - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 pl-16 lg:pl-6 py-4 sticky top-0 z-30">
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Manajemen Surat Peringatan</h1>
            <p class="text-sm font-medium text-slate-500">Kelola dan validasi SP per kategori siswa</p>
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

            <div class="bg-red-600 text-white rounded-xl shadow-md p-6 relative overflow-hidden">
                <svg class="absolute right-0 top-0 text-white/10 w-48 h-48 transform translate-x-8 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm0 3.83L19.82 19H4.18L12 5.83zM11 16h2v2h-2v-2zm0-7h2v5h-2V9z"></path></svg>
                <div class="relative z-10 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-extrabold mb-1 flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            Riwayat Surat Peringatan (SP)
                        </h2>
                        <p class="text-red-100 font-medium text-sm">Tahun Ajaran: <?= $tahun_aktif['nama_tahun'] ?></p>
                    </div>
                </div>
            </div>

            <div class="<?= $card_class ?> p-5 bg-slate-50/30">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="<?= $label_class ?>">Filter Kelas</label>
                        <select name="kelas" class="<?= $input_class ?>">
                            <option value="all">Semua Kelas</option>
                            <?php foreach ($kelas_list as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>" <?= $filter_kelas == $k['id_kelas'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Status SP</label>
                        <select name="status" class="<?= $input_class ?>">
                            <option value="all">Semua Status</option>
                            <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Menunggu TTD (Pending)</option>
                            <option value="Selesai" <?= $filter_status === 'Selesai' ? 'selected' : '' ?>>Telah Disetujui (Selesai)</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="<?= $btn_primary ?> w-full h-[38px]">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            Filter Data
                        </button>
                    </div>
                </form>
            </div>

            <div class="<?= $card_class ?> overflow-hidden">
                <div class="p-4 border-b border-[#E2E8F0] bg-white flex justify-between items-center">
                    <span class="font-bold text-slate-800 text-sm">Daftar Surat Peringatan</span>
                    <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2 py-1 rounded-md">Total: <?= count($riwayat_sp) ?> Surat</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50/50 text-xs text-slate-500 uppercase border-b border-[#E2E8F0]">
                            <tr>
                                <th class="p-4 font-bold">Siswa</th>
                                <th class="p-4 font-bold text-center">Tingkat SP</th>
                                <th class="p-4 font-bold text-center">Kategori Pemicu</th>
                                <th class="p-4 font-bold text-center">Poin Kategori</th>
                                <th class="p-4 font-bold text-center">Status 3 Silo</th>
                                <th class="p-4 font-bold text-center">Tgl Terbit</th>
                                <th class="p-4 font-bold text-center">Status</th>
                                <th class="p-4 font-bold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php if (empty($riwayat_sp)): ?>
                            <tr>
                                <td colspan="8" class="p-12 text-center text-slate-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <p class="font-medium text-sm">Tidak ada riwayat SP dengan filter ini</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($riwayat_sp as $sp): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-[#000080] rounded-xl flex items-center justify-center overflow-hidden flex-shrink-0 shadow-sm text-white font-extrabold">
                                            <?php if($sp['foto_profil']): ?>
                                                <img src="../../assets/uploads/siswa/<?= htmlspecialchars($sp['foto_profil']) ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <?= strtoupper(substr($sp['nama_siswa'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800 text-[13px]"><?= htmlspecialchars($sp['nama_siswa']) ?></p>
                                            <p class="text-[10px] font-medium text-slate-500"><?= $sp['nama_kelas'] ?> • <?= $sp['nis'] ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2.5 py-1 bg-red-50 text-red-600 border border-red-200 rounded-md font-bold text-xs shadow-sm">
                                        <?= $sp['tingkat_sp'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                        <?= $sp['kategori_pemicu'] === 'KELAKUAN' ? 'bg-red-100 text-red-700' : '' ?>
                                        <?= $sp['kategori_pemicu'] === 'KERAJINAN' ? 'bg-blue-100 text-blue-700' : '' ?>
                                        <?= $sp['kategori_pemicu'] === 'KERAPIAN' ? 'bg-yellow-100 text-yellow-700' : '' ?>">
                                        <?= $sp['kategori_pemicu'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <?php
                                    $poin_pemicu = 0;
                                    if ($sp['kategori_pemicu'] === 'KELAKUAN') $poin_pemicu = $sp['poin_kelakuan'];
                                    elseif ($sp['kategori_pemicu'] === 'KERAJINAN') $poin_pemicu = $sp['poin_kerajinan'];
                                    elseif ($sp['kategori_pemicu'] === 'KERAPIAN') $poin_pemicu = $sp['poin_kerapian'];
                                    ?>
                                    <span class="px-2.5 py-1 bg-slate-800 text-white rounded-md font-extrabold text-[11px] shadow-sm">
                                        <?= $poin_pemicu ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex gap-1 justify-center">
                                        <?php
                                        $sp_kategori = [
                                            ['label' => 'KL', 'status' => $sp['status_sp_kelakuan'], 'color' => 'red'],
                                            ['label' => 'KJ', 'status' => $sp['status_sp_kerajinan'], 'color' => 'blue'],
                                            ['label' => 'KP', 'status' => $sp['status_sp_kerapian'], 'color' => 'yellow']
                                        ];
                                        foreach ($sp_kategori as $kat):
                                            if ($kat['status'] !== 'Aman'):
                                        ?>
                                        <span class="px-1.5 py-0.5 border border-<?= $kat['color'] ?>-200 bg-<?= $kat['color'] ?>-50 text-<?= $kat['color'] ?>-700 rounded text-[9px] font-bold">
                                            <?= $kat['label'] ?>:<?= $kat['status'] ?>
                                        </span>
                                        <?php endif; endforeach; ?>
                                    </div>
                                </td>
                                <td class="p-4 text-center font-bold text-xs text-slate-600">
                                    <?= date('d M Y', strtotime($sp['tanggal_terbit'])) ?>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold shadow-sm
                                        <?= $sp['status'] === 'Pending' ? 'bg-amber-50 text-amber-600 border border-amber-200' : 'bg-emerald-50 text-emerald-600 border border-emerald-200' ?>">
                                        <?= $sp['status'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex gap-2 justify-center">
                                        <a href="cetak_sp.php?id=<?= $sp['id_sp'] ?>" target="_blank"
                                           class="p-1.5 bg-white border border-[#E2E8F0] text-[#000080] rounded-md hover:bg-blue-50 transition-colors shadow-sm" title="Cetak Surat">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                                        </a>
                                        <?php if ($sp['status'] === 'Pending'): ?>
                                        <form action="../../actions/validasi_sp.php" method="POST" class="inline">
                                            <input type="hidden" name="id_sp" value="<?= $sp['id_sp'] ?>">
                                            <button type="submit" onclick="return confirm('Validasi Surat Peringatan ini sebagai Selesai / Sudah Ditandatangani?')"
                                                    class="p-1.5 bg-emerald-50 border border-emerald-200 text-emerald-600 rounded-md hover:bg-emerald-100 transition-colors shadow-sm" title="Validasi">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            </button>
                                        </form>
                                        <?php endif; ?>
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

</body>
</html>