<?php
/**
 * SITAPSI - Rekap Kelas untuk Guru (MOBILE RESPONSIVE FIXED)
 * Fitur: Reward Badge & Laporan Wali Kelas
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
            a.poin_kelakuan,
            a.poin_kerajinan,
            a.poin_kerapian,
            a.total_poin_umum,
            a.status_sp_terakhir,
            a.status_sp_kelakuan,
            a.status_sp_kerajinan,
            a.status_sp_kerapian,
            a.id_anggota
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

$is_wali_kelas = ($id_kelas_wali !== null && $id_kelas_wali == $id_kelas);

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Rekap Kelas - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: { colors: { 'navy': '#000080' } }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <?php include '../../includes/navbar_guru.php'; ?>

    <main class="flex-1 w-full max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Rekapitulasi Kelas</h1>
            <p class="text-sm text-gray-500 mt-1">Matriks poin dan SP per kategori</p>
        </div>

        <div class="space-y-6 w-full overflow-hidden">

            <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded shadow-sm">
                <p class="text-green-700 font-medium text-sm sm:text-base"><?= htmlspecialchars($success) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
                <p class="text-red-700 font-medium text-sm sm:text-base"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($is_wali_kelas): ?>
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg flex items-start shadow-sm text-sm sm:text-base">
                <svg class="w-6 h-6 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"></path>
                </svg>
                <div>
                    <p class="font-bold text-blue-800">Anda adalah Wali Kelas <?= htmlspecialchars($kelas_info['nama_kelas']) ?></p>
                    <p class="text-xs sm:text-sm text-blue-700 mt-1">Anda dapat melakukan pengajuan revisi jika ada kesalahan input melalui halaman Detail Siswa.</p>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm p-4 sm:p-5 border border-gray-100">
                <form method="GET" class="flex flex-col sm:flex-row items-start sm:items-end gap-3 w-full">
                    <div class="w-full sm:flex-1">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Lihat Data Kelas Lain</label>
                        <div class="relative">
                            <select name="kelas" onchange="this.form.submit()" 
                                    class="w-full pl-4 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-navy appearance-none font-medium text-gray-800 bg-gray-50 text-sm">
                                <?php foreach ($kelas_list as $k): ?>
                                <option value="<?= $k['id_kelas'] ?>" <?= $id_kelas == $k['id_kelas'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['nama_kelas']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <?php if ($id_kelas && isset($kelas_info)): ?>

            <div class="bg-gradient-to-r from-navy to-blue-800 text-white rounded-xl shadow-lg p-5 relative overflow-hidden">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"></div>
                <div class="flex items-center justify-between relative z-10">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold mb-1">Kelas <?= htmlspecialchars($kelas_info['nama_kelas']) ?></h2>
                        <p class="text-blue-200 text-xs sm:text-sm opacity-90">T.A: <?= $tahun_aktif['nama_tahun'] ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-blue-200 text-[10px] sm:text-xs font-bold uppercase tracking-wider mb-1">Total Siswa</p>
                        <p class="text-3xl sm:text-5xl font-extrabold"><?= count($siswa_kelas) ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden w-full">
                <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                    <span class="font-bold text-gray-800 flex items-center text-sm sm:text-base">
                        <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Matriks Pelanggaran
                    </span>
                    <span class="text-[10px] text-gray-400 italic hidden sm:block">Geser ke kanan untuk melihat lengkap 👉</span>
                </div>
                
                <div class="overflow-x-auto w-full pb-2">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-white text-xs text-gray-500 uppercase shadow-sm">
                            <tr>
                                <th class="p-3 text-center sticky left-0 bg-gray-100 z-20 border-b w-10 min-w-[40px]">No</th>
                                <th class="p-3 text-left sticky left-[40px] bg-gray-100 z-20 border-b min-w-[180px] max-w-[200px]">Siswa</th>
                                <th class="p-3 text-center bg-red-50 border-b">🚨 KL<br><span class="text-[10px] font-normal lowercase">(Poin)</span></th>
                                <th class="p-3 text-center bg-blue-50 border-b">📘 KJ<br><span class="text-[10px] font-normal lowercase">(Poin)</span></th>
                                <th class="p-3 text-center bg-yellow-50 border-b">👔 KP<br><span class="text-[10px] font-normal lowercase">(Poin)</span></th>
                                <th class="p-3 text-center bg-gray-100 border-b font-bold text-gray-800">TOTAL<br><span class="text-[10px] font-normal lowercase">(Poin)</span></th>
                                <th class="p-3 text-center bg-red-50 border-b">SP KL</th>
                                <th class="p-3 text-center bg-blue-50 border-b">SP KJ</th>
                                <th class="p-3 text-center bg-yellow-50 border-b">SP KP</th>
                                <th class="p-3 text-center bg-gray-800 text-white border-b">SP Max</th>
                                <th class="p-3 text-center border-b">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($siswa_kelas)): ?>
                            <tr>
                                <td colspan="11" class="p-12 text-center text-gray-500 whitespace-normal">
                                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    Belum ada siswa yang terdaftar di kelas ini
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($siswa_kelas as $idx => $siswa): 
                                $is_bersih = ($siswa['total_poin_umum'] == 0);
                            ?>
                            <tr class="hover:bg-blue-50 transition-colors <?= $is_bersih ? 'bg-yellow-50/50' : '' ?>">
                                <td class="p-3 text-center sticky left-0 z-10 <?= $is_bersih ? 'bg-yellow-50' : 'bg-white' ?> text-gray-500 text-xs border-r border-gray-50">
                                    <?= $idx + 1 ?>
                                </td>
                                <td class="p-3 sticky left-[40px] z-10 <?= $is_bersih ? 'bg-yellow-50' : 'bg-white' ?> min-w-[180px] max-w-[200px] border-r border-gray-50">
                                    <div class="font-bold text-navy text-[13px] truncate">
                                        <?= htmlspecialchars($siswa['nama_siswa']) ?>
                                        <?php if($is_bersih): ?>
                                            <span class="ml-1" title="Kandidat Siswa Teladan">🌟</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[10px] text-gray-400 mt-0.5"><?= $siswa['nis'] ?></div>
                                </td>
                                
                                <td class="p-3 text-center bg-red-50/30">
                                    <span class="font-bold <?= $siswa['poin_kelakuan'] > 0 ? 'text-red-600' : 'text-gray-400' ?>"><?= $siswa['poin_kelakuan'] ?></span>
                                </td>
                                <td class="p-3 text-center bg-blue-50/30">
                                    <span class="font-bold <?= $siswa['poin_kerajinan'] > 0 ? 'text-blue-600' : 'text-gray-400' ?>"><?= $siswa['poin_kerajinan'] ?></span>
                                </td>
                                <td class="p-3 text-center bg-yellow-50/30">
                                    <span class="font-bold <?= $siswa['poin_kerapian'] > 0 ? 'text-yellow-600' : 'text-gray-400' ?>"><?= $siswa['poin_kerapian'] ?></span>
                                </td>
                                
                                <td class="p-3 text-center bg-gray-50 border-x border-gray-100">
                                    <span class="px-2.5 py-1 <?= $is_bersih ? 'bg-yellow-400 text-yellow-900' : 'bg-gray-800 text-white' ?> rounded-md text-xs font-bold shadow-sm">
                                        <?= $siswa['total_poin_umum'] ?>
                                    </span>
                                </td>
                                
                                <td class="p-3 text-center bg-red-50/30">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded <?= $siswa['status_sp_kelakuan'] === 'Aman' ? 'text-gray-400' : 'bg-red-100 text-red-700' ?>">
                                        <?= $siswa['status_sp_kelakuan'] === 'Aman' ? '-' : $siswa['status_sp_kelakuan'] ?>
                                    </span>
                                </td>
                                <td class="p-3 text-center bg-blue-50/30">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded <?= $siswa['status_sp_kerajinan'] === 'Aman' ? 'text-gray-400' : 'bg-blue-100 text-blue-700' ?>">
                                        <?= $siswa['status_sp_kerajinan'] === 'Aman' ? '-' : $siswa['status_sp_kerajinan'] ?>
                                    </span>
                                </td>
                                <td class="p-3 text-center bg-yellow-50/30">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded <?= $siswa['status_sp_kerapian'] === 'Aman' ? 'text-gray-400' : 'bg-yellow-100 text-yellow-700' ?>">
                                        <?= $siswa['status_sp_kerapian'] === 'Aman' ? '-' : $siswa['status_sp_kerapian'] ?>
                                    </span>
                                </td>
                                
                                <td class="p-3 text-center bg-gray-50 border-x border-gray-100">
                                    <span class="text-[10px] font-extrabold <?= $siswa['status_sp_terakhir'] === 'Aman' ? 'text-green-600' : 'text-red-600' ?>">
                                        <?= $siswa['status_sp_terakhir'] ?>
                                    </span>
                                </td>
                                
                                <td class="p-3 text-center">
                                    <a href="detail_siswa.php?id=<?= $siswa['id_anggota'] ?>" 
                                       class="inline-flex items-center justify-center px-3 py-1.5 bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 hover:text-navy text-xs font-semibold shadow-sm transition-all">
                                        <span>Detail</span>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white border border-gray-200 p-4 rounded-xl shadow-sm text-xs sm:text-sm mb-10">
                <div class="flex items-start">
                    <span class="text-lg mr-3">💡</span>
                    <div>
                        <h4 class="font-bold text-gray-800 mb-2">Panduan Singkat</h4>
                        <ul class="text-gray-600 space-y-1.5 list-disc list-inside ml-2">
                            <li><strong>🌟 Kandidat Reward:</strong> Siswa dengan poin 0 (nol).</li>
                            <li><strong>SP Max (Tertinggi):</strong> Adalah tingkat SP terparah yang dimiliki siswa.</li>
                            <li><strong>Lihat Detail:</strong> Untuk melihat rincian pelanggaran (bisa geser tabel ke paling kanan).</li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php endif; ?>

        </div>
    </main>

</body>
</html>