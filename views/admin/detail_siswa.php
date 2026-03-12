<?php
/**
 * SITAPSI - Detail Siswa (FIX TABLE WRAP + UI GLOBAL)
 * FIX LOGIKA: Spanduk Kandidat Reward dinamis (Semester / Sertifikat Tahunan)
 * PENYESUAIAN: UI Sanksi Kolom Mandiri (List) & Algoritma Irisan
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

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
$filter_semester = $_GET['semester'] ?? $tahun_aktif['semester_aktif'];

// Query siswa dengan SP per kategori (DISESUAIKAN NO INDUK)
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
    FROM tb_anggota_kelas a
    JOIN tb_siswa s ON a.no_induk = s.no_induk
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE a.id_anggota = :id
", ['id' => $id_anggota]);

if (!$siswa) {
    $_SESSION['error_message'] = '❌ Siswa tidak ditemukan';
    header('Location: monitoring_siswa.php');
    exit;
}

// LOGIKA BARU: Cek histori 1 tahun dan semester berjalan
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

// Hitung poin per semester
$poin_ganjil = fetchOne("
    SELECT 
        COALESCE(SUM(CASE WHEN jp.id_kategori = 1 THEN d.poin_saat_itu ELSE 0 END), 0) as kelakuan,
        COALESCE(SUM(CASE WHEN jp.id_kategori = 2 THEN d.poin_saat_itu ELSE 0 END), 0) as kerajinan,
        COALESCE(SUM(CASE WHEN jp.id_kategori = 3 THEN d.poin_saat_itu ELSE 0 END), 0) as kerapian
    FROM tb_pelanggaran_header h
    JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    WHERE h.id_anggota = :id AND h.id_tahun = :id_tahun AND h.semester = 'Ganjil'
", ['id' => $id_anggota, 'id_tahun' => $tahun_aktif['id_tahun']]);

$poin_genap = fetchOne("
    SELECT 
        COALESCE(SUM(CASE WHEN jp.id_kategori = 1 THEN d.poin_saat_itu ELSE 0 END), 0) as kelakuan,
        COALESCE(SUM(CASE WHEN jp.id_kategori = 2 THEN d.poin_saat_itu ELSE 0 END), 0) as kerajinan,
        COALESCE(SUM(CASE WHEN jp.id_kategori = 3 THEN d.poin_saat_itu ELSE 0 END), 0) as kerapian
    FROM tb_pelanggaran_header h
    JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    WHERE h.id_anggota = :id AND h.id_tahun = :id_tahun AND h.semester = 'Genap'
", ['id' => $id_anggota, 'id_tahun' => $tahun_aktif['id_tahun']]);

// [BARU] Ambil Master Data Sanksi untuk dicocokkan nanti
$ref_sanksi = fetchAll("SELECT kode_sanksi, deskripsi FROM tb_sanksi_ref");
$map_sanksi = [];
foreach($ref_sanksi as $rs) {
    $map_sanksi[$rs['kode_sanksi']] = $rs['deskripsi'];
}

// Helper query pelanggaran (MODIFIKASI PENGAMBILAN KODE SANKSI)
function getPelanggaranByKategori($id_anggota, $id_kategori, $id_tahun, $filter_semester) {
    global $pdo;
    $sql = "
        SELECT 
            h.id_transaksi, h.tanggal, h.waktu, h.semester,
            jp.nama_pelanggaran, jp.sanksi_default, d.poin_saat_itu,
            GROUP_CONCAT(DISTINCT sr.kode_sanksi SEPARATOR ',') as sanksi_aktual_kode,
            g.nama_guru
        FROM tb_pelanggaran_header h
        JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
        JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
        LEFT JOIN tb_guru g ON h.id_guru = g.id_guru
        LEFT JOIN tb_pelanggaran_sanksi ps ON h.id_transaksi = ps.id_transaksi
        LEFT JOIN tb_sanksi_ref sr ON ps.id_sanksi_ref = sr.id_sanksi_ref
        WHERE h.id_anggota = :id AND jp.id_kategori = :id_kategori
        AND h.id_tahun = :id_tahun AND h.semester = :semester
        GROUP BY h.id_transaksi, d.id_detail
        ORDER BY h.tanggal DESC, h.waktu DESC
    ";
    return fetchAll($sql, ['id' => $id_anggota, 'id_kategori' => $id_kategori, 'id_tahun' => $id_tahun, 'semester' => $filter_semester]);
}

$pelanggaran_kelakuan = getPelanggaranByKategori($id_anggota, 1, $tahun_aktif['id_tahun'], $filter_semester);
$pelanggaran_kerajinan = getPelanggaranByKategori($id_anggota, 2, $tahun_aktif['id_tahun'], $filter_semester);
$pelanggaran_kerapian = getPelanggaranByKategori($id_anggota, 3, $tahun_aktif['id_tahun'], $filter_semester);

$riwayat_sp = fetchAll("SELECT * FROM tb_riwayat_sp WHERE id_anggota = :id ORDER BY tanggal_terbit DESC", ['id' => $id_anggota]);

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// --- UI CONFIG VARIABLES ---
$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail <?= htmlspecialchars($siswa['nama_siswa']) ?> - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 py-4 sticky top-0 z-30 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="monitoring_siswa_list.php?kelas=<?= $siswa['id_kelas'] ?>" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                </a>
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Profil & Riwayat Siswa</h1>
                    <p class="text-sm font-medium text-slate-500"><?= $siswa['nama_kelas'] ?> • No Induk: <?= $siswa['no_induk'] ?></p>
                </div>
            </div>
            
            <a href="../../actions/cetak_detail_siswa.php?id=<?= $id_anggota ?>" target="_blank" class="px-4 py-2 bg-white border border-[#E2E8F0] text-slate-700 hover:bg-slate-50 text-sm font-bold rounded-lg shadow-sm transition-colors flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2-2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                <span class="hidden sm:inline">Preview & Download Buku</span>
            </a>
        </div>

        <div class="p-6 space-y-6 max-w-6xl mx-auto">

            <?php if ($success): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <p class="font-medium text-sm"><?= htmlspecialchars($success) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <p class="font-medium text-sm"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($is_kandidat_sertifikat): ?>
            <div class="bg-amber-100 border border-amber-300 rounded-xl p-5 shadow-sm flex items-center shadow-amber-900/5 animate-pulse">
                <div class="flex-shrink-0 bg-white p-3 rounded-full mr-5 shadow-sm border border-amber-200 text-amber-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 15l-3.09 1.63.59-3.45L7 10.74l3.46-.5L12 7l1.54 3.24 3.46.5-2.5 2.44.59 3.45L12 15z"></path></svg>
                </div>
                <div>
                    <h4 class="font-extrabold text-amber-800 text-lg mb-1">🌟 Kandidat Sertifikat Teladan 🌟</h4>
                    <p class="text-sm text-amber-700 font-medium">
                        Siswa ini memiliki <strong>0 Poin Pelanggaran selama 1 Tahun Ajaran penuh</strong>. Kandidat kuat penerima Sertifikat Bebas Pelanggaran! 🎓
                    </p>
                </div>
            </div>
            <?php elseif ($is_kandidat_semester): ?>
            <div class="bg-emerald-100 border border-emerald-300 rounded-xl p-5 shadow-sm flex items-center shadow-emerald-900/5 animate-pulse">
                <div class="flex-shrink-0 bg-white p-3 rounded-full mr-5 shadow-sm border border-emerald-200 text-emerald-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                </div>
                <div>
                    <h4 class="font-extrabold text-emerald-800 text-lg mb-1">🏅 Kandidat Reward Semester</h4>
                    <p class="text-sm text-emerald-700 font-medium">
                        Siswa ini memiliki <strong>0 Poin Pelanggaran di Semester ini</strong>. Pertahankan kedisiplinan ini hingga akhir semester! ✨
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-[#000080] text-white rounded-xl shadow-md shadow-blue-900/10 p-6 relative overflow-hidden">
                <svg class="absolute right-0 top-0 text-white/5 w-64 h-64 transform translate-x-12 -translate-y-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 22h20L12 2zm0 3.8L18.4 19H5.6L12 5.8z"></path></svg>
                
                <div class="relative z-10 flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6">
                    <div class="w-28 h-28 bg-white rounded-full flex items-center justify-center overflow-hidden flex-shrink-0 border-4 border-white/20 shadow-lg">
                        <span class="text-[#000080] font-extrabold text-4xl"><?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?></span>
                    </div>
                    
                    <div class="flex-1 text-center md:text-left">
                        <h2 class="text-3xl font-extrabold mb-1"><?= htmlspecialchars($siswa['nama_siswa']) ?></h2>
                        <p class="text-blue-200 font-medium text-sm mb-4"><?= $siswa['no_induk'] ?> • Kelas <?= $siswa['nama_kelas'] ?></p>
                        
                        <div class="flex flex-wrap justify-center md:justify-start gap-4 text-xs font-medium">
                            <span class="bg-white/10 px-3 py-1.5 rounded-lg border border-white/10 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                <?= $siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
                            </span>
                            <span class="bg-white/10 px-3 py-1.5 rounded-lg border border-white/10 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                Ortu: <?= htmlspecialchars($siswa['nama_ayah'] ?? $siswa['nama_ibu'] ?? '-') ?>
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 md:mt-0 w-full md:w-auto bg-white/10 p-4 rounded-xl border border-white/10 backdrop-blur-sm text-center md:text-right">
                        <p class="text-[10px] text-blue-200 uppercase tracking-wider font-bold mb-3">Status Surat Peringatan</p>
                        <div class="flex justify-center md:justify-end gap-2 mb-4">
                            <?php
                            $sp_data = [
                                ['nama' => 'KL', 'status' => $siswa['status_sp_kelakuan']],
                                ['nama' => 'KR', 'status' => $siswa['status_sp_kerajinan']],
                                ['nama' => 'KP', 'status' => $siswa['status_sp_kerapian']]
                            ];
                            foreach ($sp_data as $sp):
                                $is_aman = $sp['status'] === 'Aman';
                            ?>
                            <div class="text-center">
                                <p class="text-[9px] text-blue-200 font-bold mb-1"><?= $sp['nama'] ?></p>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $is_aman ? 'bg-emerald-500/80 text-white' : 'bg-red-500 text-white' ?>">
                                    <?= $sp['status'] === 'Aman' ? 'OK' : $sp['status'] ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="border-t border-white/20 pt-3">
                            <span class="px-4 py-1.5 rounded-full text-xs font-extrabold shadow-sm <?= $siswa['status_sp_terakhir'] === 'Aman' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white' ?>">
                                SUMMARY: <?= $siswa['status_sp_terakhir'] ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="<?= $card_class ?> p-6 border-t-4 border-t-red-500 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-extrabold text-slate-800 flex items-center text-sm">
                            <span class="w-6 h-6 rounded bg-red-100 text-red-600 flex items-center justify-center mr-2 text-xs">🚨</span> KELAKUAN
                        </h3>
                        <span class="text-3xl font-extrabold text-red-600"><?= $siswa['poin_kelakuan'] ?></span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-4 pt-4 border-t border-[#E2E8F0]">
                        <div class="bg-slate-50 p-2 rounded-lg text-center border border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Ganjil</p>
                            <p class="text-sm font-extrabold text-slate-700"><?= $poin_ganjil['kelakuan'] ?></p>
                        </div>
                        <div class="bg-slate-50 p-2 rounded-lg text-center border border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Genap</p>
                            <p class="text-sm font-extrabold text-slate-700"><?= $poin_genap['kelakuan'] ?></p>
                        </div>
                    </div>
                </div>

                <div class="<?= $card_class ?> p-6 border-t-4 border-t-blue-500 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-extrabold text-slate-800 flex items-center text-sm">
                            <span class="w-6 h-6 rounded bg-blue-100 text-blue-600 flex items-center justify-center mr-2 text-xs">📘</span> KERAJINAN
                        </h3>
                        <span class="text-3xl font-extrabold text-blue-600"><?= $siswa['poin_kerajinan'] ?></span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-4 pt-4 border-t border-[#E2E8F0]">
                        <div class="bg-slate-50 p-2 rounded-lg text-center border border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Ganjil</p>
                            <p class="text-sm font-extrabold text-slate-700"><?= $poin_ganjil['kerajinan'] ?></p>
                        </div>
                        <div class="bg-slate-50 p-2 rounded-lg text-center border border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Genap</p>
                            <p class="text-sm font-extrabold text-slate-700"><?= $poin_genap['kerajinan'] ?></p>
                        </div>
                    </div>
                </div>

                <div class="<?= $card_class ?> p-6 border-t-4 border-t-yellow-500 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-extrabold text-slate-800 flex items-center text-sm">
                            <span class="w-6 h-6 rounded bg-yellow-100 text-yellow-600 flex items-center justify-center mr-2 text-xs">👔</span> KERAPIAN
                        </h3>
                        <span class="text-3xl font-extrabold text-yellow-600"><?= $siswa['poin_kerapian'] ?></span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-4 pt-4 border-t border-[#E2E8F0]">
                        <div class="bg-slate-50 p-2 rounded-lg text-center border border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Ganjil</p>
                            <p class="text-sm font-extrabold text-slate-700"><?= $poin_ganjil['kerapian'] ?></p>
                        </div>
                        <div class="bg-slate-50 p-2 rounded-lg text-center border border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Genap</p>
                            <p class="text-sm font-extrabold text-slate-700"><?= $poin_genap['kerapian'] ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="<?= $card_class ?> p-4 flex flex-col sm:flex-row items-center justify-between bg-slate-50/50 gap-4">
                <div class="flex items-center space-x-3 w-full sm:w-auto">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Pilih Semester:</span>
                    <a href="?id=<?= $id_anggota ?>&semester=Ganjil"
                        class="px-4 py-2 rounded-lg font-bold text-xs transition-colors flex-1 text-center <?= $filter_semester === 'Ganjil' ? 'bg-[#000080] text-white shadow-md' : 'bg-white border border-[#E2E8F0] text-slate-600 hover:bg-slate-100' ?>">
                        Ganjil
                    </a>
                    <a href="?id=<?= $id_anggota ?>&semester=Genap"
                        class="px-4 py-2 rounded-lg font-bold text-xs transition-colors flex-1 text-center <?= $filter_semester === 'Genap' ? 'bg-[#000080] text-white shadow-md' : 'bg-white border border-[#E2E8F0] text-slate-600 hover:bg-slate-100' ?>">
                        Genap
                    </a>
                </div>
                <div class="text-xs font-bold px-3 py-1.5 rounded-full <?= $filter_semester === $tahun_aktif['semester_aktif'] ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' ?>">
                    <?= $filter_semester === $tahun_aktif['semester_aktif'] ? '● Aktif Berjalan' : 'Riwayat Lampau' ?>
                </div>
            </div>

            <div class="<?= $card_class ?> overflow-hidden">
                <div class="flex border-b border-[#E2E8F0] overflow-x-auto bg-slate-50/50">
                    <button onclick="switchTab('kelakuan')" id="tab-kelakuan" 
                            class="tab-button flex-1 py-4 px-4 font-extrabold text-sm text-center transition-colors bg-red-600 text-white border-b-2 border-red-700">
                        🚨 KELAKUAN (<?= count($pelanggaran_kelakuan) ?>)
                    </button>
                    <button onclick="switchTab('kerajinan')" id="tab-kerajinan" 
                            class="tab-button flex-1 py-4 px-4 font-bold text-sm text-center transition-colors text-slate-500 hover:text-slate-800 hover:bg-slate-100 border-b-2 border-transparent">
                        📘 KERAJINAN (<?= count($pelanggaran_kerajinan) ?>)
                    </button>
                    <button onclick="switchTab('kerapian')" id="tab-kerapian" 
                            class="tab-button flex-1 py-4 px-4 font-bold text-sm text-center transition-colors text-slate-500 hover:text-slate-800 hover:bg-slate-100 border-b-2 border-transparent">
                        👔 KERAPIAN (<?= count($pelanggaran_kerapian) ?>)
                    </button>
                </div>

                <?php 
                $kategori_data = [
                    'kelakuan' => ['data' => $pelanggaran_kelakuan, 'color' => 'red'],
                    'kerajinan' => ['data' => $pelanggaran_kerajinan, 'color' => 'blue'],
                    'kerapian' => ['data' => $pelanggaran_kerapian, 'color' => 'yellow'],
                ];
                
                foreach ($kategori_data as $key => $kat):
                ?>
                <div id="content-<?= $key ?>" class="tab-content <?= $key !== 'kelakuan' ? 'hidden' : '' ?>">
                    <?php if (empty($kat['data'])): ?>
                    <div class="text-center py-12 text-slate-400">
                        <svg class="w-16 h-16 text-slate-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="font-medium text-sm">Bagus! Tidak ada catatan pelanggaran di kategori ini.</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-white text-xs text-slate-500 uppercase border-b border-[#E2E8F0] whitespace-nowrap">
                                <tr>
                                    <th class="p-4 font-bold w-1/6">Waktu</th>
                                    <th class="p-4 font-bold w-1/4">Jenis Pelanggaran</th>
                                    <th class="p-4 font-bold w-1/4">Sanksi & Tindakan</th>
                                    <th class="p-4 font-bold text-center w-1/12">Poin</th>
                                    <th class="p-4 font-bold w-1/6">Pelapor</th>
                                    <th class="p-4 font-bold text-center w-1/12">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E2E8F0]">
                                <?php foreach ($kat['data'] as $p): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="p-4 whitespace-nowrap align-top">
                                        <p class="font-bold text-slate-700 text-xs"><?= date('d M Y', strtotime($p['tanggal'])) ?></p>
                                        <p class="text-[10px] text-slate-400 mt-0.5"><?= substr($p['waktu'], 0, 5) ?> WIB</p>
                                    </td>
                                    
                                    <td class="p-4 whitespace-normal align-top">
                                        <p class="text-xs font-bold text-slate-800 leading-relaxed"><?= htmlspecialchars($p['nama_pelanggaran']) ?></p>
                                    </td>
                                    
                                    <td class="p-4 align-top text-xs font-medium text-slate-600 leading-relaxed">
                                        <?php 
                                        $aktual_kodes = array_filter(explode(',', $p['sanksi_aktual_kode'] ?? ''));
                                        $default_kodes = array_filter(explode(',', $p['sanksi_default'] ?? ''));
                                        $irisan_kodes = array_intersect($aktual_kodes, $default_kodes);
                                        $kodes_tampil = !empty($irisan_kodes) ? $irisan_kodes : $aktual_kodes;

                                        if(!empty($kodes_tampil)): 
                                        ?>
                                            <ul class="list-disc pl-3 space-y-1 marker:text-slate-400">
                                                <?php foreach($kodes_tampil as $kode): ?>
                                                    <?php if(isset($map_sanksi[$kode])): ?>
                                                        <li><?= htmlspecialchars($map_sanksi[$kode]) ?></li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <span class="text-slate-400 italic text-[10px]">Tidak ada sanksi tercatat</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-4 text-center whitespace-nowrap align-top">
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-<?= $kat['color'] ?>-50 text-<?= $kat['color'] ?>-600 border border-<?= $kat['color'] ?>-200">
                                            +<?= $p['poin_saat_itu'] ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-xs font-medium text-slate-700 whitespace-nowrap align-top"><?= htmlspecialchars($p['nama_guru']) ?></td>
                                    <td class="p-4 text-center whitespace-nowrap align-top">
                                        <div class="flex items-center justify-center space-x-2">
                                            <button onclick="viewDetail(<?= $p['id_transaksi'] ?>)" 
                                                    class="p-1.5 bg-white border border-[#E2E8F0] text-blue-600 rounded-md hover:bg-blue-50 transition-colors shadow-sm" title="Lihat Bukti/Detail">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </button>
                                            <button onclick="editPelanggaran(<?= $p['id_transaksi'] ?>)" 
                                                    class="p-1.5 bg-white border border-[#E2E8F0] text-amber-600 rounded-md hover:bg-amber-50 transition-colors shadow-sm" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                            </button>
                                            <button onclick="hapusPelanggaran(<?= $p['id_transaksi'] ?>)" 
                                                    class="p-1.5 bg-white border border-red-200 text-red-600 rounded-md hover:bg-red-50 transition-colors shadow-sm" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </div>
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

            <?php if (!empty($riwayat_sp)): ?>
            <div class="<?= $card_class ?> overflow-hidden">
                <div class="p-4 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center">
                    <svg class="w-5 h-5 text-slate-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span class="font-extrabold text-slate-800 text-sm">Riwayat Surat Peringatan (SP)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-white text-xs text-slate-500 uppercase border-b border-[#E2E8F0] whitespace-nowrap">
                            <tr>
                                <th class="p-4 font-bold">Tingkat SP</th>
                                <th class="p-4 font-bold">Kategori Pemicu</th>
                                <th class="p-4 font-bold">Tgl Terbit</th>
                                <th class="p-4 font-bold">Status Surat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php foreach($riwayat_sp as $sp): ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="p-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 bg-red-50 text-red-600 border border-red-200 rounded-md font-bold text-[11px] shadow-sm">
                                        <?= $sp['tingkat_sp'] ?>
                                    </span>
                                </td>
                                <td class="p-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                        <?= $sp['kategori_pemicu'] === 'KELAKUAN' ? 'bg-red-100 text-red-700' : '' ?>
                                        <?= $sp['kategori_pemicu'] === 'KERAJINAN' ? 'bg-blue-100 text-blue-700' : '' ?>
                                        <?= $sp['kategori_pemicu'] === 'KERAPIAN' ? 'bg-yellow-100 text-yellow-700' : '' ?>">
                                        <?= $sp['kategori_pemicu'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-xs font-bold text-slate-700 whitespace-nowrap"><?= date('d M Y', strtotime($sp['tanggal_terbit'])) ?></td>
                                <td class="p-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold shadow-sm <?= $sp['status'] === 'Selesai' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-amber-50 text-amber-600 border border-amber-200' ?>">
                                        <?= $sp['status'] === 'Selesai' ? 'Sudah Ditandatangani' : 'Menunggu TTD' ?>
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

<div id="modal-detail" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full relative z-10 overflow-hidden max-h-[90vh] flex flex-col">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between flex-shrink-0">
            <h3 class="font-extrabold text-slate-800 flex items-center text-sm">
                <svg class="w-5 h-5 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Detail Pelanggaran & Bukti
            </h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div id="modal-content" class="p-6 overflow-y-auto">
            <div class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-[#000080]"></div>
            </div>
        </div>
    </div>
</div>

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

function viewDetail(id) {
    document.getElementById('modal-detail').classList.remove('hidden');
    document.getElementById('modal-content').innerHTML = `
        <div class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-[#000080]"></div>
        </div>
    `;
    
    fetch(`detail_transaksi_ajax.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modal-content').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('modal-content').innerHTML = `
                <div class="text-center text-red-600 py-8">
                    <p class="font-bold text-sm">Error memuat data</p>
                </div>
            `;
        });
}

function closeModal() {
    document.getElementById('modal-detail').classList.add('hidden');
}

function editPelanggaran(id) {
    window.location.href = `edit_pelanggaran.php?id=${id}`;
}

function hapusPelanggaran(id) {
    if (confirm('⚠️ PERINGATAN!\n\nMenghapus pelanggaran akan mengurangi total poin siswa secara otomatis.\n\nYakin ingin menghapus?')) {
        window.location.href = `../../actions/hapus_transaksi.php?id=${id}&redirect=monitoring&anggota=<?= $id_anggota ?>`;
    }
}
</script>
</body>
</html>