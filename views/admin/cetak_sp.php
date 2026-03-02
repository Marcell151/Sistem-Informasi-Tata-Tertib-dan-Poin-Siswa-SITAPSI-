<?php
/**
 * SITAPSI - Cetak Surat Peringatan (TEMPLATE ASLI SEKOLAH)
 * Desain kertas A4 disesuaikan 100% dengan format fisik sekolah.
 * Teks Pembinaan/Sanksi digenerate otomatis berdasarkan tingkat SP.
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
        s.nis,
        s.nama_ortu,
        k.nama_kelas,
        a.poin_kelakuan,
        a.poin_kerajinan,
        a.poin_kerapian,
        a.total_poin_umum
    FROM tb_riwayat_sp sp
    JOIN tb_anggota_kelas a ON sp.id_anggota = a.id_anggota
    JOIN tb_siswa s ON a.nis = s.nis
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    WHERE sp.id_sp = :id
", ['id' => $id_sp]);

if (!$sp) {
    $_SESSION['error_message'] = '❌ Data SP tidak ditemukan';
    header('Location: manajemen_sp.php');
    exit;
}

// Konversi Angka Bulan ke Romawi untuk Nomor Surat
function getRomawi($bulan) {
    $map = ['01'=>'I','02'=>'II','03'=>'III','04'=>'IV','05'=>'V','06'=>'VI','07'=>'VII','08'=>'VIII','09'=>'IX','10'=>'X','11'=>'XI','12'=>'XII'];
    return $map[$bulan] ?? 'I';
}

$bulan_terbit = date('m', strtotime($sp['tanggal_terbit']));
$tahun_terbit = date('Y', strtotime($sp['tanggal_terbit']));
$tanggal_indo = date('d F Y', strtotime($sp['tanggal_terbit']));
$romawi_bulan = getRomawi($bulan_terbit);

// Format Nomor SP (Contoh: 009 / SM.2 / F4/ I / 2026)
$no_surat = str_pad($sp['id_sp'], 3, '0', STR_PAD_LEFT) . " / SM.2 / F4/ " . $romawi_bulan . " / " . $tahun_terbit;

// Menentukan Angka Romawi Tingkat SP & Teks Pembinaan/Sanksi (Sesuai Permintaan)
$tingkat_sp_romawi = 'I';
$teks_pembinaan = 'Wali Kelas dan Bimbingan Konseling';

if (strpos($sp['tingkat_sp'], '2') !== false) {
    $tingkat_sp_romawi = 'II';
    $teks_pembinaan = 'Koordinator Tatibsi dan Bimbingan Konseling';
} elseif (strpos($sp['tingkat_sp'], '3') !== false) {
    $tingkat_sp_romawi = 'III';
    $teks_pembinaan = 'Kepala Sekolah dan Bimbingan Konseling';
}

// Menentukan Poin Pemicu
$poin_pemicu = 0;
if ($sp['kategori_pemicu'] === 'KELAKUAN') $poin_pemicu = $sp['poin_kelakuan'];
elseif ($sp['kategori_pemicu'] === 'KERAJINAN') $poin_pemicu = $sp['poin_kerajinan'];
elseif ($sp['kategori_pemicu'] === 'KERAPIAN') $poin_pemicu = $sp['poin_kerapian'];

// UI Configuration untuk tampilan Web (Non-Print)
$btn_primary = "px-6 py-2.5 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-md hover:bg-blue-900 transition-all flex items-center justify-center";
$btn_outline = "px-6 py-2.5 bg-white border border-[#E2E8F0] text-slate-700 text-sm font-semibold rounded-lg shadow-sm hover:bg-slate-50 transition-all flex items-center justify-center";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak SP <?= $tingkat_sp_romawi ?> - <?= htmlspecialchars($sp['nama_siswa']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Menggunakan Font Serif (Times New Roman) untuk nuansa dokumen resmi */
        @import url('https://fonts.googleapis.com/css2?family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap');
        
        .font-surat { font-family: 'Tinos', 'Times New Roman', Times, serif; }
        
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; margin: 0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-container { 
                box-shadow: none !important; 
                border: none !important; 
                max-width: 100% !important; 
                width: 100% !important;
                margin: 0 !important; 
                padding: 10mm 15mm !important; 
            }
        }
        @page { size: A4; margin: 0; }
    </style>
</head>
<body class="bg-[#F8FAFC] text-black min-h-screen py-8 print:py-0">

<div class="max-w-4xl mx-auto px-4 print:px-0">
    
    <div class="no-print mb-6 flex justify-between items-center bg-white p-4 rounded-xl border border-[#E2E8F0] shadow-sm font-sans">
        <a href="manajemen_sp.php" class="<?= $btn_outline ?>">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Kembali
        </a>
        <div class="flex items-center space-x-3">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider hidden sm:inline-block">Pratinjau Kertas A4</span>
            <button onclick="window.print()" class="<?= $btn_primary ?>">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Cetak Dokumen
            </button>
        </div>
    </div>

    <div class="print-container bg-white shadow-xl rounded-none sm:rounded-xl border border-[#E2E8F0] px-10 py-12 sm:px-16 sm:py-14 font-surat mx-auto" style="width: 210mm; min-height: 297mm; box-sizing: border-box;">
        
        <div class="flex items-center justify-between mb-4">
            <div class="w-24 text-center">
                <div class="w-16 h-20 border border-black mx-auto flex items-center justify-center text-[10px]">Logo Yayasan</div>
            </div>
            
            <div class="flex-1 text-center px-4">
                <p class="text-lg italic font-bold leading-tight">Perkumpulan Dharmaputri</p>
                <h1 class="text-2xl font-bold tracking-wide mt-1 mb-0.5">SMP KATOLIK SANTA MARIA II</h1>
                <p class="text-sm font-bold tracking-widest mb-1">SEKOLAH STANDAR NASIONAL</p>
                
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

        <div class="bg-black text-white text-center py-1 text-xs mb-6 w-full" style="-webkit-print-color-adjust: exact; print-color-adjust: exact;">
            Jl. Panderman 7A Telp.(0341) 551 871 Fax.(0341) 576430 MALANG 65115
        </div>

        <div class="text-center mb-8">
            <h2 class="text-xl font-bold tracking-widest underline underline-offset-4 mb-1">SURAT PERINGATAN <?= $tingkat_sp_romawi ?></h2>
            <p class="text-[15px] font-bold">No : <?= $no_surat ?></p>
        </div>

        <div class="text-[15px] leading-relaxed mb-10 text-justify">
            <p class="mb-5">
                Yth. Orangtua / Wali siswa dari <strong><?= mb_strtoupper($sp['nama_siswa']) ?></strong><br>
                di tempat
            </p>
            
            <p class="mb-4">Dengan hormat,</p>
            <p class="mb-4">
                Dengan perantaraan surat ini kami memberitahukan bahwa sampai dengan tanggal <strong><?= $tanggal_indo ?></strong>
                putra Bapak / Ibu :
            </p>
            
            <table class="w-full ml-8 mb-4">
                <tr>
                    <td class="w-36 align-top">Nama</td>
                    <td class="align-top">: <strong><?= mb_strtoupper($sp['nama_siswa']) ?></strong></td>
                </tr>
                <tr>
                    <td class="w-36 align-top">Kelas / No.urut</td>
                    <td class="align-top">: <strong><?= mb_strtoupper($sp['nama_kelas']) ?> / -</strong></td>
                </tr>
            </table>
            
            <p class="mb-4">
                Telah melakukan pelanggaran aspek <em><strong><?= mb_strtoupper($sp['kategori_pemicu']) ?></strong></em> dengan jumlah denda <strong><?= $poin_pemicu ?></strong>.
                <br>
                Pada tahap ini putra Bapak / Ibu mendapat pembinaan dari <strong><?= $teks_pembinaan ?></strong>.
            </p>
            
            <p class="mb-4">
                Diharapkan bantuan dan kerjasama yang baik dari pihak orangtua / wali siswa dalam pembinaan
                kepribadian putra Bapak / Ibu.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-8 text-[15px] mb-12">
            <div class="text-center">
                <p class="mb-1">Mengetahui,</p>
                <p class="mb-24">Kepala Sekolah</p>
                <p class="font-bold underline underline-offset-4">Sr. M. Elfrida, SPM. S.Psi.,M.M.</p>
            </div>
            <div class="text-center">
                <p class="mb-1">Malang, <?= $tanggal_indo ?></p>
                <p class="mb-24">Koord. Tim Tatibsi</p>
                <p class="font-bold underline underline-offset-4">Agnes Herawaty, S.E.M.M.</p>
            </div>
        </div>

        <div class="flex items-center text-sm text-slate-500 mb-6">
            <svg class="w-5 h-5 mr-2 -ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"></path></svg>
            <div class="flex-1 border-t-2 border-dashed border-slate-500 relative">
                <span class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white px-2">Gunting disini</span>
            </div>
        </div>

        <div class="border-2 border-black p-5 text-[14px]">
            <h3 class="text-center font-bold text-lg mb-4">Bukti Penerimaan Surat Peringatan</h3>
            
            <p class="mb-3">Kami telah menerima <strong>SURAT PERINGATAN <?= $tingkat_sp_romawi ?></strong> tertanggal <strong><?= $tanggal_indo ?></strong> atas nama putra kami :</p>
            
            <table class="w-full mb-6">
                <tr>
                    <td class="w-32 py-1">Nama</td>
                    <td class="py-1">: ......................................................................................................................................</td>
                </tr>
                <tr>
                    <td class="py-1">Kelas / No.urut</td>
                    <td class="py-1">: ......................................................................................................................................</td>
                </tr>
            </table>
            
            <div class="grid grid-cols-2 gap-8 text-center mt-8">
                <div>
                    <p class="mb-20">Mengetahui,<br>Wali Kelas <?= mb_strtoupper($sp['nama_kelas']) ?></p>
                    <p>( ........................................................ )</p>
                </div>
                <div>
                    <p class="mb-20 mt-5">Orangtua / Wali siswa</p>
                    <p>( ........................................................ )</p>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>