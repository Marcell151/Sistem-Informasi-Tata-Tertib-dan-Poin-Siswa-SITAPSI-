<?php
/**
 * SITAPSI - List Siswa untuk Rapor Karakter (UI GLOBAL PORTAL)
 * Step 2: Pilih Siswa dari Kelas
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_kelas = $_GET['kelas'] ?? null;

if (!$id_kelas) {
    $_SESSION['error_message'] = '❌ Kelas tidak valid';
    header('Location: rapor_karakter.php');
    exit;
}

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// Info kelas
$kelas = fetchOne("SELECT * FROM tb_kelas WHERE id_kelas = :id", ['id' => $id_kelas]);

if (!$kelas) {
    $_SESSION['error_message'] = '❌ Kelas tidak ditemukan';
    header('Location: rapor_karakter.php');
    exit;
}

// Ambil siswa dalam kelas
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
        a.total_poin_umum
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Siswa - Rapor Karakter</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 pl-16 lg:pl-6 py-4 sticky top-0 z-30 flex items-center space-x-4">
            <a href="rapor_karakter.php" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            </a>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Pilih Siswa</h1>
                <p class="text-sm font-medium text-slate-500">Kelas <?= htmlspecialchars($kelas['nama_kelas']) ?></p>
            </div>
        </div>

        <div class="p-6 space-y-6 max-w-7xl mx-auto">

            <div class="bg-[#000080] text-white rounded-xl shadow-md shadow-blue-900/10 p-6 relative overflow-hidden">
                <svg class="absolute right-0 top-0 text-white/5 w-48 h-48 transform translate-x-8 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-extrabold mb-1">Kelas <?= htmlspecialchars($kelas['nama_kelas']) ?></h2>
                        <p class="text-blue-200 font-medium text-sm">Tingkat <?= $kelas['tingkat'] ?> • Tahun Ajaran <?= $tahun_aktif['nama_tahun'] ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-blue-200 text-xs font-bold uppercase tracking-wider mb-1">Total Siswa</p>
                        <p class="text-4xl font-extrabold"><?= count($siswa_list) ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($siswa_list)): ?>
                <div class="col-span-full bg-white rounded-xl shadow-sm border border-dashed border-[#E2E8F0] p-12 text-center">
                    <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <p class="text-slate-500 font-medium">Belum ada siswa di kelas ini</p>
                </div>
                <?php else: ?>
                <?php foreach ($siswa_list as $siswa): ?>
                <a href="rapor_karakter_cetak.php?id=<?= $siswa['id_anggota'] ?>" class="block group">
                    <div class="bg-white rounded-xl shadow-sm border border-[#E2E8F0] transition-all hover:shadow-lg hover:border-[#000080] transform hover:-translate-y-1 overflow-hidden flex flex-col h-full">
                        
                        <div class="p-5 flex items-center space-x-4 border-b border-[#E2E8F0] bg-slate-50/50">
                            <div class="w-14 h-14 bg-[#000080] rounded-full flex items-center justify-center overflow-hidden flex-shrink-0 text-white font-extrabold text-xl shadow-sm">
                                <?php if($siswa['foto_profil']): ?>
                                    <img src="../../assets/uploads/siswa/<?= htmlspecialchars($siswa['foto_profil']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-extrabold text-slate-800 truncate group-hover:text-[#000080] transition-colors">
                                    <?= htmlspecialchars($siswa['nama_siswa']) ?>
                                </h3>
                                <p class="text-[11px] font-medium text-slate-500 mt-0.5"><?= $siswa['nis'] ?> • <?= $siswa['jenis_kelamin'] === 'L' ? 'L' : 'P' ?></p>
                            </div>
                        </div>

                        <div class="p-5 flex-1">
                            <div class="grid grid-cols-3 gap-2 mb-4">
                                <div class="bg-red-50 border border-red-100 p-2 rounded-lg text-center">
                                    <p class="text-[9px] text-red-600 font-bold uppercase tracking-wider mb-1">Kelakuan</p>
                                    <p class="text-lg font-extrabold text-red-700"><?= $siswa['poin_kelakuan'] ?></p>
                                </div>
                                <div class="bg-blue-50 border border-blue-100 p-2 rounded-lg text-center">
                                    <p class="text-[9px] text-blue-600 font-bold uppercase tracking-wider mb-1">Kerajinan</p>
                                    <p class="text-lg font-extrabold text-blue-700"><?= $siswa['poin_kerajinan'] ?></p>
                                </div>
                                <div class="bg-yellow-50 border border-yellow-100 p-2 rounded-lg text-center">
                                    <p class="text-[9px] text-yellow-600 font-bold uppercase tracking-wider mb-1">Kerapian</p>
                                    <p class="text-lg font-extrabold text-yellow-700"><?= $siswa['poin_kerapian'] ?></p>
                                </div>
                            </div>

                            <div class="w-full bg-slate-50 text-slate-700 border border-[#E2E8F0] text-center py-2.5 rounded-lg text-xs font-bold group-hover:bg-[#000080] group-hover:text-white group-hover:border-[#000080] transition-colors flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                                Lihat Rapor Karakter
                            </div>
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