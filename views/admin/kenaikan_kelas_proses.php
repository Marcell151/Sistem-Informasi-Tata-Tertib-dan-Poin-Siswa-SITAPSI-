<?php
/**
 * SITAPSI - Proses Kenaikan Kelas (UI GLOBAL PORTAL)
 * Pilih siswa dari kelas asal → Pindahkan ke kelas tujuan
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$id_kelas_asal = $_GET['kelas_asal'] ?? null;

if (!$id_kelas_asal) {
    $_SESSION['error_message'] = '❌ Kelas asal tidak valid';
    header('Location: kenaikan_kelas.php');
    exit;
}

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");

// Info kelas asal
$kelas_asal = fetchOne("SELECT * FROM tb_kelas WHERE id_kelas = :id", ['id' => $id_kelas_asal]);

if (!$kelas_asal) {
    $_SESSION['error_message'] = '❌ Kelas tidak ditemukan';
    header('Location: kenaikan_kelas.php');
    exit;
}

// Ambil siswa di kelas asal (tahun ini)
$siswa_list = fetchAll("
    SELECT 
        s.nis,
        s.nama_siswa,
        s.jenis_kelamin,
        a.id_anggota
    FROM tb_anggota_kelas a
    JOIN tb_siswa s ON a.nis = s.nis
    WHERE a.id_kelas = :id_kelas
    AND a.id_tahun = :id_tahun
    AND s.status_aktif = 'Aktif'
    ORDER BY s.nama_siswa
", [
    'id_kelas' => $id_kelas_asal,
    'id_tahun' => $tahun_aktif['id_tahun']
]);

// Ambil kelas tujuan yang mungkin (tingkat lebih tinggi)
$tingkat_tujuan = $kelas_asal['tingkat'] + 1;

if ($tingkat_tujuan > 9) {
    $_SESSION['error_message'] = '❌ Kelas 9 tidak bisa dinaikkan lagi. Gunakan fitur Kelulusan.';
    header('Location: kenaikan_kelas.php');
    exit;
}

$kelas_tujuan_list = fetchAll("
    SELECT * FROM tb_kelas 
    WHERE tingkat = :tingkat 
    ORDER BY nama_kelas
", ['tingkat' => $tingkat_tujuan]);

// --- UI CONFIG VARIABLES ---
$btn_primary = "px-6 py-3 bg-[#000080] text-white text-sm font-bold rounded-lg shadow-md shadow-blue-900/10 hover:bg-blue-900 transition-all flex items-center justify-center";
$btn_outline = "px-6 py-3 bg-white border border-[#E2E8F0] text-slate-700 text-sm font-bold rounded-lg shadow-sm hover:bg-slate-50 transition-all flex items-center justify-center";
$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Kenaikan - <?= $kelas_asal['nama_kelas'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 pl-16 lg:pl-6 py-4 sticky top-0 z-30 flex items-center space-x-4">
            <a href="kenaikan_kelas.php" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            </a>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Proses Kenaikan Kelas</h1>
                <p class="text-sm font-medium text-slate-500"><?= $kelas_asal['nama_kelas'] ?> → Tingkat <?= $tingkat_tujuan ?></p>
            </div>
        </div>

        <div class="p-6 max-w-6xl mx-auto">

            <form action="../../actions/proses_kenaikan_kelas.php" method="POST" onsubmit="return validateForm()">
                <input type="hidden" name="id_kelas_asal" value="<?= $id_kelas_asal ?>">
                
                <div class="bg-[#000080] text-white rounded-xl shadow-md shadow-blue-900/10 p-6 mb-6 flex items-center justify-between relative overflow-hidden">
                    <svg class="absolute right-0 top-0 text-white/5 w-48 h-48 transform translate-x-8 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    <div class="relative z-10">
                        <p class="text-blue-200 text-xs font-bold uppercase tracking-wider mb-1">Kelas Asal</p>
                        <h2 class="text-3xl font-extrabold"><?= $kelas_asal['nama_kelas'] ?></h2>
                    </div>
                    <div class="relative z-10 text-white/30 text-4xl mx-8">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </div>
                    <div class="relative z-10 text-right">
                        <p class="text-blue-200 text-xs font-bold uppercase tracking-wider mb-1">Tingkat Tujuan</p>
                        <h2 class="text-3xl font-extrabold">Kelas <?= $tingkat_tujuan ?></h2>
                    </div>
                </div>

                <div class="<?= $card_class ?> p-6 mb-6">
                    <label class="block text-sm font-extrabold text-slate-800 mb-4 uppercase tracking-wide">Pilih Kelas Tujuan *</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        <?php foreach ($kelas_tujuan_list as $kt): ?>
                        <label class="block">
                            <input type="radio" name="id_kelas_tujuan" value="<?= $kt['id_kelas'] ?>" required class="peer hidden">
                            <div class="border border-[#E2E8F0] peer-checked:border-[#000080] peer-checked:bg-[#000080]/5 rounded-xl p-4 text-center cursor-pointer hover:border-[#000080]/30 transition-all shadow-sm">
                                <p class="text-2xl font-extrabold text-slate-800 peer-checked:text-[#000080]"><?= $kt['nama_kelas'] ?></p>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="<?= $card_class ?> overflow-hidden mb-6">
                    <div class="p-4 border-b border-[#E2E8F0] bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <span class="font-bold text-slate-800 text-sm">Pilih Siswa (Total: <?= count($siswa_list) ?>)</span>
                        <div class="flex space-x-2">
                            <button type="button" onclick="selectAll()" class="text-xs font-bold bg-white border border-[#E2E8F0] text-slate-600 px-3 py-1.5 rounded-lg hover:bg-slate-50 shadow-sm transition-colors">
                                ✓ Pilih Semua
                            </button>
                            <button type="button" onclick="deselectAll()" class="text-xs font-bold bg-white border border-[#E2E8F0] text-slate-600 px-3 py-1.5 rounded-lg hover:bg-slate-50 shadow-sm transition-colors">
                                ✗ Batal Semua
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        <?php if (empty($siswa_list)): ?>
                        <div class="text-center py-8 text-slate-400">
                            <p class="font-medium text-sm">Tidak ada siswa tersisa di kelas ini</p>
                        </div>
                        <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($siswa_list as $siswa): ?>
                            <label class="flex items-center space-x-3 p-3.5 border border-[#E2E8F0] rounded-xl hover:border-[#000080]/30 cursor-pointer transition-all hover:bg-slate-50 shadow-sm group">
                                <input type="checkbox" name="siswa[]" value="<?= $siswa['nis'] ?>" 
                                       class="w-5 h-5 text-[#000080] border-slate-300 rounded focus:ring-[#000080]">
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-slate-800 text-sm truncate group-hover:text-[#000080]"><?= htmlspecialchars($siswa['nama_siswa']) ?></p>
                                    <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider mt-0.5"><?= $siswa['nis'] ?> • <?= $siswa['jenis_kelamin'] === 'L' ? 'L' : 'P' ?></p>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <a href="kenaikan_kelas.php" class="<?= $btn_outline ?> sm:flex-none sm:w-1/3">Batal</a>
                    <button type="submit" class="<?= $btn_primary ?> flex-1">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        Proses Kenaikan Kelas
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>

<script>
function selectAll() {
    document.querySelectorAll('input[name="siswa[]"]').forEach(cb => cb.checked = true);
}

function deselectAll() {
    document.querySelectorAll('input[name="siswa[]"]').forEach(cb => cb.checked = false);
}

function validateForm() {
    const kelasTujuan = document.querySelector('input[name="id_kelas_tujuan"]:checked');
    const siswa = document.querySelectorAll('input[name="siswa[]"]:checked');
    
    if (!kelasTujuan) {
        alert('⚠️ Pilih kelas tujuan terlebih dahulu!');
        return false;
    }
    
    if (siswa.length === 0) {
        alert('⚠️ Pilih minimal 1 siswa untuk dinaikkan!');
        return false;
    }
    
    return confirm(`⚠️ KONFIRMASI\n\n${siswa.length} siswa akan dipindahkan ke kelas yang baru dipilih.\n\nLanjutkan?`);
}
</script>

</body>
</html>