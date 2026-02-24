<?php
/**
 * SITAPSI - Kenaikan Kelas (UI GLOBAL PORTAL)
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$tahun_aktif = fetchOne("
    SELECT id_tahun, nama_tahun 
    FROM tb_tahun_ajaran 
    WHERE status = 'Aktif' 
    LIMIT 1
");

// Cek apakah ada transaksi (untuk lock system)
$cek_transaksi = fetchOne("
    SELECT COUNT(*) as total 
    FROM tb_pelanggaran_header 
    WHERE id_tahun = :id_tahun
", ['id_tahun' => $tahun_aktif['id_tahun']]);

$ada_transaksi = $cek_transaksi['total'] > 0;
$unlock_manual = isset($_GET['unlock']) && $_GET['unlock'] == '1';

// Jika locked dan tidak ada unlock manual, redirect
if ($ada_transaksi && !$unlock_manual) {
    $_SESSION['error_message'] = '❌ Kenaikan kelas terkunci! Fitur ini hanya bisa diakses di awal tahun ajaran (sebelum ada transaksi pelanggaran). Gunakan tombol Unlock Darurat jika benar-benar diperlukan.';
    header('Location: pengaturan_akademik.php');
    exit;
}

// Jika unlock manual, tampilkan warning
if ($unlock_manual) {
    $_SESSION['info_message'] = '⚠️ Mode Unlock Darurat Aktif! Pastikan Anda memahami konsekuensi kenaikan kelas di tengah tahun ajaran.';
}

// Ambil kelas
$kelas_list = fetchAll("SELECT * FROM tb_kelas ORDER BY tingkat, nama_kelas");

// Group by tingkat
$kelas_by_tingkat = [];
foreach ($kelas_list as $k) {
    $kelas_by_tingkat[$k['tingkat']][] = $k;
}

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
$info = $_SESSION['info_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message'], $_SESSION['info_message']);

$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kenaikan Kelas - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 pl-16 lg:pl-6 py-4 sticky top-0 z-30 flex items-center space-x-4">
            <a href="pengaturan_akademik.php" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            </a>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Proses Kenaikan Kelas</h1>
                <p class="text-sm font-medium text-slate-500">Pindahkan siswa ke tingkat kelas selanjutnya</p>
            </div>
        </div>

        <div class="p-6 space-y-6 max-w-7xl mx-auto">

            <?php if ($success): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <p class="font-medium text-sm"><?= htmlspecialchars($success) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <p class="font-medium text-sm"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($info): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <p class="font-medium text-sm"><?= htmlspecialchars($info) ?></p>
            </div>
            <?php endif; ?>

            <div class="bg-[#000080] text-white rounded-xl shadow-md shadow-blue-900/10 p-6 relative overflow-hidden">
                <svg class="absolute right-0 top-0 text-white/5 w-48 h-48 transform translate-x-8 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-extrabold mb-1">Kenaikan Kelas <?= $tahun_aktif['nama_tahun'] ?></h2>
                        <p class="text-blue-200 font-medium text-sm">Pilih kelas ASAL untuk memulai proses memindahkan siswa.</p>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-100 p-5 rounded-xl flex items-start shadow-sm">
                <svg class="w-6 h-6 text-blue-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <div>
                    <h4 class="font-extrabold text-[#000080] mb-2 text-sm uppercase tracking-wider">Cara Kerja Kenaikan Kelas</h4>
                    <ol class="text-sm text-blue-800 list-decimal list-inside space-y-1 font-medium">
                        <li>Pilih kelas <strong class="text-[#000080]">ASAL</strong> (tingkat sebelumnya).</li>
                        <li>Sistem akan menampilkan siswa yang masih berada di kelas tersebut.</li>
                        <li>Centang nama siswa yang dinyatakan naik kelas.</li>
                        <li>Pilih kelas <strong class="text-[#000080]">TUJUAN</strong> (tingkat baru).</li>
                        <li>Klik tombol "Proses Kenaikan Kelas".</li>
                    </ol>
                </div>
            </div>

            <div class="<?= $card_class ?> p-6">
                <h3 class="font-extrabold text-slate-800 mb-6 text-lg border-b border-[#E2E8F0] pb-3">Pilih Kelas Asal</h3>
                
                <?php foreach ($kelas_by_tingkat as $tingkat => $kelas_items): ?>
                <div class="mb-8 last:mb-0">
                    <h4 class="font-bold text-slate-500 mb-4 flex items-center text-xs uppercase tracking-wider">
                        <span class="w-2 h-2 rounded-full bg-[#000080] mr-2"></span>
                        Tingkat <?= $tingkat ?>
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <?php foreach ($kelas_items as $k): 
                            // Hitung siswa yang belum dipindahkan
                            $jumlah = fetchOne("
                                SELECT COUNT(*) as total 
                                FROM tb_anggota_kelas 
                                WHERE id_kelas = :id 
                                AND id_tahun = :tahun
                            ", [
                                'id' => $k['id_kelas'],
                                'tahun' => $tahun_aktif['id_tahun']
                            ])['total'] ?? 0;
                        ?>
                        <a href="kenaikan_kelas_proses.php?kelas_asal=<?= $k['id_kelas'] ?>" 
                           class="block bg-white border border-[#E2E8F0] hover:bg-[#000080] hover:text-white hover:border-[#000080] hover:shadow-md rounded-xl p-5 text-center transition-all group">
                            <p class="text-2xl font-extrabold text-slate-800 group-hover:text-white mb-1"><?= $k['nama_kelas'] ?></p>
                            <p class="text-[11px] font-bold text-slate-400 group-hover:text-blue-200 uppercase tracking-wider"><?= $jumlah ?> siswa</p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>

    </div>

</div>

</body>
</html>