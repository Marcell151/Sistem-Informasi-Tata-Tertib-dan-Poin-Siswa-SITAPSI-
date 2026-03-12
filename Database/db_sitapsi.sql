DROP DATABASE IF EXISTS db_sitapsi;
CREATE DATABASE db_sitapsi;
USE db_sitapsi;

-- ================================================================
-- 1. GROUP: KONFIGURASI & AKSES
-- ================================================================

-- Tabel Admin
CREATE TABLE tb_admin (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL, 
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('SuperAdmin', 'Admin') DEFAULT 'SuperAdmin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Tahun Ajaran
CREATE TABLE tb_tahun_ajaran (
    id_tahun INT AUTO_INCREMENT PRIMARY KEY,
    nama_tahun VARCHAR(20) NOT NULL, -- Contoh: "2025/2026"
    status ENUM('Aktif', 'Arsip') DEFAULT 'Aktif', 
    semester_aktif ENUM('Ganjil', 'Genap') DEFAULT 'Ganjil'
);

-- ================================================================
-- 2. GROUP: MASTER DATA UTAMA (MANUSIA)
-- ================================================================

-- Tabel Kelas
CREATE TABLE tb_kelas (
    id_kelas INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(10) NOT NULL, -- Format Romawi: VII A, VIII B
    tingkat INT NOT NULL -- 7, 8, 9
);

-- Tabel Guru (Login SSO dengan PIN) + Fitur Wali Kelas & Kode Jadwal
CREATE TABLE tb_guru (
    id_guru INT AUTO_INCREMENT PRIMARY KEY,
    nama_guru VARCHAR(100) NOT NULL,
    nip VARCHAR(30),
    kode_guru VARCHAR(10), -- [MODIFIKASI BARU: Kode khusus untuk Integrasi Sistem Jadwal/Presensi]
    id_kelas INT NULL, -- Jika berisi ID Kelas, maka dia Wali Kelas. Jika NULL, guru biasa
    pin_validasi VARCHAR(6) NOT NULL, -- PIN 6 Digit
    status ENUM('Aktif', 'Non-Aktif') DEFAULT 'Aktif',
    
    FOREIGN KEY (id_kelas) REFERENCES tb_kelas(id_kelas) ON DELETE SET NULL
);

-- Tabel Siswa (Data Statis / Induk)
CREATE TABLE tb_siswa (
    no_induk VARCHAR(50) PRIMARY KEY,
    nama_siswa VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    kota VARCHAR(100),
    tanggal_lahir DATE,
    alamat TEXT,
    nama_ayah VARCHAR(150),
    pekerjaan_ayah VARCHAR(100),
    nama_ibu VARCHAR(150),
    pekerjaan_ibu VARCHAR(100),
    no_hp_ortu VARCHAR(15), -- Notifikasi WA
    id_ortu INT NULL, -- [PENAMBAHAN BARU: Untuk tempat ikatan Relasi Orang Tua]
    status_aktif ENUM('Aktif', 'Lulus', 'Keluar', 'Dikeluarkan') DEFAULT 'Aktif'
);

-- ================================================================
-- 3. GROUP: MAPPING (LOGIKA UTAMA & RESET POIN)
-- ================================================================

-- Tabel Anggota Kelas (Jantung Sistem - Per Tahun)
CREATE TABLE tb_anggota_kelas (
    id_anggota BIGINT AUTO_INCREMENT PRIMARY KEY,
    no_induk VARCHAR(50) NOT NULL, 
    id_kelas INT NOT NULL,
    id_tahun INT NOT NULL,
    
    -- Akumulasi Poin Tahunan (Untuk perhitungan SP)
    poin_kelakuan INT DEFAULT 0,
    poin_kerajinan INT DEFAULT 0,
    poin_kerapian INT DEFAULT 0,
    
    total_poin_umum INT DEFAULT 0, -- Total Gabungan Tahunan
    
    -- 3 Silo Status SP Independen
    status_sp_kelakuan ENUM('Aman', 'SP1', 'SP2', 'SP3', 'Sanksi oleh Sekolah') DEFAULT 'Aman',
    status_sp_kerajinan ENUM('Aman', 'SP1', 'SP2', 'SP3', 'Sanksi oleh Sekolah') DEFAULT 'Aman',
    status_sp_kerapian ENUM('Aman', 'SP1', 'SP2', 'SP3', 'Sanksi oleh Sekolah') DEFAULT 'Aman',
    
    -- Summary Status Tertinggi
    status_sp_terakhir ENUM('Aman', 'SP1', 'SP2', 'SP3', 'Sanksi oleh Sekolah') DEFAULT 'Aman',
    
    -- Penanda Reward
    status_reward ENUM('None', 'Kandidat Reward Semester','Kandidat Sertifikat') DEFAULT 'None',
    
    FOREIGN KEY (no_induk) REFERENCES tb_siswa(no_induk) ON DELETE CASCADE,
    FOREIGN KEY (id_kelas) REFERENCES tb_kelas(id_kelas),
    FOREIGN KEY (id_tahun) REFERENCES tb_tahun_ajaran(id_tahun)
);

-- ================================================================
-- 4. GROUP: MASTER DATA ATURAN
-- ================================================================

-- Kategori (3 Silo)
CREATE TABLE tb_kategori_pelanggaran (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL
);

-- Referensi Sanksi Fisik (Kode 1-10)
CREATE TABLE tb_sanksi_ref (
    id_sanksi_ref INT AUTO_INCREMENT PRIMARY KEY,
    kode_sanksi VARCHAR(5) NOT NULL, 
    deskripsi TEXT NOT NULL
);

-- Jenis Pelanggaran (Detail)
CREATE TABLE tb_jenis_pelanggaran (
    id_jenis INT AUTO_INCREMENT PRIMARY KEY,
    id_kategori INT NOT NULL,
    sub_kategori VARCHAR(100), 
    nama_pelanggaran TEXT NOT NULL,
    poin_default INT NOT NULL,
    sanksi_default VARCHAR(50), 
    
    FOREIGN KEY (id_kategori) REFERENCES tb_kategori_pelanggaran(id_kategori)
);

-- Aturan Ambang Batas SP
CREATE TABLE tb_aturan_sp (
    id_aturan_sp INT AUTO_INCREMENT PRIMARY KEY,
    id_kategori INT NOT NULL, 
    level_sp ENUM('SP1', 'SP2', 'SP3', 'Sanksi oleh Sekolah') NOT NULL,
    batas_bawah_poin INT NOT NULL, 
    
    FOREIGN KEY (id_kategori) REFERENCES tb_kategori_pelanggaran(id_kategori)
);

-- Predikat Nilai Rapor
CREATE TABLE tb_predikat_nilai (
    id_predikat INT AUTO_INCREMENT PRIMARY KEY,
    id_kategori INT NOT NULL,
    huruf_mutu CHAR(1) NOT NULL, -- A, B, C, D
    batas_bawah INT NOT NULL,
    batas_atas INT NOT NULL,
    keterangan VARCHAR(100), 
    
    FOREIGN KEY (id_kategori) REFERENCES tb_kategori_pelanggaran(id_kategori)
);

-- ================================================================
-- 5. GROUP: TRANSAKSI HARIAN
-- ================================================================

-- Header Transaksi
CREATE TABLE tb_pelanggaran_header (
    id_transaksi BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_anggota BIGINT NOT NULL,
    id_guru INT NOT NULL,
    id_tahun INT NOT NULL, 
    tanggal DATE NOT NULL,
    waktu TIME NOT NULL,
    semester ENUM('Ganjil', 'Genap') NOT NULL, 
    tipe_form ENUM('Piket', 'Kelas') NOT NULL,
    bukti_foto VARCHAR(255),
    lampiran_link TEXT NULL, 
    
    -- Fitur Laporan/Revisi Wali Kelas
    status_revisi ENUM('None', 'Pending', 'Disetujui', 'Ditolak') DEFAULT 'None',
    alasan_revisi TEXT NULL,
    
    FOREIGN KEY (id_anggota) REFERENCES tb_anggota_kelas(id_anggota),
    FOREIGN KEY (id_guru) REFERENCES tb_guru(id_guru),
    FOREIGN KEY (id_tahun) REFERENCES tb_tahun_ajaran(id_tahun)
);

-- Detail Transaksi
CREATE TABLE tb_pelanggaran_detail (
    id_detail BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi BIGINT NOT NULL,
    id_jenis INT NOT NULL,
    poin_saat_itu INT NOT NULL,
    
    FOREIGN KEY (id_transaksi) REFERENCES tb_pelanggaran_header(id_transaksi) ON DELETE CASCADE,
    FOREIGN KEY (id_jenis) REFERENCES tb_jenis_pelanggaran(id_jenis)
);

-- Sanksi Checklist
CREATE TABLE tb_pelanggaran_sanksi (
    id_trans_sanksi BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi BIGINT NOT NULL,
    id_sanksi_ref INT NOT NULL,
    
    FOREIGN KEY (id_transaksi) REFERENCES tb_pelanggaran_header(id_transaksi) ON DELETE CASCADE,
    FOREIGN KEY (id_sanksi_ref) REFERENCES tb_sanksi_ref(id_sanksi_ref)
);

-- ================================================================
-- 6. GROUP: MANAJEMEN SP
-- ================================================================

-- Riwayat SP
CREATE TABLE tb_riwayat_sp (
    id_sp INT AUTO_INCREMENT PRIMARY KEY,
    id_anggota BIGINT NOT NULL,
    tingkat_sp ENUM('SP1', 'SP2', 'SP3', 'Sanksi oleh Sekolah') NOT NULL,
    kategori_pemicu VARCHAR(50), 
    tanggal_terbit DATE NOT NULL,
    tanggal_validasi DATE, 
    status ENUM('Pending', 'Selesai') DEFAULT 'Pending',
    id_admin INT, 
    
    FOREIGN KEY (id_anggota) REFERENCES tb_anggota_kelas(id_anggota),
    FOREIGN KEY (id_admin) REFERENCES tb_admin(id_admin)
);

-- ================================================================
-- INSERT DATA (DATA PENTING)
-- ================================================================

-- 1. Insert Kategori
INSERT INTO tb_kategori_pelanggaran (id_kategori, nama_kategori) VALUES 
(1, 'KELAKUAN'), (2, 'KERAJINAN'), (3, 'KERAPIAN');

-- 2. Insert Referensi Sanksi
INSERT INTO tb_sanksi_ref (kode_sanksi, deskripsi) VALUES 
('1', 'Meminta maaf dan berjanji tidak mengulang'),
('2', 'Dikeluarkan saat PBM (Proses Belajar Mengajar)'),
('3', 'Mengganti/memperbaiki fasilitas sekolah yang rusak'),
('4', 'Mengganti/mengembalikan uang atau barang yang dipinjam/diambil'),
('5', 'Menjalani pembinaan oleh Wali Kelas'),
('6', 'Membersihkan lingkungan sekolah'),
('7', 'Pemanggilan orang tua/wali siswa'),
('8', 'Menjalani pembinaan oleh BK'),
('9', 'Menjalani pembinaan khusus oleh Tim Tatib'),
('10', 'Diserahkan kembali pendidikannya kepada orang tua (Dikeluarkan)');

-- 3. INSERT JENIS PELANGGARAN
INSERT INTO tb_jenis_pelanggaran (id_kategori, sub_kategori, nama_pelanggaran, poin_default, sanksi_default) VALUES 
(1, '01. Kegiatan Sekolah', 'Tidak mengikuti kegiatan wajib sekolah / upacara tanpa keterangan.', 100, '5'),
(1, '01. Kegiatan Sekolah', 'Bergurau/tidak tertib saat kegiatan berlangsung', 100, '5'),
(1, '02. Sikap & Moral', 'Berkata tidak sopan/kasar/jorok', 100, '1'),
(1, '02. Sikap & Moral', 'Mencuri/memalak/meminta paksa', 500, '1,4,7'),
(1, '02. Sikap & Moral', 'Berbohong', 100, '1'),
(1, '02. Sikap & Moral', 'Menghina/mengejek Guru/Karyawan', 200, '1,5'),
(1, '02. Sikap & Moral', 'Menghina/mengejek Siswa/Teman', 100, '1'),
(1, '02. Sikap & Moral', 'Perundungan (Bullying)', 100, '1,5,7,8,9'),
(1, '02. Sikap & Moral', 'Membanting pintu/melempar benda', 100, '1'),
(1, '02. Sikap & Moral', 'Memanggil ortu dengan sebutan tidak sopan', 100, '1,2,5,8'),
(1, '02. Sikap & Moral', 'Bersikap tidak sopan (duduk di meja dll)', 100, '1,2'),
(1, '02. Sikap & Moral', 'Merayakan HUT teman secara negatif', 100, '1,5'),
(1, '02. Sikap & Moral', 'Memicu keributan di medsos/sekolah', 100, '1,2,7,8'),
(1, '02. Sikap & Moral', 'Membiarkan/mendorong kerusakan fasilitas', 100, '1,3'),
(1, '02. Sikap & Moral', 'Membiarkan teman celaka/sakit', 100, '1,2,7,8'),
(1, '03. Dokumen', 'Memalsukan surat/tanda tangan', 300, '7'),
(1, '04. Rokok & Miras', 'Membawa rokok', 300, '7,8'),
(1, '04. Rokok & Miras', 'Merokok (langsung/medsos)', 500, '7,8,9,10'),
(1, '04. Rokok & Miras', 'Membawa minuman keras', 300, '7,8'),
(1, '04. Rokok & Miras', 'Meminum minuman keras', 500, '7,8,9,10'),
(1, '05. NAPZA', 'Membawa/mengedarkan/menggunakan NAPZA', 9999, '10'),
(1, '06. Pelecehan Seksual', 'Membawa/akses/sebar konten porno', 300, '1,7'),
(1, '06. Pelecehan Seksual', 'Melakukan tindakan Pelecehan Seksual', 500, '1,7,8,9'),
(1, '07. Kekerasan', 'Terlibat perkelahian/main hakim sendiri', 300, '1,2,7,8,9'),
(1, '07. Kekerasan', 'Mengancam Kepala Sekolah/Guru/Karyawan', 300, '10'),
(1, '07. Kekerasan', 'Tindak kriminal terbukti hukum', 9999, '10'),
(1, '08. Gank', 'Terlibat Gank negatif', 300, '1,7,8'),
(1, '09. Sarana Prasarana', 'Mencorat-coret/merusak sarana sekolah', 75, '1,3'),
(1, '09. Sarana Prasarana', 'Bermain alat PBM/sapu di kelas', 75, '1,3'),
(1, '09. Sarana Prasarana', 'Makan dan minum di dalam kelas', 50, '1,2'),
(1, '10. Ketertiban PBM', 'Ramai/tidak memperhatikan saat PBM', 50, '1,2'),
(1, '10. Ketertiban PBM', 'Keluar kelas saat PBM tanpa izin', 50, '1,2'),
(1, '10. Ketertiban PBM', 'Menyontek saat ulangan', 300, '1,5'),
(1, '10. Ketertiban PBM', 'Mengambil alat PBM teman tanpa izin', 50, '1,2'),
(1, '10. Ketertiban PBM', 'Penyalahgunaan HP saat PBM', 50, '1,2'),
(1, '11. 10 K', 'Tidak mendukung 10 K', 50, '1,2,6'),
(1, '12. Kendaraan', 'Mengendarai kendaraan bermotor sendiri', 300, '1,7,8,9'),
(2, '01. Kehadiran', 'Terlambat sekolah/tambahan/ekstra', 25, '2,5,7,8'),
(2, '02. Efektif Sekolah', 'Tidak hadir tanpa keterangan (Alpa)', 75, '7,8'),
(2, '02. Efektif Sekolah', 'Meninggalkan sekolah saat PBM (Bolos)', 75, '7,8'),
(2, '03. PBM', 'Tidak masuk kelas jam pertama', 300, '1,7'),
(2, '03. PBM', 'Tidak ikut olahraga/praktikum tanpa izin', 500, '1,7,8,9'),
(2, '04. Perlengkapan', 'Tidak bawa buku pelajaran', 50, '1,2'),
(2, '04. Perlengkapan', 'Buku catatan campur/tidak rapi', 50, '1,2'),
(2, '04. Perlengkapan', 'Tidak bawa LKS/PR/Tugas', 50, '1,2'),
(2, '04. Perlengkapan', 'Membawa barang non-PBM', 75, '7,8'),
(2, '04. Perlengkapan', 'Tidak membawa buku tatib/literasi', 25, '1'),
(2, '05. Tugas', 'Mencontoh PR/Tugas', 50, '2'),
(2, '05. Tugas', 'Tidak mengumpulkan PR/Tugas', 50, '2'),
(2, '06. Ekstrakurikuler', 'Tidak ikut ekstra tanpa izin', 50, '7,8'),
(2, '06. Ekstrakurikuler', 'Ramai saat kegiatan ekstra', 50, '2'),
(2, '06. Ekstrakurikuler', 'Tidak ikut tambahan pelajaran', 50, '7'),
(3, '01. Seragam', 'Seragam tidak sesuai ketentuan', 75, '1,2,5,7'),
(3, '01. Seragam', 'Pakai rompi/jaket hanya aksesoris', 75, '1,2,5,7'),
(3, '01. Seragam', 'Seragam olahraga dari rumah/saat pulang', 50, '1'),
(3, '01. Seragam', 'Tidak pakai kaos dalam', 50, '1'),
(3, '01. Seragam', 'Atribut tidak lengkap (topi/dasi/sabuk/dll)', 50, '1'),
(3, '01. Seragam', 'Kaos kaki pendek/warna-warni/sepatu non-hitam', 50, '5'),
(3, '01. Seragam', 'Seragam dicoret-coret', 100, '1'),
(3, '01. Seragam', 'Mencoret anggota tubuh', 100, '1'),
(3, '01. Seragam', 'Baju tidak dimasukkan/rok-celana tidak standar', 50, '1,2,5,7'),
(3, '02. Aksesoris', 'Perhiasan/aksesoris berlebihan', 50, '1'),
(3, '02. Aksesoris', 'Putra memakai gelang/anting/kalung', 50, '1'),
(3, '02. Aksesoris', 'Putri memakai gelang/double anting', 50, '1'),
(3, '02. Aksesoris', 'Kuku panjang/dicat', 50, '1'),
(3, '03. Rambut', 'Rambut dicat', 100, '1,7'),
(3, '03. Rambut', 'Putra rambut panjang/gundul', 50, '1'),
(3, '03. Rambut', 'Rambut menutupi wajah/tidak rapi', 50, '1'),
(3, '04. Kegiatan', 'Tidak rapi/bersepatu saat ekstra/tambahan', 50, '1'),
(3, '05. Sepeda', 'Parkir sepeda tidak teratur/tidak dikunci', 25, '1');

-- 4. INSERT ATURAN SP
INSERT INTO tb_aturan_sp (id_kategori, level_sp, batas_bawah_poin) VALUES 
(1, 'SP1', 250), (1, 'SP2', 750), (1, 'SP3', 1500), (1, 'Sanksi oleh Sekolah', 2000),
(2, 'SP1', 75), (2, 'SP2', 300), (2, 'SP3', 450), (2, 'Sanksi oleh Sekolah', 600),
(3, 'SP1', 100), (3, 'SP2', 300), (3, 'SP3', 450), (3, 'Sanksi oleh Sekolah', 600);

-- 5. INSERT PREDIKAT NILAI RAPOR
INSERT INTO tb_predikat_nilai (id_kategori, huruf_mutu, batas_bawah, batas_atas, keterangan) VALUES 
(1, 'A', 0, 49, 'Sangat Baik'), (1, 'B', 50, 249, 'Baik'), (1, 'C', 250, 1499, 'Cukup (SP1/SP2)'), (1, 'D', 1500, 9999, 'Kurang (SP3/Berat)'),
(2, 'A', 0, 24, 'Sangat Baik'), (2, 'B', 25, 74, 'Baik'), (2, 'C', 75, 449, 'Cukup (SP1/SP2)'), (2, 'D', 450, 9999, 'Kurang (SP3/Berat)'),
(3, 'A', 0, 49, 'Sangat Baik'), (3, 'B', 50, 99, 'Baik'), (3, 'C', 100, 449, 'Cukup (SP1/SP2)'), (3, 'D', 450, 9999, 'Kurang (SP3/Berat)');

-- 6. INSERT USER TESTING (Contoh Data)
INSERT INTO tb_admin (username, password, nama_lengkap, role) VALUES 
('admin', 'admin123', 'Super Admin Tatib', 'SuperAdmin');

-- Insert Data Tahun 
INSERT INTO tb_tahun_ajaran (nama_tahun, status, semester_aktif) VALUES 
('2025/2026', 'Aktif', 'Ganjil');

-- INSERT KELAS
INSERT INTO tb_kelas (nama_kelas, tingkat) VALUES ('VII A', 7), ('VII B', 7), ('VII C', 7), ('VII D', 7), ('VII E', 7), ('VIII A', 8), ('VIII B', 8), ('VIII C', 8), ('VIII D', 8), ('VIII E', 8), ('IX A', 9), ('IX B', 9), ('IX C', 9), ('IX D', 9), ('IX E', 9);

-- INSERT GURU
INSERT INTO tb_guru (nama_guru, nip, kode_guru, id_kelas, pin_validasi) VALUES 
('Sr. M. Elfrida Suhartati, SPM, S.Psi.,MM', '10001', '1', NULL, '123456'),
('Antonetta Maria Kuntodiati, S.Pd', '10002', '2', NULL, '123456'),
('Dra. Maria Marsiti', '10003', '3', NULL, '123456'),
('Trianto Thomas, S.Pd', '10004', '4', NULL, '123456'),
('Agustina Peni Sarasati, S.Pd', '10005', '5', NULL, '123456'),
('Y. Pamungkas, S.Pd', '10006', '6', NULL, '123456'),
('Joseph Andiek Kristian, S.Pd, S.Kom', '10007', '7', NULL, '123456'),
('Albertha Yulanti Susetyo, M.Pd', '10008', '8', NULL, '123456'),
('Galang Bagus Afridianto, M.Pd', '10009', '9', NULL, '123456'),
('Hendrik Kiswanto, S.Pd.', '10010', '10', NULL, '123456'),
('Margareta Esti Wulan, S.Pd.', '10011', '11', NULL, '123456'),
('Theresia Sri Wahyuni, S.Pd, M.M.', '10012', '12', NULL, '123456'),
('Yosua Beni Setiawan, S.Pd.', '10014', '14', NULL, '123456'),
('God Life Endob Mesak, S.Pd', '10015', '15', NULL, '123456'),
('Agnes Herawaty Sinurat, S.E., M.M.', '10016', '16', NULL, '123456'),
('Deka Nanda Kurniawati, S.Pd.', '10017', '17', NULL, '123456'),
('Agatha Novenia Bintang Prieska, S.Pd.', '10018', '18', NULL, '123456'),
('Bernadetha Devia Tindy Noveyra, S.Pd.', '10019', '19', NULL, '123456'),
('Drs. Albertus Magnus Meo Depa', '10020', '20', NULL, '123456'),
('Giovani Bimby Dwiantonio, S.Pd', '10021', '21', NULL, '123456'),
('Arnoldus Kobe Tegar Felix Sai, S.Pd.', '10022', '22', NULL, '123456'),
('Haniar Mey Sila Kinanti, S.Pd.', '10023', '23', NULL, '123456'),
('Anjelina Wulandari Sitina De Sareng, S.Pd', '10024', '24', NULL, '123456'),
('Lydia Uli Permatasari, S.Pd.', '10025', '25', NULL, '123456'),
('Albertus Bayu Seto, S.Pd', '10026', '26', NULL, '123456'),
('Brigita Natalia Setyaningrum, S.Pd.', '10027', '27', NULL, '123456'),
('Amelia Rangel Da Silva, S.Pd', '10028', '28', NULL, '123456');

-- INSERT SISWA (Data awal)
INSERT INTO tb_siswa (no_induk, nama_siswa, jenis_kelamin, kota, tanggal_lahir, alamat, nama_ayah, pekerjaan_ayah, nama_ibu, pekerjaan_ibu, no_hp_ortu, id_ortu) VALUES 
('2024001', 'Ahmad Roland', 'L', 'Malang', '2010-05-15', 'Jl. Merdeka No. 1', 'Bpk. Dani', 'Swasta', 'Ibu Dani', 'Ibu Rumah Tangga', '081234567890', NULL);

-- INSERT ANGGOTA KELAS
INSERT INTO tb_anggota_kelas (no_induk, id_kelas, id_tahun) VALUES ('2024001', 1, 1);