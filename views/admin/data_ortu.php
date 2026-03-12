<?php
/**
 * SITAPSI - Data Orang Tua / Wali Murid
 * FITUR: CRUD Lengkap (Tambah, Edit, Hapus, Reset Sandi)
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$search = $_GET['search'] ?? '';

// Query untuk mengambil data orang tua sekaligus menghitung berapa anak yang terikat
$sql = "
    SELECT 
        o.id_ortu, 
        o.nik_ortu, 
        o.nama_ayah, 
        o.pekerjaan_ayah,
        o.nama_ibu, 
        o.pekerjaan_ibu,
        o.no_hp_ortu,
        o.alamat,
        COUNT(s.no_induk) as jumlah_anak
    FROM tb_orang_tua o
    LEFT JOIN tb_siswa s ON o.id_ortu = s.id_ortu
";

$params = [];

if (!empty($search)) {
    $sql .= " WHERE o.nik_ortu LIKE :search OR o.nama_ayah LIKE :search OR o.nama_ibu LIKE :search";
    $params['search'] = "%$search%";
}

$sql .= " GROUP BY o.id_ortu ORDER BY o.nama_ayah ASC";

$ortu_list = fetchAll($sql, $params);

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// UI Config
$btn_primary = "px-4 py-2.5 bg-[#000080] text-white text-sm font-semibold rounded-lg shadow-md hover:bg-blue-900 transition-all flex items-center justify-center";
$btn_outline = "px-4 py-2.5 bg-white border border-[#E2E8F0] text-slate-700 text-sm font-semibold rounded-lg shadow-sm hover:bg-slate-50 transition-all flex items-center justify-center";
$input_class = "w-full px-4 py-2.5 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm text-slate-700 bg-slate-50 focus:bg-white transition-all";
$label_class = "block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wide";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Wali Murid - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8FAFC]">

<div class="flex h-screen overflow-hidden">

    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto lg:ml-64">
        
        <div class="bg-white border-b border-[#E2E8F0] px-6 py-4 sticky top-0 z-30 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Data Wali Murid (Orang Tua)</h1>
                <p class="text-sm font-medium text-slate-500">Manajemen akun login & master data wali murid</p>
            </div>
            <div>
                <button onclick="openModalTambah()" class="<?= $btn_primary ?>">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                    Tambah Manual
                </button>
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

            <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm p-5">
                <form method="GET" class="flex gap-4 items-end">
                    <div class="flex-1">
                        <label class="<?= $label_class ?>">Pencarian Wali Murid</label>
                        <div class="relative">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari NIK, Nama Ayah, atau Nama Ibu..." class="w-full px-4 py-2.5 border border-[#E2E8F0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm text-slate-700 bg-white transition-all pl-10">
                            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </div>
                    </div>
                    <button type="submit" class="<?= $btn_primary ?> h-[42px] px-6">Cari Data</button>
                </form>
            </div>

            <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-[#E2E8F0] bg-slate-50/50 flex justify-between items-center">
                    <span class="font-bold text-slate-800 text-sm">Daftar Akun Terdaftar <span class="text-slate-400 font-medium ml-1">(Total: <?= count($ortu_list) ?>)</span></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-white text-xs text-slate-500 uppercase border-b border-[#E2E8F0]">
                            <tr>
                                <th class="p-4 font-bold">Data Orang Tua / Wali</th>
                                <th class="p-4 font-bold">NIK (Akun Login)</th>
                                <th class="p-4 font-bold text-center">Jumlah Anak</th>
                                <th class="p-4 font-bold text-center">Kontak (WA)</th>
                                <th class="p-4 font-bold text-center">Aksi Sistem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            <?php if(empty($ortu_list)): ?>
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-400 text-sm font-medium">Tidak ada data wali murid ditemukan</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($ortu_list as $o): 
                                // Siapkan data JSON untuk Modal Edit
                                $edit_data = htmlspecialchars(json_encode($o), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-slate-100 border border-slate-200 text-slate-500 rounded-full flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800 text-[13px]"><?= htmlspecialchars($o['nama_ayah'] ?? 'Belum Diisi') ?> (Ayah)</p>
                                            <p class="text-[11px] font-medium text-slate-500"><?= htmlspecialchars($o['nama_ibu'] ?? 'Belum Diisi') ?> (Ibu)</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 font-mono text-slate-700 font-semibold tracking-wider">
                                    <?= htmlspecialchars($o['nik_ortu']) ?>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $o['jumlah_anak'] > 0 ? 'bg-blue-50 text-blue-600 border border-blue-200' : 'bg-slate-100 text-slate-500 border border-slate-200' ?>">
                                        <?= $o['jumlah_anak'] ?> Anak Terkait
                                    </span>
                                </td>
                                <td class="p-4 text-center text-slate-600 font-medium text-xs">
                                    <?= htmlspecialchars($o['no_hp_ortu'] ?? '-') ?>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button onclick="resetPassword(<?= $o['id_ortu'] ?>, '<?= htmlspecialchars($o['nik_ortu'], ENT_QUOTES) ?>')" 
                                                class="p-1.5 bg-white border border-[#E2E8F0] text-slate-600 rounded-md hover:bg-slate-50 transition-colors shadow-sm" title="Reset Password ke 123456">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M2.5 2v6h6M21.5 22v-6h-6M22 11.5A10 10 0 0 0 3.2 7.2M2 12.5a10 10 0 0 0 18.8 4.2"></path></svg>
                                        </button>
                                        <button onclick="editOrtu(<?= $edit_data ?>)" 
                                                class="p-1.5 bg-white border border-[#E2E8F0] text-blue-600 rounded-md hover:bg-blue-50 transition-colors shadow-sm" title="Edit Data Wali Murid">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        </button>
                                        <button onclick="hapusOrtu(<?= $o['id_ortu'] ?>, '<?= htmlspecialchars($o['nik_ortu'], ENT_QUOTES) ?>', <?= $o['jumlah_anak'] ?>)" 
                                                class="p-1.5 bg-white border border-red-200 text-red-600 rounded-md hover:bg-red-50 transition-colors shadow-sm" title="Hapus Data">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </div>
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
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl relative z-10 overflow-hidden transform transition-all flex flex-col max-h-[90vh]">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between flex-shrink-0">
            <h3 class="font-extrabold text-slate-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg> 
                Tambah Wali Murid Baru
            </h3>
            <button type="button" onclick="closeModalTambah()" class="text-slate-400 hover:text-slate-600 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <div class="overflow-y-auto p-6">
            <form action="../../actions/tambah_ortu.php" method="POST" id="formTambah" class="space-y-5">
                <div>
                    <label class="<?= $label_class ?>">NIK (Nomor Induk Kependudukan) *</label>
                    <input type="text" name="nik_ortu" required class="<?= $input_class ?>" placeholder="16 Digit NIK" pattern="[0-9]{16}" title="NIK harus berupa 16 digit angka">
                    <p class="text-[10px] text-slate-400 mt-1">NIK ini akan menjadi Username Login Portal.</p>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2 border-t border-[#E2E8F0]">
                    <div>
                        <label class="<?= $label_class ?>">Nama Ayah *</label>
                        <input type="text" name="nama_ayah" required class="<?= $input_class ?>" placeholder="Nama Lengkap Ayah">
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Pekerjaan Ayah</label>
                        <input type="text" name="pekerjaan_ayah" class="<?= $input_class ?>" placeholder="Contoh: Wiraswasta">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2 border-t border-[#E2E8F0]">
                    <div>
                        <label class="<?= $label_class ?>">Nama Ibu *</label>
                        <input type="text" name="nama_ibu" required class="<?= $input_class ?>" placeholder="Nama Lengkap Ibu">
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Pekerjaan Ibu</label>
                        <input type="text" name="pekerjaan_ibu" class="<?= $input_class ?>" placeholder="Contoh: Ibu Rumah Tangga">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2 border-t border-[#E2E8F0]">
                    <div>
                        <label class="<?= $label_class ?>">No HP (WhatsApp) *</label>
                        <input type="text" name="no_hp_ortu" required class="<?= $input_class ?>" placeholder="Contoh: 08123456789">
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Alamat Lengkap</label>
                        <textarea name="alamat" rows="2" class="<?= $input_class ?> resize-none" placeholder="Alamat rumah..."></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="p-5 border-t border-[#E2E8F0] bg-white flex gap-3 flex-shrink-0">
            <button type="button" onclick="closeModalTambah()" class="<?= $btn_outline ?> flex-1">Batal</button>
            <button type="submit" form="formTambah" class="<?= $btn_primary ?> flex-1">Simpan Data</button>
        </div>
    </div>
</div>

<div id="modal-edit" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModalEdit()"></div>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl relative z-10 overflow-hidden transform transition-all flex flex-col max-h-[90vh]">
        <div class="p-5 border-b border-[#E2E8F0] bg-slate-50/50 flex items-center justify-between flex-shrink-0">
            <h3 class="font-extrabold text-slate-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                Edit Data Wali Murid
            </h3>
            <button type="button" onclick="closeModalEdit()" class="text-slate-400 hover:text-slate-600 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <div class="overflow-y-auto p-6">
            <form action="../../actions/edit_ortu.php" method="POST" id="formEdit" class="space-y-5">
                <input type="hidden" name="id_ortu" id="edit_id_ortu">
                <input type="hidden" name="nik_lama" id="edit_nik_lama">
                
                <div>
                    <label class="<?= $label_class ?>">NIK (Nomor Induk Kependudukan) *</label>
                    <input type="text" name="nik_ortu" id="edit_nik_ortu" required class="<?= $input_class ?>" placeholder="16 Digit NIK" pattern="[0-9]{16}">
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2 border-t border-[#E2E8F0]">
                    <div>
                        <label class="<?= $label_class ?>">Nama Ayah *</label>
                        <input type="text" name="nama_ayah" id="edit_nama_ayah" required class="<?= $input_class ?>">
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Pekerjaan Ayah</label>
                        <input type="text" name="pekerjaan_ayah" id="edit_pekerjaan_ayah" class="<?= $input_class ?>">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2 border-t border-[#E2E8F0]">
                    <div>
                        <label class="<?= $label_class ?>">Nama Ibu *</label>
                        <input type="text" name="nama_ibu" id="edit_nama_ibu" required class="<?= $input_class ?>">
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Pekerjaan Ibu</label>
                        <input type="text" name="pekerjaan_ibu" id="edit_pekerjaan_ibu" class="<?= $input_class ?>">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2 border-t border-[#E2E8F0]">
                    <div>
                        <label class="<?= $label_class ?>">No HP (WhatsApp) *</label>
                        <input type="text" name="no_hp_ortu" id="edit_no_hp_ortu" required class="<?= $input_class ?>">
                    </div>
                    <div>
                        <label class="<?= $label_class ?>">Alamat Lengkap</label>
                        <textarea name="alamat" id="edit_alamat" rows="2" class="<?= $input_class ?> resize-none"></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="p-5 border-t border-[#E2E8F0] bg-white flex gap-3 flex-shrink-0">
            <button type="button" onclick="closeModalEdit()" class="<?= $btn_outline ?> flex-1">Batal</button>
            <button type="submit" form="formEdit" class="<?= $btn_primary ?> flex-1">Update Data</button>
        </div>
    </div>
</div>

<script>
function openModalTambah() { document.getElementById('modal-tambah').classList.remove('hidden'); }
function closeModalTambah() { document.getElementById('modal-tambah').classList.add('hidden'); }
function closeModalEdit() { document.getElementById('modal-edit').classList.add('hidden'); }

function resetPassword(id, nik) {
    if (confirm(`⚠️ Yakin ingin me-reset password untuk akun NIK: ${nik}?\n\nPassword akan dikembalikan ke default yaitu: 123456.\nAkun yang sedang login mungkin akan terkeluar.`)) {
        window.location.href = `../../actions/reset_pass_ortu.php?id=${id}`;
    }
}

function editOrtu(data) {
    document.getElementById('edit_id_ortu').value = data.id_ortu;
    document.getElementById('edit_nik_lama').value = data.nik_ortu;
    document.getElementById('edit_nik_ortu').value = data.nik_ortu;
    document.getElementById('edit_nama_ayah').value = data.nama_ayah;
    document.getElementById('edit_pekerjaan_ayah').value = data.pekerjaan_ayah;
    document.getElementById('edit_nama_ibu').value = data.nama_ibu;
    document.getElementById('edit_pekerjaan_ibu').value = data.pekerjaan_ibu;
    document.getElementById('edit_no_hp_ortu').value = data.no_hp_ortu;
    document.getElementById('edit_alamat').value = data.alamat;
    
    document.getElementById('modal-edit').classList.remove('hidden');
}

function hapusOrtu(id, nik, jumlah_anak) {
    let msg = `⚠️ Yakin ingin menghapus Data Wali Murid (NIK: ${nik})?`;
    if (jumlah_anak > 0) {
        msg += `\n\nPERINGATAN: Ada ${jumlah_anak} data siswa yang terhubung dengan akun ini! Jika dihapus, siswa tersebut tidak akan memiliki akses Portal Orang Tua sampai di-set ulang.`;
    }
    
    if (confirm(msg)) {
        window.location.href = `../../actions/hapus_ortu.php?id=${id}`;
    }
}
</script>

</body>
</html>