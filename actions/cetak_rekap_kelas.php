<?php
/**
 * SITAPSI - Cetak & Download Rekapitulasi Denda Pelanggaran
 */

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['role'])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

$id_kelas = $_GET['kelas'] ?? null;
if (!$id_kelas) die("Kelas tidak dipilih.");

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

$kelas_info = fetchOne("
    SELECT k.nama_kelas, g.nama_guru as wali_kelas 
    FROM tb_kelas k
    LEFT JOIN tb_guru g ON k.id_kelas = g.id_kelas
    WHERE k.id_kelas = :id_kelas
", ['id_kelas' => $id_kelas]);

$siswa_kelas = fetchAll("
    SELECT 
        s.no_induk, s.jenis_kelamin, s.nama_siswa,
        a.poin_kelakuan, a.poin_kerajinan, a.poin_kerapian
    FROM tb_siswa s
    JOIN tb_anggota_kelas a ON s.no_induk = a.no_induk
    WHERE s.status_aktif = 'Aktif' 
    AND a.id_tahun = :id_tahun AND a.id_kelas = :id_kelas
    ORDER BY s.nama_siswa
", ['id_tahun' => $tahun_aktif['id_tahun'], 'id_kelas' => $id_kelas]);

$btn_primary = "px-5 py-2.5 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-md hover:bg-blue-900 transition-all flex items-center justify-center cursor-pointer";
$btn_success = "px-5 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-lg shadow-md hover:bg-emerald-700 transition-all flex items-center justify-center cursor-pointer";
$btn_outline = "px-5 py-2.5 bg-white border border-[#E2E8F0] text-slate-700 text-sm font-semibold rounded-lg shadow-sm hover:bg-slate-50 transition-all flex items-center justify-center cursor-pointer";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Denda - Kelas <?= htmlspecialchars($kelas_info['nama_kelas']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: black; }
        .table-rekap { width: 100%; border-collapse: collapse; margin-top: 15px; table-layout: auto; }
        .table-rekap th, .table-rekap td { border: 1px solid black; padding: 6px 8px; font-size: 13px; word-wrap: break-word; }
        .table-rekap th { text-align: center; font-weight: bold; }
        
        .bg-kelakuan { background-color: #e6c1c9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bg-kerajinan { background-color: #ffe699 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bg-kerapian { background-color: #c4dfb3 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bg-header-umum { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; margin: 0 !important; }
            .print-container { box-shadow: none !important; border: none !important; width: 100% !important; max-width: 100% !important; padding: 0 !important; }
        }
        @page { size: A4 portrait; margin: 10mm; }
    </style>
</head>
<body class="bg-[#F8FAFC] min-h-screen py-8 print:py-0">

<div class="max-w-4xl mx-auto px-4 print:px-0">
    <div class="no-print mb-6 flex flex-col sm:flex-row sm:items-center justify-between bg-white p-4 rounded-xl border border-[#E2E8F0] shadow-sm font-sans gap-4">
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

    <div id="dokumen-rekap" class="print-container bg-white shadow-xl rounded-none sm:rounded-xl border border-[#E2E8F0] px-8 py-10 mx-auto w-full">
        <div class="text-center mb-6">
            <h1 class="text-lg font-bold">REKAPITULASI DENDA PELANGGARAN</h1>
            <h2 class="text-lg font-bold">TAHUN PELAJARAN <?= mb_strtoupper($tahun_aktif['nama_tahun']) ?></h2>
        </div>

        <div class="flex justify-between items-end mb-2 font-bold text-[14px]">
            <div>
                <table class="whitespace-nowrap">
                    <tr><td class="pr-2 pb-1">KELAS</td><td class="pb-1">: <?= htmlspecialchars($kelas_info['nama_kelas']) ?></td></tr>
                    <tr><td class="pr-2">WALI KELAS</td><td>: <?= htmlspecialchars($kelas_info['wali_kelas'] ?? '(Belum Ada Wali Kelas)') ?></td></tr>
                </table>
            </div>
            <div class="pb-1">SEMESTER : <?= mb_strtoupper($tahun_aktif['semester_aktif']) ?></div>
        </div>

        <table class="table-rekap">
            <thead>
                <tr>
                    <th class="bg-header-umum" style="width: 8%;">No.<br>Induk</th>
                    <th class="bg-header-umum" style="width: 5%;">L/P</th>
                    <th class="bg-header-umum" style="width: auto%;">Nama Siswa</th>
                    <th class="bg-kelakuan" style="width: 12%;">Kelakuan</th>
                    <th class="bg-kerajinan" style="width: 12%;">Kerajinan</th>
                    <th class="bg-kerapian" style="width: 12%;">Kerapian</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($siswa_kelas)): ?>
                <tr><td colspan="6" class="text-center py-4">Belum ada data siswa di kelas ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($siswa_kelas as $siswa): 
                        $p_kelakuan = ($siswa['poin_kelakuan'] == 0) ? '-' : $siswa['poin_kelakuan'];
                        $p_kerajinan = ($siswa['poin_kerajinan'] == 0) ? '-' : $siswa['poin_kerajinan'];
                        $p_kerapian = ($siswa['poin_kerapian'] == 0) ? '-' : $siswa['poin_kerapian'];
                    ?>
                    <tr>
                        <td class="text-center"><?= $siswa['no_induk'] ?></td>
                        <td class="text-center"><?= $siswa['jenis_kelamin'] ?></td>
                        <td class="pl-2"><?= htmlspecialchars($siswa['nama_siswa']) ?></td>
                        <td class="text-center"><?= $p_kelakuan ?></td>
                        <td class="text-center"><?= $p_kerajinan ?></td>
                        <td class="text-center"><?= $p_kerapian ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="mt-8 flex justify-end pr-10">
            <div class="text-center">
                <p class="mb-20"></p>
                <p class="font-bold border-b border-black inline-block min-w-[200px]"></p>
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

    const element = document.getElementById('dokumen-rekap');
    const opt = {
        margin:       [10, 10, 10, 10], // Margin PDF (mm)
        filename:     'Rekap_Denda_<?= htmlspecialchars($kelas_info['nama_kelas']) ?>.pdf',
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