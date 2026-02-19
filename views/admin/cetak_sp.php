<?php
/**
 * SITAPSI - Cetak Surat Peringatan (COMPLETE - SP PER KATEGORI)
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Peringatan <?= $sp['tingkat_sp'] ?> - <?= $sp['nama_siswa'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .print-full-width { max-width: 100% !important; margin: 0 !important; padding: 20mm !important; }
        }
        @page { size: A4; margin: 20mm; }
    </style>
</head>
<body class="bg-gray-100">

<div class="max-w-4xl mx-auto p-8 print-full-width">
    
    <!-- Tombol Print -->
    <div class="no-print mb-4 flex justify-between items-center">
        <a href="manajemen_sp.php" class="text-gray-600 hover:text-gray-800 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Kembali
        </a>
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Cetak Surat
        </button>
    </div>

    <!-- Surat -->
    <div class="bg-white shadow-lg rounded-lg p-12">
        
        <!-- Kop Surat -->
        <div class="text-center mb-8 pb-6 border-b-4 border-navy">
            <div class="flex items-center justify-center mb-4">
                <div class="w-20 h-20 bg-navy rounded-full flex items-center justify-center mr-4">
                    <span class="text-white font-bold text-3xl">SM2</span>
                </div>
                <div class="text-left">
                    <h1 class="text-2xl font-bold text-navy">SMP KATOLIK SANTA MARIA 2 MALANG</h1>
                    <p class="text-sm text-gray-600">Jl. K.H. Hasyim Ashari No. 15, Malang</p>
                    <p class="text-sm text-gray-600">Telp: (0341) 551234 | Email: info@smpsm2-mlg.sch.id</p>
                </div>
            </div>
        </div>

        <!-- Nomor Surat -->
        <div class="text-center mb-6">
            <p class="text-sm text-gray-600">Nomor: <?= str_pad($sp['id_sp'], 4, '0', STR_PAD_LEFT) ?>/SP/SM2-MLG/<?= date('Y', strtotime($sp['tanggal_terbit'])) ?></p>
        </div>

        <!-- Judul -->
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">SURAT PERINGATAN <?= $sp['tingkat_sp'] ?></h2>
            <p class="text-sm text-gray-600">Tanggal: <?= date('d F Y', strtotime($sp['tanggal_terbit'])) ?></p>
        </div>

        <!-- Isi Surat -->
        <div class="mb-8 text-justify">
            <p class="mb-4">Kepada Yth.</p>
            <p class="mb-4 font-bold">Orang Tua/Wali dari:</p>
            
            <!-- Data Siswa -->
            <table class="w-full text-sm mb-6">
                <tr>
                    <td class="w-1/4 py-1">Nama</td>
                    <td class="w-1/4 py-1">: <strong><?= htmlspecialchars($sp['nama_siswa']) ?></strong></td>
                    <td class="w-1/4 py-1">NIS</td>
                    <td class="w-1/4 py-1">: <?= $sp['nis'] ?></td>
                </tr>
                <tr>
                    <td class="py-1">Kelas</td>
                    <td class="py-1">: <?= $sp['nama_kelas'] ?></td>
                    <td class="py-1">Tingkat SP</td>
                    <td class="py-1">: <strong class="text-red-600"><?= $sp['tingkat_sp'] ?></strong></td>
                </tr>
                <tr>
                    <td class="py-1 align-top">Kategori Pemicu</td>
                    <td class="py-1" colspan="3">: <strong class="text-red-600 text-lg"><?= $sp['kategori_pemicu'] ?></strong></td>
                </tr>
            </table>
            
            <p class="mb-4">Dengan hormat,</p>
            
            <p class="mb-4">
                Dengan ini kami sampaikan bahwa putra/putri Bapak/Ibu telah melakukan pelanggaran tata tertib 
                dengan akumulasi poin pada kategori <strong><?= $sp['kategori_pemicu'] ?></strong> yang telah 
                melampaui batas ketentuan sekolah, sehingga dikenakan <strong><?= $sp['tingkat_sp'] ?></strong>.
            </p>
            
            <!-- Tabel Poin Per Kategori -->
            <div class="my-6">
                <p class="font-bold mb-3">Rincian Akumulasi Poin Per Kategori:</p>
                <table class="w-full text-sm border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 p-3 text-left">Kategori</th>
                            <th class="border border-gray-300 p-3 text-center">Akumulasi Poin</th>
                            <th class="border border-gray-300 p-3 text-center">Status SP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="<?= $sp['kategori_pemicu'] === 'KELAKUAN' ? 'bg-red-50' : '' ?>">
                            <td class="border border-gray-300 p-3">
                                <strong>🚨 KELAKUAN</strong>
                                <?= $sp['kategori_pemicu'] === 'KELAKUAN' ? '<span class="text-red-600 text-xs ml-2">(Pemicu SP)</span>' : '' ?>
                            </td>
                            <td class="border border-gray-300 p-3 text-center">
                                <span class="font-bold text-lg"><?= $sp['poin_kelakuan'] ?></span>
                            </td>
                            <td class="border border-gray-300 p-3 text-center">
                                <span class="px-3 py-1 rounded text-sm font-bold <?= $sp['status_sp_kelakuan'] === 'Aman' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $sp['status_sp_kelakuan'] ?>
                                </span>
                            </td>
                        </tr>
                        <tr class="<?= $sp['kategori_pemicu'] === 'KERAJINAN' ? 'bg-blue-50' : '' ?>">
                            <td class="border border-gray-300 p-3">
                                <strong>📘 KERAJINAN</strong>
                                <?= $sp['kategori_pemicu'] === 'KERAJINAN' ? '<span class="text-blue-600 text-xs ml-2">(Pemicu SP)</span>' : '' ?>
                            </td>
                            <td class="border border-gray-300 p-3 text-center">
                                <span class="font-bold text-lg"><?= $sp['poin_kerajinan'] ?></span>
                            </td>
                            <td class="border border-gray-300 p-3 text-center">
                                <span class="px-3 py-1 rounded text-sm font-bold <?= $sp['status_sp_kerajinan'] === 'Aman' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                                    <?= $sp['status_sp_kerajinan'] ?>
                                </span>
                            </td>
                        </tr>
                        <tr class="<?= $sp['kategori_pemicu'] === 'KERAPIAN' ? 'bg-yellow-50' : '' ?>">
                            <td class="border border-gray-300 p-3">
                                <strong>👔 KERAPIAN</strong>
                                <?= $sp['kategori_pemicu'] === 'KERAPIAN' ? '<span class="text-yellow-600 text-xs ml-2">(Pemicu SP)</span>' : '' ?>
                            </td>
                            <td class="border border-gray-300 p-3 text-center">
                                <span class="font-bold text-lg"><?= $sp['poin_kerapian'] ?></span>
                            </td>
                            <td class="border border-gray-300 p-3 text-center">
                                <span class="px-3 py-1 rounded text-sm font-bold <?= $sp['status_sp_kerapian'] === 'Aman' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                    <?= $sp['status_sp_kerapian'] ?>
                                </span>
                            </td>
                        </tr>
                        <tr class="bg-gray-100 font-bold">
                            <td class="border border-gray-300 p-3">TOTAL POIN</td>
                            <td class="border border-gray-300 p-3 text-center">
                                <span class="text-xl"><?= $sp['total_poin_umum'] ?></span>
                            </td>
                            <td class="border border-gray-300 p-3 text-center">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <p class="text-sm italic text-gray-600 mb-4">
                * Surat ini diterbitkan karena poin kategori <strong><?= $sp['kategori_pemicu'] ?></strong> 
                telah mencapai ambang batas <?= $sp['tingkat_sp'] ?> sesuai peraturan tata tertib sekolah.
            </p>
            
            <p class="mb-4">
                Kami mohon perhatian dan kerjasama Bapak/Ibu untuk memberikan bimbingan kepada putra/putri 
                agar dapat memperbaiki perilaku dan tidak mengulangi kesalahan yang sama.
            </p>
            
            <p class="mb-4">
                Demikian surat peringatan ini kami sampaikan. Atas perhatian dan kerjasamanya, 
                kami ucapkan terima kasih.
            </p>
        </div>

        <!-- Tanda Tangan -->
        <div class="grid grid-cols-2 gap-8 mt-12">
            <div class="text-center">
                <p class="mb-16">Orang Tua/Wali,</p>
                <p class="font-bold border-b border-gray-800 inline-block pb-1 px-8">
                    (<?= htmlspecialchars($sp['nama_ortu']) ?>)
                </p>
            </div>
            <div class="text-center">
                <p class="mb-1">Malang, <?= date('d F Y', strtotime($sp['tanggal_terbit'])) ?></p>
                <p class="mb-12">Kepala Sekolah,</p>
                <p class="font-bold border-b border-gray-800 inline-block pb-1 px-8">
                    (Dra. Maria Theresia)
                </p>
            </div>
        </div>

        <!-- Catatan Kaki -->
        <div class="mt-8 pt-4 border-t border-gray-300 text-xs text-gray-600">
            <p>* Surat ini dibuat oleh sistem dan sah tanpa tanda tangan basah.</p>
            <p>* Mohon mengembalikan surat ini yang telah ditandatangani orang tua/wali ke sekolah.</p>
        </div>

    </div>

</div>

</body>
</html>