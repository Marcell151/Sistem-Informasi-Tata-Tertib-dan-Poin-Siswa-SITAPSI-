<?php
/**
 * SITAPSI - Import Siswa dari Excel/CSV
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
        throw new Exception('Tidak ada tahun ajaran aktif');
    }
    
    $id_tahun = $tahun_aktif['id_tahun'];
    
    // Parse CSV
    $handle = fopen($file['tmp_name'], 'r');
    
    if (!$handle) {
        throw new Exception('Gagal membuka file');
    }
    
    $pdo->beginTransaction();
    
    $row = 0;
    $success = 0;
    $failed = 0;
    
    // Skip header row
    fgetcsv($handle);
    
    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
        $row++;
        
        // Format: NIS | Nama | JK | Tempat Lahir | Tanggal Lahir | Alamat | Nama Ortu | No HP | Kelas
        if (count($data) < 9) {
            $failed++;
            continue;
        }
        
        $nis = trim($data[0]);
        $nama_siswa = trim($data[1]);
        $jk = strtoupper(trim($data[2]));
        $tempat_lahir = trim($data[3]);
        $tanggal_lahir = trim($data[4]);
        $alamat = trim($data[5]);
        $nama_ortu = trim($data[6]);
        $no_hp_ortu = trim($data[7]);
        $nama_kelas = trim($data[8]);
        
        // Validasi
        if (empty($nis) || empty($nama_siswa)) {
            $failed++;
            continue;
        }
        
        // Cari id_kelas
        $kelas = fetchOne("SELECT id_kelas FROM tb_kelas WHERE nama_kelas = :nama_kelas", ['nama_kelas' => $nama_kelas]);
        
        if (!$kelas) {
            $failed++;
            continue;
        }
        
        // Cek apakah siswa sudah ada
        $existing = fetchOne("SELECT nis FROM tb_siswa WHERE nis = :nis", ['nis' => $nis]);
        
        if ($existing) {
            // Update
            executeQuery("
                UPDATE tb_siswa 
                SET nama_siswa = :nama_siswa,
                    jenis_kelamin = :jk,
                    tempat_lahir = :tempat_lahir,
                    tanggal_lahir = :tanggal_lahir,
                    alamat_ortu = :alamat,
                    nama_ortu = :nama_ortu,
                    no_hp_ortu = :no_hp_ortu
                WHERE nis = :nis
            ", [
                'nama_siswa' => $nama_siswa,
                'jk' => $jk,
                'tempat_lahir' => $tempat_lahir,
                'tanggal_lahir' => $tanggal_lahir,
                'alamat' => $alamat,
                'nama_ortu' => $nama_ortu,
                'no_hp_ortu' => $no_hp_ortu,
                'nis' => $nis
            ]);
        } else {
            // Insert baru
            executeQuery("
                INSERT INTO tb_siswa (nis, nama_siswa, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat_ortu, nama_ortu, no_hp_ortu, status_aktif)
                VALUES (:nis, :nama_siswa, :jk, :tempat_lahir, :tanggal_lahir, :alamat, :nama_ortu, :no_hp_ortu, 'Aktif')
            ", [
                'nis' => $nis,
                'nama_siswa' => $nama_siswa,
                'jk' => $jk,
                'tempat_lahir' => $tempat_lahir,
                'tanggal_lahir' => $tanggal_lahir,
                'alamat' => $alamat,
                'nama_ortu' => $nama_ortu,
                'no_hp_ortu' => $no_hp_ortu
            ]);
        }
        
        // Insert/Update tb_anggota_kelas
        $anggota = fetchOne("SELECT id_anggota FROM tb_anggota_kelas WHERE nis = :nis AND id_tahun = :id_tahun", [
            'nis' => $nis,
            'id_tahun' => $id_tahun
        ]);
        
        if (!$anggota) {
            executeQuery("
                INSERT INTO tb_anggota_kelas (nis, id_kelas, id_tahun)
                VALUES (:nis, :id_kelas, :id_tahun)
            ", [
                'nis' => $nis,
                'id_kelas' => $kelas['id_kelas'],
                'id_tahun' => $id_tahun
            ]);
        }
        
        $success++;
    }
    
    fclose($handle);
    $pdo->commit();
    
    $_SESSION['success_message'] = "✅ Import selesai! Berhasil: $success, Gagal: $failed";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = '❌ Gagal import: ' . $e->getMessage();
}

header('Location: ../views/admin/data_siswa.php');
exit;
?>