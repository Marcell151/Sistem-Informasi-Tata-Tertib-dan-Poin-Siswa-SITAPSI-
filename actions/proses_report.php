<?php
/**
 * SITAPSI - Proses Report (MANUAL TICKETING)
 * Hanya mengubah status menjadi Disetujui/Ditolak. Tidak melakukan hapus otomatis.
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

$action = $_GET['action'] ?? null;
$id_transaksi = $_GET['id'] ?? null;

if (!$action || !$id_transaksi) {
    $_SESSION['error_message'] = '❌ Parameter tidak valid';
    header('Location: ../views/admin/kelola_report.php');
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Ambil detail transaksi
    $transaksi = fetchOne("
        SELECT 
            h.id_transaksi,
            h.status_revisi,
            s.nama_siswa
        FROM tb_pelanggaran_header h
        JOIN tb_anggota_kelas a ON h.id_anggota = a.id_anggota
        JOIN tb_siswa s ON a.nis = s.nis
        WHERE h.id_transaksi = :id
    ", ['id' => $id_transaksi]);
    
    if (!$transaksi) {
        throw new Exception('Transaksi tidak ditemukan');
    }
    
    if ($transaksi['status_revisi'] !== 'Pending') {
        throw new Exception('Report ini sudah diproses sebelumnya');
    }
    
    if ($action === 'setujui') {
        // HANYA UPDATE STATUS (MANUAL MODE)
        executeQuery("
            UPDATE tb_pelanggaran_header 
            SET status_revisi = 'Disetujui',
                alasan_revisi = CONCAT(alasan_revisi, '\n\n[ADMIN]: Keluhan Diterima. Data akan dikoreksi manual.')
            WHERE id_transaksi = :id
        ", ['id' => $id_transaksi]);
        
        $_SESSION['success_message'] = "✅ Report disetujui! Silakan pergi ke halaman Audit Harian / Detail Siswa untuk menghapus/mengedit transaksi {$transaksi['nama_siswa']} secara manual.";
        
    } elseif ($action === 'tolak') {
        // UPDATE STATUS DITOLAK
        $alasan_tolak = $_GET['alasan'] ?? 'Tidak ada kesalahan input (Ditolak Admin)';
        
        executeQuery("
            UPDATE tb_pelanggaran_header 
            SET status_revisi = 'Ditolak',
                alasan_revisi = CONCAT(alasan_revisi, '\n\n[ADMIN]: ', :alasan_tolak)
            WHERE id_transaksi = :id
        ", [
            'alasan_tolak' => $alasan_tolak,
            'id' => $id_transaksi
        ]);
        
        $_SESSION['success_message'] = "❌ Report ditolak.";
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = '❌ Gagal: ' . $e->getMessage();
}

header('Location: ../views/admin/kelola_report.php');
exit;
?>