<?php
/**
 * SITAPSI - Hapus Transaksi Pelanggaran (FIXED - HY093)
 * Bug fix: Parameter PDO yang duplikat
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';
require_once '../includes/sp_helper.php';

requireAdmin();

$id_transaksi = $_GET['id'] ?? null;
$redirect = $_GET['redirect'] ?? 'audit';
$id_anggota_redirect = $_GET['anggota'] ?? null;

if (!$id_transaksi) {
    $_SESSION['error_message'] = '❌ ID transaksi tidak valid';
    header('Location: ../views/admin/audit_harian.php');
    exit;
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    // 1. Ambil detail untuk rollback poin (gunakan query terpisah untuk hindari parameter duplikat)
    $stmt = $pdo->prepare("
        SELECT 
            h.id_anggota,
            jp.id_kategori, 
            d.poin_saat_itu
        FROM tb_pelanggaran_header h
        JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
        JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
        WHERE h.id_transaksi = ?
    ");
    $stmt->execute([$id_transaksi]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($details)) {
        throw new Exception('Transaksi tidak ditemukan');
    }
    
    $id_anggota = $details[0]['id_anggota'];
    
    // 2. Hitung total poin per kategori yang akan di-rollback
    $rollback_kelakuan = 0;
    $rollback_kerajinan = 0;
    $rollback_kerapian = 0;
    
    foreach ($details as $detail) {
        if ($detail['id_kategori'] == 1) {
            $rollback_kelakuan += $detail['poin_saat_itu'];
        } elseif ($detail['id_kategori'] == 2) {
            $rollback_kerajinan += $detail['poin_saat_itu'];
        } elseif ($detail['id_kategori'] == 3) {
            $rollback_kerapian += $detail['poin_saat_itu'];
        }
    }
    
    $rollback_total = $rollback_kelakuan + $rollback_kerajinan + $rollback_kerapian;
    
    // 3. Rollback poin di tb_anggota_kelas
    $stmtUpdate = $pdo->prepare("
        UPDATE tb_anggota_kelas 
        SET poin_kelakuan = GREATEST(0, poin_kelakuan - ?),
            poin_kerajinan = GREATEST(0, poin_kerajinan - ?),
            poin_kerapian = GREATEST(0, poin_kerapian - ?),
            total_poin_umum = GREATEST(0, total_poin_umum - ?)
        WHERE id_anggota = ?
    ");
    $stmtUpdate->execute([
        $rollback_kelakuan,
        $rollback_kerajinan,
        $rollback_kerapian,
        $rollback_total,
        $id_anggota
    ]);
    
    // 4. Hapus header (CASCADE akan hapus detail & sanksi otomatis)
    $stmtDelete = $pdo->prepare("DELETE FROM tb_pelanggaran_header WHERE id_transaksi = ?");
    $stmtDelete->execute([$id_transaksi]);
    
    $pdo->commit();
    
    // 5. RECALCULATE SP OTOMATIS setelah commit
    recalculateStatusSP($id_anggota);
    
    $_SESSION['success_message'] = "✅ Transaksi berhasil dihapus! Poin dikurangi -$rollback_total & status SP diperbarui.";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = '❌ Gagal menghapus: ' . $e->getMessage();
}

// Redirect
if ($redirect === 'monitoring' && $id_anggota_redirect) {
    header("Location: ../views/admin/detail_siswa.php?id=$id_anggota_redirect");
} else {
    header('Location: ../views/admin/audit_harian.php');
}
exit;
?>