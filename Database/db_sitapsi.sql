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
    nama_tahun VARCHAR(20) NOT NULL, -- Contoh: "2024/2025"
    status ENUM('Aktif', 'Arsip') DEFAULT 'Aktif', 
    semester_aktif ENUM('Ganjil', 'Genap') DEFAULT 'Ganjil'
);

-- ================================================================
-- 2. GROUP: MASTER DATA UTAMA (MANUSIA)
-- ================================================================

-- Tabel Guru (Login SSO dengan PIN)
CREATE TABLE tb_guru (
    id_guru INT AUTO_INCREMENT PRIMARY KEY,
    nama_guru VARCHAR(100) NOT NULL,
    nip VARCHAR(30),
    pin_validasi VARCHAR(6) NOT NULL, -- PIN 6 Digit
    status ENUM('Aktif', 'Non-Aktif') DEFAULT 'Aktif'
);

-- Tabel Siswa (Data Statis / Induk)
CREATE TABLE tb_siswa (
    nis VARCHAR(20) PRIMARY KEY,
    nama_siswa VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    tempat_lahir VARCHAR(50),
    tanggal_lahir DATE,
    alamat_ortu TEXT,
    nama_ortu VARCHAR(100),
    no_hp_ortu VARCHAR(15), -- Notifikasi WA
    foto_profil VARCHAR(255),
    -- Status 'Lulus' ditambahkan untuk alumni (Arsip 3 Tahun)
    status_aktif ENUM('Aktif', 'Lulus', 'Keluar', 'Dikeluarkan') DEFAULT 'Aktif'
);

-- Tabel Kelas
CREATE TABLE tb_kelas (
    id_kelas INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(10) NOT NULL, -- 7A, 8B
    tingkat INT NOT NULL -- 7, 8, 9
);

-- ================================================================
-- 3. GROUP: MAPPING (LOGIKA UTAMA & RESET POIN)
-- ================================================================

-- Tabel Anggota Kelas (Jantung Sistem - Per Tahun)
CREATE TABLE tb_anggota_kelas (
    id_anggota BIGINT AUTO_INCREMENT PRIMARY KEY,
    nis VARCHAR(20) NOT NULL,
    id_kelas INT NOT NULL,
    id_tahun INT NOT NULL,
    
    -- Akumulasi Poin Tahunan (Untuk perhitungan SP)
    poin_kelakuan INT DEFAULT 0,
    poin_kerajinan INT DEFAULT 0,
    poin_kerapian INT DEFAULT 0,
    
    total_poin_umum INT DEFAULT 0, -- Total Gabungan Tahunan
    
    -- [MODIFIKASI: 3 Silo Status SP Independen]
    status_sp_kelakuan ENUM('Aman', 'SP1', 'SP2', 'SP3', 'Dikeluarkan') DEFAULT 'Aman',
    status_sp_kerajinan ENUM('Aman', 'SP1', 'SP2', 'SP3', 'Dikeluarkan') DEFAULT 'Aman',
    status_sp_kerapian ENUM('Aman', 'SP1', 'SP2', 'SP3', 'Dikeluarkan') DEFAULT 'Aman',
    
    -- [Summary Status Tertinggi]
    status_sp_terakhir ENUM('Aman', 'SP1', 'SP2', 'SP3', 'Dikeluarkan') DEFAULT 'Aman',
    
    -- Penanda Reward
    status_reward ENUM('None', 'Kandidat Sertifikat') DEFAULT 'None',
    
    FOREIGN KEY (nis) REFERENCES tb_siswa(nis) ON DELETE CASCADE,
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
    level_sp ENUM('SP1', 'SP2', 'SP3', 'Dikeluarkan') NOT NULL,
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
    id_tahun INT NOT NULL, -- Field Baru: Mengunci pelanggaran ke tahun tertentu
    tanggal DATE NOT NULL,
    waktu TIME NOT NULL,
    semester ENUM('Ganjil', 'Genap') NOT NULL, -- Kunci logika "Lembar Kosong" Genap
    tipe_form ENUM('Piket', 'Kelas') NOT NULL,
    bukti_foto VARCHAR(255),
    
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
    tingkat_sp ENUM('SP1', 'SP2', 'SP3', 'Dikeluarkan') NOT NULL,
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

-- 3. INSERT JENIS PELANGGARAN (LENGKAP)
-- === A. ASPEK KELAKUAN (ID: 1) ===
INSERT INTO tb_jenis_pelanggaran (id_kategori, sub_kategori, nama_pelanggaran, poin_default, sanksi_default) VALUES 
(1, 'Kegiatan Sekolah', 'Tidak mengikuti kegiatan wajib sekolah / upacara tanpa ket.', 100, '5'),
(1, 'Kegiatan Sekolah', 'Bergurau/tidak tertib saat kegiatan berlangsung', 100, '5'),
(1, 'Sikap & Moral', 'Berkata tidak sopan/kasar/jorok', 100, '1'),
(1, 'Sikap & Moral', 'Mencuri/memalak/meminta paksa', 500, '1,4,7'),
(1, 'Sikap & Moral', 'Berbohong', 100, '1'),
(1, 'Sikap & Moral', 'Menghina/mengejek Guru/Karyawan', 200, '1,5'),
(1, 'Sikap & Moral', 'Menghina/mengejek Siswa/Teman', 100, '1'),
(1, 'Sikap & Moral', 'Perundungan (Bullying)', 100, '1,5,7,8,9'),
(1, 'Sikap & Moral', 'Membanting pintu/melempar benda', 100, '1'),
(1, 'Sikap & Moral', 'Memanggil ortu dengan sebutan tidak sopan', 100, '1,2,5,8'),
(1, 'Sikap & Moral', 'Bersikap tidak sopan (duduk di meja dll)', 100, '1,2'),
(1, 'Sikap & Moral', 'Merayakan HUT teman secara negatif', 100, '1,5'),
(1, 'Sikap & Moral', 'Memicu keributan di medsos/sekolah', 100, '1,2,7,8'),
(1, 'Sikap & Moral', 'Membiarkan/mendorong kerusakan fasilitas', 100, '1,3'),
(1, 'Sikap & Moral', 'Membiarkan teman celaka/sakit', 100, '1,2,7,8'),
(1, 'Dokumen', 'Memalsukan surat/tanda tangan/dokumen', 300, '7'),
(1, 'Rokok & Miras', 'Membawa rokok', 300, '7,8'),
(1, 'Rokok & Miras', 'Merokok (langsung/medsos)', 500, '7,8,9,10'),
(1, 'Rokok & Miras', 'Membawa minuman keras', 300, '7,8'),
(1, 'Rokok & Miras', 'Meminum minuman keras', 500, '7,8,9,10'),
(1, 'NAPZA', 'Membawa/mengedarkan/menggunakan NAPZA', 9999, '10'),
(1, 'Pelecehan Seksual', 'Membawa/akses/sebar konten porno', 300, '1,7'),
(1, 'Pelecehan Seksual', 'Melakukan tindakan pelecehan seksual', 500, '1,7,8,9'),
(1, 'Kekerasan', 'Terlibat perkelahian/main hakim sendiri', 300, '1,2,7,8,9'),
(1, 'Kekerasan', 'Mengancam Kepala Sekolah/Guru/Karyawan', 300, 'Surat Pernyataan/10'),
(1, 'Kekerasan', 'Tindak kriminal terbukti hukum', 9999, '10'),
(1, 'Gank', 'Terlibat gank negatif', 300, '1,7,8'),
(1, 'Sarana Prasarana', 'Mencorat-coret/merusak sarana sekolah', 75, '1,3'),
(1, 'Sarana Prasarana', 'Bermain alat PBM/sapu di kelas', 75, '1,3'),
(1, 'Sarana Prasarana', 'Makan dan minum di dalam kelas', 50, '1,2'),
(1, 'Ketertiban PBM', 'Ramai/tidak memperhatikan saat PBM', 50, '1,2'),
(1, 'Ketertiban PBM', 'Keluar kelas saat PBM tanpa izin', 50, '1,2'),
(1, 'Ketertiban PBM', 'Menyontek saat ulangan', 300, '1,5'),
(1, 'Ketertiban PBM', 'Mengambil alat PBM teman tanpa izin', 50, '1,2'),
(1, 'Ketertiban PBM', 'Penyalahgunaan HP saat PBM', 50, '1,2'),
(1, '10 K', 'Tidak mendukung 10 K', 50, '1,2,6'),
(1, 'Kendaraan', 'Mengendarai kendaraan bermotor sendiri', 300, '1,7,8,9');

-- === B. ASPEK KERAJINAN (ID: 2) ===
INSERT INTO tb_jenis_pelanggaran (id_kategori, sub_kategori, nama_pelanggaran, poin_default, sanksi_default) VALUES 
(2, 'Kehadiran', 'Terlambat sekolah/tambahan/ekstra', 25, '2,5,7,8'),
(2, 'Efektif Sekolah', 'Tidak hadir tanpa keterangan (Alpa)', 75, '7,8'),
(2, 'Efektif Sekolah', 'Meninggalkan sekolah saat PBM (Bolos)', 75, '7,8'),
(2, 'PBM', 'Tidak masuk kelas jam pertama', 300, '1,7'),
(2, 'PBM', 'Tidak ikut olahraga/praktikum tanpa izin', 500, '1,7,8,9'),
(2, 'Perlengkapan', 'Tidak bawa buku pelajaran', 50, '1,2'),
(2, 'Perlengkapan', 'Buku catatan campur/tidak rapi', 50, '1,2'),
(2, 'Perlengkapan', 'Tidak bawa LKS/PR/Tugas', 50, '1,2'),
(2, 'Perlengkapan', 'Membawa barang non-PBM', 75, '7,8'),
(2, 'Perlengkapan', 'Tidak membawa buku tatib/literasi', 25, '1'),
(2, 'Tugas', 'Mencontoh PR/Tugas', 50, '2'),
(2, 'Tugas', 'Tidak mengumpulkan PR/Tugas', 50, '2'),
(2, 'Ekstrakurikuler', 'Tidak ikut ekstra tanpa izin', 50, '7,8'),
(2, 'Ekstrakurikuler', 'Ramai saat kegiatan ekstra', 50, '2'),
(2, 'Ekstrakurikuler', 'Tidak ikut tambahan pelajaran', 50, '7');

-- === C. ASPEK KERAPIAN (ID: 3) ===
INSERT INTO tb_jenis_pelanggaran (id_kategori, sub_kategori, nama_pelanggaran, poin_default, sanksi_default) VALUES 
(3, 'Seragam', 'Seragam tidak sesuai ketentuan', 75, '1,2,5,7'),
(3, 'Seragam', 'Pakai rompi/jaket hanya aksesoris', 75, '1,2,5,7'),
(3, 'Seragam', 'Seragam olahraga dari rumah/saat pulang', 50, '1'),
(3, 'Seragam', 'Tidak pakai kaos dalam', 50, '1'),
(3, 'Seragam', 'Atribut tidak lengkap (topi/dasi/sabuk/dll)', 50, '1'),
(3, 'Seragam', 'Kaos kaki pendek/warna-warni/sepatu non-hitam', 50, '5'),
(3, 'Seragam', 'Seragam dicoret-coret', 100, '1'),
(3, 'Seragam', 'Mencoret anggota tubuh', 100, '1'),
(3, 'Seragam', 'Baju tidak dimasukkan/rok-celana tidak standar', 50, '1,2,5,7'),
(3, 'Aksesoris', 'Perhiasan/aksesoris berlebihan', 50, '1'),
(3, 'Aksesoris', 'Putra memakai gelang/anting/kalung', 50, '1'),
(3, 'Aksesoris', 'Putri memakai gelang/double anting', 50, '1'),
(3, 'Aksesoris', 'Kuku panjang/dicat', 50, '1'),
(3, 'Rambut', 'Rambut dicat', 100, '1,7'),
(3, 'Rambut', 'Putra rambut panjang/gundul', 50, '1'),
(3, 'Rambut', 'Rambut menutupi wajah/tidak rapi', 50, '1'),
(3, 'Kegiatan', 'Tidak rapi/bersepatu saat ekstra/tambahan', 50, '1'),
(3, 'Sepeda', 'Parkir sepeda tidak teratur/tidak dikunci', 25, '1');

-- 4. INSERT ATURAN SP
INSERT INTO tb_aturan_sp (id_kategori, level_sp, batas_bawah_poin) VALUES 
-- Kelakuan (ID: 1)
(1, 'SP1', 250), (1, 'SP2', 750), (1, 'SP3', 1500), (1, 'Dikeluarkan', 2000),
-- Kerajinan (ID: 2)
(2, 'SP1', 75), (2, 'SP2', 300), (2, 'SP3', 450), (2, 'Dikeluarkan', 600),
-- Kerapian (ID: 3)
(3, 'SP1', 100), (3, 'SP2', 300), (3, 'SP3', 450), (3, 'Dikeluarkan', 600);

-- 5. INSERT PREDIKAT NILAI RAPOR
INSERT INTO tb_predikat_nilai (id_kategori, huruf_mutu, batas_bawah, batas_atas, keterangan) VALUES 
-- Kelakuan (ID: 1)
(1, 'A', 0, 49, 'Sangat Baik'), (1, 'B', 50, 249, 'Baik'), (1, 'C', 250, 1499, 'Cukup (SP1/SP2)'), (1, 'D', 1500, 9999, 'Kurang (SP3/Berat)'),
-- Kerajinan (ID: 2)
(2, 'A', 0, 24, 'Sangat Baik'), (2, 'B', 25, 74, 'Baik'), (2, 'C', 75, 449, 'Cukup (SP1/SP2)'), (2, 'D', 450, 9999, 'Kurang (SP3/Berat)'),
-- Kerapian (ID: 3)
(3, 'A', 0, 49, 'Sangat Baik'), (3, 'B', 50, 99, 'Baik'), (3, 'C', 100, 449, 'Cukup (SP1/SP2)'), (3, 'D', 450, 9999, 'Kurang (SP3/Berat)');

-- 6. INSERT USER TESTING (Contoh Data)
INSERT INTO tb_admin (username, password, nama_lengkap, role) VALUES 
('admin', 'admin123', 'Super Admin Tatib', 'SuperAdmin');

INSERT INTO tb_guru (nama_guru, nip, pin_validasi) VALUES 
('Budi Santoso, S.Pd', '198501012010011001', '123456');

-- Tahun Aktif
INSERT INTO tb_tahun_ajaran (nama_tahun, status, semester_aktif) VALUES 
('2024/2025', 'Aktif', 'Ganjil');

INSERT INTO tb_kelas (nama_kelas, tingkat) VALUES ('7A', 7), ('7B', 7);

INSERT INTO tb_siswa (nis, nama_siswa, jenis_kelamin, nama_ortu, no_hp_ortu) VALUES 
('2024001', 'Ahmad Dani', 'L', 'Bpk. Dani', '081234567890');

INSERT INTO tb_anggota_kelas (nis, id_kelas, id_tahun) VALUES ('2024001', 1, 1);