-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 20, 2026 at 02:50 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_sepak_bola`
--
CREATE DATABASE IF NOT EXISTS `db_sepak_bola` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_sepak_bola`;

-- --------------------------------------------------------

--
-- Table structure for table `evaluasi_pemain`
--

CREATE TABLE `evaluasi_pemain` (
  `id_evaluasi` int(11) NOT NULL,
  `id_pemain` int(11) NOT NULL,
  `id_pelatih` int(11) NOT NULL,
  `tanggal_evaluasi` date NOT NULL,
  `penilaian_teknik` tinyint(3) UNSIGNED NOT NULL,
  `penilaian_fisik` tinyint(3) UNSIGNED NOT NULL,
  `penilaian_kerja_sama` tinyint(3) UNSIGNED NOT NULL,
  `catatan_pelatih` text DEFAULT NULL
) ;

--
-- Dumping data for table `evaluasi_pemain`
--

INSERT INTO `evaluasi_pemain` (`id_evaluasi`, `id_pemain`, `id_pelatih`, `tanggal_evaluasi`, `penilaian_teknik`, `penilaian_fisik`, `penilaian_kerja_sama`, `catatan_pelatih`) VALUES
(1, 5, 1, '2026-09-16', 9, 8, 8, 'Penyelesaian akhir sangat tajam; perlu lebih aktif turun membantu build-up.'),
(2, 4, 1, '2026-09-16', 8, 7, 9, 'Visi bermain bagus, umpan terobosan konsisten.'),
(3, 5, 1, '2026-10-21', 9, 8, 9, 'Menjaga performa; pergerakan tanpa bola membaik.'),
(4, 10, 2, '2026-10-07', 9, 8, 7, 'Efektif di kotak penalti, kerja sama dengan gelandang bisa ditingkatkan.'),
(5, 9, 2, '2026-10-07', 8, 8, 9, 'Pengatur serangan yang andal.'),
(6, 15, 3, '2026-09-23', 8, 7, 7, 'Perlu meningkatkan tekanan saat lawan menguasai bola.'),
(7, 19, 4, '2026-09-23', 7, 8, 8, 'Stamina baik, perlu mengurangi pelanggaran.'),
(8, 1, 1, '2026-10-21', 8, 8, 7, 'Refleks kuat; komunikasi dengan lini belakang perlu dipertajam.');

-- --------------------------------------------------------

--
-- Table structure for table `klub`
--

CREATE TABLE `klub` (
  `id_klub` int(11) NOT NULL,
  `nama_klub` varchar(100) NOT NULL,
  `negara` varchar(60) NOT NULL,
  `kota` varchar(60) NOT NULL,
  `stadion` varchar(100) DEFAULT NULL,
  `tahun_berdiri` smallint(5) UNSIGNED DEFAULT NULL,
  `logo_klub` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `klub`
--

INSERT INTO `klub` (`id_klub`, `nama_klub`, `negara`, `kota`, `stadion`, `tahun_berdiri`, `logo_klub`) VALUES
(1, 'FC Barcelona', 'Spanyol', 'Barcelona', 'Camp Nou', 1899, NULL),
(2, 'Real Madrid', 'Spanyol', 'Madrid', 'Santiago Bernabéu', 1902, NULL),
(3, 'Atlético Madrid', 'Spanyol', 'Madrid', 'Riyadh Air Metropolitano', 1903, NULL),
(4, 'Sevilla FC', 'Spanyol', 'Sevilla', 'Ramón Sánchez-Pizjuán', 1890, NULL),
(5, 'Garuda Fc', 'Indonesia', 'Jakarta', 'jakarta kota', 1990, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pelatih`
--

CREATE TABLE `pelatih` (
  `id_pelatih` int(11) NOT NULL,
  `id_klub` int(11) NOT NULL,
  `nama_pelatih` varchar(100) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `kewarganegaraan` varchar(60) NOT NULL,
  `pengalaman` varchar(255) DEFAULT NULL,
  `kontak` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pelatih`
--

INSERT INTO `pelatih` (`id_pelatih`, `id_klub`, `nama_pelatih`, `tanggal_lahir`, `kewarganegaraan`, `pengalaman`, `kontak`) VALUES
(1, 1, 'Hernán Quiroga', '1975-06-10', 'Spanyol', '18 tahun melatih; juara liga domestik 3 kali', '+34 600 111 001'),
(2, 2, 'Stefan Weiss', '1972-03-22', 'Jerman', '20 tahun melatih; finalis kompetisi Eropa', '+34 600 111 002'),
(3, 3, 'Mauricio Bellini', '1970-11-05', 'Argentina', '22 tahun melatih; spesialis strategi bertahan', '+34 600 111 003'),
(4, 4, 'Javier Ortuño', '1978-08-14', 'Spanyol', '14 tahun melatih; pembinaan pemain muda', '+34 600 111 004');

-- --------------------------------------------------------

--
-- Table structure for table `pemain`
--

CREATE TABLE `pemain` (
  `id_pemain` int(11) NOT NULL,
  `id_klub` int(11) NOT NULL,
  `id_posisi` int(11) NOT NULL,
  `nama_pemain` varchar(100) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `kewarganegaraan` varchar(60) NOT NULL,
  `nomor_punggung` tinyint(3) UNSIGNED NOT NULL,
  `tinggi_badan` smallint(5) UNSIGNED DEFAULT NULL,
  `berat_badan` smallint(5) UNSIGNED DEFAULT NULL,
  `foto_pemain` varchar(255) DEFAULT NULL,
  `status_pemain` enum('aktif','cedera','dipinjamkan','nonaktif') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pemain`
--

INSERT INTO `pemain` (`id_pemain`, `id_klub`, `id_posisi`, `nama_pemain`, `tanggal_lahir`, `kewarganegaraan`, `nomor_punggung`, `tinggi_badan`, `berat_badan`, `foto_pemain`, `status_pemain`) VALUES
(1, 1, 1, 'Rafael Mendoza', '1996-03-12', 'Spanyol', 1, 191, 84, NULL, 'aktif'),
(2, 1, 2, 'Diego Alvarez', '1998-07-21', 'Spanyol', 4, 185, 79, NULL, 'aktif'),
(3, 1, 2, 'Marc Ferrer', '2000-01-30', 'Spanyol', 5, 182, 76, NULL, 'aktif'),
(4, 1, 3, 'Luca Romero', '1999-09-04', 'Argentina', 8, 176, 70, NULL, 'aktif'),
(5, 1, 4, 'Pemain A', '2001-05-17', 'Brasil', 9, 181, 75, NULL, 'aktif'),
(6, 2, 1, 'Tomás Herrera', '1994-11-02', 'Spanyol', 1, 193, 88, NULL, 'aktif'),
(7, 2, 2, 'Álvaro Pineda', '1997-02-14', 'Spanyol', 2, 184, 78, NULL, 'aktif'),
(8, 2, 2, 'Nico Salcedo', '1999-06-25', 'Uruguay', 3, 187, 81, NULL, 'cedera'),
(9, 2, 3, 'Jude Carvalho', '2002-08-08', 'Portugal', 10, 178, 71, NULL, 'aktif'),
(10, 2, 4, 'Karim Delacroix', '1998-12-19', 'Prancis', 9, 183, 77, NULL, 'aktif'),
(11, 3, 1, 'Iker Villanueva', '1995-04-09', 'Spanyol', 13, 189, 85, NULL, 'aktif'),
(12, 3, 2, 'Mateo Gutiérrez', '1996-10-01', 'Spanyol', 2, 186, 80, NULL, 'aktif'),
(13, 3, 2, 'Joaquín Duarte', '1997-03-23', 'Uruguay', 15, 188, 82, NULL, 'aktif'),
(14, 3, 3, 'Rodrigo Paz', '2000-07-11', 'Argentina', 14, 175, 69, NULL, 'aktif'),
(15, 3, 4, 'Antoine Lemaire', '1999-01-05', 'Prancis', 19, 180, 74, NULL, 'aktif'),
(16, 4, 1, 'Bruno Castellano', '1993-05-28', 'Spanyol', 1, 190, 86, NULL, 'aktif'),
(17, 4, 2, 'Sergio Montoya', '1998-09-15', 'Spanyol', 3, 183, 77, NULL, 'aktif'),
(18, 4, 2, 'Ivan Kovač', '1996-02-06', 'Kroasia', 5, 189, 83, NULL, 'aktif'),
(19, 4, 3, 'Pablo Navarro', '2001-11-27', 'Spanyol', 8, 177, 72, NULL, 'aktif'),
(20, 4, 4, 'Emeka Okafor', '2000-04-18', 'Nigeria', 9, 184, 78, NULL, 'dipinjamkan'),
(21, 5, 5, 'ibraham', '1997-01-08', 'indonesia', 17, 178, 67, NULL, 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `pertandingan`
--

CREATE TABLE `pertandingan` (
  `id_pertandingan` int(11) NOT NULL,
  `id_klub_tuan_rumah` int(11) NOT NULL,
  `id_klub_tamu` int(11) NOT NULL,
  `tanggal_pertandingan` datetime NOT NULL,
  `lokasi` varchar(150) NOT NULL,
  `skor_tuan_rumah` tinyint(3) UNSIGNED DEFAULT NULL,
  `skor_tamu` tinyint(3) UNSIGNED DEFAULT NULL,
  `status_pertandingan` enum('dijadwalkan','selesai','ditunda','dibatalkan') NOT NULL DEFAULT 'dijadwalkan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pertandingan`
--

INSERT INTO `pertandingan` (`id_pertandingan`, `id_klub_tuan_rumah`, `id_klub_tamu`, `tanggal_pertandingan`, `lokasi`, `skor_tuan_rumah`, `skor_tamu`, `status_pertandingan`) VALUES
(1, 1, 2, '2026-09-14 20:00:00', 'Camp Nou, Barcelona', 3, 1, 'selesai'),
(2, 3, 4, '2026-09-21 19:00:00', 'Riyadh Air Metropolitano, Madrid', 1, 1, 'selesai'),
(3, 2, 3, '2026-10-05 20:00:00', 'Santiago Bernabéu, Madrid', 2, 0, 'selesai'),
(4, 4, 1, '2026-10-19 21:00:00', 'Ramón Sánchez-Pizjuán, Sevilla', 0, 2, 'selesai'),
(5, 1, 3, '2026-12-06 20:00:00', 'Camp Nou, Barcelona', NULL, NULL, 'dijadwalkan'),
(6, 4, 2, '2026-12-13 19:00:00', 'Ramón Sánchez-Pizjuán, Sevilla', NULL, NULL, 'dijadwalkan');

-- --------------------------------------------------------

--
-- Table structure for table `posisi`
--

CREATE TABLE `posisi` (
  `id_posisi` int(11) NOT NULL,
  `nama_posisi` varchar(50) NOT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posisi`
--

INSERT INTO `posisi` (`id_posisi`, `nama_posisi`, `deskripsi`) VALUES
(1, 'Kiper', 'Penjaga gawang; bertugas menghalau tembakan lawan.'),
(2, 'Bek', 'Pemain belakang; menjaga area pertahanan.'),
(3, 'Gelandang', 'Pemain tengah; penghubung pertahanan dan serangan.'),
(4, 'Penyerang', 'Pemain depan; tugas utama mencetak gol.'),
(5, 'Kapten', 'Pemimpin yg mengarahkan anggota dan memberikan kode dan taktik untuk menyerang dan serangan balik');

-- --------------------------------------------------------

--
-- Table structure for table `statistik_pemain`
--

CREATE TABLE `statistik_pemain` (
  `id_statistik` int(11) NOT NULL,
  `id_pemain` int(11) NOT NULL,
  `id_pertandingan` int(11) NOT NULL,
  `menit_bermain` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `jumlah_gol` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `jumlah_assist` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `kartu_kuning` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `kartu_merah` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `jumlah_shot` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `jumlah_saves` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `statistik_pemain`
--

INSERT INTO `statistik_pemain` (`id_statistik`, `id_pemain`, `id_pertandingan`, `menit_bermain`, `jumlah_gol`, `jumlah_assist`, `kartu_kuning`, `kartu_merah`, `jumlah_shot`, `jumlah_saves`) VALUES
(1, 5, 1, 90, 2, 1, 0, 0, 5, 0),
(2, 4, 1, 90, 1, 1, 0, 0, 3, 0),
(3, 1, 1, 90, 0, 0, 0, 0, 0, 4),
(4, 2, 1, 90, 0, 0, 1, 0, 1, 0),
(5, 10, 1, 90, 1, 0, 0, 0, 4, 0),
(6, 6, 1, 90, 0, 0, 0, 0, 0, 3),
(7, 9, 1, 78, 0, 1, 1, 0, 2, 0),
(8, 15, 2, 90, 1, 0, 0, 0, 3, 0),
(9, 20, 2, 85, 1, 0, 0, 0, 4, 0),
(10, 11, 2, 90, 0, 0, 0, 0, 0, 5),
(11, 16, 2, 90, 0, 0, 0, 0, 0, 3),
(12, 19, 2, 90, 0, 1, 1, 0, 1, 0),
(13, 13, 2, 67, 0, 0, 0, 1, 0, 0),
(14, 10, 3, 90, 2, 0, 0, 0, 6, 0),
(15, 9, 3, 90, 0, 2, 0, 0, 2, 0),
(16, 6, 3, 90, 0, 0, 0, 0, 0, 2),
(17, 11, 3, 90, 0, 0, 0, 0, 0, 6),
(18, 15, 3, 90, 0, 0, 0, 0, 3, 0),
(19, 5, 4, 90, 1, 0, 0, 0, 4, 0),
(20, 4, 4, 90, 1, 1, 0, 0, 3, 0),
(21, 16, 4, 90, 0, 0, 1, 0, 0, 4),
(22, 20, 4, 90, 0, 0, 0, 0, 3, 0),
(23, 1, 4, 90, 0, 0, 0, 0, 0, 2);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','pelatih','pengelola') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama`, `username`, `password_hash`, `role`) VALUES
(1, 'Administrator', 'admin', '$2y$10$1qbBJIbhZKc3Da/LzOuNcOxtKmO8IgeJh8wUJJuTcfM36x9z8fk5.', 'admin'),
(2, 'Pelatih Tim', 'pelatih', '$2y$10$rj05/cR7f.RJtfaDuWL89udJ.p17JeHOFg.yBBQCmyIPPG5Xbj2v2', 'pelatih'),
(3, 'Pengelola Klub', 'pengelola', '$2y$10$8XWO64y3/smXXWB/4.AkseP81sVz.s/uAYbZT/4CFfgZNNlBwE2tm', 'pengelola');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `evaluasi_pemain`
--
ALTER TABLE `evaluasi_pemain`
  ADD PRIMARY KEY (`id_evaluasi`),
  ADD KEY `fk_eval_pemain` (`id_pemain`),
  ADD KEY `fk_eval_pelatih` (`id_pelatih`);

--
-- Indexes for table `klub`
--
ALTER TABLE `klub`
  ADD PRIMARY KEY (`id_klub`),
  ADD UNIQUE KEY `nama_klub` (`nama_klub`);

--
-- Indexes for table `pelatih`
--
ALTER TABLE `pelatih`
  ADD PRIMARY KEY (`id_pelatih`),
  ADD KEY `fk_pelatih_klub` (`id_klub`);

--
-- Indexes for table `pemain`
--
ALTER TABLE `pemain`
  ADD PRIMARY KEY (`id_pemain`),
  ADD UNIQUE KEY `uq_nomor_per_klub` (`id_klub`,`nomor_punggung`),
  ADD KEY `fk_pemain_posisi` (`id_posisi`);

--
-- Indexes for table `pertandingan`
--
ALTER TABLE `pertandingan`
  ADD PRIMARY KEY (`id_pertandingan`),
  ADD KEY `fk_match_tuan` (`id_klub_tuan_rumah`),
  ADD KEY `fk_match_tamu` (`id_klub_tamu`);

--
-- Indexes for table `posisi`
--
ALTER TABLE `posisi`
  ADD PRIMARY KEY (`id_posisi`),
  ADD UNIQUE KEY `nama_posisi` (`nama_posisi`);

--
-- Indexes for table `statistik_pemain`
--
ALTER TABLE `statistik_pemain`
  ADD PRIMARY KEY (`id_statistik`),
  ADD UNIQUE KEY `uq_pemain_match` (`id_pemain`,`id_pertandingan`),
  ADD KEY `fk_stat_match` (`id_pertandingan`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `evaluasi_pemain`
--
ALTER TABLE `evaluasi_pemain`
  MODIFY `id_evaluasi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `klub`
--
ALTER TABLE `klub`
  MODIFY `id_klub` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pelatih`
--
ALTER TABLE `pelatih`
  MODIFY `id_pelatih` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pemain`
--
ALTER TABLE `pemain`
  MODIFY `id_pemain` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `pertandingan`
--
ALTER TABLE `pertandingan`
  MODIFY `id_pertandingan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `posisi`
--
ALTER TABLE `posisi`
  MODIFY `id_posisi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `statistik_pemain`
--
ALTER TABLE `statistik_pemain`
  MODIFY `id_statistik` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `evaluasi_pemain`
--
ALTER TABLE `evaluasi_pemain`
  ADD CONSTRAINT `fk_eval_pelatih` FOREIGN KEY (`id_pelatih`) REFERENCES `pelatih` (`id_pelatih`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eval_pemain` FOREIGN KEY (`id_pemain`) REFERENCES `pemain` (`id_pemain`) ON UPDATE CASCADE;

--
-- Constraints for table `pelatih`
--
ALTER TABLE `pelatih`
  ADD CONSTRAINT `fk_pelatih_klub` FOREIGN KEY (`id_klub`) REFERENCES `klub` (`id_klub`) ON UPDATE CASCADE;

--
-- Constraints for table `pemain`
--
ALTER TABLE `pemain`
  ADD CONSTRAINT `fk_pemain_klub` FOREIGN KEY (`id_klub`) REFERENCES `klub` (`id_klub`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pemain_posisi` FOREIGN KEY (`id_posisi`) REFERENCES `posisi` (`id_posisi`) ON UPDATE CASCADE;

--
-- Constraints for table `pertandingan`
--
ALTER TABLE `pertandingan`
  ADD CONSTRAINT `fk_match_tamu` FOREIGN KEY (`id_klub_tamu`) REFERENCES `klub` (`id_klub`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_match_tuan` FOREIGN KEY (`id_klub_tuan_rumah`) REFERENCES `klub` (`id_klub`) ON UPDATE CASCADE;

--
-- Constraints for table `statistik_pemain`
--
ALTER TABLE `statistik_pemain`
  ADD CONSTRAINT `fk_stat_match` FOREIGN KEY (`id_pertandingan`) REFERENCES `pertandingan` (`id_pertandingan`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stat_pemain` FOREIGN KEY (`id_pemain`) REFERENCES `pemain` (`id_pemain`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
