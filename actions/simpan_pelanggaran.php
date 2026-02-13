<?php
/**
 * SITAPSI - Simpan Pelanggaran (Final Fix Argument Count)
 */

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';

$pdo = getDBConnection(); 

if (!$pdo) {
    die("Error Fatal: Gagal melakukan koneksi ke database.");
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Guru') {
    die("Akses ditolak!");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id_guru    = $_SESSION['user_id'];
        $id_anggota = $_POST['id_anggota'] ?? '';
        $id_tahun   = $_POST['id_tahun'] ?? '';
        $semester   = $_POST['semester'] ?? '';
        $tipe_form  = $_POST['tipe_form'] ?? 'Piket';
        
        $pelanggaran_ids = $_POST['pelanggaran'] ?? [];
        $sanksi_ids      = $_POST['sanksi'] ?? [];

        if (empty($id_anggota)) throw new Exception("Siswa harus dipilih.");
        if (empty($pelanggaran_ids)) throw new Exception("Minimal pilih 1 pelanggaran.");

        // Handle Multi-Foto (JSON)
        $foto_filenames = []; 
        if (isset($_FILES['bukti_foto']) && is_array($_FILES['bukti_foto']['name'])) {
            $upload_dir = '../assets/uploads/bukti/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            foreach ($_FILES['bukti_foto']['name'] as $key => $name) {
                if ($_FILES['bukti_foto']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $new_name = "bukti_" . time() . "_" . uniqid() . "_" . $key . "." . $ext;
                    if (move_uploaded_file($_FILES['bukti_foto']['tmp_name'][$key], $upload_dir . $new_name)) {
                        $foto_filenames[] = $new_name;
                    }
                }
            }
        }
        $json_foto = !empty($foto_filenames) ? json_encode($foto_filenames) : null;

        $pdo->beginTransaction();

        // 1. Insert Header
        $sqlHeader = "INSERT INTO tb_pelanggaran_header 
                      (id_anggota, id_guru, id_tahun, tanggal, waktu, semester, tipe_form, bukti_foto) 
                      VALUES (:id_anggota, :id_guru, :id_tahun, CURDATE(), CURTIME(), :semester, :tipe_form, :bukti_foto)";
        
        $stmtH = $pdo->prepare($sqlHeader);
        $stmtH->execute([
            ':id_anggota' => $id_anggota,
            ':id_guru'    => $id_guru,
            ':id_tahun'   => $id_tahun,
            ':semester'   => $semester,
            ':tipe_form'  => $tipe_form,
            ':bukti_foto' => $json_foto
        ]);
        
        $id_transaksi = $pdo->lastInsertId();

        // 2. Insert Detail & Akumulasi Poin Baru
        $p_kelakuan = 0; $p_kerajinan = 0; $p_kerapian = 0;
        $stmtD = $pdo->prepare("INSERT INTO tb_pelanggaran_detail (id_transaksi, id_jenis, poin_saat_itu) VALUES (?, ?, ?)");
        $stmtI = $pdo->prepare("SELECT poin_default, id_kategori FROM tb_jenis_pelanggaran WHERE id_jenis = ?");

        foreach ($pelanggaran_ids as $id_jenis) {
            $stmtI->execute([$id_jenis]);
            $info = $stmtI->fetch();
            if ($info) {
                $stmtD->execute([$id_transaksi, $id_jenis, $info['poin_default']]);
                if ($info['id_kategori'] == 1) $p_kelakuan += $info['poin_default'];
                elseif ($info['id_kategori'] == 2) $p_kerajinan += $info['poin_default'];
                elseif ($info['id_kategori'] == 3) $p_kerapian += $info['poin_default'];
            }
        }

        // 3. Insert Sanksi
        if (!empty($sanksi_ids)) {
            $stmtS = $pdo->prepare("INSERT INTO tb_pelanggaran_sanksi (id_transaksi, id_sanksi_ref) VALUES (?, ?)");
            foreach ($sanksi_ids as $id_s) { $stmtS->execute([$id_transaksi, $id_s]); }
        }

        // 4. Update Poin di Tabel Anggota Kelas
        $total_input = $p_kelakuan + $p_kerajinan + $p_kerapian;
        $stmtU = $pdo->prepare("UPDATE tb_anggota_kelas 
                                SET poin_kelakuan = poin_kelakuan + :pk, 
                                    poin_kerajinan = poin_kerajinan + :prj, 
                                    poin_kerapian = poin_kerapian + :prp, 
                                    total_poin_umum = total_poin_umum + :total 
                                WHERE id_anggota = :id");
        $stmtU->execute([':pk'=>$p_kelakuan, ':prj'=>$p_kerajinan, ':prp'=>$p_kerapian, ':total'=>$total_input, ':id'=>$id_anggota]);

        // 5. CEK SP (DIPERBAIKI: Mengambil data terbaru siswa)
        $stmtC = $pdo->prepare("SELECT poin_kelakuan, poin_kerajinan, poin_kerapian, status_sp_terakhir FROM tb_anggota_kelas WHERE id_anggota = ?");
        $stmtC->execute([$id_anggota]);
        $s = $stmtC->fetch();

        if ($s) {
            // Pastikan mempassing 5 argumen sesuai definisi fungsi di bawah
            checkAndTriggerSP($pdo, $id_anggota, 1, $s['poin_kelakuan'], $s['status_sp_terakhir']); 
            checkAndTriggerSP($pdo, $id_anggota, 2, $s['poin_kerajinan'], $s['status_sp_terakhir']); 
            checkAndTriggerSP($pdo, $id_anggota, 3, $s['poin_kerapian'], $s['status_sp_terakhir']);  
        }

        $pdo->commit();
        $_SESSION['success_message'] = "✅ Berhasil disimpan! (+{$total_input} Poin)";
        header("Location: ../views/guru/input_pelanggaran.php?mode=" . $tipe_form);
        exit;

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['error_message'] = "❌ Gagal: " . $e->getMessage();
        header("Location: ../views/guru/input_pelanggaran.php");
        exit;
    }
}

/**
 * FUNGSI CEK SP - Definisi harus menerima 5 Argumen
 */
function checkAndTriggerSP($pdo, $id_anggota, $id_kategori, $poin, $status_saat_ini) {
    // Ambil aturan SP dari database
    $stmtAturan = $pdo->prepare("SELECT level_sp, batas_bawah_poin FROM tb_aturan_sp WHERE id_kategori = ? ORDER BY batas_bawah_poin DESC");
    $stmtAturan->execute([$id_kategori]);
    $list = $stmtAturan->fetchAll();

    $target = null;
    foreach ($list as $at) {
        if ($poin >= $at['batas_bawah_poin']) { $target = $at['level_sp']; break; }
    }

    $rank = ['Aman' => 0, 'SP1' => 1, 'SP2' => 2, 'SP3' => 3, 'Dikeluarkan' => 4];
    $val_now = $rank[$status_saat_ini] ?? 0;
    $val_new = $rank[$target] ?? 0;

    // Trigger hanya jika level SP yang baru lebih tinggi dari yang lama
    if ($target && $val_new > $val_now) {
        $kat_nama = ($id_kategori == 1) ? 'KELAKUAN' : (($id_kategori == 2) ? 'KERAJINAN' : 'KERAPIAN');
        
        // Simpan ke riwayat SP
        $stmtSP = $pdo->prepare("INSERT INTO tb_riwayat_sp (id_anggota, tingkat_sp, kategori_pemicu, tanggal_terbit, status) VALUES (?, ?, ?, CURDATE(), 'Pending')");
        $stmtSP->execute([$id_anggota, $target, $kat_nama]);

        // Update status SP terakhir di tabel anggota kelas
        $pdo->prepare("UPDATE tb_anggota_kelas SET status_sp_terakhir = ? WHERE id_anggota = ?")->execute([$target, $id_anggota]);
    }
}