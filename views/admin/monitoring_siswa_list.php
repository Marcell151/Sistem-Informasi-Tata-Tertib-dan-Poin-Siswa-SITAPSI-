<?php
/**
 * SITAPSI - Monitoring Siswa List (COMPLETE - SP PER KATEGORI)
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_kelas = $_GET['kelas'] ?? null;

if (!$id_kelas) {
    $_SESSION['error_message'] = '❌ Kelas tidak dipilih';
    header('Location: monitoring_siswa.php');
    exit;
}

$tahun_aktif = fetchOne("
    SELECT id_tahun, nama_tahun 
    FROM tb_tahun_ajaran 
    WHERE status = 'Aktif' 
    LIMIT 1
");

$kelas_info = fetchOne("
    SELECT * FROM tb_kelas WHERE id_kelas = :id
", ['id' => $id_kelas]);

// Query siswa dengan SP per kategori
$siswa_list = fetchAll("
    SELECT 
        s.nis,
        s.nama_siswa,
        s.jenis_kelamin,
        s.foto_profil,
        a.id_anggota,
        a.poin_kelakuan,
        a.poin_kerajinan,
        a.poin_kerapian,
        a.total_poin_umum,
        a.status_sp_terakhir,
        a.status_sp_kelakuan,
        a.status_sp_kerajinan,
        a.status_sp_kerapian
    FROM tb_anggota_kelas a
    JOIN tb_siswa s ON a.nis = s.nis
    WHERE a.id_kelas = :id_kelas
    AND a.id_tahun = :id_tahun
    AND s.status_aktif = 'Aktif'
    ORDER BY s.nama_siswa
", [
    'id_kelas' => $id_kelas,
    'id_tahun' => $tahun_aktif['id_tahun']
]);

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Siswa - Kelas <?= $kelas_info['nama_kelas'] ?> - SITAPSI</title>
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
            <a href="monitoring_siswa.php" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Kelas <?= htmlspecialchars($kelas_info['nama_kelas']) ?></h1>
                <p class="text-sm text-gray-500">Monitoring siswa per individu</p>
            </div>
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

            <!-- Header Info -->
            <div class="bg-gradient-to-r from-navy to-blue-800 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">📊 Monitoring Kelas <?= $kelas_info['nama_kelas'] ?></h2>
                        <p class="text-blue-200">Total Siswa: <?= count($siswa_list) ?> orang</p>
                    </div>
                    <div class="text-6xl">👥</div>
                </div>
            </div>

            <!-- Grid Siswa dengan SP Per Kategori -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php if (empty($siswa_list)): ?>
                <div class="col-span-full bg-white rounded-xl shadow-sm p-12 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <p class="text-gray-500 font-medium">Tidak ada siswa di kelas ini</p>
                </div>
                <?php else: ?>
                <?php foreach ($siswa_list as $siswa): ?>
                <a href="detail_siswa.php?id=<?= $siswa['id_anggota'] ?>" 
                   class="block bg-white rounded-xl shadow-sm hover:shadow-lg transition-all transform hover:-translate-y-1 overflow-hidden">
                    
                    <!-- Header Card dengan Foto -->
                    <div class="bg-gradient-to-br from-navy to-blue-800 p-4 text-white">
                        <div class="flex items-center space-x-3">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center overflow-hidden flex-shrink-0">
                                <?php if($siswa['foto_profil']): ?>
                                    <img src="../../assets/uploads/siswa/<?= $siswa['foto_profil'] ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="text-navy font-bold text-2xl"><?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-white truncate"><?= htmlspecialchars($siswa['nama_siswa']) ?></h3>
                                <p class="text-xs text-blue-200">NIS: <?= $siswa['nis'] ?></p>
                                <p class="text-xs text-blue-200"><?= $siswa['jenis_kelamin'] === 'L' ? '👦 Laki-laki' : '👧 Perempuan' ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Body Card - Poin per Kategori -->
                    <div class="p-4">
                        <div class="grid grid-cols-3 gap-2 mb-3">
                            <div class="text-center bg-red-50 rounded-lg p-2">
                                <p class="text-xs text-red-600 font-medium mb-1">🚨 Kelakuan</p>
                                <p class="text-xl font-bold text-red-700"><?= $siswa['poin_kelakuan'] ?></p>
                            </div>
                            <div class="text-center bg-blue-50 rounded-lg p-2">
                                <p class="text-xs text-blue-600 font-medium mb-1">📘 Kerajinan</p>
                                <p class="text-xl font-bold text-blue-700"><?= $siswa['poin_kerajinan'] ?></p>
                            </div>
                            <div class="text-center bg-yellow-50 rounded-lg p-2">
                                <p class="text-xs text-yellow-600 font-medium mb-1">👔 Kerapian</p>
                                <p class="text-xl font-bold text-yellow-700"><?= $siswa['poin_kerapian'] ?></p>
                            </div>
                        </div>

                        <!-- Total Poin -->
                        <div class="bg-gray-800 rounded-lg p-2 mb-3 text-center">
                            <p class="text-xs text-gray-300 mb-1">Total Poin</p>
                            <p class="text-2xl font-bold text-white"><?= $siswa['total_poin_umum'] ?></p>
                        </div>

                        <!-- Status SP per Kategori -->
                        <div class="border-t pt-3">
                            <p class="text-xs text-gray-500 mb-2 font-medium">Status SP Per Kategori:</p>
                            <div class="grid grid-cols-3 gap-1">
                                <?php
                                $sp_badges = [
                                    ['label' => 'KL', 'status' => $siswa['status_sp_kelakuan'], 'color' => 'red', 'title' => 'Kelakuan'],
                                    ['label' => 'KJ', 'status' => $siswa['status_sp_kerajinan'], 'color' => 'blue', 'title' => 'Kerajinan'],
                                    ['label' => 'KP', 'status' => $siswa['status_sp_kerapian'], 'color' => 'yellow', 'title' => 'Kerapian']
                                ];
                                foreach ($sp_badges as $badge):
                                    $is_aman = $badge['status'] === 'Aman';
                                ?>
                                <div class="text-center" title="<?= $badge['title'] ?>: <?= $badge['status'] ?>">
                                    <p class="text-xs text-gray-500 mb-1"><?= $badge['label'] ?></p>
                                    <span class="px-2 py-1 rounded text-xs font-bold block <?= $is_aman ? 'bg-green-100 text-green-800' : "bg-{$badge['color']}-100 text-{$badge['color']}-800" ?>">
                                        <?= $badge['status'] ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Status SP Tertinggi -->
                        <div class="mt-3 pt-3 border-t text-center">
                            <p class="text-xs text-gray-500 mb-1">Status Tertinggi:</p>
                            <span class="px-3 py-1 rounded-full text-xs font-bold <?= $siswa['status_sp_terakhir'] === 'Aman' ? 'bg-green-100 text-green-800' : 'bg-red-600 text-white' ?>">
                                <?= $siswa['status_sp_terakhir'] ?>
                            </span>
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