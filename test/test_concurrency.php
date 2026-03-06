<?php
/**
 * SITAPSI - Uji Konkurensi / Race Condition
 * Mensimulasikan 50 Guru menginput pelanggaran untuk 1 siswa yang sama di detik yang sama!
 */
require_once '../config/database.php';

echo "<h3>Memulai Serangan 50 Guru Bersamaan...</h3>";

try {
    // Koneksi mandiri
    $db_test = new PDO("mysql:host=localhost;dbname=db_sitapsi;charset=utf8", "root", "");
    $db_test->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Kita cari 1 siswa acak sebagai "Korban"
    $stmt_korban = $db_test->query("SELECT id_anggota, poin_kelakuan FROM tb_anggota_kelas LIMIT 1");
    $korban = $stmt_korban->fetch(PDO::FETCH_ASSOC);
    $id_korban = $korban['id_anggota'];
    $poin_awal = $korban['poin_kelakuan'];

    echo "Poin Kelakuan Awal Siswa (ID: $id_korban) = <b>$poin_awal</b><br>";

    // Kita buat 50 guru menembak poin +10 secara "bersamaan" tanpa jeda
    $sukses = 0;
    for ($i = 1; $i <= 50; $i++) {
        // Update poin (Menggunakan cara Atomic: poin = poin + X)
        $stmt_update = $db_test->prepare("UPDATE tb_anggota_kelas SET poin_kelakuan = poin_kelakuan + 10 WHERE id_anggota = ?");
        $stmt_update->execute([$id_korban]);
        $sukses++;
    }

    // Cek poin akhir
    $stmt_akhir = $db_test->query("SELECT poin_kelakuan FROM tb_anggota_kelas WHERE id_anggota = $id_korban");
    $hasil_akhir = $stmt_akhir->fetch(PDO::FETCH_ASSOC);
    $poin_akhir = $hasil_akhir['poin_kelakuan'];

    echo "<br>Selesai! $sukses Guru menembakkan poin +10.<br>";
    echo "Poin Akhir Siswa Seharusnya: <b>" . ($poin_awal + 500) . "</b><br>";
    echo "Poin Akhir di Database: <b>$poin_akhir</b><br>";

    if (($poin_awal + 500) == $poin_akhir) {
        echo "<h2 style='color:green;'>LULUS UJI! MySQL berhasil mengantrekan data, tidak ada poin yang hilang/bentrok!</h2>";
    } else {
        echo "<h2 style='color:red;'>GAGAL! Terjadi Data Collision (Tabrakan Data).</h2>";
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>