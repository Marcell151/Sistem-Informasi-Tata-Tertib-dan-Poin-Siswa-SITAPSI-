<?php
/**
 * SITAPSI - Monitoring Siswa
 * Pencarian siswa dan melihat detail riwayat pelanggaran
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

// Ambil tahun ajaran aktif
$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// Search
$search = $_GET['search'] ?? '';
$filter_kelas = $_GET['kelas'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';

// Ambil daftar kelas untuk filter
$kelas_list = fetchAll("SELECT id_kelas, nama_kelas FROM tb_kelas ORDER BY nama_kelas");

// Query siswa
$sql = "
    SELECT 
        s.nis,
        s.nama_siswa,
        s.jenis_kelamin,
        s.foto_profil,
        k.nama_kelas,
        a.id_anggota,
        a.poin_kelakuan,
        a.poin_kerajinan,
        a.poin_kerapian,
        a.total_poin_umum,
        a.status_sp_terakhir
    FROM tb_siswa s
    JOIN tb_anggota_kelas a ON s.nis = a.nis
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE s.status_aktif = 'Aktif' 
    AND a.id_tahun = :id_tahun
";

$params = ['id_tahun' => $tahun_aktif['id_tahun']];

if (!empty($search)) {
    $sql .= " AND (s.nama_siswa LIKE :search OR s.nis LIKE :search)";
    $params['search'] = "%$search%";
}

if ($filter_kelas !== 'all') {
    $sql .= " AND k.id_kelas = :kelas";
    $params['kelas'] = $filter_kelas;
}

if ($filter_status !== 'all') {
    if ($filter_status === 'aman') {
        $sql .= " AND a.status_sp_terakhir = 'Aman'";
    } else {
        $sql .= " AND a.status_sp_terakhir != 'Aman'";
    }
}

$sql .= " ORDER BY s.nama_siswa";

$siswa_list = fetchAll($sql, $params);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Siswa - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'navy': '#000080',
                        'kelakuan': '#DC2626',
                        'kerajinan': '#2563EB',
                        'kerapian': '#D97706'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto">
        
        <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-6 py-4">
                <h1 class="text-2xl font-bold text-gray-800">Monitoring Siswa</h1>
                <p class="text-sm text-gray-600 mt-1">Pencarian & detail riwayat siswa</p>
            </div>
        </div>

        <div class="p-6">

            <!-- Filter & Search -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cari Nama/NIS</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Ketik nama atau NIS..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
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

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status SP</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                            <option value="all">Semua Status</option>
                            <option value="aman" <?= $filter_status === 'aman' ? 'selected' : '' ?>>Aman</option>
                            <option value="sp" <?= $filter_status === 'sp' ? 'selected' : '' ?>>Kena SP</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-navy hover:bg-blue-900 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                            🔍 Filter
                        </button>
                    </div>

                </form>
            </div>

            <!-- Results -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-800">Hasil Pencarian</h3>
                    <p class="text-sm text-gray-600 mt-1">Ditemukan: <?= count($siswa_list) ?> siswa</p>
                </div>

                <div class="divide-y divide-gray-200">
                    <?php if (empty($siswa_list)): ?>
                    <div class="p-12 text-center">
                        <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <p class="text-gray-600 font-medium">Tidak ada data siswa ditemukan</p>
                        <p class="text-sm text-gray-500 mt-1">Coba ubah filter pencarian</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($siswa_list as $siswa): 
                        $border_color = 'border-gray-200';
                        if ($siswa['total_poin_umum'] > 200) $border_color = 'border-red-500';
                        elseif ($siswa['total_poin_umum'] > 100) $border_color = 'border-yellow-500';
                        elseif ($siswa['total_poin_umum'] > 0) $border_color = 'border-blue-300';
                    ?>
                    <div class="p-6 hover:bg-gray-50 transition-colors border-l-4 <?= $border_color ?>">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-14 h-14 bg-gray-200 rounded-full flex items-center justify-center overflow-hidden border-2 border-white shadow">
                                    <?php if($siswa['foto_profil']): ?>
                                        <img src="../../assets/uploads/siswa/<?= $siswa['foto_profil'] ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="text-gray-500 font-bold text-xl"><?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($siswa['nama_siswa']) ?></h4>
                                    <p class="text-sm text-gray-600">NIS: <?= $siswa['nis'] ?> • <?= $siswa['nama_kelas'] ?> • <?= $siswa['jenis_kelamin'] === 'L' ? '👦 Laki-laki' : '👧 Perempuan' ?></p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <div class="flex space-x-2 mb-2">
                                        <div class="text-center bg-red-50 px-3 py-1 rounded">
                                            <p class="text-[10px] text-red-600 font-bold">Kelakuan</p>
                                            <p class="text-lg font-bold text-red-700"><?= $siswa['poin_kelakuan'] ?></p>
                                        </div>
                                        <div class="text-center bg-blue-50 px-3 py-1 rounded">
                                            <p class="text-[10px] text-blue-600 font-bold">Kerajinan</p>
                                            <p class="text-lg font-bold text-blue-700"><?= $siswa['poin_kerajinan'] ?></p>
                                        </div>
                                        <div class="text-center bg-yellow-50 px-3 py-1 rounded">
                                            <p class="text-[10px] text-yellow-600 font-bold">Kerapian</p>
                                            <p class="text-lg font-bold text-yellow-700"><?= $siswa['poin_kerapian'] ?></p>
                                        </div>
                                    </div>
                                    <?php if ($siswa['status_sp_terakhir'] !== 'Aman'): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        🚨 <?= $siswa['status_sp_terakhir'] ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        ✅ Aman
                                    </span>
                                    <?php endif; ?>
                                </div>

                                <div class="text-center bg-gray-50 px-6 py-3 rounded-lg border border-gray-200">
                                    <p class="text-xs text-gray-500 font-medium">Total Poin</p>
                                    <p class="text-3xl font-bold text-gray-800"><?= $siswa['total_poin_umum'] ?></p>
                                </div>

                                <a href="detail_siswa.php?id=<?= $siswa['id_anggota'] ?>" 
                                   class="px-4 py-2 bg-navy text-white rounded-lg hover:bg-blue-900 transition-colors font-medium text-sm">
                                    Lihat Detail →
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>

</div>

</body>
</html>