<?php
/**
 * SITAPSI - Detail Siswa (REVISED)
 * FIX: Filter pelanggaran berdasarkan semester aktif (Lembar Kosong Genap)
 * Poin total tetap akumulasi tahunan, tapi LIST pelanggaran di-filter per semester
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_anggota = $_GET['id'] ?? null;

if (!$id_anggota) {
    $_SESSION['error_message'] = '❌ ID siswa tidak valid';
    header('Location: monitoring_siswa.php');
    exit;
}

$tahun_aktif = fetchOne("
    SELECT id_tahun, nama_tahun, semester_aktif 
    FROM tb_tahun_ajaran 
    WHERE status = 'Aktif' 
    LIMIT 1
");

// Filter semester: default tampilkan semester aktif (LOGIKA LEMBAR KOSONG)
$filter_semester = $_GET['semester'] ?? $tahun_aktif['semester_aktif'];

// Ambil data siswa
$siswa = fetchOne("
    SELECT 
        s.*,
        a.id_anggota,
        a.poin_kelakuan,
        a.poin_kerajinan,
        a.poin_kerapian,
        a.total_poin_umum,
        a.status_sp_terakhir,
        k.nama_kelas,
        k.id_kelas
    FROM tb_anggota_kelas a
    JOIN tb_siswa s ON a.nis = s.nis
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE a.id_anggota = :id
", ['id' => $id_anggota]);

if (!$siswa) {
    $_SESSION['error_message'] = '❌ Siswa tidak ditemukan';
    header('Location: monitoring_siswa.php');
    exit;
}

// LOGIKA LEMBAR KOSONG:
// - Poin yg ditampilkan di kartu = AKUMULASI TAHUNAN (poin_kelakuan dll di tb_anggota_kelas)
// - LIST pelanggaran = difilter by semester aktif
// - Poin semester ganjil di kartu kecil = hanya semester ganjil
// - Poin semester genap di kartu kecil = poin genap saja

// Hitung poin per semester untuk info
$poin_ganjil = fetchOne("
    SELECT 
        COALESCE(SUM(CASE WHEN jp.id_kategori = 1 THEN d.poin_saat_itu ELSE 0 END), 0) as kelakuan,
        COALESCE(SUM(CASE WHEN jp.id_kategori = 2 THEN d.poin_saat_itu ELSE 0 END), 0) as kerajinan,
        COALESCE(SUM(CASE WHEN jp.id_kategori = 3 THEN d.poin_saat_itu ELSE 0 END), 0) as kerapian
    FROM tb_pelanggaran_header h
    JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    WHERE h.id_anggota = :id
    AND h.id_tahun = :id_tahun
    AND h.semester = 'Ganjil'
", ['id' => $id_anggota, 'id_tahun' => $tahun_aktif['id_tahun']]);

$poin_genap = fetchOne("
    SELECT 
        COALESCE(SUM(CASE WHEN jp.id_kategori = 1 THEN d.poin_saat_itu ELSE 0 END), 0) as kelakuan,
        COALESCE(SUM(CASE WHEN jp.id_kategori = 2 THEN d.poin_saat_itu ELSE 0 END), 0) as kerajinan,
        COALESCE(SUM(CASE WHEN jp.id_kategori = 3 THEN d.poin_saat_itu ELSE 0 END), 0) as kerapian
    FROM tb_pelanggaran_header h
    JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    WHERE h.id_anggota = :id
    AND h.id_tahun = :id_tahun
    AND h.semester = 'Genap'
", ['id' => $id_anggota, 'id_tahun' => $tahun_aktif['id_tahun']]);

// Helper query untuk get pelanggaran per kategori + filter semester
function getPelanggaranByKategori($id_anggota, $id_kategori, $id_tahun, $filter_semester) {
    $sql = "
        SELECT 
            h.id_transaksi,
            h.tanggal,
            h.waktu,
            h.semester,
            jp.nama_pelanggaran,
            d.poin_saat_itu,
            GROUP_CONCAT(DISTINCT sr.deskripsi SEPARATOR '; ') as sanksi,
            g.nama_guru
        FROM tb_pelanggaran_header h
        JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
        JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
        JOIN tb_guru g ON h.id_guru = g.id_guru
        LEFT JOIN tb_pelanggaran_sanksi ps ON h.id_transaksi = ps.id_transaksi
        LEFT JOIN tb_sanksi_ref sr ON ps.id_sanksi_ref = sr.id_sanksi_ref
        WHERE h.id_anggota = :id
        AND jp.id_kategori = :id_kategori
        AND h.id_tahun = :id_tahun
        AND h.semester = :semester
        GROUP BY h.id_transaksi, d.id_detail
        ORDER BY h.tanggal DESC, h.waktu DESC
    ";
    
    return fetchAll($sql, [
        'id' => $id_anggota,
        'id_kategori' => $id_kategori,
        'id_tahun' => $id_tahun,
        'semester' => $filter_semester
    ]);
}

$pelanggaran_kelakuan = getPelanggaranByKategori($id_anggota, 1, $tahun_aktif['id_tahun'], $filter_semester);
$pelanggaran_kerajinan = getPelanggaranByKategori($id_anggota, 2, $tahun_aktif['id_tahun'], $filter_semester);
$pelanggaran_kerapian = getPelanggaranByKategori($id_anggota, 3, $tahun_aktif['id_tahun'], $filter_semester);

$riwayat_sp = fetchAll("
    SELECT * FROM tb_riwayat_sp 
    WHERE id_anggota = :id 
    ORDER BY tanggal_terbit DESC
", ['id' => $id_anggota]);

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail <?= $siswa['nama_siswa'] ?> - SITAPSI</title>
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
            <a href="monitoring_siswa_list.php?kelas=<?= $siswa['id_kelas'] ?>" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Detail Siswa</h1>
                <p class="text-sm text-gray-500"><?= $siswa['nama_kelas'] ?> • <?= $siswa['nis'] ?></p>
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

            <!-- Profil Siswa -->
            <div class="bg-gradient-to-r from-navy to-blue-800 text-white rounded-xl shadow-lg p-6">
                <div class="flex items-center space-x-6">
                    <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center overflow-hidden flex-shrink-0">
                        <?php if($siswa['foto_profil']): ?>
                            <img src="../../assets/uploads/siswa/<?= $siswa['foto_profil'] ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-navy font-bold text-4xl"><?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>
                    <!-- Di bagian header profil siswa -->
                    <div class="flex-1">
                        <h2 class="text-3xl font-bold mb-2"><?= htmlspecialchars($siswa['nama_siswa']) ?></h2>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <p class="text-blue-200">NIS: <span class="text-white font-semibold"><?= $siswa['nis'] ?></span></p>
                            <p class="text-blue-200">Kelas: <span class="text-white font-semibold"><?= $siswa['nama_kelas'] ?></span></p>
                            <p class="text-blue-200">Jenis Kelamin: <span class="text-white font-semibold"><?= $siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></span></p>
                            <p class="text-blue-200">Orang Tua: <span class="text-white font-semibold"><?= htmlspecialchars($siswa['nama_ortu']) ?></span></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <!-- STATUS SP PER KATEGORI -->
                        <div class="grid grid-cols-3 gap-2 mb-2">
                            <?php
                            $sp_kelakuan = $siswa['status_sp_kelakuan'] ?? 'Aman';
                            $sp_kerajinan = $siswa['status_sp_kerajinan'] ?? 'Aman';
                            $sp_kerapian = $siswa['status_sp_kerapian'] ?? 'Aman';
                            
                            $badge_color = function($sp) {
                                return $sp === 'Aman' ? 'bg-green-500' : 'bg-red-500';
                            };
                            ?>
                            <div class="bg-white/10 backdrop-blur rounded-lg p-2">
                                <p class="text-xs text-blue-200">Kelakuan</p>
                                <span class="<?= $badge_color($sp_kelakuan) ?> px-2 py-1 rounded text-xs font-bold text-white">
                                    <?= $sp_kelakuan ?>
                                </span>
                            </div>
                            <div class="bg-white/10 backdrop-blur rounded-lg p-2">
                                <p class="text-xs text-blue-200">Kerajinan</p>
                                <span class="<?= $badge_color($sp_kerajinan) ?> px-2 py-1 rounded text-xs font-bold text-white">
                                    <?= $sp_kerajinan ?>
                                </span>
                            </div>
                            <div class="bg-white/10 backdrop-blur rounded-lg p-2">
                                <p class="text-xs text-blue-200">Kerapian</p>
                                <span class="<?= $badge_color($sp_kerapian) ?> px-2 py-1 rounded text-xs font-bold text-white">
                                    <?= $sp_kerapian ?>
                                </span>
                            </div>
                        </div>
                        <p class="text-xs text-blue-200">Status Tertinggi:</p>
                        <span class="px-4 py-2 <?= $siswa['status_sp_terakhir'] !== 'Aman' ? 'bg-red-500' : 'bg-green-500' ?> text-white text-sm font-bold rounded-full">
                            <?= $siswa['status_sp_terakhir'] ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- POIN CARDS: Akumulasi Tahunan + Breakdown Semester -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- KELAKUAN -->
                <div class="bg-white rounded-xl shadow-sm border-l-4 border-red-500 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-800">🚨 KELAKUAN</h3>
                        <span class="text-3xl font-bold text-red-600"><?= $siswa['poin_kelakuan'] ?></span>
                    </div>
                    <p class="text-xs text-gray-400 font-medium mb-3">Total Akumulasi Tahunan</p>
                    <div class="grid grid-cols-2 gap-2 mt-2 pt-3 border-t border-gray-100">
                        <div class="bg-gray-50 p-2 rounded text-center">
                            <p class="text-xs text-gray-500">Ganjil</p>
                            <p class="text-sm font-bold text-gray-700"><?= $poin_ganjil['kelakuan'] ?></p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded text-center">
                            <p class="text-xs text-gray-500">Genap</p>
                            <p class="text-sm font-bold text-gray-700"><?= $poin_genap['kelakuan'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- KERAJINAN -->
                <div class="bg-white rounded-xl shadow-sm border-l-4 border-blue-500 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-800">📘 KERAJINAN</h3>
                        <span class="text-3xl font-bold text-blue-600"><?= $siswa['poin_kerajinan'] ?></span>
                    </div>
                    <p class="text-xs text-gray-400 font-medium mb-3">Total Akumulasi Tahunan</p>
                    <div class="grid grid-cols-2 gap-2 mt-2 pt-3 border-t border-gray-100">
                        <div class="bg-gray-50 p-2 rounded text-center">
                            <p class="text-xs text-gray-500">Ganjil</p>
                            <p class="text-sm font-bold text-gray-700"><?= $poin_ganjil['kerajinan'] ?></p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded text-center">
                            <p class="text-xs text-gray-500">Genap</p>
                            <p class="text-sm font-bold text-gray-700"><?= $poin_genap['kerajinan'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- KERAPIAN -->
                <div class="bg-white rounded-xl shadow-sm border-l-4 border-yellow-500 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-800">👔 KERAPIAN</h3>
                        <span class="text-3xl font-bold text-yellow-600"><?= $siswa['poin_kerapian'] ?></span>
                    </div>
                    <p class="text-xs text-gray-400 font-medium mb-3">Total Akumulasi Tahunan</p>
                    <div class="grid grid-cols-2 gap-2 mt-2 pt-3 border-t border-gray-100">
                        <div class="bg-gray-50 p-2 rounded text-center">
                            <p class="text-xs text-gray-500">Ganjil</p>
                            <p class="text-sm font-bold text-gray-700"><?= $poin_ganjil['kerapian'] ?></p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded text-center">
                            <p class="text-xs text-gray-500">Genap</p>
                            <p class="text-sm font-bold text-gray-700"><?= $poin_genap['kerapian'] ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Semester (TOMBOL LEMBAR) -->
            <div class="bg-white rounded-xl shadow-sm p-4 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="text-sm font-medium text-gray-700">Tampilkan pelanggaran semester:</span>
                    <a href="?id=<?= $id_anggota ?>&semester=Ganjil"
                       class="px-4 py-2 rounded-lg font-medium transition-colors text-sm <?= $filter_semester === 'Ganjil' ? 'bg-navy text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        📄 Ganjil
                    </a>
                    <a href="?id=<?= $id_anggota ?>&semester=Genap"
                       class="px-4 py-2 rounded-lg font-medium transition-colors text-sm <?= $filter_semester === 'Genap' ? 'bg-navy text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        📄 Genap
                    </a>
                </div>
                <div class="flex items-center space-x-2 text-sm">
                    <?php if ($filter_semester === $tahun_aktif['semester_aktif']): ?>
                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full font-medium">
                        ● Semester Aktif
                    </span>
                    <?php else: ?>
                    <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full font-medium">
                        Semester Lampau
                    </span>
                    <?php endif; ?>
                    <span class="text-gray-500">
                        Menampilkan: <strong><?= $filter_semester ?></strong>
                    </span>
                </div>
            </div>

            <!-- 3 Tabel Kategori - DIFILTER BY SEMESTER -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="flex border-b border-gray-200 overflow-x-auto">
                    <button onclick="switchTab('kelakuan')" id="tab-kelakuan" 
                            class="tab-button flex-1 py-4 px-4 font-bold text-sm text-center transition-colors bg-red-600 text-white">
                        🚨 KELAKUAN (<?= count($pelanggaran_kelakuan) ?>)
                    </button>
                    <button onclick="switchTab('kerajinan')" id="tab-kerajinan" 
                            class="tab-button flex-1 py-4 px-4 font-bold text-sm text-center transition-colors bg-gray-100 text-gray-600 hover:bg-gray-200">
                        📘 KERAJINAN (<?= count($pelanggaran_kerajinan) ?>)
                    </button>
                    <button onclick="switchTab('kerapian')" id="tab-kerapian" 
                            class="tab-button flex-1 py-4 px-4 font-bold text-sm text-center transition-colors bg-gray-100 text-gray-600 hover:bg-gray-200">
                        👔 KERAPIAN (<?= count($pelanggaran_kerapian) ?>)
                    </button>
                </div>

                <?php 
                $kategori_data = [
                    'kelakuan' => ['data' => $pelanggaran_kelakuan, 'color' => 'red', 'label' => 'Kelakuan'],
                    'kerajinan' => ['data' => $pelanggaran_kerajinan, 'color' => 'blue', 'label' => 'Kerajinan'],
                    'kerapian' => ['data' => $pelanggaran_kerapian, 'color' => 'yellow', 'label' => 'Kerapian'],
                ];
                
                foreach ($kategori_data as $key => $kat):
                    $color = $kat['color'];
                ?>
                <div id="content-<?= $key ?>" class="tab-content p-6 <?= $key !== 'kelakuan' ? 'hidden' : '' ?>">
                    
                    <!-- Info semester untuk tab ini -->
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-bold text-gray-800">Pelanggaran <?= $kat['label'] ?> - Semester <?= $filter_semester ?></h4>
                        <?php if (empty($kat['data'])): ?>
                        <span class="px-3 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">
                            ✅ Bersih semester ini
                        </span>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($kat['data'])): ?>
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="font-medium">Tidak ada pelanggaran <?= strtolower($kat['label']) ?> di semester <?= $filter_semester ?></p>
                        <?php if ($filter_semester !== $tahun_aktif['semester_aktif']): ?>
                        <p class="text-xs text-gray-400 mt-1">Data semester lain tersedia</p>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-<?= $color ?>-50 text-xs text-<?= $color ?>-700 uppercase">
                                <tr>
                                    <th class="p-3 text-left">Tanggal</th>
                                    <th class="p-3 text-left">Pelanggaran</th>
                                    <th class="p-3 text-left">Poin</th>
                                    <th class="p-3 text-left">Sanksi</th>
                                    <th class="p-3 text-left">Pelapor</th>
                                    <th class="p-3 text-left">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($kat['data'] as $p): ?>
                                <tr class="hover:bg-<?= $color ?>-50">
                                    <td class="p-3 whitespace-nowrap">
                                        <?= date('d/m/Y', strtotime($p['tanggal'])) ?><br>
                                        <span class="text-xs text-gray-500"><?= substr($p['waktu'], 0, 5) ?></span>
                                    </td>
                                    <td class="p-3"><?= htmlspecialchars($p['nama_pelanggaran']) ?></td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 bg-<?= $color ?>-100 text-<?= $color ?>-800 rounded-full font-bold text-xs">
                                            +<?= $p['poin_saat_itu'] ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-xs text-gray-600"><?= $p['sanksi'] ?: '-' ?></td>
                                    <td class="p-3 text-xs"><?= htmlspecialchars($p['nama_guru']) ?></td>
                                    <td class="p-3 whitespace-nowrap">
                                        <button onclick="editPelanggaran(<?= $p['id_transaksi'] ?>)" 
                                                class="p-1 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 mr-1" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button onclick="hapusPelanggaran(<?= $p['id_transaksi'] ?>)" 
                                                class="p-1 bg-red-50 text-red-600 rounded hover:bg-red-100" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Riwayat SP -->
            <?php if (!empty($riwayat_sp)): ?>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b font-bold text-gray-700">📜 Riwayat Surat Peringatan</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="p-4 text-left">Tingkat SP</th>
                                <th class="p-4 text-left">Kategori Pemicu</th>
                                <th class="p-4 text-left">Tanggal Terbit</th>
                                <th class="p-4 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach($riwayat_sp as $sp): ?>
                            <tr>
                                <td class="p-4">
                                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full font-bold text-xs">
                                        <?= $sp['tingkat_sp'] ?>
                                    </span>
                                </td>
                                <td class="p-4"><?= $sp['kategori_pemicu'] ?></td>
                                <td class="p-4"><?= date('d/m/Y', strtotime($sp['tanggal_terbit'])) ?></td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= $sp['status'] === 'Selesai' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                        <?= $sp['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    document.querySelectorAll('.tab-button').forEach(b => {
        b.classList.remove('bg-red-600', 'bg-blue-600', 'bg-yellow-600', 'text-white');
        b.classList.add('bg-gray-100', 'text-gray-600');
    });
    document.getElementById('content-' + tab).classList.remove('hidden');
    const activeTab = document.getElementById('tab-' + tab);
    activeTab.classList.remove('bg-gray-100', 'text-gray-600');
    activeTab.classList.add('text-white');
    if (tab === 'kelakuan') activeTab.classList.add('bg-red-600');
    else if (tab === 'kerajinan') activeTab.classList.add('bg-blue-600');
    else activeTab.classList.add('bg-yellow-600');
}

function editPelanggaran(id) {
    window.location.href = `edit_pelanggaran.php?id=${id}`;
}

function hapusPelanggaran(id) {
    if (confirm('⚠️ Menghapus pelanggaran akan mengurangi poin siswa secara otomatis.\n\nYakin ingin menghapus?')) {
        window.location.href = `../../actions/hapus_transaksi.php?id=${id}&redirect=monitoring&anggota=<?= $id_anggota ?>`;
    }
}
</script>
</body>
</html>