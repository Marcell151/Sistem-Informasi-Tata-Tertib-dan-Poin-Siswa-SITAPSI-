<?php
/**
 * SITAPSI - Detail Transaksi (AJAX - UI GLOBAL)
 * Dipanggil via AJAX untuk menampilkan detail di modal
 * PENYESUAIAN: Penampil Cerdas untuk Link Eksternal, PDF, Word, dan Gambar
 */

require_once '../../config/database.php';

$id_transaksi = $_GET['id'] ?? null;

if (!$id_transaksi) {
    echo '<div class="text-center text-red-600 py-8"><p class="font-bold">ID Transaksi tidak valid</p></div>';
    exit;
}

// Ambil header transaksi (DISESUAIKAN NO INDUK + lampiran_link)
$transaksi = fetchOne("
    SELECT 
        h.*,
        s.no_induk,
        s.nama_siswa,
        k.nama_kelas,
        g.nama_guru
    FROM tb_pelanggaran_header h
    JOIN tb_anggota_kelas a ON h.id_anggota = a.id_anggota
    JOIN tb_siswa s ON a.no_induk = s.no_induk
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    LEFT JOIN tb_guru g ON h.id_guru = g.id_guru
    WHERE h.id_transaksi = :id
", ['id' => $id_transaksi]);

if (!$transaksi) {
    echo '<div class="text-center text-red-600 py-8"><p class="font-bold">Transaksi tidak ditemukan</p></div>';
    exit;
}

// Ambil detail pelanggaran
$detail_pelanggaran = fetchAll("
    SELECT 
        d.poin_saat_itu,
        jp.nama_pelanggaran,
        k.nama_kategori
    FROM tb_pelanggaran_detail d
    JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
    JOIN tb_kategori_pelanggaran k ON jp.id_kategori = k.id_kategori
    WHERE d.id_transaksi = :id
    ORDER BY k.id_kategori, jp.nama_pelanggaran
", ['id' => $id_transaksi]);

// Ambil sanksi
$detail_sanksi = fetchAll("
    SELECT 
        sr.kode_sanksi,
        sr.deskripsi
    FROM tb_pelanggaran_sanksi s
    JOIN tb_sanksi_ref sr ON s.id_sanksi_ref = sr.id_sanksi_ref
    WHERE s.id_transaksi = :id
    ORDER BY CAST(sr.kode_sanksi AS UNSIGNED)
", ['id' => $id_transaksi]);

// Hitung total poin
$total_poin = array_sum(array_column($detail_pelanggaran, 'poin_saat_itu'));

// Parse foto (JSON array)
$foto_array = [];
if (!empty($transaksi['bukti_foto'])) {
    $foto_array = json_decode($transaksi['bukti_foto'], true) ?: [];
}

// Helper untuk cek apakah file adalah gambar
function isImage($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
}
?>

<div class="space-y-6">

    <div class="bg-slate-50 border border-[#E2E8F0] rounded-xl p-5 flex items-start space-x-4 shadow-sm">
        <div class="w-16 h-16 bg-[#000080] rounded-2xl flex items-center justify-center text-white font-extrabold text-2xl shadow-sm flex-shrink-0 overflow-hidden">
            <?php if(isset($transaksi['foto_profil']) && $transaksi['foto_profil']): ?>
                <img src="../../assets/uploads/siswa/<?= htmlspecialchars($transaksi['foto_profil']) ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <?= strtoupper(substr($transaksi['nama_siswa'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        
        <div class="flex-1">
            <h3 class="text-xl font-extrabold text-slate-800"><?= htmlspecialchars($transaksi['nama_siswa']) ?></h3>
            <p class="text-sm font-medium text-slate-600 mb-2.5"><?= htmlspecialchars($transaksi['nama_kelas']) ?> • No Induk: <?= htmlspecialchars($transaksi['no_induk']) ?></p>
            
            <div class="flex flex-wrap gap-2">
                <span class="px-2.5 py-1 bg-white border border-[#E2E8F0] text-slate-600 rounded-md text-xs font-bold flex items-center shadow-sm">
                    <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <?= date('d M Y', strtotime($transaksi['tanggal'])) ?> (<?= substr($transaksi['waktu'], 0, 5) ?>)
                </span>
                
                <span class="px-2.5 py-1 bg-white border border-[#E2E8F0] text-slate-600 rounded-md text-xs font-bold flex items-center shadow-sm">
                    <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <?= ($transaksi['id_guru'] == 0 || $transaksi['id_guru'] == null) ? 'Admin Tatibsi' : htmlspecialchars($transaksi['nama_guru']) ?>
                </span>
                
                <span class="px-2.5 py-1 bg-red-50 text-red-600 border border-red-200 rounded-md text-xs font-bold shadow-sm">
                    +<?= $total_poin ?> Poin
                </span>
            </div>
        </div>
    </div>

    <div>
        <h4 class="font-extrabold text-slate-800 text-sm mb-3 flex items-center uppercase tracking-wide">
            <svg class="w-4 h-4 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Rincian Pelanggaran
        </h4>
        <div class="space-y-2">
            <?php foreach ($detail_pelanggaran as $dp): ?>
            <div class="flex items-center justify-between p-3.5 bg-white border border-[#E2E8F0] rounded-xl shadow-sm hover:border-[#000080]/30 transition-colors">
                <div>
                    <span class="text-[10px] font-bold text-[#000080] uppercase tracking-wider bg-[#000080]/10 px-2 py-0.5 rounded mb-1 inline-block">
                        SILO <?= htmlspecialchars($dp['nama_kategori']) ?>
                    </span>
                    <p class="text-sm font-bold text-slate-700 leading-snug"><?= htmlspecialchars($dp['nama_pelanggaran']) ?></p>
                </div>
                <div class="text-right ml-4">
                    <span class="font-extrabold text-red-600 text-lg">+<?= $dp['poin_saat_itu'] ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if(!empty($detail_sanksi)): ?>
    <div>
        <h4 class="font-extrabold text-slate-800 text-sm mb-3 flex items-center uppercase tracking-wide mt-6">
            <svg class="w-4 h-4 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            Sanksi yang Diberikan
        </h4>
        <div class="space-y-2">
            <?php foreach ($detail_sanksi as $ds): ?>
            <div class="flex items-start p-3.5 bg-slate-50 border border-[#E2E8F0] rounded-xl shadow-sm">
                <span class="px-2 py-1 bg-[#000080] text-white rounded-md text-xs font-bold mr-3 flex-shrink-0 shadow-sm">
                    <?= $ds['kode_sanksi'] ?>
                </span>
                <p class="text-slate-700 text-sm font-medium pt-0.5 leading-snug"><?= htmlspecialchars($ds['deskripsi']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if(!empty($transaksi['lampiran_link']) || !empty($foto_array)): ?>
    <div>
        <h4 class="font-extrabold text-slate-800 text-sm mb-3 flex items-center uppercase tracking-wide mt-6">
            <svg class="w-4 h-4 mr-2 text-[#000080]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"></path></svg>
            Lampiran Bukti
        </h4>
        
        <div class="space-y-3">
            
            <?php if(!empty($transaksi['lampiran_link'])): ?>
                <a href="<?= htmlspecialchars($transaksi['lampiran_link']) ?>" target="_blank" rel="noopener noreferrer" 
                   class="flex items-center justify-between p-4 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-colors shadow-sm group">
                    <div class="flex items-center space-x-3 overflow-hidden">
                        <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center flex-shrink-0 shadow-sm text-blue-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-extrabold text-blue-900">Tautan Eksternal</p>
                            <p class="text-xs text-blue-700 truncate max-w-[200px] sm:max-w-xs"><?= htmlspecialchars($transaksi['lampiran_link']) ?></p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-blue-400 group-hover:text-blue-600 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                </a>
            <?php endif; ?>

            <?php if(!empty($foto_array)): ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <?php foreach ($foto_array as $file): ?>
                        
                        <?php if (isImage($file)): ?>
                            <a href="../../assets/uploads/bukti/<?= htmlspecialchars($file) ?>" target="_blank" class="block group relative rounded-xl overflow-hidden border border-[#E2E8F0] shadow-sm">
                                <img src="../../assets/uploads/bukti/<?= htmlspecialchars($file) ?>" class="w-full h-32 object-cover transition-transform duration-300 group-hover:scale-110">
                                <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </div>
                            </a>
                        <?php else: ?>
                            <a href="../../assets/uploads/bukti/<?= htmlspecialchars($file) ?>" target="_blank" class="flex flex-col items-center justify-center p-4 h-32 bg-slate-50 border border-[#E2E8F0] hover:bg-slate-100 hover:border-slate-300 rounded-xl transition-colors shadow-sm group">
                                <?php if(strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'pdf'): ?>
                                    <svg class="w-10 h-10 text-red-500 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                <?php else: ?>
                                    <svg class="w-10 h-10 text-blue-500 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                <?php endif; ?>
                                <p class="text-[10px] font-bold text-slate-600 text-center truncate w-full px-2" title="<?= htmlspecialchars($file) ?>"><?= htmlspecialchars($file) ?></p>
                            </a>
                        <?php endif; ?>

                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <?php endif; ?>

</div>