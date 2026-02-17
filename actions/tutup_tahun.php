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
        throw new Exception('Data tahun ajaran aktif tidak ditemukan.');
    }

    $id_tahun_lama = (int) $tahun_aktif['id_tahun'];
    $nama_tahun_lama = $tahun_aktif['nama_tahun'];

    // ============================================================
    // MULAI TRANSAKSI DATABASE
    // ============================================================
    $pdo->beginTransaction();

    // ------------------------------------------------------------
    // STEP 1: Arsipkan Tahun Lama
    // ------------------------------------------------------------
    $stmtArsip = $pdo->prepare("UPDATE tb_tahun_ajaran SET status = 'Arsip' WHERE id_tahun = :id");
    $stmtArsip->execute(['id' => $id_tahun_lama]);

    // ------------------------------------------------------------
    // STEP 2: Buat Tahun Ajaran Baru
    // ------------------------------------------------------------
    $stmtBaru = $pdo->prepare("INSERT INTO tb_tahun_ajaran (nama_tahun, semester_aktif, status) VALUES (:nama, 'Ganjil', 'Aktif')");
    $stmtBaru->execute(['nama' => $nama_tahun_baru]);
    
    $id_tahun_baru = $pdo->lastInsertId();

    // ------------------------------------------------------------
    // STEP 3: Luluskan Siswa Kelas 9 (Di Tahun Lama)
    // ------------------------------------------------------------
    // Logika: Siswa yang berada di kelas tingkat 9 pada tahun lama, statusnya jadi 'Lulus'
    $stmtLulus = $pdo->prepare("
        UPDATE tb_siswa 
        SET status_aktif = 'Lulus' 
        WHERE nis IN (
            SELECT DISTINCT a.nis 
            FROM tb_anggota_kelas a
            JOIN tb_kelas k ON a.id_kelas = k.id_kelas
            WHERE a.id_tahun = :id_tahun
            AND k.tingkat = 9
        )
    ");
    $stmtLulus->execute(['id_tahun' => $id_tahun_lama]);

    // ------------------------------------------------------------
    // STEP 4: Salin Siswa Aktif ke Tahun Baru
    // ------------------------------------------------------------
    
    // 4a. Cari ID anggota terakhir (terbaru) untuk setiap siswa di tahun lama
    // Tujuannya menghindari duplikasi jika data kotor
    $max_per_nis = fetchAll("
        SELECT MAX(id_anggota) as max_id
        FROM tb_anggota_kelas
        WHERE id_tahun = :id_tahun
        GROUP BY nis
    ", ['id_tahun' => $id_tahun_lama]);

    $jumlah_copy = 0;

    if (!empty($max_per_nis)) {
        // Ambil array ID saja
        $max_ids = array_column($max_per_nis, 'max_id');

        // Buat string placeholder (?,?,?) untuk query IN
        $placeholders = implode(',', array_fill(0, count($max_ids), '?'));

        // 4b. Ambil detail siswa (NIS & Kelas Terakhir) hanya yang statusnya Masih Aktif
        // (Siswa yang baru diluluskan di Step 3 tidak akan ikut terambil karena statusnya sudah 'Lulus')
        $stmtGetSiswa = $pdo->prepare("
            SELECT a.nis, a.id_kelas
            FROM tb_anggota_kelas a
            JOIN tb_siswa s ON a.nis = s.nis
            WHERE a.id_anggota IN ($placeholders)
            AND s.status_aktif = 'Aktif'
        ");
        $stmtGetSiswa->execute($max_ids);
        $siswa_untuk_copy = $stmtGetSiswa->fetchAll(PDO::FETCH_ASSOC);

        // 4c. Insert ke Tahun Baru
        // Menggunakan INSERT IGNORE untuk keamanan jika dijalankan ulang
        $stmtInsert = $pdo->prepare("
            INSERT IGNORE INTO tb_anggota_kelas (nis, id_kelas, id_tahun, total_poin_umum)
            VALUES (?, ?, ?, 0) 
        "); 
        // Note: total_poin_umum di-reset jadi 0 di tahun baru, tapi history poin lama tetap ada di tahun lama

        foreach ($siswa_untuk_copy as $siswa) {
            $stmtInsert->execute([
                $siswa['nis'],
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

    // Hapus tag HTML (<strong>) agar tampilan bersih di notifikasi
    $_SESSION['success_message'] = "✅ Berhasil! Tahun $nama_tahun_lama diarsipkan. Tahun $nama_tahun_baru kini aktif. Total $jumlah_copy siswa dipindahkan ke tahun baru.";

} catch (Exception $e) {
    // Rollback jika terjadi error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error Tutup Tahun: " . $e->getMessage());
    $_SESSION['error_message'] = '❌ Gagal melakukan tutup tahun: ' . $e->getMessage();
}

header('Location: ../views/admin/pengaturan_akademik.php');
exit;
?>