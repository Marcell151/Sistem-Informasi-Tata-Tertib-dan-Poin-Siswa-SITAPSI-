<?php
/**
 * SITAPSI - Rapor Karakter Siswa (CETAK - UI GLOBAL PORTAL)
 * Step 3: Tampilkan rapor karakter per siswa dengan predikat A/B/C/D
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_anggota = $_GET['id'] ?? null;

if (!$id_anggota) {
    $_SESSION['error_message'] = '❌ ID siswa tidak valid';
    header('Location: rapor_karakter.php');
    exit;
}

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// Ambil data siswa
$siswa = fetchOne("
    SELECT 
        s.*,
        a.id_anggota,
        a.poin_kelakuan,
        a.poin_kerajinan,
        a.poin_kerapian,
        a.total_poin_umum,
        k.nama_kelas,
        k.id_kelas
    FROM tb_anggota_kelas a
    JOIN tb_siswa s ON a.nis = s.nis
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE a.id_anggota = :id
", ['id' => $id_anggota]);

if (!$siswa) {
    $_SESSION['error_message'] = '❌ Siswa tidak ditemukan';
    header('Location: rapor_karakter.php');
    exit;
}

// Ambil predikat dari database
$predikat_kelakuan = fetchAll("SELECT * FROM tb_predikat_nilai WHERE id_kategori = 1 ORDER BY batas_bawah");
$predikat_kerajinan = fetchAll("SELECT * FROM tb_predikat_nilai WHERE id_kategori = 2 ORDER BY batas_bawah");
$predikat_kerapian = fetchAll("SELECT * FROM tb_predikat_nilai WHERE id_kategori = 3 ORDER BY batas_bawah");

// Fungsi konversi poin ke predikat
function getPredikat($poin, $predikat_list) {
    foreach ($predikat_list as $p) {
        if ($poin >= $p['batas_bawah'] && $poin <= $p['batas_atas']) {
            return [
                'huruf' => $p['huruf_mutu'],
                'keterangan' => $p['keterangan'],
                'batas' => $p['batas_bawah'] . '-' . $p['batas_atas']
            ];
        }
    }
    return ['huruf' => '-', 'keterangan' => '-', 'batas' => '-'];
}

$pred_kelakuan = getPredikat($siswa['poin_kelakuan'], $predikat_kelakuan);
$pred_kerajinan = getPredikat($siswa['poin_kerajinan'], $predikat_kerajinan);
$pred_kerapian = getPredikat($siswa['poin_kerapian'], $predikat_kerapian);

// Warna badge CSS
function getBadgeColor($huruf) {
    switch($huruf) {
        case 'A': return 'bg-emerald-500';
        case 'B': return 'bg-blue-500';
        case 'C': return 'bg-amber-500';
        case 'D': return 'bg-red-500';
        default: return 'bg-slate-500';
    }
}

// UI Config
$btn_primary = "px-4 py-2 bg-[#000080] text-white text-sm font-bold rounded-lg shadow-md hover:bg-blue-900 transition-colors flex items-center";
$btn_outline = "px-4 py-2 bg-white border border-[#E2E8F0] text-slate-700 text-sm font-bold rounded-lg shadow-sm hover:bg-slate-50 transition-colors flex items-center";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapor Karakter - <?= htmlspecialchars($siswa['nama_siswa']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-full-width { max-width: 100% !important; margin: 0 !important; padding: 0 !important; box-shadow: none !important; border: none !important;}
        }
        @page { size: A4; margin: 20mm; }
    </style>
</head>
<body class="bg-[#F8FAFC] text-slate-800 py-8 print:py-0">

<div class="max-w-4xl mx-auto px-4 print:px-0">
    <div class="no-print mb-6 flex justify-between items-center bg-white p-4 rounded-xl border border-[#E2E8F0] shadow-sm">
        <a href="rapor_karakter_list.php?kelas=<?= $siswa['id_kelas'] ?>" class="<?= $btn_outline ?>">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Kembali
        </a>
        <button onclick="window.print()" class="<?= $btn_primary ?>">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Cetak PDF / A4
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-xl border border-[#E2E8F0] print-full-width pb-10">
        
        <div class="border-b-4 border-[#000080] p-8 text-center bg-slate-50/50 print:bg-white">
            <h1 class="text-3xl font-extrabold text-[#000080] mb-2 tracking-tight">SMP KATOLIK SANTA MARIA 2 MALANG</h1>
            <p class="text-sm text-slate-600 font-medium">Jl. K.H. Hasyim Ashari No. 15, Malang • Telp: (0341) 551234</p>
            <div class="mt-6 pt-5 border-t border-slate-300">
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-wider">RAPOR KARAKTER SISWA</h2>
                <p class="text-sm font-bold text-slate-500 mt-1 uppercase">Tahun Ajaran <?= $tahun_aktif['nama_tahun'] ?> • Semester <?= $tahun_aktif['semester_aktif'] ?></p>
            </div>
        </div>

        <div class="p-8 border-b border-[#E2E8F0]">
            <div class="grid grid-cols-2 gap-6 text-sm">
                <div class="space-y-3">
                    <div class="flex">
                        <span class="w-32 text-slate-500 font-bold uppercase tracking-wider">Nama Lengkap</span>
                        <span class="flex-1 text-slate-800">: <strong class="text-base"><?= htmlspecialchars($siswa['nama_siswa']) ?></strong></span>
                    </div>
                    <div class="flex">
                        <span class="w-32 text-slate-500 font-bold uppercase tracking-wider">NIS</span>
                        <span class="flex-1 text-slate-800 font-medium">: <?= $siswa['nis'] ?></span>
                    </div>
                    <div class="flex">
                        <span class="w-32 text-slate-500 font-bold uppercase tracking-wider">Kelas</span>
                        <span class="flex-1 text-slate-800 font-medium">: <?= $siswa['nama_kelas'] ?></span>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex">
                        <span class="w-32 text-slate-500 font-bold uppercase tracking-wider">Jenis Kelamin</span>
                        <span class="flex-1 text-slate-800 font-medium">: <?= $siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></span>
                    </div>
                    <div class="flex">
                        <span class="w-32 text-slate-500 font-bold uppercase tracking-wider">Nama Orang Tua</span>
                        <span class="flex-1 text-slate-800 font-medium">: <?= htmlspecialchars($siswa['nama_ortu']) ?></span>
                    </div>
                    <div class="flex mt-2 pt-2 border-t border-slate-200">
                        <span class="w-32 text-slate-800 font-extrabold uppercase tracking-wider">Total Poin</span>
                        <span class="flex-1 text-red-600 font-extrabold text-base">: <?= $siswa['total_poin_umum'] ?> poin</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-8 py-6">
            <h3 class="text-lg font-extrabold text-slate-800 mb-6 border-b-2 border-slate-800 inline-block pb-1 uppercase tracking-wide">Hasil Penilaian Karakter</h3>
            
            <div class="space-y-5">
                
                <div class="border border-red-200 rounded-xl p-5 bg-red-50/50 print:bg-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="<?= getBadgeColor($pred_kelakuan['huruf']) ?> text-white w-16 h-16 rounded-xl flex items-center justify-center shadow-sm">
                                <span class="text-3xl font-extrabold"><?= $pred_kelakuan['huruf'] ?></span>
                            </div>
                            <div>
                                <h4 class="text-base font-extrabold text-red-700 uppercase tracking-wide flex items-center mb-1">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                    Aspek Kelakuan
                                </h4>
                                <p class="text-sm font-medium text-slate-700">Predikat: <strong><?= $pred_kelakuan['keterangan'] ?></strong></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-0.5">Akumulasi Poin</p>
                            <p class="text-2xl font-extrabold text-red-700"><?= $siswa['poin_kelakuan'] ?></p>
                        </div>
                    </div>
                </div>

                <div class="border border-blue-200 rounded-xl p-5 bg-blue-50/50 print:bg-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="<?= getBadgeColor($pred_kerajinan['huruf']) ?> text-white w-16 h-16 rounded-xl flex items-center justify-center shadow-sm">
                                <span class="text-3xl font-extrabold"><?= $pred_kerajinan['huruf'] ?></span>
                            </div>
                            <div>
                                <h4 class="text-base font-extrabold text-blue-700 uppercase tracking-wide flex items-center mb-1">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path></svg>
                                    Aspek Kerajinan
                                </h4>
                                <p class="text-sm font-medium text-slate-700">Predikat: <strong><?= $pred_kerajinan['keterangan'] ?></strong></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-0.5">Akumulasi Poin</p>
                            <p class="text-2xl font-extrabold text-blue-700"><?= $siswa['poin_kerajinan'] ?></p>
                        </div>
                    </div>
                </div>

                <div class="border border-yellow-200 rounded-xl p-5 bg-yellow-50/50 print:bg-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="<?= getBadgeColor($pred_kerapian['huruf']) ?> text-white w-16 h-16 rounded-xl flex items-center justify-center shadow-sm">
                                <span class="text-3xl font-extrabold"><?= $pred_kerapian['huruf'] ?></span>
                            </div>
                            <div>
                                <h4 class="text-base font-extrabold text-yellow-700 uppercase tracking-wide flex items-center mb-1">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    Aspek Kerapian
                                </h4>
                                <p class="text-sm font-medium text-slate-700">Predikat: <strong><?= $pred_kerapian['keterangan'] ?></strong></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-0.5">Akumulasi Poin</p>
                            <p class="text-2xl font-extrabold text-yellow-700"><?= $siswa['poin_kerapian'] ?></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="mx-8 p-5 bg-slate-50 border border-slate-200 rounded-xl">
            <h4 class="font-extrabold text-slate-800 text-xs uppercase tracking-wider mb-3">Legenda Penilaian:</h4>
            <div class="grid grid-cols-4 gap-4 text-xs font-bold">
                <div class="flex items-center text-slate-700">
                    <span class="w-6 h-6 bg-emerald-500 text-white rounded mr-2 flex items-center justify-center">A</span> Sangat Baik
                </div>
                <div class="flex items-center text-slate-700">
                    <span class="w-6 h-6 bg-blue-500 text-white rounded mr-2 flex items-center justify-center">B</span> Baik
                </div>
                <div class="flex items-center text-slate-700">
                    <span class="w-6 h-6 bg-amber-500 text-white rounded mr-2 flex items-center justify-center">C</span> Cukup
                </div>
                <div class="flex items-center text-slate-700">
                    <span class="w-6 h-6 bg-red-500 text-white rounded mr-2 flex items-center justify-center">D</span> Kurang
                </div>
            </div>
        </div>

        <div class="px-10 mt-12 mb-4">
            <div class="grid grid-cols-2 gap-8 text-sm">
                <div class="text-center">
                    <p class="mb-20 text-slate-600">Mengetahui,<br><strong class="text-slate-800">Kepala Sekolah</strong></p>
                    <p class="font-bold border-b border-slate-800 inline-block pb-1 px-8">(Dra. Maria Theresia)</p>
                </div>
                <div class="text-center">
                    <p class="text-slate-600 mb-1">Malang, <?= date('d F Y') ?></p>
                    <p class="mb-14 text-slate-600"><strong class="text-slate-800">Wali Kelas</strong></p>
                    <p class="font-bold border-b border-slate-800 inline-block pb-1 px-12">(______________________)</p>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>