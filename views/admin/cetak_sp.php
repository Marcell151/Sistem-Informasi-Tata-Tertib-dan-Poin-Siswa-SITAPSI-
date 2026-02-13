<?php
/**
 * SITAPSI - Cetak Surat Peringatan (PDF/Print)
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_sp = $_GET['id'] ?? null;

if (!$id_sp) {
    die("ID SP tidak valid");
}

// Ambil data SP
$sp = fetchOne("
    SELECT 
        sp.tingkat_sp,
        sp.kategori_pemicu,
        sp.tanggal_terbit,
        s.nis,
        s.nama_siswa,
        s.nama_ortu,
        s.alamat_ortu,
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
    die("Data SP tidak ditemukan");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Peringatan - <?= $sp['nama_siswa'] ?></title>
    <style>
        @media print {
            .no-print { display: none; }
        }
        body {
            font-family: 'Times New Roman', serif;
            margin: 40px;
            line-height: 1.6;
        }
        .kop-surat {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .kop-surat h2 {
            margin: 5px 0;
            font-size: 18px;
        }
        .kop-surat p {
            margin: 3px 0;
            font-size: 12px;
        }
        .nomor-surat {
            margin: 20px 0;
            text-align: center;
        }
        .isi-surat {
            margin: 20px 0;
            text-align: justify;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        .ttd {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .ttd-section {
            width: 45%;
            text-align: center;
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="no-print" style="position: fixed; top: 10px; right: 10px; padding: 10px 20px; background: #000080; color: white; border: none; border-radius: 5px; cursor: pointer;">
        🖨️ Cetak Surat
    </button>

    <div class="kop-surat">
        <h2>SMP KATOLIK SANTA MARIA 2 MALANG</h2>
        <p>Jl. KH. Hasyim Ashari No. 11, Malang - Jawa Timur</p>
        <p>Telp: (0341) 326598 | Email: info@santamaria2mlg.sch.id</p>
    </div>

    <div class="nomor-surat">
        <h3 style="text-decoration: underline;">SURAT PERINGATAN <?= strtoupper($sp['tingkat_sp']) ?></h3>
        <p>Nomor: <?= sprintf("%03d", $id_sp) ?>/SP/TATIB/<?= date('Y') ?></p>
    </div>

    <div class="isi-surat">
        <p>Yang bertanda tangan di bawah ini, Kepala SMP Katolik Santa Maria 2 Malang, dengan ini memberikan <strong>Surat Peringatan <?= $sp['tingkat_sp'] ?></strong> kepada:</p>

        <table style="border: none; margin: 20px 0;">
            <tr style="border: none;">
                <td style="border: none; width: 150px;">Nama Siswa</td>
                <td style="border: none; width: 20px;">:</td>
                <td style="border: none;"><strong><?= htmlspecialchars($sp['nama_siswa']) ?></strong></td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;">NIS</td>
                <td style="border: none;">:</td>
                <td style="border: none;"><?= $sp['nis'] ?></td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;">Kelas</td>
                <td style="border: none;">:</td>
                <td style="border: none;"><?= $sp['nama_kelas'] ?></td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;">Orang Tua</td>
                <td style="border: none;">:</td>
                <td style="border: none;"><?= htmlspecialchars($sp['nama_ortu']) ?></td>
            </tr>
        </table>

        <p>Surat Peringatan ini diberikan karena yang bersangkutan telah melakukan pelanggaran tata tertib dengan rincian sebagai berikut:</p>

        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Poin</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Kelakuan</td>
                    <td><?= $sp['poin_kelakuan'] ?></td>
                </tr>
                <tr>
                    <td>Kerajinan</td>
                    <td><?= $sp['poin_kerajinan'] ?></td>
                </tr>
                <tr>
                    <td>Kerapian</td>
                    <td><?= $sp['poin_kerapian'] ?></td>
                </tr>
                <tr>
                    <td><strong>Total Poin</strong></td>
                    <td><strong><?= $sp['total_poin_umum'] ?></strong></td>
                </tr>
            </tbody>
        </table>

        <p><strong>Kategori Pemicu:</strong> <?= $sp['kategori_pemicu'] ?></p>

        <p>Dengan ini kami memohon kepada orang tua/wali untuk:</p>
        <ol>
            <li>Memberikan bimbingan dan pengawasan lebih kepada putra/putri Bapak/Ibu</li>
            <li>Bekerja sama dengan pihak sekolah dalam menegakkan tata tertib</li>
            <li>Menandatangani surat ini sebagai bukti telah menerima dan membaca</li>
        </ol>

        <p>Demikian surat peringatan ini kami sampaikan. Atas perhatian dan kerjasamanya kami ucapkan terima kasih.</p>
    </div>

    <div class="ttd">
        <div class="ttd-section">
            <p>Malang, <?= date('d F Y', strtotime($sp['tanggal_terbit'])) ?></p>
            <p>Kepala Sekolah,</p>
            <br><br><br>
            <p style="text-decoration: underline;"><strong>Sr. M. Elfrida Suhartati, SPM, S.Psi, MM</strong></p>
        </div>
        <div class="ttd-section">
            <p>Orang Tua/Wali Siswa,</p>
            <br><br><br><br>
            <p>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</p>
            <p style="font-size: 12px;">Tanda tangan & nama jelas</p>
        </div>
    </div>

</body>
</html>