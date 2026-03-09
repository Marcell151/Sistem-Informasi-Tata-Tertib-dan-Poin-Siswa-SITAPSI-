-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 09 Mar 2026 pada 13.02
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_sitapsi`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_admin`
--

CREATE TABLE `tb_admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('SuperAdmin','Admin') DEFAULT 'SuperAdmin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_admin`
--

INSERT INTO `tb_admin` (`id_admin`, `username`, `password`, `nama_lengkap`, `role`, `created_at`) VALUES
(1, 'admin', 'admin123', 'Super Admin Tatib', 'SuperAdmin', '2026-03-09 07:47:19');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_anggota_kelas`
--

CREATE TABLE `tb_anggota_kelas` (
  `id_anggota` bigint(20) NOT NULL,
  `no_induk` varchar(50) NOT NULL,
  `id_kelas` int(11) NOT NULL,
  `id_tahun` int(11) NOT NULL,
  `poin_kelakuan` int(11) DEFAULT 0,
  `poin_kerajinan` int(11) DEFAULT 0,
  `poin_kerapian` int(11) DEFAULT 0,
  `total_poin_umum` int(11) DEFAULT 0,
  `status_sp_kelakuan` enum('Aman','SP1','SP2','SP3','Dikeluarkan') DEFAULT 'Aman',
  `status_sp_kerajinan` enum('Aman','SP1','SP2','SP3','Dikeluarkan') DEFAULT 'Aman',
  `status_sp_kerapian` enum('Aman','SP1','SP2','SP3','Dikeluarkan') DEFAULT 'Aman',
  `status_sp_terakhir` enum('Aman','SP1','SP2','SP3','Dikeluarkan') DEFAULT 'Aman',
  `status_reward` enum('None','Kandidat Reward Ganjil','Kandidat Sertifikat') DEFAULT 'None'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_anggota_kelas`
--

INSERT INTO `tb_anggota_kelas` (`id_anggota`, `no_induk`, `id_kelas`, `id_tahun`, `poin_kelakuan`, `poin_kerajinan`, `poin_kerapian`, `total_poin_umum`, `status_sp_kelakuan`, `status_sp_kerajinan`, `status_sp_kerapian`, `status_sp_terakhir`, `status_reward`) VALUES
(2, '11223', 1, 1, 0, 25, 75, 100, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(3, '11225', 1, 1, 0, 175, 300, 475, 'Aman', 'SP1', 'SP2', 'SP2', 'None'),
(4, '11237', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(5, '11248', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(6, '11256', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(7, '11261', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(8, '11272', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(9, '11273', 1, 1, 400, 0, 0, 400, 'SP1', 'Aman', 'Aman', 'SP1', 'None'),
(10, '11275', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(11, '11276', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(12, '11278', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(13, '11284', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(14, '11285', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(15, '11287', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(16, '11288', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(17, '11294', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(18, '11296', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(19, '11305', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(20, '11307', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(21, '11312', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(22, '11313', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(23, '11317', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(24, '11319', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(25, '11320', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(26, '11323', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(27, '11348', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(28, '11356', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None'),
(29, '11360', 1, 1, 0, 0, 0, 0, 'Aman', 'Aman', 'Aman', 'Aman', 'None');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_aturan_sp`
--

CREATE TABLE `tb_aturan_sp` (
  `id_aturan_sp` int(11) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `level_sp` enum('SP1','SP2','SP3','Dikeluarkan') NOT NULL,
  `batas_bawah_poin` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_aturan_sp`
--

INSERT INTO `tb_aturan_sp` (`id_aturan_sp`, `id_kategori`, `level_sp`, `batas_bawah_poin`) VALUES
(1, 1, 'SP1', 250),
(2, 1, 'SP2', 750),
(3, 1, 'SP3', 1500),
(4, 1, 'Dikeluarkan', 2000),
(5, 2, 'SP1', 75),
(6, 2, 'SP2', 300),
(7, 2, 'SP3', 450),
(8, 2, 'Dikeluarkan', 600),
(9, 3, 'SP1', 100),
(10, 3, 'SP2', 300),
(11, 3, 'SP3', 450),
(12, 3, 'Dikeluarkan', 600);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_guru`
--

CREATE TABLE `tb_guru` (
  `id_guru` int(11) NOT NULL,
  `nama_guru` varchar(100) NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `kode_guru` varchar(10) DEFAULT NULL,
  `id_kelas` int(11) DEFAULT NULL,
  `pin_validasi` varchar(6) NOT NULL,
  `status` enum('Aktif','Non-Aktif') DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_guru`
--

INSERT INTO `tb_guru` (`id_guru`, `nama_guru`, `nip`, `kode_guru`, `id_kelas`, `pin_validasi`, `status`) VALUES
(1, 'Sr. M. Elfrida Suhartati, SPM, S.Psi.,MM', '10001', '1', NULL, '123456', 'Aktif'),
(2, 'Antonetta Maria Kuntodiati, S.Pd', '10002', '2', NULL, '123456', 'Aktif'),
(3, 'Dra. Maria Marsiti', '10003', '3', NULL, '123456', 'Aktif'),
(4, 'Trianto Thomas, S.Pd', '10004', '4', NULL, '123456', 'Aktif'),
(5, 'Agustina Peni Sarasati, S.Pd', '10005', '5', 1, '123456', 'Aktif'),
(6, 'Y. Pamungkas, S.Pd', '10006', '6', NULL, '123456', 'Aktif'),
(7, 'Joseph Andiek Kristian, S.Pd, S.Kom', '10007', '7', NULL, '123456', 'Aktif'),
(8, 'Albertha Yulanti Susetyo, M.Pd', '10008', '8', NULL, '123456', 'Aktif'),
(9, 'Galang Bagus Afridianto, M.Pd', '10009', '9', NULL, '123456', 'Aktif'),
(10, 'Hendrik Kiswanto, S.Pd.', '10010', '10', NULL, '123456', 'Aktif'),
(11, 'Margareta Esti Wulan, S.Pd.', '10011', '11', NULL, '123456', 'Aktif'),
(12, 'Theresia Sri Wahyuni, S.Pd, M.M.', '10012', '12', NULL, '123456', 'Aktif'),
(13, 'Yosua Beni Setiawan, S.Pd.', '10014', '14', NULL, '123456', 'Aktif'),
(14, 'God Life Endob Mesak, S.Pd', '10015', '15', NULL, '123456', 'Aktif'),
(15, 'Agnes Herawaty Sinurat, S.E., M.M.', '10016', '16', NULL, '123456', 'Aktif'),
(16, 'Deka Nanda Kurniawati, S.Pd.', '10017', '17', NULL, '123456', 'Aktif'),
(17, 'Agatha Novenia Bintang Prieska, S.Pd.', '10018', '18', NULL, '123456', 'Aktif'),
(18, 'Bernadetha Devia Tindy Noveyra, S.Pd.', '10019', '19', NULL, '123456', 'Aktif'),
(19, 'Drs. Albertus Magnus Meo Depa', '10020', '20', NULL, '123456', 'Aktif'),
(20, 'Giovani Bimby Dwiantonio, S.Pd', '10021', '21', NULL, '123456', 'Aktif'),
(21, 'Arnoldus Kobe Tegar Felix Sai, S.Pd.', '10022', '22', NULL, '123456', 'Aktif'),
(22, 'Haniar Mey Sila Kinanti, S.Pd.', '10023', '23', NULL, '123456', 'Aktif'),
(23, 'Anjelina Wulandari Sitina De Sareng, S.Pd', '10024', '24', NULL, '123456', 'Aktif'),
(24, 'Lydia Uli Permatasari, S.Pd.', '10025', '25', NULL, '123456', 'Aktif'),
(25, 'Albertus Bayu Seto, S.Pd', '10026', '26', NULL, '123456', 'Aktif'),
(26, 'Brigita Natalia Setyaningrum, S.Pd.', '10027', '27', NULL, '123456', 'Aktif'),
(27, 'Amelia Rangel Da Silva, S.Pd', '10028', '28', NULL, '123456', 'Aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_jenis_pelanggaran`
--

CREATE TABLE `tb_jenis_pelanggaran` (
  `id_jenis` int(11) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `sub_kategori` varchar(100) DEFAULT NULL,
  `nama_pelanggaran` text NOT NULL,
  `poin_default` int(11) NOT NULL,
  `sanksi_default` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_jenis_pelanggaran`
--

INSERT INTO `tb_jenis_pelanggaran` (`id_jenis`, `id_kategori`, `sub_kategori`, `nama_pelanggaran`, `poin_default`, `sanksi_default`) VALUES
(1, 1, '01. Kegiatan Sekolah', 'Tidak mengikuti kegiatan wajib sekolah / upacara tanpa keterangan.', 100, '5'),
(2, 1, '01. Kegiatan Sekolah', 'Bergurau/tidak tertib saat kegiatan berlangsung', 100, '5'),
(3, 1, '02. Sikap & Moral', 'Berkata tidak sopan/kasar/jorok', 100, '1'),
(4, 1, '02. Sikap & Moral', 'Mencuri/memalak/meminta paksa', 500, '1,4,7'),
(5, 1, '02. Sikap & Moral', 'Berbohong', 100, '1'),
(6, 1, '02. Sikap & Moral', 'Menghina/mengejek Guru/Karyawan', 200, '1,5'),
(7, 1, '02. Sikap & Moral', 'Menghina/mengejek Siswa/Teman', 100, '1'),
(8, 1, '02. Sikap & Moral', 'Perundungan (Bullying)', 100, '1,5,7,8,9'),
(9, 1, '02. Sikap & Moral', 'Membanting pintu/melempar benda', 100, '1'),
(10, 1, '02. Sikap & Moral', 'Memanggil ortu dengan sebutan tidak sopan', 100, '1,2,5,8'),
(11, 1, '02. Sikap & Moral', 'Bersikap tidak sopan (duduk di meja dll)', 100, '1,2'),
(12, 1, '02. Sikap & Moral', 'Merayakan HUT teman secara negatif', 100, '1,5'),
(13, 1, '02. Sikap & Moral', 'Memicu keributan di medsos/sekolah', 100, '1,2,7,8'),
(14, 1, '02. Sikap & Moral', 'Membiarkan/mendorong kerusakan fasilitas', 100, '1,3'),
(15, 1, '02. Sikap & Moral', 'Membiarkan teman celaka/sakit', 100, '1,2,7,8'),
(16, 1, '03. Dokumen', 'Memalsukan surat/tanda tangan', 300, '7'),
(17, 1, '04. Rokok & Miras', 'Membawa rokok', 300, '7,8'),
(18, 1, '04. Rokok & Miras', 'Merokok (langsung/medsos)', 500, '7,8,9,10'),
(19, 1, '04. Rokok & Miras', 'Membawa minuman keras', 300, '7,8'),
(20, 1, '04. Rokok & Miras', 'Meminum minuman keras', 500, '7,8,9,10'),
(21, 1, '05. NAPZA', 'Membawa/mengedarkan/menggunakan NAPZA', 9999, '10'),
(22, 1, '06. Pelecehan Seksual', 'Membawa/akses/sebar konten porno', 300, '1,7'),
(23, 1, '06. Pelecehan Seksual', 'Melakukan tindakan Pelecehan Seksual', 500, '1,7,8,9'),
(24, 1, '07. Kekerasan', 'Terlibat perkelahian/main hakim sendiri', 300, '1,2,7,8,9'),
(25, 1, '07. Kekerasan', 'Mengancam Kepala Sekolah/Guru/Karyawan', 300, '10'),
(26, 1, '07. Kekerasan', 'Tindak kriminal terbukti hukum', 9999, '10'),
(27, 1, '08. Gank', 'Terlibat Gank negatif', 300, '1,7,8'),
(28, 1, '09. Sarana Prasarana', 'Mencorat-coret/merusak sarana sekolah', 75, '1,3'),
(29, 1, '09. Sarana Prasarana', 'Bermain alat PBM/sapu di kelas', 75, '1,3'),
(30, 1, '09. Sarana Prasarana', 'Makan dan minum di dalam kelas', 50, '1,2'),
(31, 1, '10. Ketertiban PBM', 'Ramai/tidak memperhatikan saat PBM', 50, '1,2'),
(32, 1, '10. Ketertiban PBM', 'Keluar kelas saat PBM tanpa izin', 50, '1,2'),
(33, 1, '10. Ketertiban PBM', 'Menyontek saat ulangan', 300, '1,5'),
(34, 1, '10. Ketertiban PBM', 'Mengambil alat PBM teman tanpa izin', 50, '1,2'),
(35, 1, '10. Ketertiban PBM', 'Penyalahgunaan HP saat PBM', 50, '1,2'),
(36, 1, '11. 10 K', 'Tidak mendukung 10 K', 50, '1,2,6'),
(37, 1, '12. Kendaraan', 'Mengendarai kendaraan bermotor sendiri', 300, '1,7,8,9'),
(38, 2, '01. Kehadiran', 'Terlambat sekolah/tambahan/ekstra', 25, '2,5,7,8'),
(39, 2, '02. Efektif Sekolah', 'Tidak hadir tanpa keterangan (Alpa)', 75, '7,8'),
(40, 2, '02. Efektif Sekolah', 'Meninggalkan sekolah saat PBM (Bolos)', 75, '7,8'),
(41, 2, '03. PBM', 'Tidak masuk kelas jam pertama', 300, '1,7'),
(42, 2, '03. PBM', 'Tidak ikut olahraga/praktikum tanpa izin', 500, '1,7,8,9'),
(43, 2, '04. Perlengkapan', 'Tidak bawa buku pelajaran', 50, '1,2'),
(44, 2, '04. Perlengkapan', 'Buku catatan campur/tidak rapi', 50, '1,2'),
(45, 2, '04. Perlengkapan', 'Tidak bawa LKS/PR/Tugas', 50, '1,2'),
(46, 2, '04. Perlengkapan', 'Membawa barang non-PBM', 75, '7,8'),
(47, 2, '04. Perlengkapan', 'Tidak membawa buku tatib/literasi', 25, '1'),
(48, 2, '05. Tugas', 'Mencontoh PR/Tugas', 50, '2'),
(49, 2, '05. Tugas', 'Tidak mengumpulkan PR/Tugas', 50, '2'),
(50, 2, '06. Ekstrakurikuler', 'Tidak ikut ekstra tanpa izin', 50, '7,8'),
(51, 2, '06. Ekstrakurikuler', 'Ramai saat kegiatan ekstra', 50, '2'),
(52, 2, '06. Ekstrakurikuler', 'Tidak ikut tambahan pelajaran', 50, '7'),
(53, 3, '01. Seragam', 'Seragam tidak sesuai ketentuan', 75, '1,2,5,7'),
(54, 3, '01. Seragam', 'Pakai rompi/jaket hanya aksesoris', 75, '1,2,5,7'),
(55, 3, '01. Seragam', 'Seragam olahraga dari rumah/saat pulang', 50, '1'),
(56, 3, '01. Seragam', 'Tidak pakai kaos dalam', 50, '1'),
(57, 3, '01. Seragam', 'Atribut tidak lengkap (topi/dasi/sabuk/dll)', 50, '1'),
(58, 3, '01. Seragam', 'Kaos kaki pendek/warna-warni/sepatu non-hitam', 50, '5'),
(59, 3, '01. Seragam', 'Seragam dicoret-coret', 100, '1'),
(60, 3, '01. Seragam', 'Mencoret anggota tubuh', 100, '1'),
(61, 3, '01. Seragam', 'Baju tidak dimasukkan/rok-celana tidak standar', 50, '1,2,5,7'),
(62, 3, '02. Aksesoris', 'Perhiasan/aksesoris berlebihan', 50, '1'),
(63, 3, '02. Aksesoris', 'Putra memakai gelang/anting/kalung', 50, '1'),
(64, 3, '02. Aksesoris', 'Putri memakai gelang/double anting', 50, '1'),
(65, 3, '02. Aksesoris', 'Kuku panjang/dicat', 50, '1'),
(66, 3, '03. Rambut', 'Rambut dicat', 100, '1,7'),
(67, 3, '03. Rambut', 'Putra rambut panjang/gundul', 50, '1'),
(68, 3, '03. Rambut', 'Rambut menutupi wajah/tidak rapi', 50, '1'),
(69, 3, '04. Kegiatan', 'Tidak rapi/bersepatu saat ekstra/tambahan', 50, '1'),
(70, 3, '05. Sepeda', 'Parkir sepeda tidak teratur/tidak dikunci', 25, '1');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_kategori_pelanggaran`
--

CREATE TABLE `tb_kategori_pelanggaran` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_kategori_pelanggaran`
--

INSERT INTO `tb_kategori_pelanggaran` (`id_kategori`, `nama_kategori`) VALUES
(1, 'KELAKUAN'),
(2, 'KERAJINAN'),
(3, 'KERAPIAN');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_kelas`
--

CREATE TABLE `tb_kelas` (
  `id_kelas` int(11) NOT NULL,
  `nama_kelas` varchar(10) NOT NULL,
  `tingkat` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_kelas`
--

INSERT INTO `tb_kelas` (`id_kelas`, `nama_kelas`, `tingkat`) VALUES
(1, 'VII A', 7),
(2, 'VII B', 7),
(3, 'VII C', 7),
(4, 'VII D', 7),
(5, 'VII E', 7),
(6, 'VIII A', 8),
(7, 'VIII B', 8),
(8, 'VIII C', 8),
(9, 'VIII D', 8),
(10, 'VIII E', 8),
(11, 'IX A', 9),
(12, 'IX B', 9),
(13, 'IX C', 9),
(14, 'IX D', 9),
(15, 'IX E', 9);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_pelanggaran_detail`
--

CREATE TABLE `tb_pelanggaran_detail` (
  `id_detail` bigint(20) NOT NULL,
  `id_transaksi` bigint(20) NOT NULL,
  `id_jenis` int(11) NOT NULL,
  `poin_saat_itu` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_pelanggaran_detail`
--

INSERT INTO `tb_pelanggaran_detail` (`id_detail`, `id_transaksi`, `id_jenis`, `poin_saat_itu`) VALUES
(1, 1, 38, 25),
(2, 1, 40, 75),
(3, 1, 39, 75),
(4, 1, 57, 50),
(5, 1, 61, 50),
(6, 1, 58, 50),
(7, 2, 38, 25),
(8, 2, 54, 75),
(9, 3, 2, 100),
(10, 3, 5, 100),
(11, 3, 3, 100),
(12, 3, 11, 100),
(18, 5, 57, 50),
(19, 5, 61, 50),
(20, 5, 58, 50);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_pelanggaran_header`
--

CREATE TABLE `tb_pelanggaran_header` (
  `id_transaksi` bigint(20) NOT NULL,
  `id_anggota` bigint(20) NOT NULL,
  `id_guru` int(11) NOT NULL,
  `id_tahun` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `semester` enum('Ganjil','Genap') NOT NULL,
  `tipe_form` enum('Piket','Kelas') NOT NULL,
  `bukti_foto` varchar(255) DEFAULT NULL,
  `status_revisi` enum('None','Pending','Disetujui','Ditolak') DEFAULT 'None',
  `alasan_revisi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_pelanggaran_header`
--

INSERT INTO `tb_pelanggaran_header` (`id_transaksi`, `id_anggota`, `id_guru`, `id_tahun`, `tanggal`, `waktu`, `semester`, `tipe_form`, `bukti_foto`, `status_revisi`, `alasan_revisi`) VALUES
(1, 3, 5, 1, '2026-03-09', '14:58:00', 'Genap', 'Piket', NULL, 'None', NULL),
(2, 2, 5, 1, '2026-03-09', '14:58:00', 'Genap', 'Piket', NULL, 'None', NULL),
(3, 9, 5, 1, '2026-03-09', '14:58:00', 'Genap', 'Kelas', NULL, 'None', NULL),
(5, 3, 5, 1, '2026-03-09', '17:47:00', 'Genap', 'Kelas', NULL, 'None', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_pelanggaran_sanksi`
--

CREATE TABLE `tb_pelanggaran_sanksi` (
  `id_trans_sanksi` bigint(20) NOT NULL,
  `id_transaksi` bigint(20) NOT NULL,
  `id_sanksi_ref` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_pelanggaran_sanksi`
--

INSERT INTO `tb_pelanggaran_sanksi` (`id_trans_sanksi`, `id_transaksi`, `id_sanksi_ref`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 5),
(4, 1, 7),
(5, 1, 8),
(6, 2, 1),
(7, 2, 2),
(8, 2, 5),
(9, 2, 7),
(10, 2, 8),
(11, 3, 1),
(12, 3, 2),
(13, 3, 5),
(18, 5, 1),
(19, 5, 2),
(20, 5, 5),
(21, 5, 7);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_predikat_nilai`
--

CREATE TABLE `tb_predikat_nilai` (
  `id_predikat` int(11) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `huruf_mutu` char(1) NOT NULL,
  `batas_bawah` int(11) NOT NULL,
  `batas_atas` int(11) NOT NULL,
  `keterangan` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_predikat_nilai`
--

INSERT INTO `tb_predikat_nilai` (`id_predikat`, `id_kategori`, `huruf_mutu`, `batas_bawah`, `batas_atas`, `keterangan`) VALUES
(1, 1, 'A', 0, 49, 'Sangat Baik'),
(2, 1, 'B', 50, 249, 'Baik'),
(3, 1, 'C', 250, 1499, 'Cukup (SP1/SP2)'),
(4, 1, 'D', 1500, 9999, 'Kurang (SP3/Berat)'),
(5, 2, 'A', 0, 24, 'Sangat Baik'),
(6, 2, 'B', 25, 74, 'Baik'),
(7, 2, 'C', 75, 449, 'Cukup (SP1/SP2)'),
(8, 2, 'D', 450, 9999, 'Kurang (SP3/Berat)'),
(9, 3, 'A', 0, 49, 'Sangat Baik'),
(10, 3, 'B', 50, 99, 'Baik'),
(11, 3, 'C', 100, 449, 'Cukup (SP1/SP2)'),
(12, 3, 'D', 450, 9999, 'Kurang (SP3/Berat)');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_riwayat_sp`
--

CREATE TABLE `tb_riwayat_sp` (
  `id_sp` int(11) NOT NULL,
  `id_anggota` bigint(20) NOT NULL,
  `tingkat_sp` enum('SP1','SP2','SP3','Dikeluarkan') NOT NULL,
  `kategori_pemicu` varchar(50) DEFAULT NULL,
  `tanggal_terbit` date NOT NULL,
  `tanggal_validasi` date DEFAULT NULL,
  `status` enum('Pending','Selesai') DEFAULT 'Pending',
  `id_admin` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_riwayat_sp`
--

INSERT INTO `tb_riwayat_sp` (`id_sp`, `id_anggota`, `tingkat_sp`, `kategori_pemicu`, `tanggal_terbit`, `tanggal_validasi`, `status`, `id_admin`) VALUES
(1, 3, 'SP1', 'KERAJINAN', '2026-03-09', NULL, 'Pending', NULL),
(2, 3, 'SP1', 'KERAPIAN', '2026-03-09', NULL, 'Pending', NULL),
(3, 9, 'SP1', 'KELAKUAN', '2026-03-09', NULL, 'Pending', NULL),
(5, 3, 'SP2', 'KERAPIAN', '2026-03-09', NULL, 'Pending', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_sanksi_ref`
--

CREATE TABLE `tb_sanksi_ref` (
  `id_sanksi_ref` int(11) NOT NULL,
  `kode_sanksi` varchar(5) NOT NULL,
  `deskripsi` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_sanksi_ref`
--

INSERT INTO `tb_sanksi_ref` (`id_sanksi_ref`, `kode_sanksi`, `deskripsi`) VALUES
(1, '1', 'Meminta maaf dan berjanji tidak mengulang'),
(2, '2', 'Dikeluarkan saat PBM (Proses Belajar Mengajar)'),
(3, '3', 'Mengganti/memperbaiki fasilitas sekolah yang rusak'),
(4, '4', 'Mengganti/mengembalikan uang atau barang yang dipinjam/diambil'),
(5, '5', 'Menjalani pembinaan oleh Wali Kelas'),
(6, '6', 'Membersihkan lingkungan sekolah'),
(7, '7', 'Pemanggilan orang tua/wali siswa'),
(8, '8', 'Menjalani pembinaan oleh BK'),
(9, '9', 'Menjalani pembinaan khusus oleh Tim Tatib'),
(10, '10', 'Diserahkan kembali pendidikannya kepada orang tua (Dikeluarkan)');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_siswa`
--

CREATE TABLE `tb_siswa` (
  `no_induk` varchar(50) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `nama_ayah` varchar(150) DEFAULT NULL,
  `pekerjaan_ayah` varchar(100) DEFAULT NULL,
  `nama_ibu` varchar(150) DEFAULT NULL,
  `pekerjaan_ibu` varchar(100) DEFAULT NULL,
  `no_hp_ortu` varchar(15) DEFAULT NULL,
  `status_aktif` enum('Aktif','Lulus','Keluar','Dikeluarkan') DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_siswa`
--

INSERT INTO `tb_siswa` (`no_induk`, `nama_siswa`, `jenis_kelamin`, `kota`, `tanggal_lahir`, `alamat`, `nama_ayah`, `pekerjaan_ayah`, `nama_ibu`, `pekerjaan_ibu`, `no_hp_ortu`, `status_aktif`) VALUES
('11223', 'Adeline Alexandra', 'P', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11225', 'Aeron Christian Nurdianto', 'L', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11237', 'Archangelo Estevo Handojo SA', 'L', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11248', 'Cinta Felicia Jalmav', 'P', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11256', 'Dyvans Edgar Zaydan Satria', 'L', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11261', 'Eugenia Phoebe Renata', 'P', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11272', 'Gabriel Neville Hidayat', 'L', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11273', 'Gabriel Saberlicht Edsel P.', 'L', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11275', 'Ganendra Immanuello Christanto', 'L', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11276', 'Gavrilla Candra Kirana', 'P', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11278', 'Greta Chandira Levyka', 'P', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11284', 'Jennifer Olivia Feranda', 'P', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11285', 'Jessica Meliana', 'P', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11287', 'Jocelyn Kaianna', 'P', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11288', 'Josephine Athalia Mittakalyani', 'P', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11294', 'Kenzo Christiano Tansrijata P.', 'L', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11296', 'Kimberly Prayugo', 'P', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11305', 'Madeline Graciella Wijaya', 'P', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11307', 'Marcello Benlie Hartono', 'L', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11312', 'Michael Caesario Christian P.', 'L', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11313', 'Michael Raindra Bayu Bimantoro', 'L', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11317', 'Nathanael Valentino', 'L', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11319', 'Nicolaus Alden Gavriel Luditra', 'L', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11320', 'Nikita Kumala Devi', 'P', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11323', 'Owen Willem Utama', 'L', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11348', 'Vanessa Angelin Darmawan', 'P', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11356', 'Yoseph Bimo Wibisono', 'L', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif'),
('11360', 'Felix Giorgi', 'L', '-', '0000-00-00', '-', '-', '-', '-', '-', '-', 'Aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_tahun_ajaran`
--

CREATE TABLE `tb_tahun_ajaran` (
  `id_tahun` int(11) NOT NULL,
  `nama_tahun` varchar(20) NOT NULL,
  `status` enum('Aktif','Arsip') DEFAULT 'Aktif',
  `semester_aktif` enum('Ganjil','Genap') DEFAULT 'Ganjil'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_tahun_ajaran`
--

INSERT INTO `tb_tahun_ajaran` (`id_tahun`, `nama_tahun`, `status`, `semester_aktif`) VALUES
(1, '2025/2026', 'Aktif', 'Genap');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `tb_admin`
--
ALTER TABLE `tb_admin`
  ADD PRIMARY KEY (`id_admin`);

--
-- Indeks untuk tabel `tb_anggota_kelas`
--
ALTER TABLE `tb_anggota_kelas`
  ADD PRIMARY KEY (`id_anggota`),
  ADD KEY `no_induk` (`no_induk`),
  ADD KEY `id_kelas` (`id_kelas`),
  ADD KEY `id_tahun` (`id_tahun`);

--
-- Indeks untuk tabel `tb_aturan_sp`
--
ALTER TABLE `tb_aturan_sp`
  ADD PRIMARY KEY (`id_aturan_sp`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indeks untuk tabel `tb_guru`
--
ALTER TABLE `tb_guru`
  ADD PRIMARY KEY (`id_guru`),
  ADD KEY `id_kelas` (`id_kelas`);

--
-- Indeks untuk tabel `tb_jenis_pelanggaran`
--
ALTER TABLE `tb_jenis_pelanggaran`
  ADD PRIMARY KEY (`id_jenis`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indeks untuk tabel `tb_kategori_pelanggaran`
--
ALTER TABLE `tb_kategori_pelanggaran`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indeks untuk tabel `tb_kelas`
--
ALTER TABLE `tb_kelas`
  ADD PRIMARY KEY (`id_kelas`);

--
-- Indeks untuk tabel `tb_pelanggaran_detail`
--
ALTER TABLE `tb_pelanggaran_detail`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_transaksi` (`id_transaksi`),
  ADD KEY `id_jenis` (`id_jenis`);

--
-- Indeks untuk tabel `tb_pelanggaran_header`
--
ALTER TABLE `tb_pelanggaran_header`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_anggota` (`id_anggota`),
  ADD KEY `id_guru` (`id_guru`),
  ADD KEY `id_tahun` (`id_tahun`);

--
-- Indeks untuk tabel `tb_pelanggaran_sanksi`
--
ALTER TABLE `tb_pelanggaran_sanksi`
  ADD PRIMARY KEY (`id_trans_sanksi`),
  ADD KEY `id_transaksi` (`id_transaksi`),
  ADD KEY `id_sanksi_ref` (`id_sanksi_ref`);

--
-- Indeks untuk tabel `tb_predikat_nilai`
--
ALTER TABLE `tb_predikat_nilai`
  ADD PRIMARY KEY (`id_predikat`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indeks untuk tabel `tb_riwayat_sp`
--
ALTER TABLE `tb_riwayat_sp`
  ADD PRIMARY KEY (`id_sp`),
  ADD KEY `id_anggota` (`id_anggota`),
  ADD KEY `id_admin` (`id_admin`);

--
-- Indeks untuk tabel `tb_sanksi_ref`
--
ALTER TABLE `tb_sanksi_ref`
  ADD PRIMARY KEY (`id_sanksi_ref`);

--
-- Indeks untuk tabel `tb_siswa`
--
ALTER TABLE `tb_siswa`
  ADD PRIMARY KEY (`no_induk`);

--
-- Indeks untuk tabel `tb_tahun_ajaran`
--
ALTER TABLE `tb_tahun_ajaran`
  ADD PRIMARY KEY (`id_tahun`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `tb_admin`
--
ALTER TABLE `tb_admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `tb_anggota_kelas`
--
ALTER TABLE `tb_anggota_kelas`
  MODIFY `id_anggota` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT untuk tabel `tb_aturan_sp`
--
ALTER TABLE `tb_aturan_sp`
  MODIFY `id_aturan_sp` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `tb_guru`
--
ALTER TABLE `tb_guru`
  MODIFY `id_guru` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT untuk tabel `tb_jenis_pelanggaran`
--
ALTER TABLE `tb_jenis_pelanggaran`
  MODIFY `id_jenis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT untuk tabel `tb_kategori_pelanggaran`
--
ALTER TABLE `tb_kategori_pelanggaran`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `tb_kelas`
--
ALTER TABLE `tb_kelas`
  MODIFY `id_kelas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `tb_pelanggaran_detail`
--
ALTER TABLE `tb_pelanggaran_detail`
  MODIFY `id_detail` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `tb_pelanggaran_header`
--
ALTER TABLE `tb_pelanggaran_header`
  MODIFY `id_transaksi` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `tb_pelanggaran_sanksi`
--
ALTER TABLE `tb_pelanggaran_sanksi`
  MODIFY `id_trans_sanksi` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `tb_predikat_nilai`
--
ALTER TABLE `tb_predikat_nilai`
  MODIFY `id_predikat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `tb_riwayat_sp`
--
ALTER TABLE `tb_riwayat_sp`
  MODIFY `id_sp` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `tb_sanksi_ref`
--
ALTER TABLE `tb_sanksi_ref`
  MODIFY `id_sanksi_ref` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `tb_tahun_ajaran`
--
ALTER TABLE `tb_tahun_ajaran`
  MODIFY `id_tahun` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `tb_anggota_kelas`
--
ALTER TABLE `tb_anggota_kelas`
  ADD CONSTRAINT `tb_anggota_kelas_ibfk_1` FOREIGN KEY (`no_induk`) REFERENCES `tb_siswa` (`no_induk`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_anggota_kelas_ibfk_2` FOREIGN KEY (`id_kelas`) REFERENCES `tb_kelas` (`id_kelas`),
  ADD CONSTRAINT `tb_anggota_kelas_ibfk_3` FOREIGN KEY (`id_tahun`) REFERENCES `tb_tahun_ajaran` (`id_tahun`);

--
-- Ketidakleluasaan untuk tabel `tb_aturan_sp`
--
ALTER TABLE `tb_aturan_sp`
  ADD CONSTRAINT `tb_aturan_sp_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `tb_kategori_pelanggaran` (`id_kategori`);

--
-- Ketidakleluasaan untuk tabel `tb_guru`
--
ALTER TABLE `tb_guru`
  ADD CONSTRAINT `tb_guru_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `tb_kelas` (`id_kelas`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `tb_jenis_pelanggaran`
--
ALTER TABLE `tb_jenis_pelanggaran`
  ADD CONSTRAINT `tb_jenis_pelanggaran_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `tb_kategori_pelanggaran` (`id_kategori`);

--
-- Ketidakleluasaan untuk tabel `tb_pelanggaran_detail`
--
ALTER TABLE `tb_pelanggaran_detail`
  ADD CONSTRAINT `tb_pelanggaran_detail_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `tb_pelanggaran_header` (`id_transaksi`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_pelanggaran_detail_ibfk_2` FOREIGN KEY (`id_jenis`) REFERENCES `tb_jenis_pelanggaran` (`id_jenis`);

--
-- Ketidakleluasaan untuk tabel `tb_pelanggaran_header`
--
ALTER TABLE `tb_pelanggaran_header`
  ADD CONSTRAINT `tb_pelanggaran_header_ibfk_1` FOREIGN KEY (`id_anggota`) REFERENCES `tb_anggota_kelas` (`id_anggota`),
  ADD CONSTRAINT `tb_pelanggaran_header_ibfk_2` FOREIGN KEY (`id_guru`) REFERENCES `tb_guru` (`id_guru`),
  ADD CONSTRAINT `tb_pelanggaran_header_ibfk_3` FOREIGN KEY (`id_tahun`) REFERENCES `tb_tahun_ajaran` (`id_tahun`);

--
-- Ketidakleluasaan untuk tabel `tb_pelanggaran_sanksi`
--
ALTER TABLE `tb_pelanggaran_sanksi`
  ADD CONSTRAINT `tb_pelanggaran_sanksi_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `tb_pelanggaran_header` (`id_transaksi`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_pelanggaran_sanksi_ibfk_2` FOREIGN KEY (`id_sanksi_ref`) REFERENCES `tb_sanksi_ref` (`id_sanksi_ref`);

--
-- Ketidakleluasaan untuk tabel `tb_predikat_nilai`
--
ALTER TABLE `tb_predikat_nilai`
  ADD CONSTRAINT `tb_predikat_nilai_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `tb_kategori_pelanggaran` (`id_kategori`);

--
-- Ketidakleluasaan untuk tabel `tb_riwayat_sp`
--
ALTER TABLE `tb_riwayat_sp`
  ADD CONSTRAINT `tb_riwayat_sp_ibfk_1` FOREIGN KEY (`id_anggota`) REFERENCES `tb_anggota_kelas` (`id_anggota`),
  ADD CONSTRAINT `tb_riwayat_sp_ibfk_2` FOREIGN KEY (`id_admin`) REFERENCES `tb_admin` (`id_admin`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
