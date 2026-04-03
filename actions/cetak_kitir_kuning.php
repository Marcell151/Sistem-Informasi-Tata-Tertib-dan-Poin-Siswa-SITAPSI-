<?php
/**
 * SITAPSI - Cetak/Preview Kitir Kuning Pelanggaran
 * Format Kertas: A5 Portrait
 * Fix: Menghilangkan ruang kosong di bawah (Print & PDF) agar pas A5
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

if (!isset($_SESSION['user_id'])) {
    die("Akses ditolak.");
}

$id_transaksi = $_GET['id'] ?? null;
if (!$id_transaksi) die("ID Transaksi tidak valid.");

// 1. Ambil data referensi dari Transaksi yang diklik (Tanggal, Waktu, Guru, Tipe)
$sql_ref = "
    SELECT h.tanggal, h.waktu, h.tipe_form, h.id_guru, g.nama_guru 
    FROM tb_pelanggaran_header h 
    JOIN tb_guru g ON h.id_guru = g.id_guru 
    WHERE h.id_transaksi = :id
";
$ref = fetchOne($sql_ref, ['id' => $id_transaksi]);
if (!$ref) die("Data transaksi tidak ditemukan.");

// Menentukan batas waktu (Rentang 30 menit sebelum & sesudah kejadian)
$waktu_start = date('H:i:s', strtotime($ref['waktu']) - (30 * 60));
$waktu_end = date('H:i:s', strtotime($ref['waktu']) + (30 * 60));

// 2. Ambil SEMUA transaksi pada rentang waktu, guru, dan tipe yang sama
$sql_group = "
    SELECT 
        h.id_transaksi, h.waktu,
        s.nama_siswa, s.no_induk, k.nama_kelas, k.id_kelas,
        GROUP_CONCAT(jp.nama_pelanggaran SEPARATOR ', ') as pelanggaran,
        SUM(d.poin_saat_itu) as total_poin
    FROM tb_pelanggaran_header h
    JOIN tb_anggota_kelas a ON h.id_anggota = a.id_anggota
    JOIN tb_siswa s ON a.no_induk = s.no_induk
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    LEFT JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
    LEFT JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    WHERE h.tanggal = :tanggal
      AND h.id_guru = :id_guru
      AND h.tipe_form = :tipe_form
      AND h.waktu BETWEEN :waktu_start AND :waktu_end
    GROUP BY h.id_transaksi
    ORDER BY h.waktu ASC
    LIMIT 6 -- Maksimal 6 baris agar pas di 1 halaman A5
";

$list_pelanggaran = fetchAll($sql_group, [
    'tanggal' => $ref['tanggal'],
    'id_guru' => $ref['id_guru'],
    'tipe_form' => $ref['tipe_form'],
    'waktu_start' => $waktu_start,
    'waktu_end' => $waktu_end
]);

// 3. Logika Pintar: Hitung No Urut Dinamis (Dipisah per Kelas)
$cache_no_urut = [];
foreach ($list_pelanggaran as &$p) {
    $id_k = $p['id_kelas'];
    if (!isset($cache_no_urut[$id_k])) {
        $sql_abs = "
            SELECT s.no_induk 
            FROM tb_anggota_kelas a 
            JOIN tb_siswa s ON a.no_induk = s.no_induk 
            WHERE a.id_kelas = :idk 
            ORDER BY s.nama_siswa ASC
        ";
        $list_abs = fetchAll($sql_abs, ['idk' => $id_k]);
        $map = [];
        foreach($list_abs as $idx => $s_abs) {
            $map[$s_abs['no_induk']] = $idx + 1;
        }
        $cache_no_urut[$id_k] = $map;
    }
    $p['no_urut_dinamis'] = $cache_no_urut[$id_k][$p['no_induk']] ?? '-';
}
unset($p); 

// Format Tanggal
$hari_array = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$hari = $hari_array[date('w', strtotime($ref['tanggal']))];
$tanggal_format = date('d/m/Y', strtotime($ref['tanggal']));

$judul_kitir = $ref['tipe_form'] === 'Piket' ? 'PIKET HARIAN PELANGGARAN SISWA' : 'PELANGGARAN KELAS SISWA';
$label_guru = $ref['tipe_form'] === 'Piket' ? 'Guru Piket,' : 'Guru Mata Pelajaran,';

// Definisi Class Button
$btn_primary = "px-5 py-2.5 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-md hover:bg-blue-900 transition-all flex items-center justify-center cursor-pointer";
$btn_success = "px-5 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-lg shadow-md hover:bg-emerald-700 transition-all flex items-center justify-center cursor-pointer";
$btn_outline = "px-5 py-2.5 bg-white border border-[#E2E8F0] text-slate-700 text-sm font-semibold rounded-lg shadow-sm hover:bg-slate-50 transition-all flex items-center justify-center cursor-pointer";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitir - <?= htmlspecialchars($ref['tipe_form']) ?> - <?= $tanggal_format ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { background-color: #F8FAFC; font-family: 'Times New Roman', Times, serif; }
        
        .kertas-kitir {
            background-color: #FFFFFF;
            width: 148mm;
            height: 209mm; /* Disesuaikan ke tinggi bersih A5 */
            max-height: 209mm;
            overflow: hidden; 
            margin: 0 auto;
            padding: 8mm; /* Padding dalam kertas */
            color: #000;
            position: relative; /* Penting untuk absolut positioning elemen bawah */
            box-sizing: border-box;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            
            /* GARIS PINGGIR (CUTTING GUIDE) MEPET LUAR */
            border: 1px dashed #cbd5e1; 
        }

        .preview-kuning { background-color: #fef08a !important; }
        .header-line { border-bottom: 3px solid #000; border-top: 1px solid #000; padding: 1px 0; margin-top: 4px; }
        .border-black { border: 1px solid #000; }
        
        /* PENGATURAN SAAT CETAK PRINTER (Ctrl+P) */
        @media print {
            @page { 
                /* DEFAULT SIZE A5: Otomatis terbaca A5 di settingan Printer */
                size: 148mm 210mm portrait; 
                margin: 0 !important; /* Hilangkan margin kertas browser sama sekali */
            }
            body { background: #FFF; padding-top: 0; }
            .no-print { display: none !important; }
            .kertas-kitir { 
                width: 148mm !important; 
                height: 209.5mm !important; 
                margin: 0 !important; 
                box-shadow: none !important; 
                padding: 8mm !important; 
                background-color: #FFFFFF !important; 
                
                /* GARIS PINGGIR JADI HITAM SEBAGAI PANDUAN POTONG DI KERTAS BESAR */
                border: 1px dashed #000000 !important; 

                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="pt-8 pb-12 print:pt-0 print:pb-0">

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

        <div class="kertas-kitir preview-kuning flex flex-col" id="area-kitir">
            
            <div class="flex items-start justify-between">
                <img src="../assets/img/logo_sekolah.png" class="w-[50px] h-20 object-contain" onerror="this.src='https://via.placeholder.com/60x80'">
                
                <div class="flex-1 text-center px-2">
                    <h4 class="font-serif italic text-sm mb-0.5">Perkumpulan Dharmaputri</h4>
                    <h1 class="font-extrabold text-xl uppercase tracking-wider mb-0.5">SMP Katolik Santa Maria II</h1>
                    <h3 class="font-bold text-sm tracking-wide mb-0.5">SEKOLAH STANDAR NASIONAL</h3>
                    <h4 class="text-xs font-semibold mb-1">STATUS TERAKREDITASI "A"</h4>
                    
                    <div class="header-line flex justify-between text-[10px] font-medium px-2">
                        <div class="text-left leading-tight">
                            <p>NSS &nbsp;&nbsp;: 203056101019</p>
                            <p>NPSN : 20533743</p>
                        </div>
                        <div class="text-right leading-tight">
                            <p>Website : www.smpksantamaria2malang.sch.id</p>
                            <p>E-mail &nbsp;: smpkstmaria2mlg@gmail.com</p>
                        </div>
                    </div>
                    <p class="text-[10px] mt-1 font-semibold">Jl. Panderman 7A Malang Telp. 0341 - 551871</p>
                </div>

                <img src="../assets/img/logo_iso.png" alt="Logo ISO" class="w-[75px] h-20 object-contain" onerror="this.src='https://via.placeholder.com/90x80?text=ISO'">
            </div>

            <div class="text-center mt-4 mb-4">
                <h2 class="font-extrabold text-sm uppercase tracking-widest"><?= $judul_kitir ?></h2>
            </div>

            <div class="mb-2 font-sans font-bold text-[11px]">
                <span>Hari/Tanggal : </span>
                <span class="dotted underline-offset-4 font-medium italic"><?= $hari ?>, <?= $tanggal_format ?></span>
            </div>

            <div>
                <table class="w-full text-[10px] border-collapse border-black font-sans table-fixed">
                    <thead>
                        <tr class="text-center font-bold bg-transparent">
                            <th class="border border-black py-1.5 w-[8%]">No.</th>
                            <th class="border border-black py-1.5 w-[27%]">Nama</th>
                            <th class="border border-black py-1.5 w-[15%]">Kls/No.</th>
                            <th class="border border-black py-1.5 w-[35%]">Pelanggaran</th>
                            <th class="border border-black py-1.5 w-[15%]">Denda/Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $max_rows = 6; 
                        $current_row = 1;
                        foreach($list_pelanggaran as $p): 
                            if($current_row > $max_rows) break;
                        ?>
                        <tr>
                            <td class="border border-black p-1.5 text-center align-top h-8"><?= $current_row ?>.</td>
                            <td class="border border-black p-1.5 align-top font-bold truncate"><?= htmlspecialchars($p['nama_siswa']) ?></td>
                            <td class="border border-black p-1.5 text-center align-top"><?= $p['nama_kelas'] ?> / <?= $p['no_urut_dinamis'] ?></td>
                            <td class="border border-black p-1.5 align-top leading-tight break-words"><?= htmlspecialchars($p['pelanggaran']) ?></td>
                            <td class="border border-black p-1.5 text-center align-top font-bold"><?= $p['total_poin'] ?></td>
                        </tr>
                        <?php 
                            $current_row++;
                        endforeach; 
                        ?>
                        
                        <?php for($i = $current_row; $i <= $max_rows; $i++): ?>
                        <tr>
                            <td class="border border-black p-1 text-center h-6 text-slate-300"><?= $i ?>.</td>
                            <td class="border border-black"></td>
                            <td class="border border-black"></td>
                            <td class="border border-black"></td>
                            <td class="border border-black"></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-auto pt-6 flex justify-end pr-8 font-sans w-full">
                <div class="text-center">
                    <p class="mb-12 font-bold text-[11px]"><?= $label_guru ?></p>
                    <p class="font-extrabold text-[11px] underline underline-offset-4 italic"><?= htmlspecialchars($ref['nama_guru']) ?></p>
                </div>
            </div>

        </div>
    </div>

    <script>
        function downloadPDF() {
            const element = document.getElementById('area-kitir');
            const originalOverflow = element.style.overflow;
            
            // Siapkan kanvas untuk PDF
            element.classList.remove('preview-kuning');
            element.style.overflow = 'visible'; 
            
            // Tambahkan class border hitam tajam sementara saat akan didownload ke PDF
            const isBorderDashed = element.style.border;
            element.style.border = "1px dashed #000000";

            const options = {
                margin:       0, // Nol mutlak agar mepet
                filename:     'Kitir_<?= $ref['tipe_form'] ?>_<?= date("Ymd", strtotime($ref['tanggal'])) ?>.pdf',
                image:        { type: 'jpeg', quality: 1 },
                html2canvas:  { 
                    scale: 2, 
                    useCORS: true,
                    backgroundColor: '#FFFFFF',
                    scrollY: 0
                },
                jsPDF:        { 
                    unit: 'mm', 
                    format: 'a5', // Memastikan wadah PDF A5 murni
                    orientation: 'portrait',
                    compress: true
                }
            };

            setTimeout(() => {
                html2pdf().set(options).from(element).save().then(() => {
                    // Kembalikan ke format preview UI
                    element.classList.add('preview-kuning');
                    element.style.overflow = originalOverflow;
                    element.style.border = isBorderDashed;
                });
            }, 100);
        }
    </script>

</body>
</html>