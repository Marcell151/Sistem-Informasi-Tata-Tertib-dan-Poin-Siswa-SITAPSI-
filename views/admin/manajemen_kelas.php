<?php
/**
 * SITAPSI - Manajemen Kelas (UI GLOBAL PORTAL)
 * CRUD lengkap untuk data kelas
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

// Ambil daftar kelas
$kelas_list = fetchAll("SELECT * FROM tb_kelas ORDER BY tingkat, nama_kelas");

// Group by tingkat
$kelas_by_tingkat = [];
foreach ($kelas_list as $k) {
    $kelas_by_tingkat[$k['tingkat']][] = $k;
}

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// --- UI CONFIG VARIABLES ---
$btn_primary = "px-4 py-2.5 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-md shadow-blue-900/10 hover:bg-blue-900 transition-all flex items-center justify-center";
$btn_outline = "px-4 py-2.5 bg-white border border-[#E2E8F0] text-slate-700 text-sm font-semibold rounded-lg shadow-sm hover:bg-slate-50 transition-all flex items-center justify-center";
$input_class = "w-full px-4 py-2.5 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm text-slate-700 bg-white transition-all";
$label_class = "block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wide";
$card_class = "bg-white border border-[#E2E8F0] rounded-xl shadow-sm";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kelas - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 pl-16 lg:pl-6 py-4 sticky top-0 z-30 flex justify-between items-center gap-4">
            <div class="flex items-center space-x-4">
                <a href="pengaturan_akademik.php" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                </a>
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Manajemen Kelas</h1>
                    <p class="text-sm font-medium text-slate-500">Kelola master data kelas (CRUD)</p>
                </div>
            </div>
            <button onclick="document.getElementById('modal-tambah').classList.remove('hidden')" class="<?= $btn_primary ?>">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span class="hidden sm:inline">Tambah Kelas</span>
                <span class="sm:hidden">Tambah</span>
            </button>
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

            <div class="bg-[#000080] text-white rounded-xl shadow-md shadow-blue-900/10 p-6 relative overflow-hidden">
                <svg class="absolute right-0 top-0 text-white/5 w-48 h-48 transform translate-x-8 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <div class="relative z-10 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
                    <div>
                        <h2 class="text-2xl font-extrabold mb-1 flex items-center justify-center sm:justify-start">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            Data Kelas
                        </h2>
                        <p class="text-blue-200 font-medium text-sm">Kelola semua kelas yang terdaftar di sekolah.</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm px-6 py-3 rounded-xl border border-white/20">
                        <p class="text-[10px] text-blue-200 uppercase tracking-wider font-bold mb-1">Total Kelas</p>
                        <p class="text-4xl font-extrabold leading-none"><?= count($kelas_list) ?></p>
                    </div>
                </div>
            </div>

            <?php foreach ($kelas_by_tingkat as $tingkat => $kelas_items): ?>
            <div class="<?= $card_class ?> overflow-hidden">
                <div class="p-4 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="bg-[#000080] text-white px-3 py-1 rounded-md text-xs font-bold mr-3 shadow-sm">TINGKAT <?= $tingkat ?></span>
                        <span class="font-bold text-slate-800 text-sm">Daftar Kelas</span>
                    </div>
                    <span class="text-xs font-medium text-slate-500"><?= count($kelas_items) ?> kelas</span>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                        <?php foreach ($kelas_items as $k): 
                            // Hitung jumlah siswa
                            $tahun_aktif = fetchOne("SELECT id_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
                            $jumlah_siswa = fetchOne("
                                SELECT COUNT(*) as total 
                                FROM tb_anggota_kelas 
                                WHERE id_kelas = :id 
                                AND id_tahun = :tahun
                            ", [
                                'id' => $k['id_kelas'],
                                'tahun' => $tahun_aktif['id_tahun']
                            ])['total'] ?? 0;
                        ?>
                        <div class="bg-white border border-[#E2E8F0] rounded-xl p-4 hover:border-[#000080]/30 hover:shadow-md transition-all group flex flex-col justify-between">
                            <div class="text-center mb-4 pt-2">
                                <h3 class="text-2xl font-extrabold text-slate-800 group-hover:text-[#000080] transition-colors"><?= htmlspecialchars($k['nama_kelas']) ?></h3>
                                <p class="text-[11px] font-bold text-slate-400 mt-1 uppercase tracking-wider bg-slate-50 inline-block px-2 py-0.5 rounded"><?= $jumlah_siswa ?> siswa</p>
                            </div>
                            <div class="flex space-x-2 border-t border-[#E2E8F0] pt-3">
                                <button onclick='editKelas(<?= json_encode($k) ?>)' 
                                        class="flex-1 p-2 bg-slate-50 text-slate-600 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors flex justify-center" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                </button>
                                <button onclick="hapusKelas(<?= $k['id_kelas'] ?>, '<?= htmlspecialchars($k['nama_kelas']) ?>', <?= $jumlah_siswa ?>)" 
                                        class="flex-1 p-2 bg-slate-50 text-slate-600 rounded-lg hover:bg-red-50 hover:text-red-600 transition-colors flex justify-center" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($kelas_list)): ?>
            <div class="bg-white rounded-xl shadow-sm p-12 text-center border border-dashed border-[#E2E8F0]">
                <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <p class="text-slate-500 font-bold text-lg">Belum ada kelas yang terdaftar</p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<div id="modal-tambah" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModal('modal-tambah')"></div>
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full relative z-10 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 4v16m8-8H4"></path></svg>
                Tambah Kelas Baru
            </h3>
            <button onclick="closeModal('modal-tambah')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <form action="../../actions/tambah_kelas.php" method="POST" class="p-6 space-y-5">
            <div>
                <label class="<?= $label_class ?>">Tingkat *</label>
                <select name="tingkat" required class="<?= $input_class ?>">
                    <option value="">Pilih Tingkat</option>
                    <option value="7">7 (Tujuh)</option>
                    <option value="8">8 (Delapan)</option>
                    <option value="9">9 (Sembilan)</option>
                </select>
            </div>
            <div>
                <label class="<?= $label_class ?>">Nama Kelas *</label>
                <input type="text" name="nama_kelas" required placeholder="Contoh: VIIA, VIIIB, IXC" class="<?= $input_class ?>">
                <p class="text-[10px] text-slate-500 mt-1.5 font-medium">Format umum: [Tingkat][Nama], misal: VIIA, VIIIB</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('modal-tambah')" class="<?= $btn_outline ?> flex-1">Batal</button>
                <button type="submit" class="<?= $btn_primary ?> flex-1">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModal('modal-edit')"></div>
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full relative z-10 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                Edit Data Kelas
            </h3>
            <button onclick="closeModal('modal-edit')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <form action="../../actions/edit_kelas.php" method="POST" class="p-6 space-y-5">
            <input type="hidden" name="id_kelas" id="edit-id-kelas">
            <div>
                <label class="<?= $label_class ?>">Tingkat *</label>
                <select name="tingkat" id="edit-tingkat" required class="<?= $input_class ?>">
                    <option value="7">7 (Tujuh)</option>
                    <option value="8">8 (Delapan)</option>
                    <option value="9">9 (Sembilan)</option>
                </select>
            </div>
            <div>
                <label class="<?= $label_class ?>">Nama Kelas *</label>
                <input type="text" name="nama_kelas" id="edit-nama-kelas" required class="<?= $input_class ?>">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('modal-edit')" class="<?= $btn_outline ?> flex-1">Batal</button>
                <button type="submit" class="<?= $btn_primary ?> flex-1">Update Data</button>
            </div>
        </form>
    </div>
</div>

<script>
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function editKelas(data) {
    document.getElementById('edit-id-kelas').value = data.id_kelas;
    document.getElementById('edit-tingkat').value = data.tingkat;
    document.getElementById('edit-nama-kelas').value = data.nama_kelas;
    document.getElementById('modal-edit').classList.remove('hidden');
}

function hapusKelas(id, nama, jumlahSiswa) {
    if (jumlahSiswa > 0) {
        alert(`⚠️ Tidak dapat menghapus kelas ${nama}!\n\nMasih ada ${jumlahSiswa} siswa di kelas ini.\nPindahkan/hapus siswa terlebih dahulu.`);
        return;
    }
    
    if (confirm(`⚠️ Yakin ingin menghapus kelas ${nama}?`)) {
        window.location.href = `../../actions/hapus_kelas.php?id=${id}`;
    }
}
</script>

</body>
</html>