<?php
/**
 * SITAPSI - Manajemen Surat Peringatan
 * Cetak & validasi SP
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

// Ambil tahun ajaran aktif
$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// Filter status
$filter_status = $_GET['status'] ?? 'Pending';

// Ambil daftar SP
$sql = "
    SELECT 
        sp.id_sp,
        sp.tingkat_sp,
        sp.kategori_pemicu,
        sp.tanggal_terbit,
        sp.tanggal_validasi,
        sp.status,
        s.nis,
        s.nama_siswa,
        s.nama_ortu,
        k.nama_kelas,
        a.total_poin_umum
    FROM tb_riwayat_sp sp
    JOIN tb_anggota_kelas a ON sp.id_anggota = a.id_anggota
    JOIN tb_siswa s ON a.nis = s.nis
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE a.id_tahun = :id_tahun
";

$params = ['id_tahun' => $tahun_aktif['id_tahun']];

if ($filter_status !== 'all') {
    $sql .= " AND sp.status = :status";
    $params['status'] = $filter_status;
}

$sql .= " ORDER BY sp.tanggal_terbit DESC";

$daftar_sp = fetchAll($sql, $params);

// Success/Error message
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
                <h1 class="text-2xl font-bold text-gray-800">Manajemen Surat Peringatan</h1>
                <p class="text-sm text-gray-600 mt-1">Cetak & validasi SP</p>
            </div>
        </div>

        <div class="p-6">

            <?php if ($success): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <p class="text-green-700 font-medium"><?= htmlspecialchars($success) ?></p>
            </div>
            <?php endif; ?>

            <!-- Filter Status -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex space-x-2">
                    <a href="?status=Pending" 
                       class="px-4 py-2 rounded-lg font-medium transition-colors <?= $filter_status === 'Pending' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Menunggu Validasi
                    </a>
                    <a href="?status=Selesai" 
                       class="px-4 py-2 rounded-lg font-medium transition-colors <?= $filter_status === 'Selesai' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Selesai
                    </a>
                    <a href="?status=all" 
                       class="px-4 py-2 rounded-lg font-medium transition-colors <?= $filter_status === 'all' ? 'bg-navy text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Semua
                    </a>
                </div>
            </div>

            <!-- Daftar SP -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-800">Daftar Surat Peringatan</h3>
                    <p class="text-sm text-gray-600 mt-1">Total: <?= count($daftar_sp) ?> SP</p>
                </div>

                <div class="divide-y divide-gray-200">
                    <?php if(empty($daftar_sp)): ?>
                    <div class="p-12 text-center">
                        <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-gray-600 font-medium">Tidak ada SP dengan status ini</p>
                    </div>
                    <?php else: ?>
                    <?php foreach($daftar_sp as $sp): ?>
                    <div class="p-6 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <h4 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($sp['nama_siswa']) ?></h4>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">
                                        <?= $sp['tingkat_sp'] ?>
                                    </span>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?= $sp['status'] === 'Selesai' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                        <?= $sp['status'] ?>
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600 space-y-1">
                                    <p><strong>Kelas:</strong> <?= $sp['nama_kelas'] ?> • <strong>NIS:</strong> <?= $sp['nis'] ?></p>
                                    <p><strong>Kategori Pemicu:</strong> <?= $sp['kategori_pemicu'] ?></p>
                                    <p><strong>Total Poin:</strong> <?= $sp['total_poin_umum'] ?></p>
                                    <p><strong>Tanggal Terbit:</strong> <?= date('d F Y', strtotime($sp['tanggal_terbit'])) ?></p>
                                    <?php if($sp['tanggal_validasi']): ?>
                                    <p><strong>Tanggal Validasi:</strong> <?= date('d F Y', strtotime($sp['tanggal_validasi'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="flex space-x-2">
                                <a href="cetak_sp.php?id=<?= $sp['id_sp'] ?>" 
                                   target="_blank"
                                   class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium text-sm">
                                    📄 Cetak Surat
                                </a>
                                <?php if($sp['status'] === 'Pending'): ?>
                                <button onclick="validasiSP(<?= $sp['id_sp'] ?>)" 
                                        class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium text-sm">
                                    ✅ Validasi
                                </button>
                                <?php endif; ?>
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

<script>
function validasiSP(id) {
    if (confirm('Apakah surat SP sudah dikembalikan dan ditandatangani oleh orang tua?\n\nKlik OK untuk validasi.')) {
        window.location.href = `../../actions/validasi_sp.php?id=${id}`;
    }
}
</script>

</body>
</html>