<?php
/**
 * SITAPSI - Cetak Rapor Laporan Tata Tertib Siswa
 * FIXED: Tanggal cetak di bagian tanda tangan (bawah) sudah menggunakan Bahasa Indonesia.
 */

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['role'])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

$id_anggota = $_GET['id'] ?? null;
if (!$id_anggota) die("ID Siswa tidak dipilih.");

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

$siswa = fetchOne("
    SELECT 
        s.no_induk, 
        s.nama_siswa, 
        k.nama_kelas,
        (
            SELECT COUNT(*) + 1 
            FROM tb_anggota_kelas a2 
            JOIN tb_siswa s2 ON a2.no_induk = s2.no_induk 
            WHERE a2.id_kelas = a.id_kelas 
            AND a2.id_tahun = a.id_tahun 
            AND s2.nama_siswa < s.nama_siswa
        ) AS no_urut
    FROM tb_anggota_kelas a
    JOIN tb_siswa s ON a.no_induk = s.no_induk
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE a.id_anggota = :id
", ['id' => $id_anggota]);

if (!$siswa) die("Data siswa tidak ditemukan.");

$pelanggaran_raw = fetchAll("
    SELECT h.tanggal, jp.nama_pelanggaran, jp.id_kategori, d.poin_saat_itu
    FROM tb_pelanggaran_header h
    JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    WHERE h.id_anggota = :id AND h.id_tahun = :id_tahun AND h.status_revisi != 'Ditolak'
    ORDER BY h.tanggal ASC
", ['id' => $id_anggota, 'id_tahun' => $tahun_aktif['id_tahun']]);

// Fungsi format tanggal Bahasa Indonesia
function tglIndo($date) {
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = explode('-', $date);
    return (int)$t[2] . ' ' . $bulan[(int)$t[1]] . ' ' . $t[0];
}

$kelakuan = []; $kerajinan = []; $kerapian = [];
$tot_kel = 0; $tot_ker = 0; $tot_rap = 0;

foreach ($pelanggaran_raw as $p) {
    $item = ['tgl' => tglIndo($p['tanggal']), 'aspek' => $p['nama_pelanggaran'], 'denda' => $p['poin_saat_itu']];
    if ($p['id_kategori'] == 1) { $kelakuan[] = $item; $tot_kel += $p['poin_saat_itu']; }
    if ($p['id_kategori'] == 2) { $kerajinan[] = $item; $tot_ker += $p['poin_saat_itu']; }
    if ($p['id_kategori'] == 3) { $kerapian[] = $item; $tot_rap += $p['poin_saat_itu']; }
}

$min_rows = 3; 

$btn_primary = "px-5 py-2.5 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-md hover:bg-blue-900 transition-all flex items-center justify-center cursor-pointer";
$btn_success = "px-5 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-lg shadow-md hover:bg-emerald-700 transition-all flex items-center justify-center cursor-pointer";
$btn_outline = "px-5 py-2.5 bg-white border border-[#E2E8F0] text-slate-700 text-sm font-semibold rounded-lg shadow-sm hover:bg-slate-50 transition-all flex items-center justify-center cursor-pointer";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Tata Tertib - <?= htmlspecialchars($siswa['nama_siswa']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: black; }
        .font-serif-kop { font-family: 'Times New Roman', Times, serif; }
        
        .tabel-rapor { width: 100%; border-collapse: collapse; margin-bottom: 6px; table-layout: fixed; }
        .tabel-rapor th, .tabel-rapor td { border: 1px solid black; padding: 3px 5px; font-size: 11px; word-wrap: break-word; overflow-wrap: break-word; }
        .tabel-rapor th { text-align: center; font-weight: bold; background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        .header-kategori { background-color: #3b4a59 !important; color: white !important; font-weight: bold; text-align: center; font-size: 11px; padding: 3px; border: 1px solid black; -webkit-print-color-adjust: exact; print-color-adjust: exact; border-bottom: none; }
        .footer-kategori { background-color: #e5e7eb !important; font-weight: bold; text-align: center; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .col-no { width: 5%; text-align: center; }
        .col-tgl { width: 20%; text-align: center; }
        .col-jenis { width: 63%; }
        .col-skor { width: 12%; text-align: center; }

        .kriteria-box { border: 1px solid black; padding: 5px 8px; margin-top: 6px; font-size: 11px; }

        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; margin: 0 !important; }
            .print-container { box-shadow: none !important; border: none !important; margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
        }
        @page { size: A4 portrait; margin: 8mm; }
    </style>
</head>
<body class="bg-[#F8FAFC] min-h-screen py-8 print:py-0">

<div class="max-w-4xl mx-auto px-4 print:px-0">
    <div class="no-print mb-6 flex flex-col sm:flex-row justify-between items-center bg-white p-4 rounded-xl border border-[#E2E8F0] shadow-sm font-sans gap-4">
        <button onclick="window.close()" class="<?= $btn_outline ?>">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            Tutup Tab
        </button>
        <div class="flex items-center space-x-3">
            <button onclick="downloadPDF()" class="<?= $btn_success ?>">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Download PDF
            </button>
            <button onclick="window.print()" class="<?= $btn_primary ?>">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2-2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Cetak Printer
            </button>
        </div>
    </div>

    <div id="dokumen-rapor" class="print-container bg-white shadow-xl rounded-none sm:rounded-xl border border-[#E2E8F0] px-8 py-6 mx-auto w-full">
        
        <div class="flex items-center justify-between mb-2">
            <div class="w-28 shrink-0 flex justify-center">
                <img src="../assets/img/logo_sekolah.png" alt="Logo Yayasan" class="w-20 h-auto object-contain">
            </div>
            
            <div class="flex-1 text-center px-2">
                <p class="text-[15px] italic font-bold leading-tight font-serif-kop">Perkumpulan Dharmaputri</p>
                <h1 class="text-[20px] font-bold tracking-wide mt-0.5 mb-0.5 font-serif-kop">SMP KATOLIK SANTA MARIA II</h1>
                <p class="text-xs font-bold tracking-widest mb-1">SEKOLAH STANDAR NASIONAL</p>
                <p class="text-[10px] mb-0.5">STATUS TERAKREDITASI "A"</p>
                
                <div class="flex justify-between text-[9px] leading-tight px-6 mt-0.5">
                    <div class="text-left">
                        <p>NSS : 203056101019</p>
                        <p>NPSN : 20533743</p>
                    </div>
                    <div class="text-right">
                        <p>Website : www.smpksantamaria2-mlg.sch.id</p>
                        <p>E-mail : smpkstmaria2mlg@gmail.com</p>
                    </div>
                </div>
            </div>
            
            <div class="w-28 shrink-0 flex flex-col items-center justify-center">
                <img src="../assets/img/logo_iso.png" alt="Logo ISO" class="w-30 h-auto object-contain">
            </div>
        </div>

        <div class="bg-[#1a1a1a] text-white text-center py-0.5 text-[10px] mb-3 w-[65%] mx-auto font-bold" style="-webkit-print-color-adjust: exact; print-color-adjust: exact;">
        Jl. Panderman 7A Malang Telp. 0341 - 551871
        </div>

        <div class="text-center mb-4">
            <h2 class="text-[13px] font-bold tracking-wide">LAPORAN TATA TERTIB SISWA</h2>
            <h3 class="text-[13px] font-bold tracking-wide">SEMESTER <?= mb_strtoupper($tahun_aktif['semester_aktif']) ?> TAHUN PELAJARAN <?= mb_strtoupper($tahun_aktif['nama_tahun']) ?></h3>
        </div>

        <div class="flex justify-between text-[12px] font-bold mb-2">
            <table class="whitespace-nowrap">
                <tr><td class="w-28 pb-1">Nama</td><td class="pb-1">: <?= htmlspecialchars($siswa['nama_siswa']) ?></td></tr>
                <tr><td>Nomor Induk</td><td>: <?= $siswa['no_induk'] ?></td></tr>
            </table>
            <table class="whitespace-nowrap">
                <tr><td class="w-16 pb-1">Kelas</td><td class="pb-1">: <?= htmlspecialchars($siswa['nama_kelas']) ?></td></tr>
                <tr><td>No. Urut</td><td>: <?= $siswa['no_urut'] ?></td></tr>
            </table>
        </div>

        <div class="header-kategori">A. ASPEK KELAKUAN</div>
        <table class="tabel-rapor">
            <thead>
                <tr>
                    <th class="col-no">No.</th>
                    <th class="col-tgl">Tanggal</th>
                    <th class="col-jenis">Jenis Pelanggaran</th>
                    <th class="col-skor">Skor Denda</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $count = max(count($kelakuan), $min_rows);
                for ($i = 0; $i < $count; $i++): 
                ?>
                <tr>
                    <td class="col-no"><?= isset($kelakuan[$i]) ? ($i+1) : '&nbsp;' ?></td>
                    <td class="col-tgl"><?= isset($kelakuan[$i]) ? $kelakuan[$i]['tgl'] : '&nbsp;' ?></td>
                    <td class="col-jenis"><?= isset($kelakuan[$i]) ? htmlspecialchars($kelakuan[$i]['aspek']) : '&nbsp;' ?></td>
                    <td class="col-skor"><?= isset($kelakuan[$i]) ? $kelakuan[$i]['denda'] : '&nbsp;' ?></td>
                </tr>
                <?php endfor; ?>
                <tr>
                    <td colspan="3" class="footer-kategori">TOTAL SKOR PELANGGARAN</td>
                    <td class="footer-kategori text-center"><?= $tot_kel ?></td>
                </tr>
            </tbody>
        </table>

        <div class="header-kategori">B. ASPEK KERAJINAN</div>
        <table class="tabel-rapor">
            <thead>
                <tr>
                    <th class="col-no">No.</th>
                    <th class="col-tgl">Tanggal</th>
                    <th class="col-jenis">Jenis Pelanggaran</th>
                    <th class="col-skor">Skor Denda</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $count = max(count($kerajinan), $min_rows);
                for ($i = 0; $i < $count; $i++): 
                ?>
                <tr>
                    <td class="col-no"><?= isset($kerajinan[$i]) ? ($i+1) : '&nbsp;' ?></td>
                    <td class="col-tgl"><?= isset($kerajinan[$i]) ? $kerajinan[$i]['tgl'] : '&nbsp;' ?></td>
                    <td class="col-jenis"><?= isset($kerajinan[$i]) ? htmlspecialchars($kerajinan[$i]['aspek']) : '&nbsp;' ?></td>
                    <td class="col-skor"><?= isset($kerajinan[$i]) ? $kerajinan[$i]['denda'] : '&nbsp;' ?></td>
                </tr>
                <?php endfor; ?>
                <tr>
                    <td colspan="3" class="footer-kategori">TOTAL SKOR PELANGGARAN</td>
                    <td class="footer-kategori text-center"><?= $tot_ker ?></td>
                </tr>
            </tbody>
        </table>

        <div class="header-kategori">C. ASPEK KERAPIAN</div>
        <table class="tabel-rapor">
            <thead>
                <tr>
                    <th class="col-no">No.</th>
                    <th class="col-tgl">Tanggal</th>
                    <th class="col-jenis">Jenis Pelanggaran</th>
                    <th class="col-skor">Skor Denda</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $count = max(count($kerapian), $min_rows);
                for ($i = 0; $i < $count; $i++): 
                ?>
                <tr>
                    <td class="col-no"><?= isset($kerapian[$i]) ? ($i+1) : '&nbsp;' ?></td>
                    <td class="col-tgl"><?= isset($kerapian[$i]) ? $kerapian[$i]['tgl'] : '&nbsp;' ?></td>
                    <td class="col-jenis"><?= isset($kerapian[$i]) ? htmlspecialchars($kerapian[$i]['aspek']) : '&nbsp;' ?></td>
                    <td class="col-skor"><?= isset($kerapian[$i]) ? $kerapian[$i]['denda'] : '&nbsp;' ?></td>
                </tr>
                <?php endfor; ?>
                <tr>
                    <td colspan="3" class="footer-kategori">TOTAL SKOR PELANGGARAN</td>
                    <td class="footer-kategori text-center"><?= $tot_rap ?></td>
                </tr>
            </tbody>
        </table>

        <div class="kriteria-box bg-[#f2f2f2] -webkit-print-color-adjust: exact; print-color-adjust: exact;">
            <div class="font-bold text-center mb-1">KRITERIA PENILAIAN</div>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <div class="font-bold mb-1">Aspek Kelakuan</div>
                    <table class="mx-auto text-left">
                        <tr><td class="pr-2">A =</td><td class="text-right w-8">0</td><td class="px-1">-</td><td class="w-10">250</td></tr>
                        <tr><td class="pr-2">B =</td><td class="text-right">251</td><td class="px-1">-</td><td>2000</td></tr>
                        <tr><td class="pr-2">C =</td><td class="text-right">2001</td><td class="px-1">-</td><td>3000</td></tr>
                        <tr><td class="pr-2">D &ge;</td><td class="text-right">3001</td><td></td><td></td></tr>
                    </table>
                </div>
                <div>
                    <div class="font-bold mb-1">Aspek Kerajinan</div>
                    <table class="mx-auto text-left">
                        <tr><td class="pr-2">A =</td><td class="text-right w-8">0</td><td class="px-1">-</td><td class="w-10">75</td></tr>
                        <tr><td class="pr-2">B =</td><td class="text-right">76</td><td class="px-1">-</td><td>600</td></tr>
                        <tr><td class="pr-2">C =</td><td class="text-right">601</td><td class="px-1">-</td><td>900</td></tr>
                        <tr><td class="pr-2">D &ge;</td><td class="text-right">901</td><td></td><td></td></tr>
                    </table>
                </div>
                <div>
                    <div class="font-bold mb-1">Aspek Kerapian</div>
                    <table class="mx-auto text-left">
                        <tr><td class="pr-2">A =</td><td class="text-right w-8">0</td><td class="px-1">-</td><td class="w-10">100</td></tr>
                        <tr><td class="pr-2">B =</td><td class="text-right">101</td><td class="px-1">-</td><td>600</td></tr>
                        <tr><td class="pr-2">C =</td><td class="text-right">601</td><td class="px-1">-</td><td>900</td></tr>
                        <tr><td class="pr-2">D &ge;</td><td class="text-right">901</td><td></td><td></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-8 text-[12px] mt-2">
            <div class="pl-4">
                <p class="mb-1">Mengetahui</p>
                <p class="mb-12">Kepala Sekolah,</p>
                <p class="font-bold">Sr. M. Dorothea, SPM, M.Pd.</p>
            </div>
            <div class="text-left pl-10">
                <p class="mb-1">Malang, <?= tglIndo(date('Y-m-d')) ?></p>
                <p class="mb-12">Koordinator Tata Tertib Siswa,</p>
                <p class="font-bold">Agnes Herawaty Sinurat, S.E., M.M.</p>
            </div>
        </div>

    </div>
</div>

<script>
function downloadPDF() {
    const btn = document.querySelector('.bg-emerald-600');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div> Proses...';
    btn.classList.add('opacity-75', 'cursor-not-allowed');
    btn.disabled = true;

    const element = document.getElementById('dokumen-rapor');
    const opt = {
        margin:       [5, 8, 5, 8], 
        filename:     'Rapor_Tatib_<?= htmlspecialchars($siswa['nama_siswa']) ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, scrollY: 0 },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save().then(() => {
        btn.innerHTML = originalText;
        btn.classList.remove('opacity-75', 'cursor-not-allowed');
        btn.disabled = false;
    });
}
</script>
</body>
</html>