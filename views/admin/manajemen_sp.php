<?php
/**
 * SITAPSI - Manajemen SP (COMPLETE - SP PER KATEGORI)
 * Menampilkan riwayat SP dengan kategori pemicu
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$tahun_aktif = fetchOne("
    SELECT id_tahun, nama_tahun 
    FROM tb_tahun_ajaran 
    WHERE status = 'Aktif' 
    LIMIT 1
");

$filter_kelas = $_GET['kelas'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';

$kelas_list = fetchAll("SELECT * FROM tb_kelas ORDER BY tingkat, nama_kelas");

// Query riwayat SP dengan detail per kategori
$sql = "
    SELECT 
        s.nis,
        s.nama_siswa,
        s.foto_profil,
        k.nama_kelas,
        a.total_poin_umum,
        a.status_sp_terakhir,
        a.status_sp_kelakuan,
        a.status_sp_kerajinan,
        a.status_sp_kerapian,
        a.poin_kelakuan,
        a.poin_kerajinan,
        a.poin_kerapian,
        sp.id_sp,
        sp.tingkat_sp,
        sp.kategori_pemicu,
        sp.tanggal_terbit,
        sp.tanggal_validasi,
        sp.status
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen SP - SITAPSI</title>
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
        
        <div class="bg-white shadow-sm border-b px-6 py-4 sticky top-0 z-30">
            <h1 class="text-2xl font-bold text-gray-800">Manajemen Surat Peringatan (SP)</h1>
            <p class="text-sm text-gray-500">Kelola dan validasi SP per kategori</p>
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

            <!-- Info Header -->
            <div class="bg-gradient-to-r from-red-600 to-red-800 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">📋 Riwayat Surat Peringatan</h2>
                        <p class="text-red-200">Tahun Ajaran: <?= $tahun_aktif['nama_tahun'] ?></p>
                    </div>
                    <div class="text-6xl">⚠️</div>
                </div>
            </div>

            <!-- Filter -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filter Kelas</label>
                        <select name="kelas" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                            <option value="all">Semua Kelas</option>
                            <?php foreach ($kelas_list as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>" <?= $filter_kelas == $k['id_kelas'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kelas']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status SP</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                            <option value="all">Semua Status</option>
                            <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Selesai" <?= $filter_status === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-navy hover:bg-blue-900 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                            🔍 Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabel Riwayat SP dengan Kategori Pemicu -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b font-bold text-gray-700">
                    📜 Daftar Surat Peringatan (Total: <?= count($riwayat_sp) ?>)
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="p-4">Siswa</th>
                                <th class="p-4">Kelas</th>
                                <th class="p-4 text-center">Tingkat SP</th>
                                <th class="p-4 text-center">Kategori Pemicu</th>
                                <th class="p-4 text-center">Poin Kategori</th>
                                <th class="p-4 text-center">Status SP Per Kategori</th>
                                <th class="p-4 text-center">Tanggal Terbit</th>
                                <th class="p-4 text-center">Status</th>
                                <th class="p-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($riwayat_sp)): ?>
                            <tr>
                                <td colspan="9" class="p-12 text-center text-gray-500">
                                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="font-medium">Tidak ada riwayat SP dengan filter ini</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($riwayat_sp as $sp): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-navy rounded-full flex items-center justify-center overflow-hidden flex-shrink-0">
                                            <?php if($sp['foto_profil']): ?>
                                                <img src="../../assets/uploads/siswa/<?= $sp['foto_profil'] ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <span class="text-white font-bold text-sm"><?= strtoupper(substr($sp['nama_siswa'], 0, 1)) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-navy"><?= htmlspecialchars($sp['nama_siswa']) ?></p>
                                            <p class="text-xs text-gray-500"><?= $sp['nis'] ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-gray-600"><?= $sp['nama_kelas'] ?></td>
                                <td class="p-4 text-center">
                                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full font-bold text-xs">
                                        <?= $sp['tingkat_sp'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-3 py-1 rounded text-xs font-bold
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
                                    <span class="px-2 py-1 bg-gray-800 text-white rounded-full font-bold text-xs">
                                        <?= $poin_pemicu ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex gap-1 justify-center">
                                        <?php
                                        $sp_kategori = [
                                            ['label' => 'K', 'status' => $sp['status_sp_kelakuan'], 'title' => 'Kelakuan', 'color' => 'red'],
                                            ['label' => 'Rj', 'status' => $sp['status_sp_kerajinan'], 'title' => 'Kerajinan', 'color' => 'blue'],
                                            ['label' => 'Rp', 'status' => $sp['status_sp_kerapian'], 'title' => 'Kerapian', 'color' => 'yellow']
                                        ];
                                        foreach ($sp_kategori as $kat):
                                            if ($kat['status'] !== 'Aman'):
                                        ?>
                                        <span class="px-2 py-1 bg-<?= $kat['color'] ?>-100 text-<?= $kat['color'] ?>-800 rounded text-xs font-medium" 
                                              title="<?= $kat['title'] ?>: <?= $kat['status'] ?>">
                                            <?= $kat['label'] ?>:<?= $kat['status'] ?>
                                        </span>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                </td>
                                <td class="p-4 text-center whitespace-nowrap text-gray-600">
                                    <?= date('d/m/Y', strtotime($sp['tanggal_terbit'])) ?>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium
                                        <?= $sp['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                                        <?= $sp['status'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex gap-2 justify-center">
                                        <a href="cetak_sp.php?id=<?= $sp['id_sp'] ?>" target="_blank"
                                           class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100" title="Cetak Surat">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                            </svg>
                                        </a>
                                        <?php if ($sp['status'] === 'Pending'): ?>
                                        <form action="../../actions/validasi_sp.php" method="POST" class="inline">
                                            <input type="hidden" name="id_sp" value="<?= $sp['id_sp'] ?>">
                                            <button type="submit" onclick="return confirm('Validasi SP ini sebagai Selesai?')"
                                                    class="p-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100" title="Validasi">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
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