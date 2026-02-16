<?php
/**
 * SITAPSI - Detail Transaksi (AJAX)
 * Dipanggil via AJAX untuk menampilkan detail di modal
 */

require_once '../../config/database.php';

$id_transaksi = $_GET['id'] ?? null;

if (!$id_transaksi) {
    echo '<div class="text-center text-red-600 py-8"><p class="font-bold">ID Transaksi tidak valid</p></div>';
    exit;
}

// Ambil header transaksi
$transaksi = fetchOne("
    SELECT 
        h.*,
        s.nis,
        s.nama_siswa,
        s.foto_profil,
        k.nama_kelas,
        g.nama_guru
    FROM tb_pelanggaran_header h
    JOIN tb_anggota_kelas a ON h.id_anggota = a.id_anggota
    JOIN tb_siswa s ON a.nis = s.nis
    JOIN tb_kelas k ON a.id_kelas = k.id_kelas
    JOIN tb_guru g ON h.id_guru = g.id_guru
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
?>

<!-- Info Siswa -->
<div class="bg-gradient-to-r from-navy to-blue-800 text-white rounded-lg p-6 mb-6">
    <div class="flex items-center space-x-4">
        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center overflow-hidden">
            <?php if($transaksi['foto_profil']): ?>
                <img src="../../assets/uploads/siswa/<?= $transaksi['foto_profil'] ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <span class="text-navy font-bold text-2xl"><?= strtoupper(substr($transaksi['nama_siswa'], 0, 1)) ?></span>
            <?php endif; ?>
        </div>
        <div class="flex-1">
            <h3 class="text-xl font-bold"><?= htmlspecialchars($transaksi['nama_siswa']) ?></h3>
            <p class="text-blue-200 text-sm"><?= $transaksi['nama_kelas'] ?> • <?= $transaksi['nis'] ?></p>
        </div>
        <div class="text-right">
            <p class="text-blue-200 text-xs">Total Poin</p>
            <p class="text-3xl font-bold">+<?= $total_poin ?></p>
        </div>
    </div>
</div>

<!-- Info Transaksi -->
<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="bg-gray-50 p-4 rounded-lg">
        <p class="text-xs text-gray-500 font-medium">ID Transaksi</p>
        <p class="text-lg font-bold text-gray-800">#<?= $transaksi['id_transaksi'] ?></p>
    </div>
    <div class="bg-gray-50 p-4 rounded-lg">
        <p class="text-xs text-gray-500 font-medium">Tanggal & Waktu</p>
        <p class="text-lg font-bold text-gray-800"><?= date('d/m/Y', strtotime($transaksi['tanggal'])) ?></p>
        <p class="text-sm text-gray-600"><?= substr($transaksi['waktu'], 0, 5) ?> WIB</p>
    </div>
    <div class="bg-gray-50 p-4 rounded-lg">
        <p class="text-xs text-gray-500 font-medium">Tipe Form</p>
        <p class="text-lg font-bold text-gray-800"><?= $transaksi['tipe_form'] ?></p>
    </div>
    <div class="bg-gray-50 p-4 rounded-lg">
        <p class="text-xs text-gray-500 font-medium">Pelapor</p>
        <p class="text-lg font-bold text-gray-800"><?= htmlspecialchars($transaksi['nama_guru']) ?></p>
    </div>
</div>

<!-- Detail Pelanggaran -->
<div class="mb-6">
    <h4 class="font-bold text-gray-800 mb-3 flex items-center">
        <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        Detail Pelanggaran
    </h4>
    <div class="space-y-2">
        <?php foreach($detail_pelanggaran as $dp): ?>
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex-1">
                <span class="px-2 py-1 rounded-full text-xs font-medium mr-2
                    <?= $dp['nama_kategori'] === 'KELAKUAN' ? 'bg-red-100 text-red-800' : '' ?>
                    <?= $dp['nama_kategori'] === 'KERAJINAN' ? 'bg-blue-100 text-blue-800' : '' ?>
                    <?= $dp['nama_kategori'] === 'KERAPIAN' ? 'bg-yellow-100 text-yellow-800' : '' ?>">
                    <?= $dp['nama_kategori'] ?>
                </span>
                <span class="text-gray-800 font-medium"><?= htmlspecialchars($dp['nama_pelanggaran']) ?></span>
            </div>
            <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full font-bold text-sm">
                +<?= $dp['poin_saat_itu'] ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Sanksi -->
<?php if(!empty($detail_sanksi)): ?>
<div class="mb-6">
    <h4 class="font-bold text-gray-800 mb-3 flex items-center">
        <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
        </svg>
        Sanksi yang Diberikan
    </h4>
    <div class="space-y-2">
        <?php foreach($detail_sanksi as $ds): ?>
        <div class="flex items-start p-3 bg-yellow-50 rounded-lg border border-yellow-200">
            <span class="px-2 py-1 bg-yellow-600 text-white rounded-full text-xs font-bold mr-3 flex-shrink-0">
                <?= $ds['kode_sanksi'] ?>
            </span>
            <p class="text-gray-800 text-sm"><?= htmlspecialchars($ds['deskripsi']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Bukti Foto -->
<?php if(!empty($foto_array)): ?>
<div>
    <h4 class="font-bold text-gray-800 mb-3 flex items-center">
        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        Bukti Foto (<?= count($foto_array) ?>)
    </h4>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <?php foreach($foto_array as $foto): ?>
        <a href="../../assets/uploads/bukti/<?= $foto ?>" target="_blank" class="block">
            <img src="../../assets/uploads/bukti/<?= $foto ?>" 
                 class="w-full h-32 object-cover rounded-lg border-2 border-gray-200 hover:border-navy transition-colors cursor-pointer"
                 alt="Bukti">
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>