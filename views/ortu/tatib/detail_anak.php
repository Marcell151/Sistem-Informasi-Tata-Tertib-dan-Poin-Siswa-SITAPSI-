<?php
/**
 * SITAPSI - Detail Siswa untuk Orang Tua (READ ONLY)
 * FITUR BARU: Komunikasi 2 Arah (Buku Penghubung Digital via SP)
 */

session_start();
require_once '../../../config/database.php';

// 1. Validasi Akses Ortu
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Ortu') {
    header("Location: ../login.php");
    exit;
}

$id_ortu = $_SESSION['ortu_id'];
$no_induk = $_GET['induk'] ?? '';

if (empty($no_induk)) {
    header("Location: ../dashboard.php");
    exit;
}

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
$filter_semester = $_GET['semester'] ?? $tahun_aktif['semester_aktif'];

// 2. Query Siswa + VALIDASI KEPEMILIKAN
$siswa = fetchOne("
    SELECT 
        s.*,
        a.id_anggota,
        a.poin_kelakuan,
        a.poin_kerajinan,
        a.poin_kerapian,
        a.total_poin_umum,
        a.status_sp_terakhir,
        a.status_sp_kelakuan,
        a.status_sp_kerajinan,
        a.status_sp_kerapian,
        k.nama_kelas,
        k.id_kelas
    FROM tb_siswa s
    JOIN tb_anggota_kelas a ON s.no_induk = a.no_induk
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE s.no_induk = :induk AND s.id_ortu = :id_ortu AND a.id_tahun = :id_tahun
", [
    'induk' => $no_induk, 
    'id_ortu' => $id_ortu, 
    'id_tahun' => $tahun_aktif['id_tahun']
]);

if (!$siswa) {
    die("<div style='font-family:sans-serif;text-align:center;margin-top:50px;color:red;'><h2>Akses Ditolak!</h2><p>Data anak tidak ditemukan atau Anda tidak memiliki hak akses untuk profil ini.</p><a href='../dashboard.php'>Kembali ke Dashboard</a></div>");
}

$id_anggota = $siswa['id_anggota'];

// 3. LOGIKA REWARD
$cek_history = fetchOne("
    SELECT 
        COALESCE(SUM(d.poin_saat_itu), 0) as total_tahunan,
        COALESCE(SUM(CASE WHEN h.semester = :semester_berjalan THEN d.poin_saat_itu ELSE 0 END), 0) as total_semester
    FROM tb_pelanggaran_header h
    JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    WHERE h.id_anggota = :id_anggota AND h.id_tahun = :id_tahun
", [
    'id_anggota' => $id_anggota, 
    'id_tahun' => $tahun_aktif['id_tahun'],
    'semester_berjalan' => $tahun_aktif['semester_aktif']
]);

$is_kandidat_sertifikat = ($cek_history['total_tahunan'] == 0);
$is_kandidat_semester = (!$is_kandidat_sertifikat && $cek_history['total_semester'] == 0);

$ref_sanksi = fetchAll("SELECT kode_sanksi, deskripsi FROM tb_sanksi_ref");
$map_sanksi = [];
foreach($ref_sanksi as $rs) {
    $map_sanksi[$rs['kode_sanksi']] = $rs['deskripsi'];
}

// [MODIFIKASI] Ambil Riwayat SP beserta "Catatan Admin"
$riwayat_sp = fetchAll("
    SELECT id_sp, tingkat_sp, kategori_pemicu, tanggal_terbit, status, catatan_admin 
    FROM tb_riwayat_sp 
    WHERE id_anggota = :id_anggota AND status = 'Pending'
", ['id_anggota' => $id_anggota]);

// Helper query pelanggaran per kategori
function getPelanggaranOrtu($id_anggota, $id_kategori, $id_tahun, $filter_semester) {
    $sql = "
        SELECT 
            h.id_transaksi, h.tanggal, h.waktu, h.bukti_foto, h.lampiran_link,
            jp.nama_pelanggaran, jp.sanksi_default, d.poin_saat_itu,
            GROUP_CONCAT(DISTINCT sr.kode_sanksi SEPARATOR ',') as sanksi_aktual_kode
        FROM tb_pelanggaran_header h
        JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
        JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
        LEFT JOIN tb_pelanggaran_sanksi ps ON h.id_transaksi = ps.id_transaksi
        LEFT JOIN tb_sanksi_ref sr ON ps.id_sanksi_ref = sr.id_sanksi_ref
        WHERE h.id_anggota = :id AND jp.id_kategori = :id_kategori AND h.id_tahun = :id_tahun AND h.semester = :semester
        GROUP BY h.id_transaksi, d.id_detail
        ORDER BY h.tanggal DESC, h.waktu DESC
    ";
    return fetchAll($sql, ['id' => $id_anggota, 'id_kategori' => $id_kategori, 'id_tahun' => $id_tahun, 'semester' => $filter_semester]);
}

$pelanggaran_kelakuan = getPelanggaranOrtu($id_anggota, 1, $tahun_aktif['id_tahun'], $filter_semester);
$pelanggaran_kerajinan = getPelanggaranOrtu($id_anggota, 2, $tahun_aktif['id_tahun'], $filter_semester);
$pelanggaran_kerapian = getPelanggaranOrtu($id_anggota, 3, $tahun_aktif['id_tahun'], $filter_semester);

$card_class = "bg-white border border-slate-200 rounded-2xl shadow-sm";

// Notifikasi Feedback
$fb_success = $_SESSION['feedback_success'] ?? '';
$fb_error = $_SESSION['feedback_error'] ?? '';
unset($_SESSION['feedback_success'], $_SESSION['feedback_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Rapor Disiplin - <?= htmlspecialchars($siswa['nama_siswa']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-50 pb-24 md:pb-12">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="../dashboard.php" class="flex items-center text-slate-500 hover:text-[#000080] font-bold transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Kembali ke Dashboard
                </a>
                <div class="flex items-center space-x-2 text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    <span class="text-xs font-bold uppercase tracking-wider hidden sm:inline">Modul Tata Tertib</span>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Rapor Kedisiplinan</h1>
                <p class="text-sm font-medium text-slate-500">Tahun Ajaran <?= $tahun_aktif['nama_tahun'] ?></p>
            </div>
            <a href="../../../actions/cetak_detail_siswa.php?id=<?= $id_anggota ?>" target="_blank" class="px-5 py-2.5 bg-[#000080] text-white hover:bg-blue-900 text-sm font-bold rounded-xl shadow-md transition-colors flex items-center justify-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2-2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Unduh PDF
            </a>
        </div>

        <div class="space-y-6">

            <?php if ($fb_success): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <p class="font-bold text-sm"><?= htmlspecialchars($fb_success) ?></p>
            </div>
            <?php endif; ?>
            <?php if ($fb_error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                <p class="font-bold text-sm"><?= htmlspecialchars($fb_error) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($is_kandidat_sertifikat): ?>
            <div class="bg-amber-100 border border-amber-300 rounded-2xl p-5 shadow-sm flex items-center">
                <div class="flex-shrink-0 bg-white p-3 rounded-full mr-5 shadow-sm border border-amber-200 text-amber-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 15l-3.09 1.63.59-3.45L7 10.74l3.46-.5L12 7l1.54 3.24 3.46.5-2.5 2.44.59 3.45L12 15z"></path></svg>
                </div>
                <div>
                    <h4 class="font-extrabold text-amber-800 text-lg mb-1">🌟 Prestasi Kedisiplinan Luar Biasa 🌟</h4>
                    <p class="text-sm text-amber-700 font-medium">Selamat! Putra/putri Anda memiliki <strong>0 Poin Pelanggaran selama 1 Tahun Penuh</strong>.</p>
                </div>
            </div>
            <?php elseif ($is_kandidat_semester): ?>
            <div class="bg-emerald-100 border border-emerald-300 rounded-2xl p-5 shadow-sm flex items-center">
                <div class="flex-shrink-0 bg-white p-3 rounded-full mr-5 shadow-sm border border-emerald-200 text-emerald-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                </div>
                <div>
                    <h4 class="font-extrabold text-emerald-800 text-lg mb-1">🏅 Pertahankan Disiplin</h4>
                    <p class="text-sm text-emerald-700 font-medium">Putra/putri Anda memiliki <strong>0 Poin Pelanggaran di Semester ini</strong>.</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($riwayat_sp)): ?>
            <div class="bg-red-50 border-2 border-red-400 rounded-2xl p-5 sm:p-6 shadow-md relative overflow-hidden">
                <svg class="absolute -right-4 -bottom-4 w-32 h-32 text-red-500 opacity-10" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zM9 13v-2h2v2H9zm0-8h2v5H9V5z"></path></svg>
                
                <div class="relative z-10">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="flex-shrink-0 bg-red-100 p-3 rounded-full shadow-sm text-red-600">
                            <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        </div>
                        <div class="flex-1">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2">
                                <div>
                                    <h4 class="font-black text-red-800 text-lg sm:text-xl mb-1 uppercase tracking-wider">Perhatian Wali Murid!</h4>
                                    <p class="text-sm text-red-700 font-medium mb-3">Terdapat <strong class="font-extrabold"><?= count($riwayat_sp) ?> Surat Peringatan (SP)</strong> aktif yang membutuhkan perhatian Anda.</p>
                                </div>
                                <button onclick="bukaModalFeedback()" class="flex-shrink-0 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-black text-sm rounded-xl shadow-md transition-transform transform active:scale-95 flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                    Balas / Tanggapi
                                </button>
                            </div>

                            <div class="space-y-3 mt-2">
                                <?php foreach($riwayat_sp as $sp): ?>
                                    <div class="bg-white/60 border border-red-200 rounded-xl p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 bg-red-100 text-red-700 text-xs font-extrabold rounded-md uppercase">
                                                <?= $sp['tingkat_sp'] ?>
                                            </span>
                                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wide">
                                                Kategori <?= $sp['kategori_pemicu'] ?>
                                            </span>
                                            <span class="text-xs text-slate-500 ml-auto font-medium">
                                                Terbit: <?= date('d M Y', strtotime($sp['tanggal_terbit'])) ?>
                                            </span>
                                        </div>
                                        
                                        <?php if (!empty($sp['catatan_admin'])): ?>
                                            <div class="mt-3 p-3 bg-red-50 border-l-4 border-red-500 rounded-r-lg">
                                                <p class="text-[10px] font-extrabold text-red-800 uppercase tracking-wider mb-1 flex items-center">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                                    Pesan dari Admin Tatib:
                                                </p>
                                                <p class="text-sm text-slate-700 font-medium italic">"<?= nl2br(htmlspecialchars($sp['catatan_admin'])) ?>"</p>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-xs text-slate-500 italic mt-2 mb-2">Silakan cek detail pelanggaran di tabel bawah. Tunggu pesan dari Admin atau klik tombol "Balas" untuk konfirmasi.</p>
                                        <?php endif; ?>

                                        <?php if (!empty($sp['balasan_ortu'])): ?>
                                            <div class="mt-2 p-3 bg-blue-50 border-r-4 border-blue-500 rounded-l-lg text-right ml-8 shadow-sm">
                                                <p class="text-[10px] font-extrabold text-blue-800 uppercase tracking-wider mb-1 flex items-center justify-end">
                                                    Balasan Anda:
                                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                </p>
                                                <p class="text-sm text-slate-800 font-medium">"<?= nl2br(htmlspecialchars($sp['balasan_ortu'])) ?>"</p>
                                                <p class="text-[9px] text-slate-400 mt-1"><?= date('d M Y H:i', strtotime($sp['waktu_balasan'])) ?> WIB</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-[#000080] text-white rounded-3xl shadow-lg p-6 md:p-8 relative overflow-hidden flex flex-col md:flex-row md:items-stretch gap-6">
                <svg class="absolute right-0 top-0 text-white/5 w-64 h-64 transform translate-x-12 -translate-y-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 22h20L12 2zm0 3.8L18.4 19H5.6L12 5.8z"></path></svg>
                
                <div class="flex-1 relative z-10 flex flex-col sm:flex-row items-center sm:items-start gap-6">
                    <div class="w-24 h-24 md:w-28 md:h-28 bg-white rounded-full flex items-center justify-center overflow-hidden flex-shrink-0 border-4 border-white/20 shadow-lg">
                        <span class="text-[#000080] font-extrabold text-5xl"><?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?></span>
                    </div>
                    
                    <div class="text-center sm:text-left flex-1">
                        <h2 class="text-3xl font-extrabold mb-1"><?= htmlspecialchars($siswa['nama_siswa']) ?></h2>
                        <p class="text-blue-200 font-medium mb-5">NIS: <?= $siswa['no_induk'] ?> • Kelas <?= $siswa['nama_kelas'] ?></p>
                        
                        <div class="flex flex-wrap justify-center sm:justify-start gap-3">
                            <div class="bg-white/10 px-4 py-2.5 rounded-xl border border-white/10 text-center min-w-[90px]">
                                <p class="text-xs text-blue-200 font-bold uppercase tracking-wider mb-0.5">Kelakuan</p>
                                <p class="text-2xl font-extrabold text-red-300"><?= $siswa['poin_kelakuan'] ?></p>
                            </div>
                            <div class="bg-white/10 px-4 py-2.5 rounded-xl border border-white/10 text-center min-w-[90px]">
                                <p class="text-xs text-blue-200 font-bold uppercase tracking-wider mb-0.5">Kerajinan</p>
                                <p class="text-2xl font-extrabold text-blue-300"><?= $siswa['poin_kerajinan'] ?></p>
                            </div>
                            <div class="bg-white/10 px-4 py-2.5 rounded-xl border border-white/10 text-center min-w-[90px]">
                                <p class="text-xs text-blue-200 font-bold uppercase tracking-wider mb-0.5">Kerapian</p>
                                <p class="text-2xl font-extrabold text-yellow-300"><?= $siswa['poin_kerapian'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full md:w-80 bg-white/10 p-5 rounded-2xl border border-white/10 backdrop-blur-sm relative z-10 flex flex-col justify-center">
                    <p class="text-xs text-blue-200 uppercase tracking-widest font-extrabold mb-3 text-center">Status Kedisiplinan</p>
                    <div class="flex items-center justify-center gap-5 mb-5">
                        <div class="text-center">
                            <p class="text-[10px] text-blue-200 font-bold uppercase mb-1">Total Poin</p>
                            <p class="text-5xl font-black text-white"><?= $siswa['total_poin_umum'] ?></p>
                        </div>
                        <div class="w-px h-16 bg-white/20"></div>
                        <div class="text-center flex-1">
                            <p class="text-[10px] text-blue-200 font-bold uppercase mb-1">Status Akhir</p>
                            <span class="inline-block px-4 py-2 rounded-xl text-lg font-black shadow-md w-full <?= $siswa['status_sp_terakhir'] === 'Aman' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white animate-pulse' ?>">
                                <?= strtoupper($siswa['status_sp_terakhir']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-2 border-t border-white/20 pt-4">
                        <?php
                        $sp_data = [
                            ['nama' => 'Kelakuan', 'status' => $siswa['status_sp_kelakuan']],
                            ['nama' => 'Kerajinan', 'status' => $siswa['status_sp_kerajinan']],
                            ['nama' => 'Kerapian', 'status' => $siswa['status_sp_kerapian']]
                        ];
                        foreach ($sp_data as $sp):
                            $is_aman = $sp['status'] === 'Aman';
                        ?>
                        <div class="text-center">
                            <p class="text-[10px] text-blue-100 font-medium mb-1.5"><?= $sp['nama'] ?></p>
                            <span class="px-2 py-1 rounded text-xs font-bold block <?= $is_aman ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-red-500/80 text-white shadow-sm border border-red-400' ?>">
                                <?= $sp['status'] === 'Aman' ? 'OK' : $sp['status'] ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="<?= $card_class ?> p-4 flex flex-col sm:flex-row items-center justify-between bg-slate-50 gap-4">
                <div class="flex items-center space-x-3 w-full sm:w-auto">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Pilih Semester:</span>
                    <a href="?induk=<?= $no_induk ?>&semester=Ganjil"
                        class="px-5 py-2.5 rounded-xl font-bold text-xs transition-colors flex-1 text-center <?= $filter_semester === 'Ganjil' ? 'bg-[#000080] text-white shadow-md' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100' ?>">
                        Ganjil
                    </a>
                    <a href="?induk=<?= $no_induk ?>&semester=Genap"
                        class="px-5 py-2.5 rounded-xl font-bold text-xs transition-colors flex-1 text-center <?= $filter_semester === 'Genap' ? 'bg-[#000080] text-white shadow-md' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100' ?>">
                        Genap
                    </a>
                </div>
            </div>

            <div class="<?= $card_class ?> overflow-hidden">
                <div class="flex border-b border-slate-200 overflow-x-auto bg-slate-50 scrollbar-hide">
                    <button onclick="switchTab('kelakuan')" id="tab-kelakuan" class="tab-button flex-1 py-4 px-4 font-extrabold text-sm text-center transition-colors bg-red-600 text-white border-b-2 border-red-700 whitespace-nowrap">🚨 KELAKUAN</button>
                    <button onclick="switchTab('kerajinan')" id="tab-kerajinan" class="tab-button flex-1 py-4 px-4 font-bold text-sm text-center transition-colors text-slate-500 hover:text-slate-800 hover:bg-slate-100 border-b-2 border-transparent whitespace-nowrap">📘 KERAJINAN</button>
                    <button onclick="switchTab('kerapian')" id="tab-kerapian" class="tab-button flex-1 py-4 px-4 font-bold text-sm text-center transition-colors text-slate-500 hover:text-slate-800 hover:bg-slate-100 border-b-2 border-transparent whitespace-nowrap">👔 KERAPIAN</button>
                </div>

                <?php 
                $kategori_data = ['kelakuan' => ['data' => $pelanggaran_kelakuan, 'color' => 'red'], 'kerajinan' => ['data' => $pelanggaran_kerajinan, 'color' => 'blue'], 'kerapian' => ['data' => $pelanggaran_kerapian, 'color' => 'yellow']];
                foreach ($kategori_data as $key => $kat): $color = $kat['color'];
                ?>
                <div id="content-<?= $key ?>" class="tab-content <?= $key !== 'kelakuan' ? 'hidden' : '' ?>">
                    <?php if (empty($kat['data'])): ?>
                    <div class="text-center py-16 text-slate-400">
                        <div class="bg-slate-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <p class="font-extrabold text-slate-600">Bagus!</p>
                        <p class="font-medium text-sm mt-1">Tidak ada catatan pelanggaran di kategori ini.</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs text-slate-500 uppercase border-b border-slate-200 whitespace-nowrap">
                                <tr>
                                    <th class="p-4 font-bold w-1/6">Waktu</th>
                                    <th class="p-4 font-bold w-1/3">Jenis Pelanggaran</th>
                                    <th class="p-4 font-bold w-1/4">Sanksi Spesifik</th>
                                    <th class="p-4 font-bold text-center">Poin</th>
                                    <th class="p-4 font-bold text-center">Bukti / Lampiran</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <?php foreach ($kat['data'] as $p): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="p-4 whitespace-nowrap align-top">
                                        <p class="font-bold text-slate-800 text-sm"><?= date('d/m/Y', strtotime($p['tanggal'])) ?></p>
                                        <p class="text-xs text-slate-500 font-medium mt-1"><?= substr($p['waktu'], 0, 5) ?></p>
                                    </td>
                                    
                                    <td class="p-4 align-top">
                                        <p class="text-sm font-bold text-slate-800 leading-relaxed"><?= htmlspecialchars($p['nama_pelanggaran']) ?></p>
                                    </td>
                                    
                                    <td class="p-4 align-top text-sm font-medium text-slate-700 leading-relaxed">
                                        <?php 
                                        $aktual_kodes = array_filter(explode(',', $p['sanksi_aktual_kode'] ?? ''));
                                        $default_kodes = array_filter(explode(',', $p['sanksi_default'] ?? ''));
                                        $irisan_kodes = array_intersect($aktual_kodes, $default_kodes);
                                        $kodes_tampil = !empty($irisan_kodes) ? $irisan_kodes : $aktual_kodes;

                                        if(!empty($kodes_tampil)): 
                                        ?>
                                            <ul class="list-disc pl-4 space-y-1.5 marker:text-slate-400">
                                                <?php foreach($kodes_tampil as $kode): ?>
                                                    <?php if(isset($map_sanksi[$kode])): ?>
                                                        <li><?= htmlspecialchars($map_sanksi[$kode]) ?></li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <span class="text-slate-400 italic text-xs">Tidak ada catatan sanksi spesifik</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="p-4 text-center whitespace-nowrap align-top">
                                        <span class="px-3 py-1.5 rounded-lg text-sm font-extrabold bg-<?= $color ?>-50 text-<?= $color ?>-600 border border-<?= $color ?>-200">+<?= $p['poin_saat_itu'] ?></span>
                                    </td>
                                    
                                    <td class="p-4 text-center whitespace-nowrap align-top">
                                        <?php if ((!empty($p['bukti_foto']) && $p['bukti_foto'] !== 'null') || !empty($p['lampiran_link'])): ?>
                                            <button onclick="lihatBukti('<?= htmlspecialchars($p['bukti_foto'] ?? 'null', ENT_QUOTES) ?>', '<?= htmlspecialchars($p['lampiran_link'] ?? '', ENT_QUOTES) ?>')" class="inline-flex items-center px-4 py-2 bg-white border border-blue-200 text-blue-600 rounded-lg hover:bg-blue-50 font-bold text-sm transition-colors shadow-sm" title="Lihat Bukti">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                Lihat
                                            </button>
                                        <?php else: ?>
                                            <span class="px-3 py-1.5 rounded-lg text-sm font-medium text-slate-400">Kosong</span>
                                        <?php endif; ?>
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
        </div>
    </main>

<div id="modal-bukti" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" onclick="document.getElementById('modal-bukti').classList.add('hidden')"></div>
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full flex flex-col max-h-[90vh] relative z-10 overflow-hidden">
        <div class="p-6 border-b border-slate-200 bg-slate-50/50 flex justify-between items-center">
            <h3 class="font-extrabold text-slate-800 flex items-center text-lg">
                <svg class="w-6 h-6 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                Bukti Kejadian
            </h3>
            <button onclick="document.getElementById('modal-bukti').classList.add('hidden')" class="text-slate-400 hover:text-slate-800 transition-colors bg-white p-2 rounded-xl border border-slate-200 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto bg-slate-100/50" id="bukti-container"></div>
    </div>
</div>

<?php if (!empty($riwayat_sp)): ?>
<div id="modal-feedback" class="hidden fixed inset-0 z-[80] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" onclick="tutupModalFeedback()"></div>
    <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full relative z-10 overflow-hidden">
        
        <div class="bg-red-600 p-6 flex justify-between items-center text-white">
            <div>
                <h3 class="font-black text-xl mb-1">Tanggapan Wali Murid</h3>
                <p class="text-red-100 text-sm font-medium">Buku Penghubung Digital Kedisiplinan</p>
            </div>
            <button onclick="tutupModalFeedback()" class="text-red-200 hover:text-white transition-colors bg-red-700/50 p-2 rounded-xl border border-red-500/50 hover:bg-red-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        <form action="../../../actions/submit_feedback_ortu.php" method="POST" class="p-6 space-y-5">
            <input type="hidden" name="id_ortu" value="<?= $id_ortu ?>">
            <input type="hidden" name="no_induk" value="<?= htmlspecialchars($no_induk) ?>">
            
            <div>
                <label class="block text-xs font-extrabold text-slate-500 mb-2 uppercase tracking-wide">Surat Peringatan <span class="text-red-500">*</span></label>
                <select name="id_sp" required class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 font-semibold focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                    <option value="">-- Pilih SP yang akan ditanggapi --</option>
                    <?php foreach($riwayat_sp as $sp): ?>
                        <option value="<?= $sp['id_sp'] ?>">SP: <?= $sp['tingkat_sp'] ?> - <?= $sp['kategori_pemicu'] ?> (Tgl: <?= date('d/m/Y', strtotime($sp['tanggal_terbit'])) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-extrabold text-slate-500 mb-2 uppercase tracking-wide">Pesan / Balasan Anda <span class="text-red-500">*</span></label>
                <textarea name="isi_feedback" required rows="4" placeholder="Contoh: Baik Pak/Bu, besok saya akan menghadap ke sekolah jam 09.00 WIB..." class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors resize-none"></textarea>
                <p class="text-[10px] text-slate-400 mt-2 font-medium">*Pesan ini akan langsung dibaca oleh Admin Tata Tertib Sekolah.</p>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="tutupModalFeedback()" class="flex-1 py-3 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-colors shadow-sm">Batal</button>
                <button type="submit" class="flex-1 py-3 bg-red-600 text-white font-extrabold rounded-xl shadow-md hover:bg-red-700 transition-colors shadow-red-500/20 flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    Kirim Pesan
                </button>
            </div>
        </form>

    </div>
</div>
<?php endif; ?>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    document.querySelectorAll('.tab-button').forEach(b => {
        b.classList.remove('bg-red-600', 'bg-blue-600', 'bg-yellow-500', 'text-white', 'border-red-700', 'border-blue-700', 'border-yellow-600');
        b.classList.add('bg-white', 'text-slate-500', 'border-transparent');
    });
    
    document.getElementById('content-' + tab).classList.remove('hidden');
    const activeTab = document.getElementById('tab-' + tab);
    
    activeTab.classList.remove('bg-white', 'text-slate-500', 'border-transparent');
    activeTab.classList.add('text-white');
    
    if (tab === 'kelakuan') activeTab.classList.add('bg-red-600', 'border-red-700');
    else if (tab === 'kerajinan') activeTab.classList.add('bg-blue-600', 'border-blue-700');
    else activeTab.classList.add('bg-yellow-500', 'border-yellow-600');
}

function lihatBukti(jsonString, lampiranLink) {
    const container = document.getElementById('bukti-container');
    container.innerHTML = '';
    
    if (lampiranLink && lampiranLink !== 'null' && lampiranLink !== '') {
        container.innerHTML += `
            <a href="${lampiranLink}" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between p-5 mb-4 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-2xl transition-colors shadow-sm group">
                <div class="flex items-center space-x-4 overflow-hidden">
                    <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center flex-shrink-0 shadow-sm text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-extrabold text-blue-900">Buka Tautan Lampiran</p>
                        <p class="text-xs text-blue-700 truncate max-w-[200px] sm:max-w-sm mt-0.5">${lampiranLink}</p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-blue-400 group-hover:text-blue-600 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
            </a>
        `;
    }
    
    if (jsonString && jsonString !== 'null' && jsonString !== '') {
        try {
            const fotos = JSON.parse(jsonString);
            let fileGrid = '<div class="grid grid-cols-2 sm:grid-cols-3 gap-4">';
            
            fotos.forEach(foto => {
                const imgPath = '../../../assets/uploads/bukti/' + foto;
                const ext = foto.split('.').pop().toLowerCase();
                const isImage = ['jpg', 'jpeg', 'png', 'webp'].includes(ext);
                
                if(isImage) {
                    fileGrid += `
                        <a href="${imgPath}" target="_blank" class="block group relative rounded-2xl overflow-hidden border border-slate-200 shadow-sm bg-white">
                            <img src="${imgPath}" class="w-full h-36 object-cover transition-transform duration-300 group-hover:scale-110" onerror="this.onerror=null; this.src='../../../assets/img/no-image.png';">
                        </a>`;
                } else if(ext === 'pdf') {
                    fileGrid += `
                        <a href="${imgPath}" target="_blank" class="flex flex-col items-center justify-center p-4 h-36 bg-white border border-slate-200 hover:border-red-300 hover:bg-red-50 rounded-2xl transition-colors shadow-sm group">
                            <svg class="w-10 h-10 text-red-500 mb-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            <p class="text-[10px] font-bold text-slate-600 text-center truncate w-full px-2" title="${foto}">Unduh PDF</p>
                        </a>`;
                } else {
                    fileGrid += `
                        <a href="${imgPath}" target="_blank" class="flex flex-col items-center justify-center p-4 h-36 bg-white border border-slate-200 hover:border-blue-300 hover:bg-blue-50 rounded-2xl transition-colors shadow-sm group">
                            <svg class="w-10 h-10 text-blue-500 mb-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            <p class="text-[10px] font-bold text-slate-600 text-center truncate w-full px-2" title="${foto}">Unduh Dokumen</p>
                        </a>`;
                }
            });
            
            fileGrid += '</div>';
            container.innerHTML += fileGrid;
            
        } catch(e) {
            console.error("Gagal parsing JSON foto", e);
        }
    }
    
    if(container.innerHTML === '') {
        container.innerHTML = '<div class="text-center text-slate-400 py-10"><p class="font-bold">Tidak ada bukti visual yang dilampirkan.</p></div>';
    }

    document.getElementById('modal-bukti').classList.remove('hidden');
}

// BUKA TUTUP MODAL FEEDBACK
function bukaModalFeedback() {
    document.getElementById('modal-feedback').classList.remove('hidden');
}
function tutupModalFeedback() {
    document.getElementById('modal-feedback').classList.add('hidden');
}
</script>
</body>
</html>