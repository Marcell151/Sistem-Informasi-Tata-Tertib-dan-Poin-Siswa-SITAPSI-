<?php
/**
 * SITAPSI - Detail Siswa untuk Guru (MANUAL REPORT SYSTEM - UI GLOBAL)
 * FIX LOGIKA: Kandidat Reward mengecek poin 1 Tahun Penuh
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireGuru();

$id_anggota = $_GET['id'] ?? null;

if (!$id_anggota) {
    $_SESSION['error_message'] = '❌ ID siswa tidak valid';
    header('Location: rekap_kelas.php');
    exit;
}

$id_guru_login = $_SESSION['user_id'];
$guru = fetchOne("SELECT id_guru, nama_guru, id_kelas FROM tb_guru WHERE id_guru = :id", ['id' => $id_guru_login]);

$tahun_aktif = fetchOne("
    SELECT id_tahun, nama_tahun, semester_aktif 
    FROM tb_tahun_ajaran 
    WHERE status = 'Aktif' 
    LIMIT 1
");

$filter_semester = $_GET['semester'] ?? $tahun_aktif['semester_aktif'];

// Query siswa dengan SP per kategori (nis diubah ke no_induk)
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
    header('Location: rekap_kelas.php');
    exit;
}

// CEK STATUS WALI KELAS
$id_kelas_wali = $guru['id_kelas'] ?? null;
$is_wali_kelas = ($id_kelas_wali !== null && $id_kelas_wali == $siswa['id_kelas']);

// LOGIKA BARU: Cek histori 1 tahun
$cek_history = fetchOne("
    SELECT COALESCE(SUM(d.poin_saat_itu), 0) as total_tahunan
    FROM tb_pelanggaran_header h
    JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    WHERE h.id_anggota = :id_anggota AND h.id_tahun = :id_tahun
", ['id_anggota' => $id_anggota, 'id_tahun' => $tahun_aktif['id_tahun']]);

$is_bersih = ($cek_history['total_tahunan'] == 0);

// Ambil semua daftar pelanggaran khusus untuk DROPDOWN REPORT
$list_pelanggaran_dropdown = fetchAll("
    SELECT 
        h.id_transaksi, 
        h.tanggal, 
        GROUP_CONCAT(jp.nama_pelanggaran SEPARATOR ', ') as nama_pelanggaran 
    FROM tb_pelanggaran_header h
    JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    WHERE h.id_anggota = ? AND h.id_tahun = ? AND h.status_revisi IN ('None', 'Ditolak')
    GROUP BY h.id_transaksi
    ORDER BY h.tanggal DESC
", [$id_anggota, $tahun_aktif['id_tahun']]);

// Helper query pelanggaran per kategori (Untuk Tabel)
function getPelanggaranByKategori($id_anggota, $id_kategori, $id_tahun, $filter_semester) {
    $sql = "
        SELECT 
            h.id_transaksi,
            h.tanggal,
            h.waktu,
            h.semester,
            h.status_revisi,
            h.alasan_revisi,
            h.bukti_foto,
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Detail <?= htmlspecialchars($siswa['nama_siswa']) ?> - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC] pb-24 md:pb-8"> <?php 
    $navbar_path = __DIR__ . '/../../includes/navbar_guru.php';
    if (file_exists($navbar_path)) include $navbar_path; 
    ?>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <a href="rekap_kelas.php?kelas=<?= $siswa['id_kelas'] ?>" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 border border-transparent hover:border-[#E2E8F0] transition-colors bg-white shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                </a>
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Detail Siswa</h1>
                    <p class="text-sm font-medium text-slate-500"><?= $siswa['nama_kelas'] ?> • No Induk: <?= $siswa['no_induk'] ?></p>
                </div>
            </div>
            
            <a href="../../actions/cetak_detail_siswa.php?id=<?= $id_anggota ?>" target="_blank" class="px-4 py-2 bg-white border border-[#E2E8F0] text-slate-700 hover:bg-slate-50 text-sm font-bold rounded-lg shadow-sm transition-colors flex items-center justify-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2-2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                <span class="hidden sm:inline">Preview & Download Buku</span>
            </a>
        </div>

        <div class="space-y-6">

            <?php if ($success): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <p class="font-medium text-sm sm:text-base"><?= htmlspecialchars($success) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                <p class="font-medium text-sm sm:text-base"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($is_bersih): ?>
            <div class="bg-amber-100 border border-amber-300 rounded-xl p-5 shadow-sm flex items-center shadow-amber-900/5 animate-pulse">
                <div class="flex-shrink-0 bg-white p-3 rounded-full mr-5 shadow-sm border border-amber-200 text-amber-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 15l-3.09 1.63.59-3.45L7 10.74l3.46-.5L12 7l1.54 3.24 3.46.5-2.5 2.44.59 3.45L12 15z"></path></svg>
                </div>
                <div>
                    <h4 class="font-extrabold text-amber-800 text-lg mb-1">🌟 Kandidat Siswa Teladan</h4>
                    <p class="text-sm text-amber-700 font-medium">Siswa ini bersih (0 Poin) selama <strong>1 Tahun Ajaran penuh (Ganjil & Genap)</strong>. Kandidat kuat penerima Sertifikat Bebas Pelanggaran!</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($is_wali_kelas): ?>
            <div class="bg-blue-50 border border-blue-200 p-5 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between shadow-sm gap-4">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    <div>
                        <p class="font-extrabold text-[#000080]">Anda adalah Wali Kelas <?= htmlspecialchars($siswa['nama_kelas']) ?></p>
                        <p class="text-xs sm:text-sm text-blue-800 mt-1 font-medium">Jika terdapat kesalahan input data oleh guru lain, Anda dapat mengajukan perbaikan (Revisi/Hapus) ke Admin.</p>
                    </div>
                </div>
                <button onclick="openReportModal()" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl shadow-md transition-colors whitespace-nowrap">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                    Ajukan Report Data
                </button>
            </div>
            <?php endif; ?>

            <div class="bg-[#000080] text-white rounded-xl shadow-md shadow-blue-900/10 p-6 relative overflow-hidden">
                <svg class="absolute right-0 top-0 text-white/5 w-64 h-64 transform translate-x-12 -translate-y-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 22h20L12 2zm0 3.8L18.4 19H5.6L12 5.8z"></path></svg>
                
                <div class="relative z-10 flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6">
                    <div class="w-28 h-28 bg-white rounded-full flex items-center justify-center overflow-hidden flex-shrink-0 border-4 border-white/20 shadow-lg">
                        <?php if($siswa['foto_profil'] ?? null): ?>
                            <img src="../../assets/uploads/siswa/<?= htmlspecialchars($siswa['foto_profil']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-[#000080] font-extrabold text-4xl"><?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-1 text-center md:text-left">
                        <h2 class="text-3xl font-extrabold mb-1"><?= htmlspecialchars($siswa['nama_siswa']) ?></h2>
                        <p class="text-blue-200 font-medium text-sm mb-4"><?= $siswa['no_induk'] ?> • Kelas <?= $siswa['nama_kelas'] ?></p>
                        
                        <div class="flex flex-wrap justify-center md:justify-start gap-4 text-xs font-medium">
                            <span class="bg-white/10 px-3 py-1.5 rounded-lg border border-white/10 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                <?= $siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
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
                    <?= $filter_semester === $tahun_aktif['semester_aktif'] ? '● Aktif' : 'Riwayat Lampau' ?>
                </div>
            </div>

            <div class="<?= $card_class ?> overflow-hidden">
                <div class="flex border-b border-[#E2E8F0] overflow-x-auto bg-slate-50/50 scrollbar-hide">
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
                    <div class="text-center py-12 text-slate-400">
                        <svg class="w-16 h-16 text-slate-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="font-medium text-sm">Tidak ada catatan pelanggaran.</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs text-slate-500 uppercase border-b border-[#E2E8F0] whitespace-nowrap">
                                <tr>
                                    <th class="p-4 font-bold w-1/6">Tanggal</th>
                                    <th class="p-4 font-bold w-2/5">Pelanggaran</th>
                                    <th class="p-4 font-bold text-center">Poin</th>
                                    <th class="p-4 font-bold text-center">Bukti</th>
                                    <th class="p-4 font-bold">Pelapor</th>
                                    <th class="p-4 font-bold text-center">Status Laporan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E2E8F0]">
                                <?php foreach ($kat['data'] as $p): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="p-4 whitespace-nowrap align-top">
                                        <p class="font-bold text-slate-700 text-xs"><?= date('d/m/Y', strtotime($p['tanggal'])) ?></p>
                                    </td>
                                    <td class="p-4 whitespace-normal min-w-[200px] align-top">
                                        <p class="text-xs font-bold text-slate-800 leading-relaxed"><?= htmlspecialchars($p['nama_pelanggaran']) ?></p>
                                    </td>
                                    <td class="p-4 text-center whitespace-nowrap align-top">
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-<?= $color ?>-50 text-<?= $color ?>-600 border border-<?= $color ?>-200">+<?= $p['poin_saat_itu'] ?></span>
                                    </td>
                                    <td class="p-4 text-center whitespace-nowrap align-top">
                                        <?php if (!empty($p['bukti_foto']) && $p['bukti_foto'] !== 'null'): ?>
                                            <button onclick="lihatBukti('<?= htmlspecialchars($p['bukti_foto'], ENT_QUOTES) ?>')" class="p-1.5 bg-white border border-[#E2E8F0] text-blue-600 rounded-md hover:bg-blue-50 transition-colors shadow-sm" title="Lihat Bukti Foto">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-[10px] text-slate-400 italic">Kosong</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-xs font-medium text-slate-700 whitespace-nowrap align-top"><?= htmlspecialchars($p['nama_guru']) ?></td>
                                    <td class="p-4 text-center whitespace-nowrap align-top">
                                        <?php if ($p['status_revisi'] === 'Pending'): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-600 rounded-md text-[10px] font-bold">Menunggu</span>
                                        <?php elseif ($p['status_revisi'] === 'Disetujui'): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 bg-emerald-50 border border-emerald-200 text-emerald-600 rounded-md text-[10px] font-bold">Disetujui</span>
                                        <?php elseif ($p['status_revisi'] === 'Ditolak'): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 bg-red-50 border border-red-200 text-red-600 rounded-md text-[10px] font-bold cursor-help" title="<?= htmlspecialchars($p['alasan_revisi']) ?>">Ditolak</span>
                                        <?php else: ?>
                                            <span class="text-slate-400 font-bold text-lg">-</span>
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
</div>

<?php if ($is_wali_kelas): ?>
<div id="modal-report" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeReportModal()"></div>
    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full relative z-10 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-[#E2E8F0] bg-amber-50/50 flex items-center justify-between">
            <h3 class="font-extrabold text-amber-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                Pengajuan Revisi Data
            </h3>
            <button onclick="closeReportModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <form action="../../actions/kirim_report.php" method="POST" class="p-6 space-y-5">
            <input type="hidden" name="id_anggota" value="<?= $id_anggota ?>">
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wide">Pilih Transaksi yang Salah *</label>
                <select name="id_transaksi" required class="w-full px-4 py-3 border border-[#E2E8F0] rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-sm bg-slate-50 text-slate-700 transition-all">
                    <option value="">-- Pilih Transaksi Pelanggaran --</option>
                    <?php foreach ($list_pelanggaran_dropdown as $opt): ?>
                        <option value="<?= $opt['id_transaksi'] ?>"><?= date('d/m/Y', strtotime($opt['tanggal'])) ?> - <?= htmlspecialchars($opt['nama_pelanggaran']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wide">Pesan Untuk Admin *</label>
                <textarea name="alasan_revisi" required rows="3" placeholder="Contoh: Tolong hapus transaksi ini, karena salah identitas siswa..." class="w-full px-4 py-3 border border-[#E2E8F0] rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-sm text-slate-700 transition-all resize-none"></textarea>
            </div>
            <div class="flex space-x-3 pt-2">
                <button type="button" onclick="closeReportModal()" class="flex-1 px-4 py-2.5 bg-white border border-[#E2E8F0] text-slate-700 rounded-lg hover:bg-slate-50 font-bold text-sm transition-colors shadow-sm">Batal</button>
                <button type="submit" <?= empty($list_pelanggaran_dropdown) ? 'disabled' : '' ?> class="flex-1 px-4 py-2.5 bg-amber-500 text-white rounded-lg hover:bg-amber-600 font-bold text-sm transition-colors shadow-sm">Kirim Pesan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div id="modal-bukti" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" onclick="document.getElementById('modal-bukti').classList.add('hidden')"></div>
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full flex flex-col max-h-[90vh] relative z-10 overflow-hidden">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex justify-between items-center">
            <h3 class="font-extrabold text-slate-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                Bukti Foto Pelanggaran
            </h3>
            <button onclick="document.getElementById('modal-bukti').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto bg-slate-100" id="bukti-container"></div>
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

function openReportModal() { document.getElementById('modal-report').classList.remove('hidden'); }
function closeReportModal() { document.getElementById('modal-report').classList.add('hidden'); }

function lihatBukti(jsonString) {
    const fotos = JSON.parse(jsonString);
    const container = document.getElementById('bukti-container');
    container.innerHTML = '';
    
    fotos.forEach(foto => {
        const imgPath = '../../assets/uploads/bukti/' + foto;
        container.innerHTML += `
            <div class="mb-4 bg-white p-3 rounded-xl border border-[#E2E8F0] shadow-sm">
                <img src="${imgPath}" class="w-full h-auto rounded-lg object-contain" alt="Bukti Pelanggaran" onerror="this.onerror=null; this.src='../../assets/img/no-image.png'; this.parentElement.innerHTML='<div class=\\'text-center text-red-500 py-6 font-bold\\'><p>Foto tidak ditemukan di server.</p></div>';">
            </div>`;
    });
    
    document.getElementById('modal-bukti').classList.remove('hidden');
}
</script>
</body>
</html>