<?php
/**
 * SITAPSI - Action Ganti Semester (SMART SYNC FIXED)
 * Mereset dan mengkalkulasi ulang poin & SP berdasarkan riwayat semester aktif.
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Dapatkan tahun ajaran aktif saat ini
        $tahun_aktif = fetchOne("SELECT id_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
        
        if (!$tahun_aktif) {
            throw new Exception("Tidak ada tahun ajaran aktif.");
        }

        $id_tahun = $tahun_aktif['id_tahun'];
        $semester_sekarang = $tahun_aktif['semester_aktif'];
        
        // Tentukan semester tujuan (Toggle)
        $semester_baru = ($semester_sekarang === 'Ganjil') ? 'Genap' : 'Ganjil';

        // 2. Update status semester di tb_tahun_ajaran
        executeQuery("UPDATE tb_tahun_ajaran SET semester_aktif = :semester_baru WHERE id_tahun = :id_tahun", [
            'semester_baru' => $semester_baru,
            'id_tahun' => $id_tahun
        ]);

        // 3. HARD RESET: Kembalikan semua poin ke 0 dan SP ke Aman sementara waktu
        executeQuery("
            UPDATE tb_anggota_kelas 
            SET 
                poin_kelakuan = 0, poin_kerajinan = 0, poin_kerapian = 0, total_poin_umum = 0,
                status_sp_kelakuan = 'Aman', status_sp_kerajinan = 'Aman', status_sp_kerapian = 'Aman', status_sp_terakhir = 'Aman'
            WHERE id_tahun = :id_tahun
        ", ['id_tahun' => $id_tahun]);

        // 4. RESTORE DATA: Kalkulasi ulang dari riwayat transaksi untuk semester yang dipilih
        // FIX HY093: Membedakan nama parameter id_tahun_1 dan id_tahun_2
        executeQuery("
            UPDATE tb_anggota_kelas a
            JOIN (
                SELECT 
                    h.id_anggota,
                    COALESCE(SUM(CASE WHEN jp.id_kategori = 1 THEN d.poin_saat_itu ELSE 0 END), 0) as kelakuan,
                    COALESCE(SUM(CASE WHEN jp.id_kategori = 2 THEN d.poin_saat_itu ELSE 0 END), 0) as kerajinan,
                    COALESCE(SUM(CASE WHEN jp.id_kategori = 3 THEN d.poin_saat_itu ELSE 0 END), 0) as kerapian,
                    COALESCE(SUM(d.poin_saat_itu), 0) as total
                FROM tb_pelanggaran_header h
                JOIN tb_pelanggaran_detail d ON h.id_transaksi = d.id_transaksi
                JOIN tb_jenis_pelanggaran jp ON d.id_jenis = jp.id_jenis
                WHERE h.id_tahun = :id_tahun_1 AND h.semester = :semester
                GROUP BY h.id_anggota
            ) t ON a.id_anggota = t.id_anggota
            SET 
                a.poin_kelakuan = t.kelakuan,
                a.poin_kerajinan = t.kerajinan,
                a.poin_kerapian = t.kerapian,
                a.total_poin_umum = t.total
            WHERE a.id_tahun = :id_tahun_2
        ", [
            'id_tahun_1' => $id_tahun, 
            'semester' => $semester_baru,
            'id_tahun_2' => $id_tahun
        ]);

        // 5. RESTORE STATUS SP: Hitung ulang SP siswa yang memiliki poin > 0
        $siswa_berpoin = fetchAll("SELECT id_anggota, poin_kelakuan, poin_kerajinan, poin_kerapian FROM tb_anggota_kelas WHERE id_tahun = :id_tahun AND total_poin_umum > 0", ['id_tahun' => $id_tahun]);
        $aturan_sp = fetchAll("SELECT sp.*, k.nama_kategori FROM tb_aturan_sp sp JOIN tb_kategori_pelanggaran k ON sp.id_kategori = k.id_kategori ORDER BY sp.batas_bawah_poin DESC");

        foreach ($siswa_berpoin as $s) {
            $sp_kelakuan = 'Aman';
            $sp_kerajinan = 'Aman';
            $sp_kerapian = 'Aman';
            
            $sp_tertinggi = 'Aman';
            $highest_threshold = -1;

            foreach ($aturan_sp as $a) {
                $kat = $a['nama_kategori'];
                $batas = $a['batas_bawah_poin'];
                $level = $a['level_sp'];

                // Cek SP Per Kategori (Ambil level tertinggi sesuai urutan DESC)
                if ($kat === 'KELAKUAN' && $s['poin_kelakuan'] >= $batas && $sp_kelakuan === 'Aman') $sp_kelakuan = $level;
                if ($kat === 'KERAJINAN' && $s['poin_kerajinan'] >= $batas && $sp_kerajinan === 'Aman') $sp_kerajinan = $level;
                if ($kat === 'KERAPIAN' && $s['poin_kerapian'] >= $batas && $sp_kerapian === 'Aman') $sp_kerapian = $level;

                // Cari SP Max/Tertinggi secara keseluruhan (Lokal Silo)
                $poin_cek = 0;
                if ($kat === 'KELAKUAN') $poin_cek = $s['poin_kelakuan'];
                if ($kat === 'KERAJINAN') $poin_cek = $s['poin_kerajinan'];
                if ($kat === 'KERAPIAN') $poin_cek = $s['poin_kerapian'];

                if ($poin_cek >= $batas && $batas > $highest_threshold) {
                    $highest_threshold = $batas;
                    $sp_tertinggi = $level;
                }
            }

            // Update status SP ke database
            executeQuery("
                UPDATE tb_anggota_kelas 
                SET status_sp_kelakuan = :sp_kel,
                    status_sp_kerajinan = :sp_ker,
                    status_sp_kerapian = :sp_rap,
                    status_sp_terakhir = :sp_max
                WHERE id_anggota = :id_anggota
            ", [
                'sp_kel' => $sp_kelakuan,
                'sp_ker' => $sp_kerajinan,
                'sp_rap' => $sp_kerapian,
                'sp_max' => $sp_tertinggi,
                'id_anggota' => $s['id_anggota']
            ]);
        }

        $_SESSION['success_message'] = "✅ Berhasil berpindah ke Semester $semester_baru. Data poin dan status SP secara otomatis disinkronkan dengan riwayat semester tersebut.";
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "❌ Gagal mengubah semester: " . $e->getMessage();
    }

    header("Location: ../views/admin/pengaturan_akademik.php");
    exit;
} else {
    header("Location: ../views/admin/pengaturan_akademik.php");
    exit;
}