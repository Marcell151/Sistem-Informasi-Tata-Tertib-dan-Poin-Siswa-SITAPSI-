<?php
/**
 * SITAPSI - Simpan Pelanggaran (FIX - Upload Foto Bukti & No Induk)
 */

date_default_timezone_set('Asia/Jakarta');

session_start();
require_once '../config/database.php';
require_once '../includes/session_check.php';
require_once '../includes/sp_helper.php';

requireGuru();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Siapkan array untuk menampung nama file jika error agar bisa dihapus (rollback file)
    $foto_filenames = []; 
    
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();

        $id_anggota = $_POST['id_anggota'] ?? '';
        $pelanggaran_ids = $_POST['pelanggaran'] ?? [];
        $sanksi_ids = $_POST['sanksi'] ?? [];
        $tipe_form = $_POST['tipe_form'] ?? 'Piket';
        
        $id_guru = $_SESSION['user_id'] ?? null;

        $tanggal = !empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d');
        $waktu = !empty($_POST['waktu']) ? $_POST['waktu'] : date('H:i:s');

        // Validasi
        if (empty($id_guru)) throw new Exception('Sesi login Guru tidak terdeteksi.');
        if (empty($id_anggota)) throw new Exception('Siswa belum dipilih.');
        if (empty($pelanggaran_ids)) throw new Exception('Minimal pilih 1 jenis pelanggaran.');

        // AMBIL TAHUN DAN SEMESTER AKTIF SAAT INI UNTUK DISIMPAN DI TRANSAKSI
        $akademik = fetchOne("SELECT id_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
        if (!$akademik) throw new Exception('Tahun ajaran aktif tidak ditemukan.');

        $id_tahun = $akademik['id_tahun'];
        $semester = $akademik['semester_aktif'];

        // PROSES UPLOAD MULTIPLE FOTO
        if (isset($_FILES['bukti_foto']) && is_array($_FILES['bukti_foto']['name']) && $_FILES['bukti_foto']['name'][0] !== '') {
            $upload_dir = '../../assets/uploads/bukti/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $files = $_FILES['bukti_foto'];
            $jumlah_file = count($files['name']);
            
            for ($i = 0; $i < $jumlah_file; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $file_ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
                    
                    if (in_array($file_ext, $allowed_ext)) {
                        // Format: [timestamp]_[random].[ext]
                        $new_filename = time() . '_' . rand(1000,9999) . '.' . $file_ext;
                        $target_file = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($files['tmp_name'][$i], $target_file)) {
                            $foto_filenames[] = $new_filename;
                        } else {
                            throw new Exception("Gagal mengupload foto ke-$i.");
                        }
                    } else {
                        throw new Exception("Format foto tidak didukung (hanya JPG/PNG/WEBP).");
                    }
                }
            }
        }
        
        // Konversi array foto ke JSON untuk disimpan di database, simpan NULL jika kosong
        $bukti_foto_json = !empty($foto_filenames) ? json_encode($foto_filenames) : null;

        // 1. Insert Header
        $sql_header = "
            INSERT INTO tb_pelanggaran_header 
            (id_anggota, id_guru, id_tahun, tanggal, waktu, semester, tipe_form, bukti_foto) 
            VALUES 
            (:id_anggota, :id_guru, :id_tahun, :tanggal, :waktu, :semester, :tipe_form, :bukti_foto)
        ";
        executeQuery($sql_header, [
            'id_anggota' => $id_anggota,
            'id_guru' => $id_guru,
            'id_tahun' => $id_tahun,
            'tanggal' => $tanggal,
            'waktu' => $waktu,
            'semester' => $semester,
            'tipe_form' => $tipe_form,
            'bukti_foto' => $bukti_foto_json
        ]);
        
        $id_transaksi = $pdo->lastInsertId();

        // 2. Insert Detail & Hitung per Kategori
        $poin_per_kategori = [1 => 0, 2 => 0, 3 => 0];

        $sql_detail = "INSERT INTO tb_pelanggaran_detail (id_transaksi, id_jenis, poin_saat_itu) VALUES (?, ?, ?)";
        $stmt_detail = $pdo->prepare($sql_detail);

        foreach ($pelanggaran_ids as $id_jenis) {
            $jenis = fetchOne("SELECT id_kategori, poin_default FROM tb_jenis_pelanggaran WHERE id_jenis = ?", [$id_jenis]);
            if ($jenis) {
                $stmt_detail->execute([$id_transaksi, $id_jenis, $jenis['poin_default']]);
                $poin_per_kategori[$jenis['id_kategori']] += $jenis['poin_default'];
            }
        }

        // 3. Insert Sanksi Checklist
        if (!empty($sanksi_ids)) {
            $sql_sanksi = "INSERT INTO tb_pelanggaran_sanksi (id_transaksi, id_sanksi_ref) VALUES (?, ?)";
            $stmt_sanksi = $pdo->prepare($sql_sanksi);
            foreach ($sanksi_ids as $id_sanksi) {
                $stmt_sanksi->execute([$id_transaksi, $id_sanksi]);
            }
        }

        // 4. Update Akumulasi Poin Siswa
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

        // Recalculate SP
        recalculateStatusSP($id_anggota);

        // UBAH JOIN NIS MENJADI NO_INDUK
        $siswa = fetchOne("SELECT s.nama_siswa FROM tb_anggota_kelas a JOIN tb_siswa s ON a.no_induk = s.no_induk WHERE a.id_anggota = :id", ['id' => $id_anggota]);

        $_SESSION['success_message'] = "✅ Pelanggaran berhasil disimpan untuk " . htmlspecialchars($siswa['nama_siswa']) . " (+{$total_poin} Poin)";
        header("Location: ../views/guru/input_pelanggaran.php?mode=" . strtolower($tipe_form));
        exit;

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // CLEANUP: Hapus foto fisik jika database gagal disimpan agar tidak menumpuk sampah
        if (!empty($foto_filenames)) {
            foreach ($foto_filenames as $f) {
                $path = "../../assets/uploads/bukti/" . $f;
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }

        $_SESSION['error_message'] = "❌ Gagal menyimpan: " . $e->getMessage();
        header("Location: ../views/guru/input_pelanggaran.php?mode=" . strtolower($tipe_form ?? 'piket'));
        exit;
    }
}