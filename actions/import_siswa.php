<?php
/**
 * SITAPSI - Import Siswa dari Excel/CSV
 * Disesuaikan dengan struktur database baru
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/data_siswa.php');
    exit;
}

// Cek file upload
if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error_message'] = '❌ File tidak valid atau gagal diupload';
    header('Location: ../views/admin/data_siswa.php');
    exit;
}

$file = $_FILES['file_excel'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Validasi ekstensi
if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
    $_SESSION['error_message'] = '❌ Format file harus CSV atau Excel (.xlsx, .xls)';
    header('Location: ../views/admin/data_siswa.php');
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Ambil tahun ajaran aktif
    $tahun_aktif = fetchOne("SELECT id_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
    
    if (!$tahun_aktif) {
        throw new Exception('Tidak ada Tahun Ajaran Aktif. Silakan set di Pengaturan Akademik.');
    }
    
    $id_tahun = $tahun_aktif['id_tahun'];
    $pdo->beginTransaction();
    
    $handle = fopen($file['tmp_name'], 'r');
    
    // Lewati baris header (baris pertama)
    fgetcsv($handle, 1000, ',');
    
    $success = 0;
    
    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
        // Asumsi format kolom CSV sesuai dengan template yang di-download
        // 0:No Induk, 1:Nama, 2:JK, 3:Kota, 4:Tgl Lahir, 5:Alamat, 6:Nama Ayah, 7:Pek Ayah, 8:Nama Ibu, 9:Pek Ibu, 10:HP, 11:Kelas
        
        if (empty($data[0]) || empty($data[1])) continue; // Skip jika no_induk atau nama kosong
        
        $no_induk = trim($data[0]);
        $nama_siswa = trim($data[1]);
        $jk = strtoupper(trim($data[2])) === 'P' ? 'P' : 'L';
        $kota = trim($data[3]);
        $tanggal_lahir = !empty(trim($data[4])) ? trim($data[4]) : null;
        $alamat = trim($data[5]);
        $nama_ayah = trim($data[6]);
        $pekerjaan_ayah = trim($data[7]);
        $nama_ibu = trim($data[8]);
        $pekerjaan_ibu = trim($data[9]);
        $no_hp_ortu = trim($data[10]);
        $nama_kelas = trim($data[11]);
        
        // Cari ID kelas berdasarkan nama kelas
        $kelas = fetchOne("SELECT id_kelas FROM tb_kelas WHERE nama_kelas = :nama LIMIT 1", ['nama' => $nama_kelas]);
        
        if (!$kelas) continue; // Skip jika kelas tidak valid
        
        // Insert atau Update tb_siswa
        $siswa_exist = fetchOne("SELECT no_induk FROM tb_siswa WHERE no_induk = :no_induk", ['no_induk' => $no_induk]);
        
        if ($siswa_exist) {
            executeQuery("
                UPDATE tb_siswa SET 
                    nama_siswa = :nama_siswa, jenis_kelamin = :jk, kota = :kota, tanggal_lahir = :tanggal_lahir,
                    alamat = :alamat, nama_ayah = :nama_ayah, pekerjaan_ayah = :pekerjaan_ayah,
                    nama_ibu = :nama_ibu, pekerjaan_ibu = :pekerjaan_ibu, no_hp_ortu = :no_hp_ortu
                WHERE no_induk = :no_induk
            ", [
                'nama_siswa' => $nama_siswa, 'jk' => $jk, 'kota' => $kota, 'tanggal_lahir' => $tanggal_lahir,
                'alamat' => $alamat, 'nama_ayah' => $nama_ayah, 'pekerjaan_ayah' => $pekerjaan_ayah,
                'nama_ibu' => $nama_ibu, 'pekerjaan_ibu' => $pekerjaan_ibu, 'no_hp_ortu' => $no_hp_ortu, 'no_induk' => $no_induk
            ]);
        } else {
            executeQuery("
                INSERT INTO tb_siswa (no_induk, nama_siswa, jenis_kelamin, kota, tanggal_lahir, alamat, nama_ayah, pekerjaan_ayah, nama_ibu, pekerjaan_ibu, no_hp_ortu, status_aktif)
                VALUES (:no_induk, :nama_siswa, :jk, :kota, :tanggal_lahir, :alamat, :nama_ayah, :pekerjaan_ayah, :nama_ibu, :pekerjaan_ibu, :no_hp_ortu, 'Aktif')
            ", [
                'no_induk' => $no_induk, 'nama_siswa' => $nama_siswa, 'jk' => $jk, 'kota' => $kota, 'tanggal_lahir' => $tanggal_lahir,
                'alamat' => $alamat, 'nama_ayah' => $nama_ayah, 'pekerjaan_ayah' => $pekerjaan_ayah,
                'nama_ibu' => $nama_ibu, 'pekerjaan_ibu' => $pekerjaan_ibu, 'no_hp_ortu' => $no_hp_ortu
            ]);
        }
        
        // Insert/Update tb_anggota_kelas
        $anggota = fetchOne("SELECT id_anggota FROM tb_anggota_kelas WHERE no_induk = :no_induk AND id_tahun = :id_tahun", [
            'no_induk' => $no_induk,
            'id_tahun' => $id_tahun
        ]);
        
        if ($anggota) {
            executeQuery("UPDATE tb_anggota_kelas SET id_kelas = :id_kelas WHERE no_induk = :no_induk AND id_tahun = :id_tahun", [
                'id_kelas' => $kelas['id_kelas'], 'no_induk' => $no_induk, 'id_tahun' => $id_tahun
            ]);
        } else {
            executeQuery("
                INSERT INTO tb_anggota_kelas (no_induk, id_kelas, id_tahun)
                VALUES (:no_induk, :id_kelas, :id_tahun)
            ", [
                'no_induk' => $no_induk,
                'id_kelas' => $kelas['id_kelas'],
                'id_tahun' => $id_tahun
            ]);
        }
        
        $success++;
    }
    
    fclose($handle);
    $pdo->commit();
    
    $_SESSION['success_message'] = "✅ Berhasil import $success data siswa!";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = '❌ Gagal import: ' . $e->getMessage();
}

header('Location: ../views/admin/data_siswa.php');
exit;