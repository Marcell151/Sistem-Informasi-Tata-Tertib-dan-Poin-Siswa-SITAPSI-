<?php
/**
 * SITAPSI - Detail Siswa untuk Guru (MANUAL REPORT SYSTEM)
 * Tombol report terpusat (1 tombol) dengan dropdown pilihan pelanggaran
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

// FIX PENGAMBILAN DATA GURU LOGIN
$id_guru_login = $_SESSION['user_id'];
$guru = fetchOne("SELECT id_guru, nama_guru, id_kelas FROM tb_guru WHERE id_guru = :id", ['id' => $id_guru_login]);

$tahun_aktif = fetchOne("
    SELECT id_tahun, nama_tahun, semester_aktif 
    FROM tb_tahun_ajaran 
    WHERE status = 'Aktif' 
    LIMIT 1
");

$filter_semester = $_GET['semester'] ?? $tahun_aktif['semester_aktif'];

// Query siswa dengan SP per kategori
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
    JOIN tb_siswa s ON a.nis = s.nis
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
    
    // PERBAIKAN: Gunakan fungsi bawaan fetchAll agar tidak error PDO null
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

$is_bersih = ($siswa['total_poin_umum'] == 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Detail <?= htmlspecialchars($siswa['nama_siswa']) ?> - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { 'navy': '#000080' } } }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <?php 
    $navbar_path = __DIR__ . '/../../includes/navbar_guru.php';
    if (file_exists($navbar_path)) include $navbar_path; 
    ?>

    <main class="flex-1 w-full max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <div class="mb-6 flex items-center space-x-4">
            <a href="rekap_kelas.php?kelas=<?= $siswa['id_kelas'] ?>" class="text-gray-400 hover:text-gray-600 bg-white p-2 rounded-full shadow-sm transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Detail Siswa</h1>
                <p class="text-sm text-gray-500"><?= $siswa['nama_kelas'] ?> • <?= $siswa['nis'] ?></p>
            </div>
        </div>

        <div class="space-y-6">

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

            <?php if ($is_bersih): ?>
            <div class="bg-gradient-to-r from-yellow-100 to-yellow-50 border border-yellow-300 rounded-xl p-4 shadow-sm flex items-center animate-pulse">
                <div class="flex-shrink-0 bg-yellow-200 p-3 rounded-full mr-4 shadow-inner">
                    <span class="text-3xl">🏆</span>
                </div>
                <div>
                    <h4 class="font-bold text-yellow-800 text-lg">Kandidat Siswa Teladan</h4>
                    <p class="text-sm text-yellow-700">Siswa ini memiliki 0 Poin Pelanggaran. Kandidat penerima reward! 🎁</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($is_wali_kelas): ?>
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg flex flex-col sm:flex-row sm:items-center justify-between shadow-sm gap-4">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"></path></svg>
                    <div>
                        <p class="font-bold text-blue-800">Anda adalah Wali Kelas <?= htmlspecialchars($siswa['nama_kelas']) ?></p>
                        <p class="text-xs sm:text-sm text-blue-700 mt-1">Jika ada kesalahan data pelanggaran, klik tombol di samping untuk mengajukan perbaikan ke Admin.</p>
                    </div>
                </div>
                <button onclick="openReportModal()" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold rounded-lg shadow-md transition-colors whitespace-nowrap">
                    🚩 Ajukan Report Data
                </button>
            </div>
            <?php endif; ?>

            <div class="bg-gradient-to-r from-navy to-blue-800 text-white rounded-xl shadow-lg p-5 sm:p-6 overflow-hidden relative">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"></div>
                <div class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-6 relative z-10">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 bg-white rounded-full flex items-center justify-center overflow-hidden flex-shrink-0 shadow-md">
                        <?php if($siswa['foto_profil']): ?>
                            <img src="../../assets/uploads/siswa/<?= $siswa['foto_profil'] ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-navy font-bold text-3xl sm:text-4xl"><?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-2xl sm:text-3xl font-bold mb-2"><?= htmlspecialchars($siswa['nama_siswa']) ?></h2>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                            <p class="text-blue-200">NIS: <span class="text-white font-semibold"><?= $siswa['nis'] ?></span></p>
                            <p class="text-blue-200">Kelas: <span class="text-white font-semibold"><?= $siswa['nama_kelas'] ?></span></p>
                        </div>
                    </div>
                    <div class="sm:text-right pt-4 sm:pt-0 border-t sm:border-t-0 border-blue-700/50">
                        <p class="text-blue-200 text-xs mb-1">Status SP Tertinggi:</p>
                        <span class="inline-block px-4 py-1.5 <?= $siswa['status_sp_terakhir'] !== 'Aman' ? 'bg-red-500' : 'bg-green-500' ?> text-white text-sm font-bold rounded-full shadow-sm">
                            <?= $siswa['status_sp_terakhir'] ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border border-gray-100">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium text-gray-700 w-full sm:w-auto">Semester:</span>
                    <a href="?id=<?= $id_anggota ?>&semester=Ganjil" class="px-4 py-1.5 rounded-lg font-bold transition-colors text-xs sm:text-sm <?= $filter_semester === 'Ganjil' ? 'bg-navy text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">Ganjil</a>
                    <a href="?id=<?= $id_anggota ?>&semester=Genap" class="px-4 py-1.5 rounded-lg font-bold transition-colors text-xs sm:text-sm <?= $filter_semester === 'Genap' ? 'bg-navy text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">Genap</a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden w-full">
                <div class="flex border-b border-gray-200 overflow-x-auto whitespace-nowrap">
                    <button onclick="switchTab('kelakuan')" id="tab-kelakuan" class="tab-button flex-1 min-w-[140px] py-3 px-4 font-bold text-xs sm:text-sm text-center transition-colors bg-red-600 text-white">🚨 KELAKUAN (<?= count($pelanggaran_kelakuan) ?>)</button>
                    <button onclick="switchTab('kerajinan')" id="tab-kerajinan" class="tab-button flex-1 min-w-[140px] py-3 px-4 font-bold text-xs sm:text-sm text-center transition-colors bg-gray-100 text-gray-600 hover:bg-gray-200">📘 KERAJINAN (<?= count($pelanggaran_kerajinan) ?>)</button>
                    <button onclick="switchTab('kerapian')" id="tab-kerapian" class="tab-button flex-1 min-w-[140px] py-3 px-4 font-bold text-xs sm:text-sm text-center transition-colors bg-gray-100 text-gray-600 hover:bg-gray-200">👔 KERAPIAN (<?= count($pelanggaran_kerapian) ?>)</button>
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
                <div id="content-<?= $key ?>" class="tab-content p-0 sm:p-4 <?= $key !== 'kelakuan' ? 'hidden' : '' ?>">
                    <?php if (empty($kat['data'])): ?>
                    <div class="text-center py-10 text-gray-500">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="font-medium text-sm">Tidak ada pelanggaran <?= strtolower($kat['label']) ?> di semester ini</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto w-full pb-2">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-<?= $color ?>-50 text-xs text-<?= $color ?>-700 uppercase">
                                <tr>
                                    <th class="p-3">Tanggal</th>
                                    <th class="p-3">Pelanggaran</th>
                                    <th class="p-3 text-center">Poin</th>
                                    <th class="p-3">Bukti</th>
                                    <th class="p-3">Pelapor</th>
                                    <th class="p-3 text-center">Status Laporan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($kat['data'] as $p): ?>
                                <tr class="hover:bg-<?= $color ?>-50/50">
                                    <td class="p-3">
                                        <div class="font-medium text-gray-800"><?= date('d/m/Y', strtotime($p['tanggal'])) ?></div>
                                        <div class="text-[10px] text-gray-500"><?= substr($p['waktu'], 0, 5) ?> WIB</div>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-bold text-gray-800 max-w-[200px] truncate" title="<?= htmlspecialchars($p['nama_pelanggaran']) ?>">
                                            <?= htmlspecialchars($p['nama_pelanggaran']) ?>
                                        </div>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-1 bg-<?= $color ?>-100 text-<?= $color ?>-800 rounded-md font-bold text-[10px]">
                                            +<?= $p['poin_saat_itu'] ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <?php if (!empty($p['bukti_foto']) && $p['bukti_foto'] !== 'null'): ?>
                                            <button onclick="lihatBukti('<?= htmlspecialchars($p['bukti_foto'], ENT_QUOTES) ?>')" class="text-blue-500 hover:text-blue-700 p-1.5 bg-blue-50 rounded-lg transition-colors">📸</button>
                                        <?php else: ?>
                                            <span class="text-[10px] text-gray-400 italic">Kosong</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3">
                                        <div class="text-xs text-gray-700"><?= htmlspecialchars($p['nama_guru']) ?></div>
                                    </td>
                                    <td class="p-3 text-center">
                                        <?php if ($p['status_revisi'] === 'Pending'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded text-[10px] font-bold">Menunggu</span>
                                        <?php elseif ($p['status_revisi'] === 'Disetujui'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 bg-green-100 text-green-800 rounded text-[10px] font-bold">Selesai/Disetujui</span>
                                        <?php elseif ($p['status_revisi'] === 'Ditolak'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 bg-red-100 text-red-800 rounded text-[10px] font-bold cursor-help" title="<?= htmlspecialchars($p['alasan_revisi']) ?>">Ditolak</span>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
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
<div id="modal-report" class="hidden fixed inset-0 bg-gray-900 bg-opacity-75 z-[60] flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full overflow-hidden transform transition-all">
        <div class="p-5 sm:p-6 border-b border-gray-100 flex items-center justify-between bg-orange-50">
            <h3 class="text-lg font-bold text-orange-800 flex items-center"><span class="text-2xl mr-2">🚩</span> Pengajuan Revisi Data</h3>
            <button onclick="closeReportModal()" class="text-gray-400 hover:text-gray-600 bg-white rounded-full p-1 shadow-sm transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="../../actions/kirim_report.php" method="POST" class="p-5 sm:p-6 space-y-5">
            <input type="hidden" name="id_anggota" value="<?= $id_anggota ?>">
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Pilih Transaksi yang Salah <span class="text-red-500">*</span></label>
                <select name="id_transaksi" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent text-sm bg-gray-50">
                    <option value="">-- Pilih Transaksi Pelanggaran --</option>
                    <?php foreach ($list_pelanggaran_dropdown as $opt): ?>
                        <option value="<?= $opt['id_transaksi'] ?>">
                            <?= date('d/m/Y', strtotime($opt['tanggal'])) ?> - <?= htmlspecialchars($opt['nama_pelanggaran']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if(empty($list_pelanggaran_dropdown)): ?>
                    <p class="text-xs text-red-500 mt-1">Siswa ini tidak memiliki pelanggaran untuk dilaporkan.</p>
                <?php endif; ?>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Pesan Untuk Admin <span class="text-red-500">*</span></label>
                <textarea name="alasan_revisi" required rows="3" placeholder="Contoh: Tolong hapus transaksi ini, karena salah identitas siswa..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent text-sm resize-none"></textarea>
            </div>
            
            <div class="flex items-start bg-blue-50 p-3 rounded-lg border border-blue-100">
                <span class="text-blue-500 mr-2 mt-0.5">ℹ️</span>
                <p class="text-[11px] text-blue-700 leading-relaxed">
                    Pesan ini akan dikirim ke Admin/Tim Tatibsi. Admin akan memverifikasi dan mengeksekusi perbaikan (edit/hapus) secara manual jika disetujui.
                </p>
            </div>
            
            <div class="flex space-x-3 pt-2">
                <button type="button" onclick="closeReportModal()" class="flex-1 px-4 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-bold text-sm transition-colors">Batal</button>
                <button type="submit" <?= empty($list_pelanggaran_dropdown) ? 'disabled' : '' ?> class="flex-1 px-4 py-2.5 bg-orange-600 text-white rounded-lg hover:bg-orange-700 shadow-md font-bold text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">Kirim Pesan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div id="modal-bukti" class="hidden fixed inset-0 bg-black bg-opacity-90 z-[70] flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full flex flex-col max-h-[90vh]">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="font-bold text-gray-800">📸 Bukti Foto Pelanggaran</h3>
            <button onclick="document.getElementById('modal-bukti').classList.add('hidden')" class="text-gray-400 hover:text-red-500 p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-4 overflow-y-auto" id="bukti-container"></div>
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

function openReportModal() {
    document.getElementById('modal-report').classList.remove('hidden');
}

function closeReportModal() {
    document.getElementById('modal-report').classList.add('hidden');
}

function lihatBukti(jsonString) {
    const fotos = JSON.parse(jsonString);
    const container = document.getElementById('bukti-container');
    container.innerHTML = '';
    
    fotos.forEach(foto => {
        const imgPath = '../../assets/uploads/bukti/' + foto;
        container.innerHTML += `
            <div class="mb-4 bg-gray-100 p-2 rounded-lg border border-gray-200">
                <img src="${imgPath}" class="w-full h-auto rounded object-contain" alt="Bukti Pelanggaran" onerror="this.onerror=null; this.src='../../assets/img/no-image.png'; this.parentElement.innerHTML='<p class=\\'text-center text-red-500 py-4\\'>Foto tidak ditemukan di server.</p>';">
            </div>`;
    });
    
    document.getElementById('modal-bukti').classList.remove('hidden');
}
</script>
</body>
</html>