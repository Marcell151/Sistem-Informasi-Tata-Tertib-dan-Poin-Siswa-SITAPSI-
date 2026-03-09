<?php
/**
 * SITAPSI - Cetak Surat Peringatan (TEMPLATE ASLI SEKOLAH)
 * Desain kertas A4 disesuaikan 100% dengan format fisik sekolah.
 * Teks Pembinaan/Sanksi digenerate otomatis berdasarkan tingkat SP sesuai Buku Tatibsi.
 * Penyesuaian: Fix Halaman Kedua Kosong (Limit Max Height & Pagebreak Avoid).
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_sp = $_GET['id'] ?? null;

if (!$id_sp) {
    $_SESSION['error_message'] = '❌ ID SP tidak valid';
    header('Location: manajemen_sp.php');
    exit;
}

// Query SP dengan detail lengkap siswa
$sp = fetchOne("
    SELECT 
        sp.*,
        s.nama_siswa,
        s.no_induk,
        s.nama_ayah,
        s.nama_ibu,
        k.nama_kelas,
        a.id_kelas,
        a.id_tahun,
        a.poin_kelakuan,
        a.poin_kerajinan,
        a.poin_kerapian,
        a.total_poin_umum
    FROM tb_riwayat_sp sp
    JOIN tb_anggota_kelas a ON sp.id_anggota = a.id_anggota
    JOIN tb_siswa s ON a.no_induk = s.no_induk
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE sp.id_sp = :id
", ['id' => $id_sp]);

if (!$sp) {
    $_SESSION['error_message'] = '❌ Data SP tidak ditemukan';
    header('Location: manajemen_sp.php');
    exit;
}

// =========================================================================
// MENCARI NOMOR URUT (ABSEN) BERDASARKAN ABJAD
// =========================================================================
$query_absen = fetchOne("
    SELECT COUNT(*) as no_absen 
    FROM tb_anggota_kelas ak
    JOIN tb_siswa ts ON ak.no_induk = ts.no_induk
    WHERE ak.id_kelas = :id_kelas 
      AND ak.id_tahun = :id_tahun 
      AND ts.nama_siswa <= :nama_siswa
", [
    'id_kelas' => $sp['id_kelas'],
    'id_tahun' => $sp['id_tahun'],
    'nama_siswa' => $sp['nama_siswa']
]);

$no_urut = $query_absen ? $query_absen['no_absen'] : '-';

// Konversi Angka Bulan ke Romawi
function getRomawi($bulan) {
    $map = ['01'=>'I','02'=>'II','03'=>'III','04'=>'IV','05'=>'V','06'=>'VI','07'=>'VII','08'=>'VIII','09'=>'IX','10'=>'X','11'=>'XI','12'=>'XII'];
    return $map[$bulan] ?? 'I';
}

// =========================================================================
// KAMUS BULAN BAHASA INDONESIA
// =========================================================================
$bulan_indo = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

$hari_terbit  = date('d', strtotime($sp['tanggal_terbit']));
$bulan_terbit = date('m', strtotime($sp['tanggal_terbit']));
$tahun_terbit = date('Y', strtotime($sp['tanggal_terbit']));

// Format Tanggal jadi: 09 Maret 2026
$tanggal_indo = $hari_terbit . ' ' . $bulan_indo[$bulan_terbit] . ' ' . $tahun_terbit;
$romawi_bulan = getRomawi($bulan_terbit);

// Format Nomor SP
$no_surat = str_pad($sp['id_sp'], 3, '0', STR_PAD_LEFT) . " / SM.2 / F4/ " . $romawi_bulan . " / " . $tahun_terbit;

// =========================================================================
// PENYESUAIAN SESUAI BUKU TATIBSI
// =========================================================================
$tingkat_sp_romawi = 'I';
$teks_pembinaan = 'Wali Kelas'; // SP 1

if (strpos($sp['tingkat_sp'], '2') !== false) {
    $tingkat_sp_romawi = 'II';
    $teks_pembinaan = 'guru Bimbingan Konseling (BK)'; // SP 2
} elseif (strpos($sp['tingkat_sp'], '3') !== false) {
    $tingkat_sp_romawi = 'III';
    $teks_pembinaan = 'Tim Tatibsi'; // SP 3
}

// Menentukan Poin Pemicu
$poin_pemicu = 0;
if ($sp['kategori_pemicu'] === 'KELAKUAN') $poin_pemicu = $sp['poin_kelakuan'];
elseif ($sp['kategori_pemicu'] === 'KERAJINAN') $poin_pemicu = $sp['poin_kerajinan'];
elseif ($sp['kategori_pemicu'] === 'KERAPIAN') $poin_pemicu = $sp['poin_kerapian'];

// UI Configuration (Tombol Sesuai Screenshot)
$btn_primary = "px-5 py-2 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-blue-900 transition-all flex items-center justify-center cursor-pointer";
$btn_success = "px-5 py-2 bg-[#219653] text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-emerald-700 transition-all flex items-center justify-center cursor-pointer";
$btn_outline = "px-5 py-2 bg-white border border-[#E2E8F0] text-slate-700 text-sm font-semibold rounded-lg shadow-sm hover:bg-slate-50 transition-all flex items-center justify-center cursor-pointer";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak SP <?= $tingkat_sp_romawi ?> - <?= htmlspecialchars($sp['nama_siswa']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap');
        .font-surat { font-family: 'Tinos', 'Times New Roman', Times, serif; }
        
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; margin: 0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-container { 
                box-shadow: none !important; 
                border: none !important; 
                max-width: 100% !important; 
                width: 210mm !important;
                margin: 0 !important; 
                padding: 10mm 15mm !important; 
            }
        }
        @page { size: A4 portrait; margin: 0; }
    </style>
</head>
<body class="bg-[#F8FAFC] text-black min-h-screen py-8 print:py-0">

<div class="max-w-4xl mx-auto px-4 print:px-0">
    
    <div class="no-print mb-6 flex flex-wrap justify-between items-center bg-white p-4 rounded-xl border border-[#E2E8F0] shadow-sm font-sans gap-3">
        <button onclick="tutupTab()" class="<?= $btn_outline ?>">
            <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
            Tutup Tab
        </button>
        
        <div class="flex items-center space-x-3 flex-wrap gap-y-2">
            <button onclick="downloadPDF()" class="<?= $btn_success ?>" id="btn-download">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Download PDF
            </button>
            <button onclick="window.print()" class="<?= $btn_primary ?>">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Cetak Printer
            </button>
        </div>
    </div>

    <div id="dokumen-sp" class="print-container bg-white shadow-xl rounded-none sm:rounded-xl border border-[#E2E8F0] px-8 py-8 sm:px-12 sm:py-8 font-surat mx-auto" style="width: 210mm; height: 296mm; max-height: 296mm; overflow: hidden; box-sizing: border-box;">
        
        <div class="flex items-center justify-between mb-4">
            <div class="w-24 text-center">
                <div class="w-16 h-20 border border-black mx-auto flex items-center justify-center text-[10px]">Logo Yayasan</div>
            </div>
            
            <div class="flex-1 text-center px-4">
                <p class="text-lg italic font-bold leading-tight">Perkumpulan Dharmaputri</p>
                <h1 class="text-2xl font-bold tracking-wide mt-1 mb-0.5">SMP KATOLIK SANTA MARIA II</h1>
                <p class="text-[13px] font-bold tracking-widest mb-1">SEKOLAH STANDAR NASIONAL</p>
                
                <div class="flex justify-between text-[11px] leading-tight px-4 mt-1">
                    <div class="text-left">
                        <p>STATUS TERAKREDITASI "A"</p>
                        <p>NSS : 203056101019</p>
                        <p>NPSN : 20533743</p>
                    </div>
                    <div class="text-right">
                        <p>Website : www.smpksantamaria2-mlg.sch.id</p>
                        <p>E-mail : infogk.smpksantamaria2mlg.sch.id</p>
                        <p>smpksantamaria2mlg@gmail.com</p>
                    </div>
                </div>
            </div>
            
            <div class="w-24 text-center">
                <div class="w-16 h-16 border border-black rounded-full mx-auto flex items-center justify-center text-[10px]">Logo ISO</div>
                <p class="text-[8px] mt-1">CN 40664/A/0001/UK/En</p>
            </div>
        </div>

        <div class="bg-black text-white text-center py-1 text-[11px] mb-6 w-full font-sans" style="-webkit-print-color-adjust: exact; print-color-adjust: exact;">
            Jl. Panderman 7A Telp.(0341) 551 871 Fax.(0341) 576430 MALANG 65115
        </div>

        <div class="text-center mb-6">
            <h2 class="text-lg font-bold tracking-widest underline underline-offset-4 mb-1">SURAT PERINGATAN <?= $tingkat_sp_romawi ?></h2>
            <p class="text-[14px] font-bold">No : <?= $no_surat ?></p>
        </div>

        <div class="text-[14px] leading-snug mb-5 text-justify">
            <p class="mb-4">
                Yth. Orangtua / Wali siswa dari <strong><?= mb_strtoupper($sp['nama_siswa']) ?></strong><br>
                di tempat
            </p>
            
            <p class="mb-3">Dengan hormat,</p>
            <p class="mb-3">
                Dengan perantaraan surat ini kami memberitahukan bahwa sampai dengan tanggal <strong><?= $tanggal_indo ?></strong>
                putra Bapak / Ibu :
            </p>
            
            <table class="w-full ml-8 mb-3">
                <tr>
                    <td class="w-32 align-top py-0.5">Nama</td>
                    <td class="align-top py-0.5">: <strong><?= mb_strtoupper($sp['nama_siswa']) ?></strong></td>
                </tr>
                <tr>
                    <td class="w-32 align-top py-0.5">Kelas / No.urut</td>
                    <td class="align-top py-0.5">: <strong><?= mb_strtoupper($sp['nama_kelas']) ?> / <?= $no_urut ?></strong></td>
                </tr>
            </table>
            
            <p class="mb-3">
                Telah melakukan pelanggaran aspek <em><strong><?= mb_strtoupper($sp['kategori_pemicu']) ?></strong></em> dengan jumlah denda <strong><?= $poin_pemicu ?></strong>.
                Pada tahap ini putra Bapak / Ibu mendapat pembinaan dari <strong><?= $teks_pembinaan ?></strong>.
            </p>
            
            <p class="mb-3">
                Diharapkan bantuan dan kerjasama yang baik dari pihak orangtua / wali siswa dalam pembinaan
                kepribadian putra Bapak / Ibu.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-4 text-[14px] mb-6">
            <div class="text-center">
                <p class="mb-1">Mengetahui,</p>
                <p class="mb-16">Kepala Sekolah</p>
                <p class="font-bold underline underline-offset-4">Sr. M. Elfrida, SPM. S.Psi.,M.M.</p>
            </div>
            <div class="text-center">
                <p class="mb-1">Malang, <?= $tanggal_indo ?></p>
                <p class="mb-16">Koord. Tim Tatibsi</p>
                <p class="font-bold underline underline-offset-4">Agnes Herawaty, S.E.M.M.</p>
            </div>
        </div>

        <div class="flex items-center text-sm text-slate-500 mb-5 mt-4">
            <svg class="w-5 h-5 mr-2 -ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"></path></svg>
            <div class="flex-1 border-t-2 border-dashed border-slate-500 relative">
                <span class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white px-2 italic text-[12px]">Gunting disini</span>
            </div>
        </div>

        <div class="border-2 border-black p-4 text-[13px]">
            <h3 class="text-center font-bold text-[15px] mb-3">Bukti Penerimaan Surat Peringatan</h3>
            
            <p class="mb-2">Kami telah menerima <strong>SURAT PERINGATAN <?= $tingkat_sp_romawi ?></strong> tertanggal <strong><?= $tanggal_indo ?></strong> atas nama putra/putri kami :</p>
            
            <table class="w-full mb-3">
                <tr>
                    <td class="w-32 py-0.5">Nama</td>
                    <td class="py-0.5">: <strong><?= mb_strtoupper($sp['nama_siswa']) ?></strong></td>
                </tr>
                <tr>
                    <td class="w-32 py-0.5">Kelas / No.urut</td>
                    <td class="py-0.5">: <strong><?= mb_strtoupper($sp['nama_kelas']) ?> / <?= $no_urut ?></strong></td>
                </tr>
            </table>
            
            <div class="grid grid-cols-2 gap-4 text-center mt-4">
                <div>
                    <p class="mb-14">Mengetahui,<br>Wali Kelas <?= mb_strtoupper($sp['nama_kelas']) ?></p>
                    <p>( ........................................................ )</p>
                </div>
                <div>
                    <p class="mb-14 mt-4">Orangtua / Wali siswa</p>
                    <p>( ........................................................ )</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // Logika Tombol Tutup Tab
    function tutupTab() {
        if(window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = 'manajemen_sp.php';
        }
    }

    // Logika Download PDF
    function downloadPDF() {
        const btn = document.getElementById('btn-download');
        const originalText = btn.innerHTML;
        btn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memproses...`;
        btn.disabled = true;

        const element = document.getElementById('dokumen-sp');
        
        // Nama file otomatis mengikuti nama siswa
        const namaSiswa = "<?= htmlspecialchars($sp['nama_siswa'], ENT_QUOTES) ?>".replace(/[^a-zA-Z0-9]/g, '_');
        const tingkatSP = "<?= $tingkat_sp_romawi ?>";
        const fileName = `Surat_Peringatan_${tingkatSP}_${namaSiswa}.pdf`;

        // FIX 2 HALAMAN: Tambahkan pagebreak: mode avoid-all dan scrollY
        const opt = {
            margin:       0,
            filename:     fileName,
            pagebreak:    { mode: 'avoid-all' },
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, scrollY: 0 },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(element).save().then(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
</script>

</body>
</html>