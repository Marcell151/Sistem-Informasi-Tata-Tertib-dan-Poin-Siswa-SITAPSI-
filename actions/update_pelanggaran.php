<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';
require_once '../includes/sp_helper.php'; // TAMBAH INI

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/audit_harian.php');
    exit;
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    $id_transaksi = $_POST['id_transaksi'];
    $id_anggota = $_POST['id_anggota'];
    $pelanggaran_ids = $_POST['pelanggaran'] ?? [];
    $sanksi_ids = $_POST['sanksi'] ?? [];
    
    if (empty($id_transaksi) || empty($id_anggota)) {
        throw new Exception('Data transaksi tidak valid');
    }
    
    if (empty($pelanggaran_ids)) {
        throw new Exception('Minimal pilih 1 pelanggaran');
    }
    
    // 1. Ambil poin LAMA per kategori
    $old_poin = fetchAll("
        SELECT jp.id_kategori, SUM(d.poin_saat_itu) as total
        FROM tb_pelanggaran_detail d
        JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
        WHERE d.id_transaksi = :id
        GROUP BY jp.id_kategori
    ", ['id' => $id_transaksi]);
    
    $old_kelakuan = 0; $old_kerajinan = 0; $old_kerapian = 0;
    foreach ($old_poin as $op) {
        if ($op['id_kategori'] == 1) $old_kelakuan = $op['total'];
        elseif ($op['id_kategori'] == 2) $old_kerajinan = $op['total'];
        elseif ($op['id_kategori'] == 3) $old_kerapian = $op['total'];
    }
    $old_total = $old_kelakuan + $old_kerajinan + $old_kerapian;
    
    // 2. Hapus detail dan sanksi lama
    executeQuery("DELETE FROM tb_pelanggaran_detail WHERE id_transaksi = :id", ['id' => $id_transaksi]);
    executeQuery("DELETE FROM tb_pelanggaran_sanksi WHERE id_transaksi = :id", ['id' => $id_transaksi]);
    
    // 3. Insert detail BARU dan hitung poin baru
    $new_kelakuan = 0; $new_kerajinan = 0; $new_kerapian = 0;
    
    $stmtD = $pdo->prepare("INSERT INTO tb_pelanggaran_detail (id_transaksi, id_jenis, poin_saat_itu) VALUES (?, ?, ?)");
    $stmtI = $pdo->prepare("SELECT poin_default, id_kategori FROM tb_jenis_pelanggaran WHERE id_jenis = ?");
    
    foreach ($pelanggaran_ids as $id_jenis) {
        $stmtI->execute([$id_jenis]);
        $info = $stmtI->fetch();
        if ($info) {
            $stmtD->execute([$id_transaksi, $id_jenis, $info['poin_default']]);
            if ($info['id_kategori'] == 1) $new_kelakuan += $info['poin_default'];
            elseif ($info['id_kategori'] == 2) $new_kerajinan += $info['poin_default'];
            elseif ($info['id_kategori'] == 3) $new_kerapian += $info['poin_default'];
        }
    }
    
    $new_total = $new_kelakuan + $new_kerajinan + $new_kerapian;
    
    // 4. Insert sanksi baru
    if (!empty($sanksi_ids)) {
        $stmtS = $pdo->prepare("INSERT INTO tb_pelanggaran_sanksi (id_transaksi, id_sanksi_ref) VALUES (?, ?)");
        foreach ($sanksi_ids as $id_s) {
            $stmtS->execute([$id_transaksi, $id_s]);
        }
    }
    
    // 5. SINKRONISASI POIN
    $diff_kelakuan = $new_kelakuan - $old_kelakuan;
    $diff_kerajinan = $new_kerajinan - $old_kerajinan;
    $diff_kerapian = $new_kerapian - $old_kerapian;
    $diff_total = $new_total - $old_total;
    
    executeQuery("
        UPDATE tb_anggota_kelas 
        SET poin_kelakuan = GREATEST(0, poin_kelakuan + :dk),
            poin_kerajinan = GREATEST(0, poin_kerajinan + :drj),
            poin_kerapian = GREATEST(0, poin_kerapian + :drp),
            total_poin_umum = GREATEST(0, total_poin_umum + :dt)
        WHERE id_anggota = :id
    ", [
        'dk' => $diff_kelakuan,
        'drj' => $diff_kerajinan,
        'drp' => $diff_kerapian,
        'dt' => $diff_total,
        'id' => $id_anggota
    ]);
    
    $pdo->commit();
    
    // 6. RECALCULATE SP OTOMATIS (FIX BUG 3)
    recalculateStatusSP($id_anggota);
    
    $diff_info = $diff_total >= 0 ? "+$diff_total" : "$diff_total";
    $_SESSION['success_message'] = "✅ Pelanggaran berhasil diupdate! Selisih poin: $diff_info. Status SP diperbarui.";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = '❌ Gagal update: ' . $e->getMessage();
}

header('Location: ../views/admin/audit_harian.php');
exit;
?>