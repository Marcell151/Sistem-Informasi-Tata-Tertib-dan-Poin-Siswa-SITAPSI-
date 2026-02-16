<?php
/**
 * SITAPSI - Dashboard Admin (REVISED)
 * Aktivitas Terbaru: 1 Bulan Terakhir (bukan hanya hari ini)
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$user = getCurrentUser();

// Ambil tahun ajaran aktif
$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

if (!$tahun_aktif) {
    die("Error: Tidak ada tahun ajaran aktif.");
}

$id_tahun = $tahun_aktif['id_tahun'];

// 1. Statistik Hari Ini
$pelanggaran_hari_ini = fetchOne("
    SELECT COUNT(*) as total 
    FROM tb_pelanggaran_header 
    WHERE tanggal = CURDATE() 
    AND id_tahun = :id_tahun
", ['id_tahun' => $id_tahun])['total'] ?? 0;

// 2. Siswa Kena SP
$siswa_sp = fetchOne("
    SELECT COUNT(*) as total 
    FROM tb_anggota_kelas 
    WHERE status_sp_terakhir != 'Aman' 
    AND id_tahun = :id_tahun
", ['id_tahun' => $id_tahun])['total'] ?? 0;

// 3. Total Poin Sistem
$total_poin = fetchOne("
    SELECT SUM(total_poin_umum) as total 
    FROM tb_anggota_kelas 
    WHERE id_tahun = :id_tahun
", ['id_tahun' => $id_tahun])['total'] ?? 0;

// 4. Siswa Aktif
$total_siswa = fetchOne("
    SELECT COUNT(*) as total 
    FROM tb_anggota_kelas 
    WHERE id_tahun = :id_tahun
", ['id_tahun' => $id_tahun])['total'] ?? 0;

// 5. PERBAIKAN: Aktivitas Terbaru (1 BULAN TERAKHIR)
$aktivitas_terbaru = fetchAll("
    SELECT 
        h.tanggal,
        h.waktu,
        s.nama_siswa,
        k.nama_kelas,
        g.nama_guru,
        h.tipe_form,
        GROUP_CONCAT(jp.nama_pelanggaran SEPARATOR ', ') as pelanggaran_list
    FROM tb_pelanggaran_header h
    JOIN tb_anggota_kelas a ON h.id_anggota = a.id_anggota
    JOIN tb_siswa s ON a.nis = s.nis
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    JOIN tb_guru g ON h.id_guru = g.id_guru
    LEFT JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    LEFT JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    WHERE h.id_tahun = :id_tahun
    AND h.tanggal >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
    GROUP BY h.id_transaksi
    ORDER BY h.tanggal DESC, h.waktu DESC
    LIMIT 15
", ['id_tahun' => $id_tahun]);

// 6. Data Chart Mingguan (7 hari terakhir)
$tren_mingguan = fetchAll("
    SELECT 
        DATE(tanggal) as tgl,
        COUNT(*) as jumlah
    FROM tb_pelanggaran_header
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    AND id_tahun = :id_tahun
    GROUP BY DATE(tanggal)
    ORDER BY tgl ASC
", ['id_tahun' => $id_tahun]);

// 7. Data Chart Pie
$proporsi_kategori = fetchAll("
    SELECT 
        k.nama_kategori,
        COUNT(d.id_detail) as jumlah
    FROM tb_pelanggaran_detail d
    JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    JOIN tb_kategori_pelanggaran k ON jp.id_kategori = k.id_kategori
    JOIN tb_pelanggaran_header h ON d.id_transaksi = h.id_transaksi
    WHERE h.id_tahun = :id_tahun
    GROUP BY k.id_kategori, k.nama_kategori
", ['id_tahun' => $id_tahun]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SITAPSI Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 'navy': '#000080' }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto bg-gray-100">
        
        <div class="bg-white shadow-sm border-b px-6 py-4 sticky top-0 z-30 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Dashboard Admin</h1>
                <p class="text-sm text-gray-500">Ringkasan data Tahun Ajaran <?= $tahun_aktif['nama_tahun'] ?></p>
            </div>
            <div class="text-right hidden sm:block">
                <span class="text-xs font-bold text-gray-400 uppercase">Semester</span>
                <p class="text-lg font-bold text-navy"><?= $tahun_aktif['semester_aktif'] ?></p>
            </div>
        </div>

        <div class="p-6 space-y-6">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-blue-600">
                    <p class="text-xs font-bold text-gray-400 uppercase">Pelanggaran Hari Ini</p>
                    <p class="text-3xl font-bold text-gray-800"><?= $pelanggaran_hari_ini ?></p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-red-600">
                    <p class="text-xs font-bold text-gray-400 uppercase">Siswa Kena SP</p>
                    <p class="text-3xl font-bold text-red-600"><?= $siswa_sp ?></p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-yellow-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Total Poin Sistem</p>
                    <p class="text-3xl font-bold text-yellow-600"><?= number_format($total_poin) ?></p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-green-600">
                    <p class="text-xs font-bold text-gray-400 uppercase">Total Siswa Aktif</p>
                    <p class="text-3xl font-bold text-green-600"><?= $total_siswa ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <h3 class="font-bold text-gray-700 mb-4">Tren Pelanggaran 7 Hari Terakhir</h3>
                    <div class="relative h-[300px]">
                        <canvas id="chartTren"></canvas>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <h3 class="font-bold text-gray-700 mb-4">Proporsi Kategori Pelanggaran</h3>
                    <div class="relative h-[300px]">
                        <canvas id="chartPie"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b font-bold text-gray-700">
                    🕐 Aktivitas Pelaporan Terbaru (1 Bulan Terakhir)
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="p-4">Waktu</th>
                                <th class="p-4">Siswa</th>
                                <th class="p-4">Pelanggaran</th>
                                <th class="p-4">Pelapor</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y">
                            <?php if (empty($aktivitas_terbaru)): ?>
                            <tr>
                                <td colspan="4" class="p-12 text-center text-gray-500">Belum ada aktivitas dalam 1 bulan terakhir</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($aktivitas_terbaru as $a): ?>
                            <tr>
                                <td class="p-4"><?= date('d/m', strtotime($a['tanggal'])) ?> - <?= substr($a['waktu'], 0, 5) ?></td>
                                <td class="p-4 font-bold text-navy"><?= htmlspecialchars($a['nama_siswa']) ?> <br><span class="text-xs font-normal text-gray-400"><?= $a['nama_kelas'] ?></span></td>
                                <td class="p-4 text-gray-600"><?= htmlspecialchars($a['pelanggaran_list']) ?></td>
                                <td class="p-4"><?= htmlspecialchars($a['nama_guru']) ?> <span class="text-xs text-blue-500">(<?= $a['tipe_form'] ?>)</span></td>
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

<script>
// Logic Chart Tren
const trenCtx = document.getElementById('chartTren');
const dataTren = <?= json_encode($tren_mingguan) ?>;
new Chart(trenCtx, {
    type: 'line',
    data: {
        labels: dataTren.map(d => d.tgl),
        datasets: [{
            label: 'Pelanggaran',
            data: dataTren.map(d => d.jumlah),
            borderColor: '#2563EB',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
    }
});

// Logic Chart Pie
const pieCtx = document.getElementById('chartPie');
const dataPie = <?= json_encode($proporsi_kategori) ?>;
new Chart(pieCtx, {
    type: 'pie',
    data: {
        labels: dataPie.map(d => d.nama_kategori),
        datasets: [{
            data: dataPie.map(d => d.jumlah),
            backgroundColor: ['#DC2626', '#2563EB', '#D97706']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

</body>
</html>