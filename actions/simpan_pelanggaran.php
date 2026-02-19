<?php
/**
 * SITAPSI - Simpan Pelanggaran (FIX - Call recalculateStatusSP)
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';
require_once '../includes/sp_helper.php'; // TAMBAH INI

requireGuru();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();

        // Ambil data POST
        $id_anggota = $_POST['id_anggota'];
        $pelanggaran_ids = $_POST['pelanggaran'] ?? [];
        $sanksi_ids = $_POST['sanksi'] ?? [];
        $tanggal = $_POST['tanggal'];
        $waktu = $_POST['waktu'];
        $tipe_form = $_POST['tipe_form'];
        $id_guru = getCurrentUser()['id_guru'];

        // Validasi
        if (empty($id_anggota) || empty($pelanggaran_ids) || empty($tanggal) || empty($waktu)) {
            throw new Exception('Data tidak lengkap');
        }

        // Ambil tahun dan semester aktif
        $tahun_aktif = fetchOne("
            SELECT id_tahun, semester_aktif 
            FROM tb_tahun_ajaran 
            WHERE status = 'Aktif' 
            LIMIT 1
        ");

        // Insert header
        $stmtHeader = $pdo->prepare("
            INSERT INTO tb_pelanggaran_header 
            (id_anggota, id_guru, id_tahun, tanggal, waktu, semester, tipe_form) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtHeader->execute([
            $id_anggota,
            $id_guru,
            $tahun_aktif['id_tahun'],
            $tanggal,
            $waktu,
            $tahun_aktif['semester_aktif'],
            $tipe_form
        ]);

        $id_transaksi = $pdo->lastInsertId();

        // Insert detail & hitung poin per kategori
        $poin_per_kategori = [1 => 0, 2 => 0, 3 => 0];

        $stmtDetail = $pdo->prepare("
            INSERT INTO tb_pelanggaran_detail 
            (id_transaksi, id_jenis, poin_saat_itu) 
            VALUES (?, ?, ?)
        ");

        $stmtGetPoin = $pdo->prepare("
            SELECT poin_default, id_kategori 
            FROM tb_jenis_pelanggaran 
            WHERE id_jenis = ?
        ");

        foreach ($pelanggaran_ids as $id_jenis) {
            $stmtGetPoin->execute([$id_jenis]);
            $pelanggaran = $stmtGetPoin->fetch(PDO::FETCH_ASSOC);
            
            if ($pelanggaran) {
                $stmtDetail->execute([
                    $id_transaksi,
                    $id_jenis,
                    $pelanggaran['poin_default']
                ]);
                
                $poin_per_kategori[$pelanggaran['id_kategori']] += $pelanggaran['poin_default'];
            }
        }

        // Insert sanksi
        if (!empty($sanksi_ids)) {
            $stmtSanksi = $pdo->prepare("
                INSERT INTO tb_pelanggaran_sanksi 
                (id_transaksi, id_sanksi_ref) 
                VALUES (?, ?)
            ");
            foreach ($sanksi_ids as $id_sanksi) {
                $stmtSanksi->execute([$id_transaksi, $id_sanksi]);
            }
        }

        // Update poin di tb_anggota_kelas
        $sql_update = "
            UPDATE tb_anggota_kelas 
            SET poin_kelakuan = poin_kelakuan + :kelakuan,
                poin_kerajinan = poin_kerajinan + :kerajinan,
                poin_kerapian = poin_kerapian + :kerapian,
                total_poin_umum = total_poin_umum + :total
            WHERE id_anggota = :id
        ";

        $total_poin = array_sum($poin_per_kategori);

        executeQuery($sql_update, [
            'kelakuan' => $poin_per_kategori[1],
            'kerajinan' => $poin_per_kategori[2],
            'kerapian' => $poin_per_kategori[3],
            'total' => $total_poin,
            'id' => $id_anggota
        ]);

        $pdo->commit();

        // ============================================
        // CRITICAL FIX: RECALCULATE SP AFTER COMMIT
        // ============================================
        recalculateStatusSP($id_anggota);

        // Ambil nama siswa
        $siswa = fetchOne("
            SELECT s.nama_siswa 
            FROM tb_anggota_kelas a
            JOIN tb_siswa s ON a.nis = s.nis
            WHERE a.id_anggota = :id
        ", ['id' => $id_anggota]);

        $_SESSION['success_message'] = "✅ Pelanggaran berhasil disimpan untuk " . $siswa['nama_siswa'] . " (+{$total_poin} Poin)";
        header("Location: ../views/guru/input_pelanggaran.php?mode=" . strtolower($tipe_form));
        exit;

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_message'] = "❌ Gagal: " . $e->getMessage();
        header("Location: ../views/guru/input_pelanggaran.php");
        exit;
    }
}
?>