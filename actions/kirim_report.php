<?php
/**
 * SITAPSI - Kirim Report / Pengajuan Revisi
 * Memproses laporan dari Wali Kelas
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireGuru();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        
        // 1. Ambil inputan form
        $id_transaksi = $_POST['id_transaksi'] ?? null;
        $id_anggota = $_POST['id_anggota'] ?? null;
        $alasan_revisi = trim($_POST['alasan_revisi'] ?? '');
        $id_guru_login = $_SESSION['user_id'];

        if (!$id_transaksi || !$id_anggota || empty($alasan_revisi)) {
            throw new Exception("Data form tidak lengkap atau alasan belum diisi.");
        }

        // 2. AMBIL ID KELAS GURU (REAL-TIME DARI DATABASE)
        // Kita tidak pakai $_SESSION agar terhindar dari bug cache login lama
        $stmtGuru = $pdo->prepare("SELECT id_kelas FROM tb_guru WHERE id_guru = ?");
        $stmtGuru->execute([$id_guru_login]);
        $guru = $stmtGuru->fetch();

        if (!$guru || empty($guru['id_kelas'])) {
            throw new Exception("Anda tidak terdaftar sebagai Wali Kelas manapun.");
        }
        $id_kelas_guru = $guru['id_kelas'];

        // 3. AMBIL ID KELAS SISWA (REAL-TIME DARI DATABASE)
        $stmtSiswa = $pdo->prepare("SELECT id_kelas FROM tb_anggota_kelas WHERE id_anggota = ?");
        $stmtSiswa->execute([$id_anggota]);
        $siswa = $stmtSiswa->fetch();

        if (!$siswa) {
            throw new Exception("Data siswa tidak ditemukan di sistem.");
        }
        $id_kelas_siswa = $siswa['id_kelas'];

        // 4. COCOKKAN ID KELAS (Gunakan != agar angka 1 dan string "1" tetap dianggap sama)
        if ($id_kelas_guru != $id_kelas_siswa) {
            throw new Exception("Anda hanya bisa melaporkan pelanggaran siswa di kelas Anda sendiri (Siswa: Kelas ID {$id_kelas_siswa}, Anda: Kelas ID {$id_kelas_guru}).");
        }

        // 5. UPDATE STATUS TRANSAKSI JADI PENDING
        $stmtUpdate = $pdo->prepare("
            UPDATE tb_pelanggaran_header 
            SET status_revisi = 'Pending', 
                alasan_revisi = ? 
            WHERE id_transaksi = ?
        ");
        $stmtUpdate->execute([$alasan_revisi, $id_transaksi]);

        $_SESSION['success_message'] = "✅ Pengajuan revisi berhasil dikirim! Menunggu konfirmasi dari Admin.";

    } catch (Exception $e) {
        $_SESSION['error_message'] = "❌ Gagal: " . $e->getMessage();
    }
    
    // Redirect kembali ke halaman sebelumnya (Detail Siswa) dengan aman
    if (!empty($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        header("Location: ../views/guru/detail_siswa.php?id=" . ($id_anggota ?? ''));
    }
    exit;
} else {
    // Jika diakses tidak melalui POST
    header("Location: ../views/guru/rekap_kelas.php");
    exit;
}
?>