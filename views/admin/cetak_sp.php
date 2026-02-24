<?php
/**
 * SITAPSI - Cetak Surat Peringatan (UI GLOBAL PORTAL)
 * Surat dengan info kategori pemicu dan breakdown poin
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

// Query SP dengan detail lengkap
$sp = fetchOne("
    SELECT 
        sp.*,
        s.nama_siswa,
        s.nis,
        s.nama_ortu,
        s.alamat_ortu,
        k.nama_kelas,
        a.poin_kelakuan,
        a.poin_kerajinan,
        a.poin_kerapian,
        a.total_poin_umum,
        a.status_sp_kelakuan,
        a.status_sp_kerajinan,
        a.status_sp_kerapian
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

// --- UI CONFIG VARIABLES ---
$btn_primary = "px-6 py-2.5 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-md shadow-blue-900/10 hover:bg-blue-900 transition-all flex items-center justify-center";
$btn_outline = "px-6 py-2.5 bg-white border border-[#E2E8F0] text-slate-700 text-sm font-semibold rounded-lg shadow-sm hover:bg-slate-50 transition-all flex items-center justify-center";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak SP <?= $sp['tingkat_sp'] ?> - <?= htmlspecialchars($sp['nama_siswa']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Pengaturan Khusus Print (Kertas A4) */
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-container { 
                box-shadow: none !important; 
                border: none !important; 
                max-width: 100% !important; 
                margin: 0 !important; 
                padding: 0 !important; 
            }
            .print-table th, .print-table td { border-color: #000 !important; }
        }
        @page { size: A4; margin: 20mm; }
    </style>
</head>
<body class="bg-[#F8FAFC] text-slate-800 min-h-screen py-8 print:py-0">

<div class="max-w-4xl mx-auto px-4 print:px-0">
    
    <div class="no-print mb-6 flex justify-between items-center bg-white p-4 rounded-xl border border-[#E2E8F0] shadow-sm">
        <a href="manajemen_sp.php" class="<?= $btn_outline ?>">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Kembali
        </a>
        <div class="flex items-center space-x-3">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider hidden sm:inline-block">Pratinjau Surat</span>
            <button onclick="window.print()" class="<?= $btn_primary ?>">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Cetak ke A4
            </button>
        </div>
    </div>

    <div class="print-container bg-white shadow-xl rounded-2xl border border-[#E2E8F0] p-10 sm:p-14 mb-8">
        
        <div class="text-center mb-8 pb-6 border-b-[3px] border-[#000080]">
            <div class="flex items-center justify-center mb-2">
                <div class="w-20 h-20 bg-[#000080] rounded-full flex items-center justify-center mr-5 shadow-sm">
                    <span class="text-white font-extrabold text-3xl tracking-wider">SM2</span>
                </div>
                <div class="text-left">
                    <h1 class="text-2xl font-extrabold text-[#000080] tracking-tight">SMP KATOLIK SANTA MARIA 2 MALANG</h1>
                    <p class="text-sm font-medium text-slate-600 mt-1">Jl. K.H. Hasyim Ashari No. 15, Malang</p>
                    <p class="text-sm font-medium text-slate-600">Telp: (0341) 551234 | Email: info@smpsm2-mlg.sch.id</p>
                </div>
            </div>
        </div>

        <div class="text-center mb-6">
            <p class="text-sm font-bold text-slate-600">Nomor: <?= str_pad($sp['id_sp'], 4, '0', STR_PAD_LEFT) ?>/SP/SM2-MLG/<?= date('Y', strtotime($sp['tanggal_terbit'])) ?></p>
        </div>

        <div class="text-center mb-10">
            <h2 class="text-2xl font-extrabold text-slate-800 mb-1 tracking-tight border-b border-slate-800 inline-block pb-1">SURAT PERINGATAN <?= $sp['tingkat_sp'] ?></h2>
            <p class="text-sm font-medium text-slate-500 mt-2">Tanggal: <?= date('d F Y', strtotime($sp['tanggal_terbit'])) ?></p>
        </div>

        <div class="mb-8 text-justify text-slate-800 text-[15px] leading-relaxed">
            <p class="mb-4">Kepada Yth.</p>
            <p class="mb-5 font-bold">Orang Tua/Wali dari:</p>
            
            <div class="ml-6 mb-6">
                <table class="w-full text-[15px]">
                    <tr>
                        <td class="w-1/4 py-1.5 font-medium">Nama</td>
                        <td class="py-1.5">: <strong class="text-slate-900"><?= htmlspecialchars($sp['nama_siswa']) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="py-1.5 font-medium">NIS</td>
                        <td class="py-1.5">: <?= htmlspecialchars($sp['nis']) ?></td>
                    </tr>
                    <tr>
                        <td class="py-1.5 font-medium">Kelas</td>
                        <td class="py-1.5">: <?= htmlspecialchars($sp['nama_kelas']) ?></td>
                    </tr>
                    <tr>
                        <td class="py-1.5 align-top font-medium mt-2 block">Tingkat SP</td>
                        <td class="py-1.5 align-top">: <strong class="bg-red-100 text-red-700 px-2 py-0.5 rounded ml-1"><?= $sp['tingkat_sp'] ?></strong></td>
                    </tr>
                    <tr>
                        <td class="py-1.5 align-top font-medium">Kategori Pemicu</td>
                        <td class="py-1.5 align-top">: <strong class="text-red-600 ml-1"><?= $sp['kategori_pemicu'] ?></strong></td>
                    </tr>
                </table>
            </div>
            
            <p class="mb-4">Dengan hormat,</p>
            
            <p class="mb-4">
                Berdasarkan rekapitulasi tata tertib siswa, dengan ini kami sampaikan bahwa putra/putri Bapak/Ibu 
                telah mencapai akumulasi poin pelanggaran pada kategori <strong class="text-red-600 underline"><?= $sp['kategori_pemicu'] ?></strong> 
                yang telah melampaui batas ketentuan sekolah. Oleh karena itu, pihak sekolah memberikan <strong class="font-bold"><?= $sp['tingkat_sp'] ?></strong>.
            </p>
            
            <div class="my-8">
                <p class="font-bold mb-3 text-sm">Rincian Akumulasi Poin Per Kategori saat ini:</p>
                <table class="w-full text-sm border-collapse border border-slate-300 print-table">
                    <thead>
                        <tr class="bg-slate-100 print:bg-slate-100 text-slate-700">
                            <th class="border border-slate-300 p-3 text-left w-1/2">Aspek Kedisiplinan (Silo)</th>
                            <th class="border border-slate-300 p-3 text-center">Akumulasi Poin</th>
                            <th class="border border-slate-300 p-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="<?= $sp['kategori_pemicu'] === 'KELAKUAN' ? 'bg-red-50/50 print:bg-transparent' : '' ?>">
                            <td class="border border-slate-300 p-3 font-medium">
                                KELAKUAN
                                <?= $sp['kategori_pemicu'] === 'KELAKUAN' ? '<span class="text-red-600 text-[10px] ml-2 font-bold bg-red-100 px-1.5 py-0.5 rounded print:border print:border-red-600">PEMICU</span>' : '' ?>
                            </td>
                            <td class="border border-slate-300 p-3 text-center">
                                <span class="font-bold text-base"><?= $sp['poin_kelakuan'] ?></span>
                            </td>
                            <td class="border border-slate-300 p-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider <?= $sp['status_sp_kelakuan'] === 'Aman' ? 'bg-emerald-100 text-emerald-700 print:border print:border-emerald-600' : 'bg-red-100 text-red-700 print:border print:border-red-600' ?>">
                                    <?= $sp['status_sp_kelakuan'] ?>
                                </span>
                            </td>
                        </tr>
                        <tr class="<?= $sp['kategori_pemicu'] === 'KERAJINAN' ? 'bg-blue-50/50 print:bg-transparent' : '' ?>">
                            <td class="border border-slate-300 p-3 font-medium">
                                KERAJINAN
                                <?= $sp['kategori_pemicu'] === 'KERAJINAN' ? '<span class="text-blue-600 text-[10px] ml-2 font-bold bg-blue-100 px-1.5 py-0.5 rounded print:border print:border-blue-600">PEMICU</span>' : '' ?>
                            </td>
                            <td class="border border-slate-300 p-3 text-center">
                                <span class="font-bold text-base"><?= $sp['poin_kerajinan'] ?></span>
                            </td>
                            <td class="border border-slate-300 p-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider <?= $sp['status_sp_kerajinan'] === 'Aman' ? 'bg-emerald-100 text-emerald-700 print:border print:border-emerald-600' : 'bg-red-100 text-red-700 print:border print:border-red-600' ?>">
                                    <?= $sp['status_sp_kerajinan'] ?>
                                </span>
                            </td>
                        </tr>
                        <tr class="<?= $sp['kategori_pemicu'] === 'KERAPIAN' ? 'bg-yellow-50/50 print:bg-transparent' : '' ?>">
                            <td class="border border-slate-300 p-3 font-medium">
                                KERAPIAN
                                <?= $sp['kategori_pemicu'] === 'KERAPIAN' ? '<span class="text-yellow-600 text-[10px] ml-2 font-bold bg-yellow-100 px-1.5 py-0.5 rounded print:border print:border-yellow-600">PEMICU</span>' : '' ?>
                            </td>
                            <td class="border border-slate-300 p-3 text-center">
                                <span class="font-bold text-base"><?= $sp['poin_kerapian'] ?></span>
                            </td>
                            <td class="border border-slate-300 p-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider <?= $sp['status_sp_kerapian'] === 'Aman' ? 'bg-emerald-100 text-emerald-700 print:border print:border-emerald-600' : 'bg-red-100 text-red-700 print:border print:border-red-600' ?>">
                                    <?= $sp['status_sp_kerapian'] ?>
                                </span>
                            </td>
                        </tr>
                        <tr class="bg-slate-100 print:bg-slate-100 font-bold text-slate-800">
                            <td class="border border-slate-300 p-3 text-right">TOTAL POIN GABUNGAN:</td>
                            <td class="border border-slate-300 p-3 text-center">
                                <span class="text-lg"><?= $sp['total_poin_umum'] ?></span>
                            </td>
                            <td class="border border-slate-300 p-3 text-center">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <p class="mb-4">
                Kami sangat memohon perhatian dan kerjasama Bapak/Ibu untuk memberikan bimbingan serta pengawasan 
                kepada putra/putri Bapak/Ibu agar dapat memperbaiki kedisiplinan dan tidak mengulangi pelanggaran tata tertib sekolah.
            </p>
            
            <p class="mb-4">
                Demikian surat peringatan ini kami sampaikan untuk menjadi perhatian. Atas kerjasama Bapak/Ibu, kami ucapkan terima kasih.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-8 mt-16 text-[15px]">
            <div class="text-center">
                <p class="mb-24">Orang Tua/Wali Siswa,</p>
                <p class="font-bold border-b border-slate-800 inline-block pb-1 px-10">
                    (<?= htmlspecialchars($sp['nama_ortu']) ?>)
                </p>
            </div>
            <div class="text-center">
                <p class="mb-1">Malang, <?= date('d F Y', strtotime($sp['tanggal_terbit'])) ?></p>
                <p class="mb-20">Kepala Sekolah,</p>
                <p class="font-bold border-b border-slate-800 inline-block pb-1 px-10">
                    (Dra. Maria Theresia)
                </p>
            </div>
        </div>

        <div class="mt-12 pt-4 border-t border-slate-300 text-[11px] text-slate-500 font-medium">
            <p class="mb-1"><strong>Catatan:</strong></p>
            <p>1. Surat Peringatan ini dihasilkan oleh Sistem Informasi Tata Tertib (SITAPSI) dan sah secara administratif.</p>
            <p>2. Mohon kembalikan surat ini (fotokopi/potongan) yang telah ditandatangani oleh orang tua/wali ke Wali Kelas / Guru BK terkait selambat-lambatnya 3 hari setelah diterima.</p>
        </div>

    </div>

</div>

</body>
</html>