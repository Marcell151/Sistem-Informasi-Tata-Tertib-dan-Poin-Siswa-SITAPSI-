<?php
/**
 * SITAPSI - Data Guru (UI GLOBAL PORTAL)
 * Mengelola Master Data Guru & Status Wali Kelas
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

// Ambil daftar kelas untuk dropdown
$kelas_list = fetchAll("SELECT id_kelas, nama_kelas FROM tb_kelas ORDER BY tingkat, nama_kelas");

// Query guru dengan info wali kelas
$guru_list = fetchAll("
    SELECT 
        g.*,
        k.nama_kelas
    FROM tb_guru g
    LEFT JOIN tb_kelas k ON g.id_kelas = k.id_kelas
    ORDER BY g.nama_guru
");

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
    <title>Data Guru - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 py-4 sticky top-0 z-30 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Data Guru</h1>
                <p class="text-sm font-medium text-slate-500">Manajemen akses portal guru dan penugasan Wali Kelas</p>
            </div>
            <button onclick="openModalTambah()" class="<?= $btn_primary ?>">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Tambah Guru Baru
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

            <div class="<?= $card_class ?> overflow-hidden">
                <div class="p-4 border-b border-[#E2E8F0] bg-slate-50/50 flex justify-between items-center">
                    <span class="font-bold text-slate-800 text-sm">Daftar Guru / Pegawai</span>
                    <span class="px-2.5 py-1 bg-slate-200 text-slate-700 rounded-md text-[10px] font-bold">Total: <?= count($guru_list) ?> Data</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-white text-xs text-slate-500 uppercase border-b border-[#E2E8F0]">
                            <tr>
                                <th class="p-4 font-bold">Nama Guru</th>
                                <th class="p-4 font-bold">NIP / ID Pegawai</th>
                                <th class="p-4 font-bold text-center">PIN Akses</th>
                                <th class="p-4 font-bold text-center">Tugas Wali Kelas</th>
                                <th class="p-4 font-bold text-center">Status</th>
                                <th class="p-4 font-bold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php if(empty($guru_list)): ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400 font-medium">Belum ada data guru.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($guru_list as $guru): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-[#000080] rounded-xl flex items-center justify-center text-white font-extrabold shadow-sm flex-shrink-0">
                                            <?= strtoupper(substr($guru['nama_guru'], 0, 1)) ?>
                                        </div>
                                        <p class="font-bold text-slate-800 text-[13px]"><?= htmlspecialchars($guru['nama_guru']) ?></p>
                                    </div>
                                </td>
                                <td class="p-4 text-slate-600 font-medium">
                                    <?= htmlspecialchars($guru['nip'] ?: '-') ?>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="font-mono text-xs font-bold bg-slate-100 text-slate-600 px-2.5 py-1 rounded border border-slate-200">
                                        <?= htmlspecialchars($guru['pin_validasi']) ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if($guru['id_kelas']): ?>
                                        <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-md bg-[#000080]/10 text-[#000080] border border-[#000080]/20">
                                            Wali Kelas <?= htmlspecialchars($guru['nama_kelas']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-[10px] font-medium text-slate-400">- Bukan Wali -</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-md 
                                        <?= $guru['status'] === 'Aktif' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-red-50 text-red-600 border border-red-200' ?>">
                                        <?= $guru['status'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <button onclick='editGuru(<?= json_encode([
                                        "id_guru" => $guru["id_guru"],
                                        "nama_guru" => $guru["nama_guru"],
                                        "nip" => $guru["nip"],
                                        "pin_validasi" => $guru["pin_validasi"],
                                        "id_kelas" => $guru["id_kelas"],
                                        "status" => $guru["status"]
                                    ]) ?>)' 
                                    class="p-1.5 bg-white border border-[#E2E8F0] text-slate-600 rounded-md hover:bg-slate-50 hover:text-[#000080] transition-colors shadow-sm" title="Edit Data">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="modal-tambah" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModalTambah()"></div>
    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full relative z-10 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                Tambah Guru Baru
            </h3>
            <button onclick="closeModalTambah()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <form action="../../actions/tambah_guru.php" method="POST" class="p-6 space-y-4">
            <div>
                <label class="<?= $label_class ?>">Nama Lengkap (Serta Gelar) *</label>
                <input type="text" name="nama_guru" required class="<?= $input_class ?>" placeholder="Contoh: Budi Santoso, S.Pd">
            </div>
            <div>
                <label class="<?= $label_class ?>">NIP / ID Pegawai</label>
                <input type="text" name="nip" class="<?= $input_class ?>" placeholder="Boleh dikosongkan jika tidak ada">
            </div>
            <div>
                <label class="<?= $label_class ?>">PIN Akses Portal (6 Digit) *</label>
                <input type="text" name="pin_validasi" required pattern="\d{6}" maxlength="6" class="<?= $input_class ?>" placeholder="Contoh: 123456" title="Harus 6 digit angka">
                <p class="text-[10px] text-slate-500 mt-1">Digunakan untuk login ke Portal Guru.</p>
            </div>
            <div>
                <label class="<?= $label_class ?>">Tugas Wali Kelas (Opsional)</label>
                <select name="id_kelas" class="<?= $input_class ?>">
                    <option value="">-- Bukan Wali Kelas --</option>
                    <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k['id_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-3 pt-4 border-t border-[#E2E8F0]">
                <button type="button" onclick="closeModalTambah()" class="<?= $btn_outline ?> flex-1">Batal</button>
                <button type="submit" class="<?= $btn_primary ?> flex-1">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModalEdit()"></div>
    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full relative z-10 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                Edit Data Guru
            </h3>
            <button onclick="closeModalEdit()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <form action="../../actions/edit_guru.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="id_guru" id="edit-id-guru">
            <div>
                <label class="<?= $label_class ?>">Nama Lengkap *</label>
                <input type="text" name="nama_guru" id="edit-nama-guru" required class="<?= $input_class ?>">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="<?= $label_class ?>">NIP / ID</label>
                    <input type="text" name="nip" id="edit-nip" class="<?= $input_class ?>">
                </div>
                <div>
                    <label class="<?= $label_class ?>">PIN (6 Digit) *</label>
                    <input type="text" name="pin_validasi" id="edit-pin" required pattern="\d{6}" maxlength="6" class="<?= $input_class ?>">
                </div>
            </div>
            <div>
                <label class="<?= $label_class ?>">Tugas Wali Kelas</label>
                <select name="id_kelas" id="edit-id-kelas" class="<?= $input_class ?>">
                    <option value="">-- Bukan Wali Kelas --</option>
                    <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k['id_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[10px] text-orange-600 mt-1.5 flex items-center font-medium">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Satu kelas hanya boleh dipegang oleh satu Wali Kelas aktif.
                </p>
            </div>
            <div>
                <label class="<?= $label_class ?>">Status Akses *</label>
                <select name="status" id="edit-status" required class="<?= $input_class ?>">
                    <option value="Aktif">Aktif</option>
                    <option value="Non-Aktif">Non-Aktif (Cabut Akses)</option>
                </select>
            </div>
            <div class="flex gap-3 pt-4 border-t border-[#E2E8F0]">
                <button type="button" onclick="closeModalEdit()" class="<?= $btn_outline ?> flex-1">Batal</button>
                <button type="submit" class="<?= $btn_primary ?> flex-1">Update Data</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModalTambah() {
    document.getElementById('modal-tambah').classList.remove('hidden');
}
function closeModalTambah() {
    document.getElementById('modal-tambah').classList.add('hidden');
}

function editGuru(data) {
    document.getElementById('edit-id-guru').value = data.id_guru;
    document.getElementById('edit-nama-guru').value = data.nama_guru;
    document.getElementById('edit-nip').value = data.nip || '';
    document.getElementById('edit-pin').value = data.pin_validasi;
    document.getElementById('edit-id-kelas').value = data.id_kelas || '';
    document.getElementById('edit-status').value = data.status;
    document.getElementById('modal-edit').classList.remove('hidden');
}
function closeModalEdit() {
    document.getElementById('modal-edit').classList.add('hidden');
}
</script>

</body>
</html>