<?php
/**
 * SITAPSI - Stress Test Transaksi
 * Menyuntikkan 1000 Pelanggaran Acak ke Database (Koneksi Mandiri)
 */

session_start();
require_once '../../config/database.php';

// Validasi Keamanan
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'SuperAdmin' && $_SESSION['role'] !== 'Admin')) {
    die("Akses ditolak! Hanya Admin/SuperAdmin yang boleh menjalankan Stress Test.");
}

// =======================================================
// KONEKSI MANDIRI ANTI-GAGAL
// =======================================================
try {
    $db_stress = new PDO("mysql:host=localhost;dbname=db_sitapsi;charset=utf8", "root", "");
    $db_stress->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

echo "<h3>Memulai injeksi 1000 data pelanggaran...</h3>";

try {
    // 1. Ambil Data Referensi menggunakan koneksi mandiri
    $stmt_tahun = $db_stress->query("SELECT id_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
    $tahun_aktif = $stmt_tahun->fetch(PDO::FETCH_ASSOC);
    if (!$tahun_aktif) die("Tidak ada tahun ajaran aktif!");
    
    $id_tahun = $tahun_aktif['id_tahun'];
    $semester = $tahun_aktif['semester_aktif'];

    $stmt_anggota = $db_stress->query("SELECT id_anggota FROM tb_anggota_kelas WHERE id_tahun = $id_tahun");
    $anggota = $stmt_anggota->fetchAll(PDO::FETCH_ASSOC);

    $stmt_guru = $db_stress->query("SELECT id_guru FROM tb_guru");
    $guru = $stmt_guru->fetchAll(PDO::FETCH_ASSOC);

    $stmt_jenis = $db_stress->query("SELECT id_jenis, id_kategori, poin_default FROM tb_jenis_pelanggaran");
    $jenis = $stmt_jenis->fetchAll(PDO::FETCH_ASSOC);

    if (empty($anggota) || empty($guru) || empty($jenis)) {
        die("Data referensi (Siswa/Guru/Pelanggaran) belum lengkap. Pastikan Anda sudah mengimport 500 siswa dummy tadi.");
    }

    // Mulai Transaksi agar 1000 data masuk secepat kilat (menggunakan $db_stress)
    $db_stress->beginTransaction(); 

    $sukses = 0;
    for ($i = 1; $i <= 1000; $i++) {
        // Pilih acak
        $rand_anggota = $anggota[array_rand($anggota)]['id_anggota'];
        $rand_guru    = $guru[array_rand($guru)]['id_guru'];
        $rand_jenis   = $jenis[array_rand($jenis)];
        
        // Tanggal Acak 3 bulan terakhir
        $tgl_acak = date('Y-m-d', strtotime('-'.rand(0, 90).' days'));
        $waktu_acak = str_pad(rand(7, 14), 2, "0", STR_PAD_LEFT) . ":" . str_pad(rand(0, 59), 2, "0", STR_PAD_LEFT) . ":00";
        $tipe_form = (rand(0, 1) == 0) ? 'Piket' : 'Kelas';

        // Insert Header
        $stmtH = $db_stress->prepare("INSERT INTO tb_pelanggaran_header (id_anggota, id_guru, id_tahun, tanggal, waktu, semester, tipe_form, status_revisi) VALUES (?, ?, ?, ?, ?, ?, ?, 'Disetujui')");
        $stmtH->execute([$rand_anggota, $rand_guru, $id_tahun, $tgl_acak, $waktu_acak, $semester, $tipe_form]);
        $id_trans = $db_stress->lastInsertId();

        // Insert Detail
        $stmtD = $db_stress->prepare("INSERT INTO tb_pelanggaran_detail (id_transaksi, id_jenis, poin_saat_itu) VALUES (?, ?, ?)");
        $stmtD->execute([$id_trans, $rand_jenis['id_jenis'], $rand_jenis['poin_default']]);

        // Update Poin Akumulasi di tb_anggota_kelas
        $kolom_poin = '';
        if ($rand_jenis['id_kategori'] == 1) $kolom_poin = 'poin_kelakuan';
        elseif ($rand_jenis['id_kategori'] == 2) $kolom_poin = 'poin_kerajinan';
        elseif ($rand_jenis['id_kategori'] == 3) $kolom_poin = 'poin_kerapian';

        $stmtU = $db_stress->prepare("UPDATE tb_anggota_kelas SET $kolom_poin = $kolom_poin + ?, total_poin_umum = total_poin_umum + ? WHERE id_anggota = ?");
        $stmtU->execute([$rand_jenis['poin_default'], $rand_jenis['poin_default'], $rand_anggota]);

        $sukses++;
    }

    $db_stress->commit();
    echo "<h2 style='color:green;'>Selesai! $sukses pelanggaran berhasil disuntikkan secara acak ke 500 siswa Anda!</h2>";
    echo "<p><a href='../views/admin/monitoring_siswa_list.php'>Klik di sini untuk melihat hasilnya di halaman Monitoring</a></p>";

} catch (Exception $e) {
    if ($db_stress->inTransaction()) {
        $db_stress->rollBack();
    }
    die("Gagal melakukan stress test: " . $e->getMessage());
}
?>