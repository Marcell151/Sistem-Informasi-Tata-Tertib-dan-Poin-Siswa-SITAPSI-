<?php
/**
 * SITAPSI - Hapus Transaksi
 * Menghapus transaksi pelanggaran dan rollback poin otomatis
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

$id_transaksi = $_GET['id'] ?? null;
$redirect = $_GET['redirect'] ?? 'audit';

if (!$id_transaksi) {
    $_SESSION['error_message'] = '❌ ID transaksi tidak valid';
    header('Location: ../views/admin/audit_harian.php');
    exit;
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    // 1. Ambil detail poin per kategori untuk rollback
    $details = fetchAll("
        SELECT 
            h.id_anggota,
            jp.id_kategori, 
            SUM(d.poin_saat_itu) as poin
        FROM tb_pelanggaran_header h
        JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
        JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
        WHERE h.id_transaksi = :id
        GROUP BY h.id_anggota, jp.id_kategori
    ", ['id' => $id_transaksi]);
    
    if (empty($details)) {
        throw new Exception('Transaksi tidak ditemukan');
    }
    
    $id_anggota = $details[0]['id_anggota'];
    
    // 2. Rollback poin di tb_anggota_kelas
    foreach ($details as $detail) {
        $kategori = $detail['id_kategori'];
        $poin = $detail['poin'];
        
        if ($kategori == 1) {
            executeQuery("UPDATE tb_anggota_kelas SET poin_kelakuan = GREATEST(0, poin_kelakuan - :poin), total_poin_umum = GREATEST(0, total_poin_umum - :poin) WHERE id_anggota = :id", 
                ['poin' => $poin, 'id' => $id_anggota]);
        } elseif ($kategori == 2) {
            executeQuery("UPDATE tb_anggota_kelas SET poin_kerajinan = GREATEST(0, poin_kerajinan - :poin), total_poin_umum = GREATEST(0, total_poin_umum - :poin) WHERE id_anggota = :id", 
                ['poin' => $poin, 'id' => $id_anggota]);
        } elseif ($kategori == 3) {
            executeQuery("UPDATE tb_anggota_kelas SET poin_kerapian = GREATEST(0, poin_kerapian - :poin), total_poin_umum = GREATEST(0, total_poin_umum - :poin) WHERE id_anggota = :id", 
                ['poin' => $poin, 'id' => $id_anggota]);
        }
    }
    
    // 3. Hapus header (CASCADE akan handle detail & sanksi)
    executeQuery("DELETE FROM tb_pelanggaran_header WHERE id_transaksi = :id", ['id' => $id_transaksi]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = '✅ Transaksi berhasil dihapus dan poin telah dikurangi';
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = '❌ Gagal menghapus: ' . $e->getMessage();
}

// Redirect
if ($redirect === 'audit') {
    header('Location: ../views/admin/audit_harian.php');
} else {
    header('Location: ../views/admin/monitoring_siswa.php');
}
exit;
?>