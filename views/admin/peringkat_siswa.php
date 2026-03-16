<?php
/**
 * SITAPSI - Peringkat & Statistik Kedisiplinan (Leaderboard)
 * Penyesuaian: Menghapus Filter "Semua (1 Tahun)" karena poin di-reset per semester.
 * PENYESUAIAN BARU: Modal Konsistensi Kedisiplinan mengikuti Filter Kelas/Tingkat di halaman utama.
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

// 1. AMBIL REFERENSI UNTUK FILTER
$list_tahun = fetchAll("SELECT * FROM tb_tahun_ajaran ORDER BY id_tahun DESC"); // Termasuk Arsip
$list_kelas = fetchAll("SELECT * FROM tb_kelas ORDER BY tingkat, nama_kelas");

$tahun_aktif = fetchOne("SELECT id_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
$tahun_default = $tahun_aktif ? $tahun_aktif['id_tahun'] : (isset($list_tahun[0]) ? $list_tahun[0]['id_tahun'] : 0);

// FIX: Default semester langsung ke semester yang sedang aktif (Tidak ada lagi 'all')
$semester_default = $tahun_aktif ? $tahun_aktif['semester_aktif'] : 'Ganjil';

// 2. TANGKAP PARAMETER FILTER
$filter_tahun = $_GET['tahun'] ?? $tahun_default;
$filter_semester = $_GET['semester'] ?? $semester_default;
$filter_tingkat = $_GET['tingkat'] ?? 'all';
$filter_kelas = $_GET['kelas'] ?? 'all';

// =========================================================================
// CEK SENSOR WAKTU: Apakah semester yang difilter belum berjalan?
// =========================================================================
$selected_tahun_data = fetchOne("SELECT status, semester_aktif FROM tb_tahun_ajaran WHERE id_tahun = :id", ['id' => $filter_tahun]);
$is_future_semester = false;

if ($selected_tahun_data && $selected_tahun_data['status'] === 'Aktif') {
    // Jika tahun ini sedang aktif ganjil, tapi user filter genap = Belum berjalan
    if ($selected_tahun_data['semester_aktif'] === 'Ganjil' && $filter_semester === 'Genap') {
        $is_future_semester = true;
    }
}

// 3. BANGUN KUERI PENCARIAN (DYNAMIC BASE)
$where_clauses = ["a.id_tahun = :tahun"];
$base_params = ['tahun' => $filter_tahun];

if ($filter_tingkat !== 'all') {
    $where_clauses[] = "k.tingkat = :tingkat";
    $base_params['tingkat'] = $filter_tingkat;
}

if ($filter_kelas !== 'all') {
    $where_clauses[] = "k.id_kelas = :kelas";
    $base_params['kelas'] = $filter_kelas;
}

$where_sql = implode(" AND ", $where_clauses);

// FIX: Logika Join WAJIB mengikat semester yang dipilih (karena poin direset per semester)
$join_pelanggaran = "LEFT JOIN tb_pelanggaran_header ph ON ph.id_anggota = a.id_anggota AND ph.semester = :semester";
$param_semester = ['semester' => $filter_semester];
$join_pelanggaran .= " LEFT JOIN tb_pelanggaran_detail pd ON pd.id_transaksi = ph.id_transaksi";


$siswa_teladan = [];
$siswa_pelanggar = [];

// JIKA SEMESTER SUDAH/SEDANG BERJALAN, JALANKAN KUERI
if (!$is_future_semester) {
    // A. Kueri Siswa Teladan (0 Poin) - Spesifik Semester
    $params_teladan = array_merge($base_params, $param_semester);
    $sql_teladan = "
        SELECT s.no_induk, s.nama_siswa, k.nama_kelas, 
               COALESCE(SUM(pd.poin_saat_itu), 0) as total_poin
        FROM tb_anggota_kelas a 
        JOIN tb_siswa s ON a.no_induk = s.no_induk 
        JOIN tb_kelas k ON a.id_kelas = k.id_kelas 
        $join_pelanggaran
        WHERE $where_sql 
        GROUP BY a.id_anggota
        HAVING total_poin = 0
        ORDER BY k.tingkat, k.nama_kelas, s.nama_siswa
    ";
    $siswa_teladan = fetchAll($sql_teladan, $params_teladan);


    // B. Kueri Perlu Perhatian (Top 15 Poin Tertinggi) - Spesifik Semester
    $params_pelanggar = array_merge($base_params, $param_semester);
    $sql_pelanggar = "
        SELECT s.no_induk, s.nama_siswa, k.nama_kelas, a.status_sp_terakhir,
               COALESCE(SUM(pd.poin_saat_itu), 0) as total_poin
        FROM tb_anggota_kelas a 
        JOIN tb_siswa s ON a.no_induk = s.no_induk 
        JOIN tb_kelas k ON a.id_kelas = k.id_kelas 
        $join_pelanggaran
        WHERE $where_sql 
        GROUP BY a.id_anggota
        HAVING total_poin > 0
        ORDER BY total_poin DESC, s.nama_siswa ASC
        LIMIT 15
    ";
    $siswa_pelanggar = fetchAll($sql_pelanggar, $params_pelanggar);
}


// C. Kueri Konsistensi Kedisiplinan (0 Poin selama > 1 tahun ajaran)
// PENYESUAIAN: Dibatasi berdasarkan filter Kelas/Tingkat di tahun yang sedang dipilih di dropdown utama
$sql_abadi = "
    SELECT s.no_induk, s.nama_siswa, k.nama_kelas, COUNT(a_all.id_tahun) as total_tahun 
    FROM tb_siswa s 
    JOIN tb_anggota_kelas a_all ON s.no_induk = a_all.no_induk 
    JOIN tb_anggota_kelas a_curr ON s.no_induk = a_curr.no_induk AND a_curr.id_tahun = :tahun_curr
    JOIN tb_kelas k ON a_curr.id_kelas = k.id_kelas
";
$params_abadi = ['tahun_curr' => $filter_tahun];

$where_abadi = [];
if ($filter_tingkat !== 'all') {
    $where_abadi[] = "k.tingkat = :tingkat";
    $params_abadi['tingkat'] = $filter_tingkat;
}
if ($filter_kelas !== 'all') {
    $where_abadi[] = "k.id_kelas = :kelas";
    $params_abadi['kelas'] = $filter_kelas;
}

if (!empty($where_abadi)) {
    $sql_abadi .= " WHERE " . implode(" AND ", $where_abadi);
}

$sql_abadi .= " 
    GROUP BY s.no_induk, s.nama_siswa, k.nama_kelas 
    HAVING SUM(a_all.total_poin_umum) = 0 AND COUNT(a_all.id_tahun) > 1
    ORDER BY total_tahun DESC, s.nama_siswa ASC
";
$teladan_abadi = fetchAll($sql_abadi, $params_abadi);


// UI Config
$btn_primary = "px-4 py-2.5 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-md shadow-blue-900/10 hover:bg-blue-900 transition-all flex items-center justify-center";
$input_class = "w-full px-3 py-2 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm text-slate-700 bg-white transition-all";
$label_class = "block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wide";
$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Kedisiplinan - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 pl-16 lg:pl-6 py-4 sticky top-0 z-30 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Statistik Kedisiplinan</h1>
                <p class="text-sm font-medium text-slate-500">Pantau rekam jejak siswa teladan dan siswa yang butuh perhatian</p>
            </div>
            <div>
                <button onclick="document.getElementById('modal-abadi').classList.remove('hidden')" class="px-4 py-2 bg-amber-100 text-amber-700 border border-amber-300 font-bold rounded-lg shadow-sm hover:bg-amber-200 transition-colors flex items-center text-sm">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                    Konsistensi Kedisiplinan (>1 Tahun)
                </button>
            </div>
        </div>

        <div class="p-6 space-y-6 max-w-7xl mx-auto">

            <div class="<?= $card_class ?> p-5 bg-slate-50/30">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    
                    <div>
                        <label class="<?= $label_class ?>">Tahun Ajaran</label>
                        <select name="tahun" class="<?= $input_class ?> font-bold text-[#000080]">
                            <?php foreach ($list_tahun as $t): ?>
                            <option value="<?= $t['id_tahun'] ?>" <?= $filter_tahun == $t['id_tahun'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['nama_tahun']) ?> <?= $t['status'] === 'Arsip' ? '(Arsip)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="<?= $label_class ?>">Semester</label>
                        <select name="semester" class="<?= $input_class ?>">
                            <option value="Ganjil" <?= $filter_semester === 'Ganjil' ? 'selected' : '' ?>>Semester Ganjil</option>
                            <option value="Genap" <?= $filter_semester === 'Genap' ? 'selected' : '' ?>>Semester Genap</option>
                        </select>
                    </div>

                    <div>
                        <label class="<?= $label_class ?>">Angkatan (Tingkat)</label>
                        <select name="tingkat" id="filter_tingkat" class="<?= $input_class ?>" onchange="updateKelasDropdown()">
                            <option value="all">Semua Angkatan</option>
                            <option value="7" <?= $filter_tingkat == '7' ? 'selected' : '' ?>>Kelas 7</option>
                            <option value="8" <?= $filter_tingkat == '8' ? 'selected' : '' ?>>Kelas 8</option>
                            <option value="9" <?= $filter_tingkat == '9' ? 'selected' : '' ?>>Kelas 9</option>
                        </select>
                    </div>

                    <div>
                        <label class="<?= $label_class ?>">Kelas</label>
                        <select name="kelas" id="filter_kelas" class="<?= $input_class ?>">
                            <option value="all">Semua Kelas</option>
                            <?php foreach ($list_kelas as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>" data-tingkat="<?= $k['tingkat'] ?>" <?= $filter_kelas == $k['id_kelas'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kelas']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="<?= $btn_primary ?> w-full h-[38px]">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            Terapkan
                        </button>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <div class="<?= $card_class ?> overflow-hidden border-emerald-200">
                    <div class="bg-emerald-50 border-b border-emerald-200 p-4 flex justify-between items-center">
                        <div>
                            <h2 class="font-extrabold text-emerald-800 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                                Siswa Teladan (0 Poin)
                            </h2>
                            <p class="text-[11px] text-emerald-600 font-medium mt-0.5">Siswa bersih dari pelanggaran (Semester <?= $filter_semester ?>)</p>
                        </div>
                        <span class="bg-emerald-200 text-emerald-800 text-xs font-bold px-2.5 py-1 rounded-md"><?= count($siswa_teladan) ?> Siswa</span>
                    </div>
                    <div class="overflow-auto max-h-[500px]">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-white sticky top-0 border-b border-slate-200 shadow-sm text-xs text-slate-500 uppercase">
                                <tr>
                                    <th class="p-3 font-bold">Nama Siswa</th>
                                    <th class="p-3 font-bold text-center">Kelas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if ($is_future_semester): ?>
                                <tr>
                                    <td colspan="2" class="p-10 text-center">
                                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 text-slate-400 mb-3">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        </div>
                                        <p class="text-slate-500 font-bold">Semester Genap Belum Berjalan</p>
                                        <p class="text-xs text-slate-400 mt-1">Tidak ada data untuk ditampilkan</p>
                                    </td>
                                </tr>
                                <?php elseif (empty($siswa_teladan)): ?>
                                <tr><td colspan="2" class="p-8 text-center text-slate-400 italic">Tidak ada siswa teladan di kategori ini.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($siswa_teladan as $t): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-3">
                                            <p class="font-bold text-slate-800"><?= htmlspecialchars($t['nama_siswa']) ?></p>
                                            <p class="text-[10px] text-slate-500"><?= $t['no_induk'] ?></p>
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded text-[11px] font-bold"><?= $t['nama_kelas'] ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="<?= $card_class ?> overflow-hidden border-rose-200">
                    <div class="bg-rose-50 border-b border-rose-200 p-4 flex justify-between items-center">
                        <div>
                            <h2 class="font-extrabold text-rose-800 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                Perhatian Khusus (Top 15)
                            </h2>
                            <p class="text-[11px] text-rose-600 font-medium mt-0.5">Akumulasi poin terbanyak (Semester <?= $filter_semester ?>)</p>
                        </div>
                    </div>
                    <div class="overflow-auto max-h-[500px]">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-white sticky top-0 border-b border-slate-200 shadow-sm text-xs text-slate-500 uppercase">
                                <tr>
                                    <th class="p-3 font-bold">Nama Siswa</th>
                                    <th class="p-3 font-bold text-center">Kelas</th>
                                    <th class="p-3 font-bold text-center">Poin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if ($is_future_semester): ?>
                                <tr>
                                    <td colspan="3" class="p-10 text-center">
                                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 text-slate-400 mb-3">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        </div>
                                        <p class="text-slate-500 font-bold">Semester Genap Belum Berjalan</p>
                                    </td>
                                </tr>
                                <?php elseif (empty($siswa_pelanggar)): ?>
                                <tr><td colspan="3" class="p-8 text-center text-slate-400 italic">Belum ada data pelanggaran di kategori ini.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($siswa_pelanggar as $p): ?>
                                    <tr class="hover:bg-rose-50/30 transition-colors">
                                        <td class="p-3">
                                            <p class="font-bold text-slate-800"><?= htmlspecialchars($p['nama_siswa']) ?></p>
                                            <p class="text-[10px] text-slate-500"><?= $p['no_induk'] ?> 
                                                <?php if($p['status_sp_terakhir'] !== 'Aman'): ?>
                                                    <span class="ml-1 text-rose-600 font-bold uppercase">• <?= $p['status_sp_terakhir'] ?></span>
                                                <?php endif; ?>
                                            </p>
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded text-[11px] font-bold"><?= $p['nama_kelas'] ?></span>
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="px-2 py-1 bg-rose-100 text-rose-700 rounded text-[12px] font-extrabold"><?= $p['total_poin'] ?></span>
                                        </td>
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
</div>

<div id="modal-abadi" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('modal-abadi').classList.add('hidden')"></div>
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full relative z-10 overflow-hidden transform transition-all flex flex-col max-h-[80vh]">
        <div class="p-5 border-b border-[#E2E8F0] bg-gradient-to-r from-amber-100 to-yellow-50 flex items-center justify-between">
            <h3 class="font-extrabold text-amber-800 flex items-center text-lg">
                <svg class="w-6 h-6 mr-2 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                Rekam Jejak Konsistensi Kedisiplinan
            </h3>
            <button onclick="document.getElementById('modal-abadi').classList.add('hidden')" class="text-amber-500 hover:text-amber-700 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <div class="p-5 bg-amber-50/30 border-b border-amber-100">
            <?php
            // Membangun teks filter untuk modal
            $filter_teks = "Semua Kelas / Angkatan";
            if ($filter_kelas !== 'all') {
                foreach ($list_kelas as $lk) {
                    if ($lk['id_kelas'] == $filter_kelas) {
                        $filter_teks = "Kelas " . htmlspecialchars($lk['nama_kelas']);
                        break;
                    }
                }
            } elseif ($filter_tingkat !== 'all') {
                $filter_teks = "Semua Kelas " . htmlspecialchars($filter_tingkat);
            }
            ?>
            <p class="text-sm text-slate-600 leading-relaxed mb-2">
                Ini adalah daftar eksklusif siswa yang memiliki rekam jejak <strong>0 Poin Pelanggaran</strong> secara berturut-turut selama <span class="font-bold text-amber-600">lebih dari 1 tahun ajaran</span>.
            </p>
            <div class="inline-block bg-white border border-amber-200 px-3 py-1 rounded-md text-xs font-bold text-amber-800 shadow-sm">
                Filter Aktif: <?= $filter_teks ?>
            </div>
        </div>
        <div class="overflow-y-auto p-0">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 sticky top-0 border-b border-slate-200 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="p-4 font-bold w-16 text-center">No</th>
                        <th class="p-4 font-bold">Nama Siswa & Kelas Saat Ini</th>
                        <th class="p-4 font-bold text-center">Konsistensi (Tahun)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($teladan_abadi)): ?>
                    <tr><td colspan="3" class="p-10 text-center text-slate-400 italic">Belum ada siswa yang memenuhi kriteria filter ini.</td></tr>
                    <?php else: ?>
                        <?php $no=1; foreach ($teladan_abadi as $abadi): ?>
                        <tr class="hover:bg-amber-50/30 transition-colors">
                            <td class="p-4 text-center font-bold text-slate-400"><?= $no++ ?></td>
                            <td class="p-4">
                                <p class="font-bold text-slate-800 text-[14px]"><?= htmlspecialchars($abadi['nama_siswa']) ?></p>
                                <p class="text-[11px] text-slate-500 font-medium"><?= $abadi['nama_kelas'] ?> • No Induk: <?= $abadi['no_induk'] ?></p>
                            </td>
                            <td class="p-4 text-center">
                                <span class="px-3 py-1 bg-amber-100 text-amber-700 border border-amber-300 rounded-full text-xs font-extrabold shadow-sm">
                                    <?= $abadi['total_tahun'] ?> Tahun Terakhir
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function updateKelasDropdown() {
        const tingkatSelect = document.getElementById('filter_tingkat');
        const kelasSelect = document.getElementById('filter_kelas');
        const selectedTingkat = tingkatSelect.value;
        
        // Loop seluruh option kelas
        Array.from(kelasSelect.options).forEach(option => {
            if (option.value === 'all') return; // Biarkan "Semua Kelas" tetap muncul
            
            const optionTingkat = option.getAttribute('data-tingkat');
            
            // Tampilkan jika tingkat sesuai, atau jika "Semua Angkatan" dipilih
            if (selectedTingkat === 'all' || optionTingkat === selectedTingkat) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
        
        // Validasi: Jika kelas yang saat ini terpilih ternyata disembunyikan, reset ke "Semua Kelas"
        const currentSelectedOption = kelasSelect.options[kelasSelect.selectedIndex];
        if (currentSelectedOption && currentSelectedOption.style.display === 'none') {
            kelasSelect.value = 'all';
        }
    }

    // Jalankan fungsi saat halaman pertama kali dimuat agar menyesuaikan dengan URL (jika ada filter yang sedang aktif)
    document.addEventListener('DOMContentLoaded', updateKelasDropdown);
</script>

</body>
</html>