<?php
/**
 * SITAPSI - Cetak Detail Pelanggaran Siswa (Format Buku Pembinaan 3 Kolom)
 * FIXED: Lebar kolom 'Denda' diperbesar agar teks header tidak turun/terpotong.
 * PENYESUAIAN AKSES: Mengizinkan Orang Tua mencetak rapor dengan validasi IDOR.
 */

session_start();
require_once '../config/database.php';

// Validasi Keamanan Lintas Peran (Admin, Guru, atau Ortu)
if (!isset($_SESSION['role'])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

$id_anggota = $_GET['id'] ?? null;

if (!$id_anggota) {
    die("ID Siswa tidak dipilih.");
}

// 1. Ambil tahun ajaran aktif
$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// 2. Ambil data siswa & LAKUKAN VALIDASI KEPEMILIKAN JIKA YANG LOGIN ADALAH ORTU
if ($_SESSION['role'] === 'Ortu') {
    $id_ortu_login = $_SESSION['ortu_id'];
    $siswa = fetchOne("
        SELECT 
            s.no_induk,
            s.nama_siswa,
            k.nama_kelas
        FROM tb_anggota_kelas a
        JOIN tb_siswa s ON a.no_induk = s.no_induk
        JOIN tb_kelas k ON a.id_kelas = k.id_kelas
        WHERE a.id_anggota = :id AND s.id_ortu = :id_ortu
    ", ['id' => $id_anggota, 'id_ortu' => $id_ortu_login]);
    
    // Jika tidak ditemukan, berarti Ortu ini mencoba akses ID anak orang lain (Mencegah IDOR)
    if (!$siswa) {
        die("<div style='font-family:sans-serif;text-align:center;margin-top:50px;color:red;'><h2>Akses Ditolak!</h2><p>Data anak tidak ditemukan atau Anda tidak memiliki hak akses untuk mencetak profil ini.</p></div>");
    }
} else {
    // Jika Admin atau Guru, bebas akses siapa saja
    $siswa = fetchOne("
        SELECT 
            s.no_induk,
            s.nama_siswa,
            k.nama_kelas
        FROM tb_anggota_kelas a
        JOIN tb_siswa s ON a.no_induk = s.no_induk
        JOIN tb_kelas k ON a.id_kelas = k.id_kelas
        WHERE a.id_anggota = :id
    ", ['id' => $id_anggota]);
    
    if (!$siswa) {
        die("Data siswa tidak ditemukan.");
    }
}

// 3. Ambil seluruh pelanggaran siswa ini pada tahun aktif
$pelanggaran_raw = fetchAll("
    SELECT 
        h.tanggal,
        jp.nama_pelanggaran,
        jp.id_kategori,
        d.poin_saat_itu
    FROM tb_pelanggaran_header h
    JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    WHERE h.id_anggota = :id
    AND h.id_tahun = :id_tahun
    AND h.status_revisi != 'Ditolak'
    ORDER BY h.tanggal ASC
", [
    'id' => $id_anggota,
    'id_tahun' => $tahun_aktif['id_tahun']
]);

// Fungsi translate hari ke Bahasa Indonesia
function hariIndo($date) {
    $hari = date('l', strtotime($date));
    $map = ['Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'];
    return $map[$hari] ?? $hari;
}

// 4. Pisahkan data ke dalam 3 Array (Sesuai 3 Kolom di Buku)
$kelakuan = []; $kerajinan = []; $kerapian = [];
$tot_kel = 0; $tot_ker = 0; $tot_rap = 0;

foreach ($pelanggaran_raw as $p) {
    $format_tgl = hariIndo($p['tanggal']) . ', ' . date('d/m/y', strtotime($p['tanggal']));
    $item = [
        'tgl' => $format_tgl,
        'aspek' => $p['nama_pelanggaran'],
        'denda' => $p['poin_saat_itu']
    ];
    
    if ($p['id_kategori'] == 1) { $kelakuan[] = $item; $tot_kel += $p['poin_saat_itu']; }
    if ($p['id_kategori'] == 2) { $kerajinan[] = $item; $tot_ker += $p['poin_saat_itu']; }
    if ($p['id_kategori'] == 3) { $kerapian[] = $item; $tot_rap += $p['poin_saat_itu']; }
}

// Cari jumlah baris terbanyak untuk membuat tabel sejajar, minimal buat 20 baris kosong agar mirip buku asli
$max_rows = max(count($kelakuan), count($kerajinan), count($kerapian), 20);

// UI Button non-print
$btn_primary = "px-6 py-2.5 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-md hover:bg-blue-900 transition-all cursor-pointer flex items-center";
$btn_success = "px-6 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-lg shadow-md hover:bg-emerald-700 transition-all cursor-pointer flex items-center";
$btn_outline = "px-6 py-2.5 bg-white border border-[#E2E8F0] text-slate-700 text-sm font-semibold rounded-lg shadow-sm hover:bg-slate-50 transition-all cursor-pointer flex items-center";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Pembinaan - <?= htmlspecialchars($siswa['nama_siswa']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: black; }
        
        .table-buku { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
            table-layout: fixed; 
        }
        .table-buku th, .table-buku td { 
            border: 1px solid black; 
            padding: 6px 4px; 
            font-size: 11px; 
            vertical-align: top; 
            word-wrap: break-word; 
            overflow-wrap: break-word; 
        }
        .table-buku th { 
            text-align: center; 
            font-weight: bold; 
            background-color: #f2f2f2 !important; 
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact; 
        }
        
        /* FIX LEBAR KOLOM: Denda diperlebar jadi 6%, Aspek dikurangi jadi 15% */
        /* Total 1 Kelompok: Tgl(11) + Aspek(15) + Denda(6) = 32% */
        /* Total 3 Kelompok (3 x 32) = 96% + Spacer (2 x 2) = 100% */
        .col-tgl { width: 11%; }
        .col-aspek { width: 15%; }
        .col-denda { width: 6%; text-align: center; white-space: nowrap; } 
        .spacer { width: 2%; border: none !important; background: transparent !important; }

        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; margin: 0 !important; }
            .print-container { box-shadow: none !important; border: none !important; margin: 0 !important; padding: 10mm !important; max-width: 100% !important; width: 100% !important; }
        }
        @page { size: A4 landscape; margin: 10mm; }
    </style>
</head>
<body class="bg-[#F8FAFC] py-8 print:py-0">

<div class="max-w-7xl mx-auto px-4 print:px-0">
    
    <div class="no-print mb-6 flex justify-between items-center bg-white p-4 rounded-xl border border-[#E2E8F0] shadow-sm font-sans gap-4">
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

    <div id="dokumen-buku" class="print-container bg-white shadow-xl rounded-none sm:rounded-xl border border-[#E2E8F0] px-8 py-10 mx-auto w-full">
        
        <table class="w-full mb-2 font-bold text-[14px]">
            <tr>
                <td class="w-32 pb-1">Nama</td>
                <td class="pb-1">: <?= htmlspecialchars($siswa['nama_siswa']) ?></td>
            </tr>
            <tr>
                <td>Kelas / Nomor</td>
                <td>: <?= htmlspecialchars($siswa['nama_kelas']) ?> / <?= $siswa['no_induk'] ?></td>
            </tr>
        </table>

        <table class="table-buku">
            <thead>
                <tr>
                    <th class="col-tgl">Hari / Tanggal</th>
                    <th class="col-aspek">Aspek Kelakuan</th>
                    <th class="col-denda">Denda</th>
                    
                    <th class="spacer"></th>
                    
                    <th class="col-tgl">Hari / Tanggal</th>
                    <th class="col-aspek">Aspek Kerajinan</th>
                    <th class="col-denda">Denda</th>

                    <th class="spacer"></th>
                    
                    <th class="col-tgl">Hari / Tanggal</th>
                    <th class="col-aspek">Aspek Kerapian</th>
                    <th class="col-denda">Denda</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 0; $i < $max_rows; $i++): ?>
                <tr>
                    <td><?= isset($kelakuan[$i]) ? $kelakuan[$i]['tgl'] : '&nbsp;' ?></td>
                    <td><?= isset($kelakuan[$i]) ? htmlspecialchars($kelakuan[$i]['aspek']) : '&nbsp;' ?></td>
                    <td style="text-align:center; font-weight:bold; color:blue;"><?= isset($kelakuan[$i]) ? $kelakuan[$i]['denda'] : '&nbsp;' ?></td>
                    
                    <td class="spacer"></td>

                    <td><?= isset($kerajinan[$i]) ? $kerajinan[$i]['tgl'] : '&nbsp;' ?></td>
                    <td><?= isset($kerajinan[$i]) ? htmlspecialchars($kerajinan[$i]['aspek']) : '&nbsp;' ?></td>
                    <td style="text-align:center; font-weight:bold; color:blue;"><?= isset($kerajinan[$i]) ? $kerajinan[$i]['denda'] : '&nbsp;' ?></td>

                    <td class="spacer"></td>

                    <td><?= isset($kerapian[$i]) ? $kerapian[$i]['tgl'] : '&nbsp;' ?></td>
                    <td><?= isset($kerapian[$i]) ? htmlspecialchars($kerapian[$i]['aspek']) : '&nbsp;' ?></td>
                    <td style="text-align:center; font-weight:bold; color:blue;"><?= isset($kerapian[$i]) ? $kerapian[$i]['denda'] : '&nbsp;' ?></td>
                </tr>
                <?php endfor; ?>
                
                <tr style="background-color: #f9f9f9; font-weight: bold;">
                    <td colspan="2" style="text-align: right; padding-right: 10px;">TOTAL DENDA KELAKUAN</td>
                    <td style="text-align:center; color:red;"><?= $tot_kel ?></td>
                    
                    <td class="spacer"></td>
                    
                    <td colspan="2" style="text-align: right; padding-right: 10px;">TOTAL DENDA KERAJINAN</td>
                    <td style="text-align:center; color:red;"><?= $tot_ker ?></td>
                    
                    <td class="spacer"></td>

                    <td colspan="2" style="text-align: right; padding-right: 10px;">TOTAL DENDA KERAPIAN</td>
                    <td style="text-align:center; color:red;"><?= $tot_rap ?></td>
                </tr>
            </tbody>
        </table>

    </div>
</div>

<script>
function downloadPDF() {
    const btn = document.querySelector('.bg-emerald-600');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div> Proses...';
    btn.classList.add('opacity-75', 'cursor-not-allowed');
    btn.disabled = true;

    const element = document.getElementById('dokumen-buku');
    const opt = {
        margin:       [10, 10, 10, 10], 
        filename:     'Buku_Pembinaan_<?= htmlspecialchars($siswa['nama_siswa']) ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, scrollY: 0 },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
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