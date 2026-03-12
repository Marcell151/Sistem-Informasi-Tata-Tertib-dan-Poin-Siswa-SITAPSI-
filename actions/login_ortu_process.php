<?php
/**
 * SITAPSI - Proses Login Orang Tua / Wali Murid
 * Keamanan: Menggunakan MD5 Hash (sesuai dummy database awal)
 * PENYESUAIAN: Pembuatan Cookie "Ingat Saya"
 */

session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Ambil input dan bersihkan
    $nik = trim($_POST['nik'] ?? '');
    $password_raw = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']); // Tangkap status Checkbox Ingat Saya
    
    if (empty($nik) || empty($password_raw)) {
        $_SESSION['error_message'] = "⚠️ NIK dan Password tidak boleh kosong.";
        header("Location: ../views/ortu/login.php");
        exit;
    }

    try {
        // Enkripsi inputan password menggunakan MD5 (karena di dummy DB pakai MD5 'e10adc39...')
        $password_hashed = md5($password_raw);

        // Cek data di tabel tb_orang_tua
        $ortu = fetchOne("SELECT * FROM tb_orang_tua WHERE nik_ortu = :nik AND password = :pass LIMIT 1", [
            'nik' => $nik,
            'pass' => $password_hashed
        ]);

        if ($ortu) {
            // LOGIN BERHASIL: Buat Session Khusus Wali Murid
            
            // Kita bedakan nama variabel session agar tidak bertabrakan dengan admin/guru
            $_SESSION['ortu_id'] = $ortu['id_ortu'];
            $_SESSION['ortu_nik'] = $ortu['nik_ortu'];
            
            // Prioritaskan Nama Ayah. Jika kosong, pakai Nama Ibu. Jika kosong lagi, tulis "Wali Murid".
            $nama_panggilan = !empty($ortu['nama_ayah']) ? $ortu['nama_ayah'] : (!empty($ortu['nama_ibu']) ? $ortu['nama_ibu'] : 'Wali Murid');
            $_SESSION['nama_user'] = $nama_panggilan;
            
            // Tanda pengenal akses portal
            $_SESSION['role'] = 'Ortu'; 
            
            // LOGIKA REMEMBER ME (Simpan di Cookie Browser selama 30 Hari)
            if ($remember_me) {
                // Simpan NIK dan Password raw ke cookie agar bisa di-load otomatis di form
                setcookie('saved_ortu_nik', $nik, time() + (86400 * 30), "/"); 
                setcookie('saved_ortu_pass', $password_raw, time() + (86400 * 30), "/");
            } else {
                // Jika tidak dicentang, hapus Cookie yang mungkin pernah tersimpan sebelumnya
                setcookie('saved_ortu_nik', '', time() - 3600, "/");
                setcookie('saved_ortu_pass', '', time() - 3600, "/");
            }

            // Lempar ke Dashboard Utama (Hub)
            header("Location: ../views/ortu/dashboard.php");
            exit;
            
        } else {
            // LOGIN GAGAL: NIK atau Password salah
            $_SESSION['error_message'] = "❌ NIK atau Password tidak sesuai.";
            header("Location: ../views/ortu/login.php");
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = "🚨 Terjadi kesalahan sistem: " . $e->getMessage();
        header("Location: ../views/ortu/login.php");
        exit;
    }
} else {
    // Jika ada yang mencoba akses file ini secara langsung via URL
    header("Location: ../views/ortu/login.php");
    exit;
}
?>