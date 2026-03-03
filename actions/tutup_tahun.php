<?php
/**
 * SITAPSI - Tutup Tahun Ajaran
 * Logic: Arsip tahun lama, Buat tahun baru, Luluskan kelas 9, Copy siswa aktif
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

// Cegah akses langsung via URL
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/pengaturan_akademik.php');
    exit;
}

try {
    // 1. Inisialisasi Koneksi & Validasi Input
    $pdo = getDBConnection();
    
    $nama_tahun_baru = trim($_POST['nama_tahun_baru'] ?? '');

    if (empty($nama_tahun_baru)) {
        throw new Exception('Nama tahun ajaran baru wajib diisi.');
    }

    // Cek duplikasi nama tahun
    $cek = fetchOne("SELECT id_tahun FROM tb_tahun_ajaran WHERE nama_tahun = :nama", ['nama' => $nama_tahun_baru]);
    if ($cek) {
        throw new Exception("Tahun ajaran '$nama_tahun_baru' sudah ada di database!");
    }

    // Ambil tahun yang sedang aktif saat ini
    $tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
    if (!$tahun_aktif) {
        throw new Exception("Tidak ada tahun ajaran aktif saat ini.");
    }

    $nama_tahun_lama = $tahun_aktif['nama_tahun'];

    // ============================================================
    // MULAI TRANSAKSI DATABASE
    // ============================================================
    $pdo->beginTransaction();

    // 1. Arsipkan Tahun Lama
    executeQuery("UPDATE tb_tahun_ajaran SET status = 'Arsip' WHERE status = 'Aktif'");

    // 2. Buat Tahun Baru (Otomatis di-set ke Ganjil)
    executeQuery("
        INSERT INTO tb_tahun_ajaran (nama_tahun, status, semester_aktif) 
        VALUES (:nama, 'Aktif', 'Ganjil')
    ", ['nama' => $nama_tahun_baru]);
    
    $id_tahun_baru = $pdo->lastInsertId();

    // 3. LULUSKAN OTOMATIS KELAS 9 (Mencegah kelas 9 ikut tercopy ke tahun baru) - DISESUAIKAN NO INDUK
    $kelas_9 = fetchAll("SELECT id_kelas FROM tb_kelas WHERE tingkat = 9");
    if (!empty($kelas_9)) {
        $kelas_ids = array_column($kelas_9, 'id_kelas');
        $placeholders = implode(',', array_fill(0, count($kelas_ids), '?'));
        
        $params = $kelas_ids;
        $params[] = $tahun_aktif['id_tahun'];
        
        $stmtLulus = $pdo->prepare("
            UPDATE tb_siswa 
            SET status_aktif = 'Lulus' 
            WHERE no_induk IN (
                SELECT DISTINCT no_induk 
                FROM tb_anggota_kelas 
                WHERE id_kelas IN ($placeholders)
                AND id_tahun = ?
            )
        ");
        $stmtLulus->execute($params);
    }
    
    // 4. Ambil semua siswa yang masih "Aktif" di tahun ajaran lama (selain kelas 9 yang baru diluluskan) - DISESUAIKAN NO INDUK
    $siswa_untuk_copy = fetchAll("
        SELECT a.no_induk, a.id_kelas 
        FROM tb_anggota_kelas a
        JOIN tb_siswa s ON a.no_induk = s.no_induk
        WHERE a.id_tahun = :id_tahun 
        AND s.status_aktif = 'Aktif'
    ", ['id_tahun' => $tahun_aktif['id_tahun']]);
    
    $jumlah_copy = 0;
    
    if (!empty($siswa_untuk_copy)) {
        // 5. Pindahkan (Copy) siswa ke Tahun Ajaran Baru
        // Menggunakan INSERT IGNORE untuk keamanan jika dijalankan ulang
        $stmtInsert = $pdo->prepare("
            INSERT IGNORE INTO tb_anggota_kelas (no_induk, id_kelas, id_tahun, total_poin_umum)
            VALUES (?, ?, ?, 0) 
        "); 
        // Note: total_poin_umum di-reset jadi 0 di tahun baru, tapi history poin lama tetap ada di tahun lama

        foreach ($siswa_untuk_copy as $siswa) {
            $stmtInsert->execute([
                $siswa['no_induk'],
                $siswa['id_kelas'], // Masih di kelas yang sama, nanti dipindah via fitur 'Kenaikan Kelas'
                $id_tahun_baru
            ]);
            $jumlah_copy++;
        }
    }

    // ============================================================
    // KOMIT TRANSAKSI
    // ============================================================
    $pdo->commit();

    $_SESSION['success_message'] = "✅ Berhasil! Tahun $nama_tahun_lama diarsipkan. Tahun $nama_tahun_baru kini aktif. Total $jumlah_copy siswa dipindahkan ke tahun baru.";

} catch (Exception $e) {
    // Rollback jika terjadi error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error Tutup Tahun: " . $e->getMessage());
    $_SESSION['error_message'] = "❌ Gagal: " . $e->getMessage();
}

header('Location: ../views/admin/pengaturan_akademik.php');
exit;