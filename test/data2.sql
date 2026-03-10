-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 10 Mar 2026 pada 12.52
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
-- Struktur dari tabel `tb_riwayat_sp`
--

CREATE TABLE `tb_riwayat_sp` (
  `id_sp` int(11) NOT NULL,
  `id_anggota` bigint(20) NOT NULL,
  `tingkat_sp` enum('SP1','SP2','SP3','Sanksi oleh Sekolah') NOT NULL,
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
(3, 3, 'Sanksi oleh Sekolah', 'KELAKUAN', '2026-03-10', NULL, 'Pending', NULL),
(4, 29, 'SP1', 'KELAKUAN', '2026-03-10', NULL, 'Pending', NULL),
(5, 29, 'SP1', 'KERAJINAN', '2026-03-10', NULL, 'Pending', NULL),
(6, 29, 'SP1', 'KERAPIAN', '2026-03-10', NULL, 'Pending', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `tb_riwayat_sp`
--
ALTER TABLE `tb_riwayat_sp`
  ADD PRIMARY KEY (`id_sp`),
  ADD KEY `id_anggota` (`id_anggota`),
  ADD KEY `id_admin` (`id_admin`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `tb_riwayat_sp`
--
ALTER TABLE `tb_riwayat_sp`
  MODIFY `id_sp` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

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
