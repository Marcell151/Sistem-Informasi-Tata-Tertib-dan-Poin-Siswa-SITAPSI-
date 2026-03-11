<?php
/**
 * SITAPSI - Monitoring Siswa List (UI GLOBAL PORTAL)
 * FIX LOGIKA: Lencana Kandidat Reward dinamis (Semester / Sertifikat Tahunan)
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_kelas = $_GET['kelas'] ?? null;
if (!$id_kelas) {
    header('Location: monitoring_siswa.php');
    exit;
}

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
$kelas_info = fetchOne("SELECT * FROM tb_kelas WHERE id_kelas = :id", ['id' => $id_kelas]);
$semester_berjalan = $tahun_aktif['semester_aktif'];

// Query siswa dengan pengecekan total poin TAHUNAN dan poin SEMESTER BERJALAN
$siswa_list = fetchAll("
    SELECT 
        s.no_induk, s.nama_siswa, s.jenis_kelamin,
        a.id_anggota, a.poin_kelakuan, a.poin_kerajinan, a.poin_kerapian, a.total_poin_umum,
        a.status_sp_terakhir, a.status_sp_kelakuan, a.status_sp_kerajinan, a.status_sp_kerapian,
        (SELECT COALESCE(SUM(d.poin_saat_itu), 0) 
         FROM tb_pelanggaran_header h 
         JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi 
         WHERE h.id_anggota = a.id_anggota AND h.id_tahun = a.id_tahun) as total_tahunan,
        (SELECT COALESCE(SUM(d.poin_saat_itu), 0) 
         FROM tb_pelanggaran_header h 
         JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi 
         WHERE h.id_anggota = a.id_anggota AND h.id_tahun = a.id_tahun AND h.semester = :semester) as total_semester
    FROM tb_anggota_kelas a
    JOIN tb_siswa s ON a.no_induk = s.no_induk
    WHERE a.id_kelas = :id_kelas AND a.id_tahun = :id_tahun AND s.status_aktif = 'Aktif'
    ORDER BY s.nama_siswa
", ['id_kelas' => $id_kelas, 'id_tahun' => $tahun_aktif['id_tahun'], 'semester' => $semester_berjalan]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Kelas <?= htmlspecialchars($kelas_info['nama_kelas']) ?> - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    <?php include '../../includes/sidebar_admin.php'; ?>
    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 pl-16 lg:pl-6 py-4 sticky top-0 z-30 flex items-center space-x-4">
            <a href="monitoring_siswa.php" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            </a>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Kelas <?= htmlspecialchars($kelas_info['nama_kelas']) ?></h1>
                <p class="text-sm font-medium text-slate-500">Monitoring profil dan pelanggaran individu</p>
            </div>
        </div>

        <div class="p-6 space-y-6 max-w-7xl mx-auto">
            <div class="bg-[#000080] text-white rounded-xl shadow-md shadow-blue-900/10 p-6 relative overflow-hidden">
                <svg class="absolute right-0 top-0 text-white/5 w-48 h-48 transform translate-x-8 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-extrabold mb-1">Daftar Siswa Kelas <?= htmlspecialchars($kelas_info['nama_kelas']) ?></h2>
                        <p class="text-blue-200 font-medium text-sm">Tahun Ajaran <?= $tahun_aktif['nama_tahun'] ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-blue-200 text-xs font-bold uppercase tracking-wider mb-1">Total Siswa</p>
                        <p class="text-4xl font-extrabold"><?= count($siswa_list) ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php if (empty($siswa_list)): ?>
                <div class="col-span-full bg-white rounded-xl shadow-sm p-12 text-center border border-dashed border-[#E2E8F0]">
                    <p class="text-slate-500 font-bold">Tidak ada siswa di kelas ini</p>
                </div>
                <?php else: ?>
                <?php foreach ($siswa_list as $siswa): 
                    // LOGIKA BARU LENCANA REWARD
                    $is_kandidat_sertifikat = ($siswa['total_tahunan'] == 0); // 0 poin full 1 tahun
                    $is_kandidat_semester = (!$is_kandidat_sertifikat && $siswa['total_semester'] == 0); // 0 poin di semester ini saja
                    $is_bersih = ($is_kandidat_sertifikat || $is_kandidat_semester);
                ?>
                <a href="detail_siswa.php?id=<?= $siswa['id_anggota'] ?>" 
                   class="block bg-white rounded-xl shadow-sm border <?= $is_bersih ? 'border-amber-400' : 'border-[#E2E8F0]' ?> hover:shadow-lg transition-all transform hover:-translate-y-1 overflow-hidden relative group">
                    
                    <?php if ($is_kandidat_sertifikat): ?>
                    <div class="absolute top-0 right-0 bg-amber-400 text-amber-900 text-[10px] font-extrabold px-3 py-1 rounded-bl-xl shadow-sm z-10 flex items-center">
                        🏆 Kandidat Sertifikat
                    </div>
                    <?php elseif ($is_kandidat_semester): ?>
                    <div class="absolute top-0 right-0 bg-emerald-400 text-emerald-900 text-[10px] font-extrabold px-3 py-1 rounded-bl-xl shadow-sm z-10 flex items-center">
                        🏅 Kandidat Reward Semester
                    </div>
                    <?php endif; ?>

                    <div class="p-5 flex items-center space-x-4 border-b border-[#E2E8F0] bg-slate-50/50 mt-4">
                        <div class="w-14 h-14 bg-[#000080] rounded-full flex items-center justify-center overflow-hidden flex-shrink-0 text-white font-extrabold text-xl shadow-sm <?= $is_kandidat_sertifikat ? 'ring-2 ring-amber-400 ring-offset-2' : ($is_kandidat_semester ? 'ring-2 ring-emerald-400 ring-offset-2' : '') ?>">
                            <?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-extrabold text-slate-800 truncate text-sm group-hover:text-[#000080] transition-colors"><?= htmlspecialchars($siswa['nama_siswa']) ?></h3>
                            <p class="text-[11px] font-medium text-slate-500 mt-0.5">No Induk: <?= $siswa['no_induk'] ?> • <?= $siswa['jenis_kelamin'] === 'L' ? 'L' : 'P' ?></p>
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            <div class="text-center bg-slate-50 border border-[#E2E8F0] rounded-lg p-2">
                                <p class="text-[9px] text-slate-400 font-bold mb-1 uppercase">Kelakuan</p>
                                <p class="text-base font-extrabold text-red-600"><?= $siswa['poin_kelakuan'] ?></p>
                            </div>
                            <div class="text-center bg-slate-50 border border-[#E2E8F0] rounded-lg p-2">
                                <p class="text-[9px] text-slate-400 font-bold mb-1 uppercase">Kerajinan</p>
                                <p class="text-base font-extrabold text-blue-600"><?= $siswa['poin_kerajinan'] ?></p>
                            </div>
                            <div class="text-center bg-slate-50 border border-[#E2E8F0] rounded-lg p-2">
                                <p class="text-[9px] text-slate-400 font-bold mb-1 uppercase">Kerapian</p>
                                <p class="text-base font-extrabold text-yellow-600"><?= $siswa['poin_kerapian'] ?></p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-[#E2E8F0]">
                            <div>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Total Poin Smt</p>
                                <p class="font-extrabold text-lg text-slate-800 leading-none"><?= $siswa['total_poin_umum'] ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Status SP</p>
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold <?= $siswa['status_sp_terakhir'] === 'Aman' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-red-50 text-red-600 border border-red-200' ?>">
                                    <?= $siswa['status_sp_terakhir'] ?>
                                </span>
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