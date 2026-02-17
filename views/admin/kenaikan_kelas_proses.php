<?php
/**
 * SITAPSI - Proses Kenaikan Kelas
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Kenaikan - <?= $kelas_asal['nama_kelas'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 'navy': '#000080' }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto bg-gray-100">
        
        <div class="bg-white shadow-sm border-b px-6 py-4 sticky top-0 z-30 flex items-center space-x-4">
            <a href="kenaikan_kelas.php" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Kenaikan Kelas: <?= $kelas_asal['nama_kelas'] ?> → Tingkat <?= $tingkat_tujuan ?></h1>
                <p class="text-sm text-gray-500">Centang siswa yang akan naik kelas</p>
            </div>
        </div>

        <div class="p-6">

            <form action="../../actions/proses_kenaikan_kelas.php" method="POST" onsubmit="return validateForm()">
                <input type="hidden" name="id_kelas_asal" value="<?= $id_kelas_asal ?>">
                
                <!-- Info Kelas -->
                <div class="bg-gradient-to-r from-purple-600 to-purple-800 text-white rounded-xl shadow-lg p-6 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-200 text-sm mb-1">Kelas Asal</p>
                            <h2 class="text-3xl font-bold"><?= $kelas_asal['nama_kelas'] ?></h2>
                        </div>
                        <div class="text-6xl">→</div>
                        <div class="text-right">
                            <p class="text-purple-200 text-sm mb-1">Tingkat Tujuan</p>
                            <h2 class="text-3xl font-bold">Kelas <?= $tingkat_tujuan ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Pilih Kelas Tujuan -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <label class="block text-sm font-bold text-gray-800 mb-3">Pilih Kelas Tujuan *</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        <?php foreach ($kelas_tujuan_list as $kt): ?>
                        <label class="block">
                            <input type="radio" name="id_kelas_tujuan" value="<?= $kt['id_kelas'] ?>" required class="peer hidden">
                            <div class="border-2 border-gray-300 peer-checked:border-purple-600 peer-checked:bg-purple-50 rounded-lg p-4 text-center cursor-pointer hover:border-purple-400 transition-all">
                                <p class="text-2xl font-bold text-gray-800 peer-checked:text-purple-600"><?= $kt['nama_kelas'] ?></p>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Daftar Siswa -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                    <div class="p-4 border-b flex items-center justify-between">
                        <span class="font-bold text-gray-700">Daftar Siswa (Total: <?= count($siswa_list) ?>)</span>
                        <div class="flex space-x-2">
                            <button type="button" onclick="selectAll()" class="text-sm bg-green-50 text-green-700 px-3 py-1 rounded hover:bg-green-100">
                                ✓ Pilih Semua
                            </button>
                            <button type="button" onclick="deselectAll()" class="text-sm bg-red-50 text-red-700 px-3 py-1 rounded hover:bg-red-100">
                                ✗ Batal Semua
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        <?php if (empty($siswa_list)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <p class="font-medium">Tidak ada siswa di kelas ini</p>
                        </div>
                        <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($siswa_list as $siswa): ?>
                            <label class="flex items-center space-x-3 p-4 border-2 border-gray-200 rounded-lg hover:border-purple-400 cursor-pointer transition-all group">
                                <input type="checkbox" name="siswa[]" value="<?= $siswa['nis'] ?>" 
                                       class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-600">
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-gray-800 truncate group-hover:text-purple-600"><?= htmlspecialchars($siswa['nama_siswa']) ?></p>
                                    <p class="text-xs text-gray-500"><?= $siswa['nis'] ?> • <?= $siswa['jenis_kelamin'] === 'L' ? 'L' : 'P' ?></p>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex space-x-4">
                    <a href="kenaikan_kelas.php" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium text-center">
                        Batal
                    </a>
                    <button type="submit" class="flex-1 px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium">
                        📈 Proses Kenaikan Kelas
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
    
    return confirm(`⚠️ Konfirmasi Kenaikan Kelas\n\n${siswa.length} siswa akan dipindahkan ke kelas tujuan.\n\nLanjutkan?`);
}
</script>

</body>
</html>