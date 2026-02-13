<?php
/**
 * SITAPSI - Rekap Kelas
 * Monitoring siswa per kelas untuk Guru
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireGuru();

$user = getCurrentUser();

// Ambil tahun ajaran aktif
$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

if (!$tahun_aktif) {
    die("Error: Tidak ada tahun ajaran aktif. Hubungi admin.");
}

// Ambil daftar kelas
$kelas_list = fetchAll("SELECT id_kelas, nama_kelas FROM tb_kelas ORDER BY nama_kelas");

// Ambil kelas yang dipilih (default: kelas pertama)
$selected_kelas = $_GET['kelas'] ?? ($kelas_list[0]['id_kelas'] ?? null);

// Ambil data siswa dalam kelas terpilih
$siswa_kelas = [];
if ($selected_kelas) {
    $siswa_kelas = fetchAll("
        SELECT 
            s.nis,
            s.nama_siswa,
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
        AND k.id_kelas = :id_kelas
        ORDER BY s.nama_siswa
    ", [
        'id_tahun' => $tahun_aktif['id_tahun'],
        'id_kelas' => $selected_kelas
    ]);
}

// Get nama kelas terpilih
$nama_kelas_terpilih = '';
foreach ($kelas_list as $k) {
    if ($k['id_kelas'] == $selected_kelas) {
        $nama_kelas_terpilih = $k['nama_kelas'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Kelas - SITAPSI</title>
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
<body class="bg-gray-50 pb-20 md:pb-0">

<?php include '../../includes/navbar_guru.php'; ?>

<div class="container mx-auto px-4 py-6">
    
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">📊 Rekapitulasi Kelas</h2>
                <p class="text-gray-600"><?= $tahun_aktif['nama_tahun'] ?> - Semester <?= $tahun_aktif['semester_aktif'] ?></p>
            </div>
            <a href="export_rekap.php?kelas=<?= $selected_kelas ?>" 
               class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium flex items-center space-x-2 transition-colors shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="hidden md:inline">Unduh Rekap</span>
            </a>
        </div>

        <div class="flex space-x-2 overflow-x-auto pb-2 scrollbar-hide">
            <?php foreach ($kelas_list as $kelas): ?>
            <a href="?kelas=<?= $kelas['id_kelas'] ?>" 
               class="flex-shrink-0 px-6 py-3 rounded-lg font-semibold transition-colors border <?= $kelas['id_kelas'] == $selected_kelas ? 'bg-navy text-white border-navy shadow-md' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100 hover:text-navy' ?>">
                <?= htmlspecialchars($kelas['nama_kelas']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php 
    $total_siswa = count($siswa_kelas);
    $siswa_aman = 0;
    $siswa_peringatan = 0;
    $total_poin_kelas = 0;
    
    foreach ($siswa_kelas as $s) {
        $total_poin_kelas += $s['total_poin_umum'];
        if ($s['status_sp_terakhir'] === 'Aman') {
            $siswa_aman++;
        } else {
            $siswa_peringatan++;
        }
    }
    ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 md:p-6 border-l-4 border-gray-400">
            <p class="text-gray-500 text-xs md:text-sm font-medium uppercase tracking-wider">Total Siswa</p>
            <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1"><?= $total_siswa ?></p>
        </div>

        <div class="bg-white rounded-lg shadow p-4 md:p-6 border-l-4 border-green-500">
            <p class="text-gray-500 text-xs md:text-sm font-medium uppercase tracking-wider">Siswa Aman</p>
            <p class="text-2xl md:text-3xl font-bold text-green-600 mt-1"><?= $siswa_aman ?></p>
        </div>

        <div class="bg-white rounded-lg shadow p-4 md:p-6 border-l-4 border-red-500">
            <p class="text-gray-500 text-xs md:text-sm font-medium uppercase tracking-wider">Perlu Perhatian</p>
            <p class="text-2xl md:text-3xl font-bold text-red-600 mt-1"><?= $siswa_peringatan ?></p>
        </div>

        <div class="bg-white rounded-lg shadow p-4 md:p-6 border-l-4 border-yellow-500">
            <p class="text-gray-500 text-xs md:text-sm font-medium uppercase tracking-wider">Total Poin</p>
            <p class="text-2xl md:text-3xl font-bold text-yellow-600 mt-1"><?= $total_poin_kelas ?></p>
        </div>
    </div>

    <div class="space-y-4">
        <?php if (empty($siswa_kelas)): ?>
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <div class="bg-gray-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Belum Ada Data Siswa</h3>
            <p class="text-gray-500">Pilih kelas lain atau hubungi admin untuk menambahkan siswa</p>
        </div>
        <?php else: ?>
        <?php foreach ($siswa_kelas as $siswa): 
            // Tentukan warna border berdasarkan total poin
            $border_color = 'border-gray-200';
            if ($siswa['total_poin_umum'] > 200) {
                $border_color = 'border-red-500 ring-1 ring-red-100';
            } elseif ($siswa['total_poin_umum'] > 100) {
                $border_color = 'border-yellow-500 ring-1 ring-yellow-100';
            } elseif ($siswa['total_poin_umum'] > 0) {
                $border_color = 'border-blue-300';
            }
        ?>
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-all border-l-4 <?= $border_color ?> p-4 md:p-6">
            <div class="flex items-start md:items-center space-x-4">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0 overflow-hidden border-2 border-white shadow-sm">
                    <?php if($siswa['foto_profil']): ?>
                        <img src="../../assets/uploads/siswa/<?= $siswa['foto_profil'] ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="text-gray-500 font-bold text-xl"><?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?></span>
                    <?php endif; ?>
                </div>

                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-bold text-gray-800 truncate"><?= htmlspecialchars($siswa['nama_siswa']) ?></h3>
                    <p class="text-sm text-gray-500">NIS: <?= $siswa['nis'] ?> • <?= $siswa['nama_kelas'] ?></p>
                    
                    <div class="mt-2 flex items-center space-x-2 md:hidden">
                        <?php if ($siswa['status_sp_terakhir'] !== 'Aman'): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            🚨 <?= $siswa['status_sp_terakhir'] ?>
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            ✅ Aman
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-center bg-gray-50 px-3 py-2 rounded-lg border border-gray-100 min-w-[80px]">
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wide">Total</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $siswa['total_poin_umum'] ?></p>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 md:grid-cols-4 gap-4">
                
                <div class="hidden md:flex items-center">
                    <?php if ($siswa['status_sp_terakhir'] !== 'Aman'): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 w-full justify-center">
                        🚨 Status: <?= $siswa['status_sp_terakhir'] ?>
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 w-full justify-center">
                        ✅ Status: Aman
                    </span>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-3 gap-2 md:col-span-3">
                    <div class="text-center bg-red-50 rounded p-2">
                        <p class="text-[10px] text-red-600 font-bold uppercase">Kelakuan</p>
                        <p class="text-lg font-bold text-red-700"><?= $siswa['poin_kelakuan'] ?></p>
                    </div>
                    <div class="text-center bg-blue-50 rounded p-2">
                        <p class="text-[10px] text-blue-600 font-bold uppercase">Kerajinan</p>
                        <p class="text-lg font-bold text-blue-700"><?= $siswa['poin_kerajinan'] ?></p>
                    </div>
                    <div class="text-center bg-yellow-50 rounded p-2">
                        <p class="text-[10px] text-yellow-600 font-bold uppercase">Kerapian</p>
                        <p class="text-lg font-bold text-yellow-700"><?= $siswa['poin_kerapian'] ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

</body>
</html>