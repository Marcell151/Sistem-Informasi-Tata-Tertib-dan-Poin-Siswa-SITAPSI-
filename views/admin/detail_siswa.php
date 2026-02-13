<?php
/**
 * SITAPSI - Detail Siswa (Rapor Digital)
 * Menampilkan detail poin dengan logika "Clean Slate" semester
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_anggota = $_GET['id'] ?? null;

if (!$id_anggota) {
    header('Location: monitoring_siswa.php');
    exit;
}

// Ambil data siswa
$siswa = fetchOne("
    SELECT 
        s.nis,
        s.nama_siswa,
        s.jenis_kelamin,
        s.tempat_lahir,
        s.tanggal_lahir,
        s.nama_ortu,
        s.no_hp_ortu,
        s.foto_profil,
        k.nama_kelas,
        t.nama_tahun,
        t.semester_aktif,
        a.poin_kelakuan,
        a.poin_kerajinan,
        a.poin_kerapian,
        a.total_poin_umum,
        a.status_sp_terakhir,
        a.id_anggota
    FROM tb_anggota_kelas a
    JOIN tb_siswa s ON a.nis = s.nis
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    JOIN tb_tahun_ajaran t ON a.id_tahun = t.id_tahun
    WHERE a.id_anggota = :id
", ['id' => $id_anggota]);

if (!$siswa) {
    die("Data siswa tidak ditemukan");
}

// Filter semester untuk view (default: semester aktif)
$view_semester = $_GET['semester'] ?? $siswa['semester_aktif'];

// LOGIKA CLEAN SLATE: 
// Jika view semester = Genap, hitung poin yang didapat di semester genap saja
$poin_semester_kelakuan = 0;
$poin_semester_kerajinan = 0;
$poin_semester_kerapian = 0;

if ($view_semester === 'Genap') {
    // Hitung poin yang didapat di semester genap
    $poin_genap = fetchAll("
        SELECT jp.id_kategori, SUM(d.poin_saat_itu) as total
        FROM tb_pelanggaran_header h
        JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
        JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
        WHERE h.id_anggota = :id
        AND h.semester = 'Genap'
        GROUP BY jp.id_kategori
    ", ['id' => $id_anggota]);
    
    foreach ($poin_genap as $pg) {
        if ($pg['id_kategori'] == 1) $poin_semester_kelakuan = $pg['total'];
        elseif ($pg['id_kategori'] == 2) $poin_semester_kerajinan = $pg['total'];
        elseif ($pg['id_kategori'] == 3) $poin_semester_kerapian = $pg['total'];
    }
} else {
    // Semester Ganjil: tampilkan poin sesuai database
    $poin_semester_kelakuan = $siswa['poin_kelakuan'];
    $poin_semester_kerajinan = $siswa['poin_kerajinan'];
    $poin_semester_kerapian = $siswa['poin_kerapian'];
}

$total_poin_semester = $poin_semester_kelakuan + $poin_semester_kerajinan + $poin_semester_kerapian;

// Riwayat Pelanggaran
$riwayat = fetchAll("
    SELECT 
        h.tanggal,
        h.waktu,
        h.tipe_form,
        h.semester,
        g.nama_guru,
        GROUP_CONCAT(jp.nama_pelanggaran SEPARATOR ', ') as pelanggaran,
        SUM(d.poin_saat_itu) as total_poin
    FROM tb_pelanggaran_header h
    JOIN tb_guru g ON h.id_guru = g.id_guru
    LEFT JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    LEFT JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    WHERE h.id_anggota = :id
    GROUP BY h.id_transaksi
    ORDER BY h.tanggal DESC, h.waktu DESC
", ['id' => $id_anggota]);

// Riwayat SP
$riwayat_sp = fetchAll("
    SELECT tingkat_sp, kategori_pemicu, tanggal_terbit, status
    FROM tb_riwayat_sp
    WHERE id_anggota = :id
    ORDER BY tanggal_terbit DESC
", ['id' => $id_anggota]);

// Cek reward (0 poin = kandidat sertifikat)
$is_kandidat_reward = ($total_poin_semester === 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Siswa - <?= htmlspecialchars($siswa['nama_siswa']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'navy': '#000080',
                        'kelakuan': '#DC2626',
                        'kerajinan': '#2563EB',
                        'kerapian': '#D97706',
                        'reward': '#16A34A'
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
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2">
                        <a href="monitoring_siswa.php" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </a>
                        <h1 class="text-2xl font-bold text-gray-800">Rapor Digital Siswa</h1>
                    </div>
                    <p class="text-sm text-gray-600 mt-1"><?= $siswa['nama_tahun'] ?></p>
                </div>
                <div class="flex space-x-2">
                    <a href="?id=<?= $id_anggota ?>&semester=Ganjil" 
                       class="px-4 py-2 rounded-lg font-medium transition-colors <?= $view_semester === 'Ganjil' ? 'bg-navy text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Semester Ganjil
                    </a>
                    <a href="?id=<?= $id_anggota ?>&semester=Genap" 
                       class="px-4 py-2 rounded-lg font-medium transition-colors <?= $view_semester === 'Genap' ? 'bg-navy text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Semester Genap
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">

            <!-- Reward Banner -->
            <?php if ($is_kandidat_reward): ?>
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center space-x-4">
                    <div class="bg-white rounded-full p-3">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">🏆 Kandidat Sertifikat Bebas Pelanggaran!</h3>
                        <p class="text-green-100 mt-1">Siswa ini tidak memiliki pelanggaran di semester <?= $view_semester ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Profil Siswa -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-start space-x-6">
                    <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center overflow-hidden border-4 border-white shadow-lg">
                        <?php if($siswa['foto_profil']): ?>
                            <img src="../../assets/uploads/siswa/<?= $siswa['foto_profil'] ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-gray-500 font-bold text-3xl"><?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Nama Lengkap</p>
                            <p class="text-lg font-bold text-gray-800"><?= htmlspecialchars($siswa['nama_siswa']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">NIS</p>
                            <p class="text-lg font-bold text-gray-800"><?= $siswa['nis'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Kelas</p>
                            <p class="text-lg font-bold text-gray-800"><?= $siswa['nama_kelas'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Jenis Kelamin</p>
                            <p class="text-lg font-bold text-gray-800"><?= $siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Orang Tua</p>
                            <p class="text-lg font-bold text-gray-800"><?= htmlspecialchars($siswa['nama_ortu']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">No. HP Ortu</p>
                            <p class="text-lg font-bold text-gray-800"><?= htmlspecialchars($siswa['no_hp_ortu']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3 Silo Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Kelakuan -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden border-t-4 border-kelakuan">
                    <div class="bg-red-50 px-6 py-4">
                        <h3 class="text-lg font-bold text-kelakuan flex items-center">
                            🚨 KELAKUAN
                        </h3>
                    </div>
                    <div class="p-6 text-center">
                        <p class="text-6xl font-bold text-kelakuan"><?= $poin_semester_kelakuan ?></p>
                        <p class="text-sm text-gray-500 mt-2">Poin Semester <?= $view_semester ?></p>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <p class="text-xs text-gray-500">Akumulasi SP (Total Tahun)</p>
                            <p class="text-2xl font-bold text-gray-700"><?= $siswa['poin_kelakuan'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Kerajinan -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden border-t-4 border-kerajinan">
                    <div class="bg-blue-50 px-6 py-4">
                        <h3 class="text-lg font-bold text-kerajinan flex items-center">
                            📘 KERAJINAN
                        </h3>
                    </div>
                    <div class="p-6 text-center">
                        <p class="text-6xl font-bold text-kerajinan"><?= $poin_semester_kerajinan ?></p>
                        <p class="text-sm text-gray-500 mt-2">Poin Semester <?= $view_semester ?></p>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <p class="text-xs text-gray-500">Akumulasi SP (Total Tahun)</p>
                            <p class="text-2xl font-bold text-gray-700"><?= $siswa['poin_kerajinan'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Kerapian -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden border-t-4 border-kerapian">
                    <div class="bg-yellow-50 px-6 py-4">
                        <h3 class="text-lg font-bold text-kerapian flex items-center">
                            👔 KERAPIAN
                        </h3>
                    </div>
                    <div class="p-6 text-center">
                        <p class="text-6xl font-bold text-kerapian"><?= $poin_semester_kerapian ?></p>
                        <p class="text-sm text-gray-500 mt-2">Poin Semester <?= $view_semester ?></p>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <p class="text-xs text-gray-500">Akumulasi SP (Total Tahun)</p>
                            <p class="text-2xl font-bold text-gray-700"><?= $siswa['poin_kerapian'] ?></p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Riwayat SP -->
            <?php if (!empty($riwayat_sp)): ?>
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200 bg-red-50">
                    <h3 class="text-lg font-bold text-red-800">⚠️ Riwayat Surat Peringatan</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($riwayat_sp as $sp): ?>
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div>
                            <p class="font-bold text-gray-800"><?= $sp['tingkat_sp'] ?> - <?= $sp['kategori_pemicu'] ?></p>
                            <p class="text-sm text-gray-600">Diterbitkan: <?= date('d F Y', strtotime($sp['tanggal_terbit'])) ?></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium <?= $sp['status'] === 'Selesai' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                            <?= $sp['status'] ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Riwayat Pelanggaran -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800">📋 Riwayat Pelanggaran</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pelanggaran</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Poin</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Semester</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pelapor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if(empty($riwayat)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">Tidak ada riwayat pelanggaran</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($riwayat as $r): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= date('d/m/Y', strtotime($r['tanggal'])) ?><br>
                                    <span class="text-xs text-gray-500"><?= substr($r['waktu'], 0, 5) ?></span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700 max-w-md">
                                    <?= htmlspecialchars($r['pelanggaran']) ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800">
                                        +<?= $r['total_poin'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700"><?= $r['semester'] ?></td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?= $r['tipe_form'] === 'Piket' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' ?>">
                                        <?= $r['tipe_form'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700"><?= htmlspecialchars($r['nama_guru']) ?></td>
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