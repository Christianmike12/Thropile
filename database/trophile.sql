-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 30, 2026 at 05:52 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `trophile`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_tu`
--

CREATE TABLE `admin_tu` (
  `nip` varchar(20) NOT NULL,
  `nama_admin` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('Aktif','Non-Aktif') DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_tu`
--

INSERT INTO `admin_tu` (`nip`, `nama_admin`, `username`, `password`, `status`) VALUES
('111', 'Admin Pusat', 'admin', '$2y$10$phwEVo5VKn8l3mV1G/pEl.JmLqoxyf/C2RzsIvHiwCU/OI.3fvlQq', 'Aktif');

-- --------------------------------------------------------

--
-- Table structure for table `guru`
--

CREATE TABLE `guru` (
  `nip` varchar(20) NOT NULL,
  `nama_guru` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('Aktif','Pensiun','Pindah Tugas') DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guru`
--

INSERT INTO `guru` (`nip`, `nama_guru`, `password`, `status`) VALUES
('guru1', 'Budi Santoso', '$2y$10$y0V/OwhSNQ14LAnlbbGN0eCBlDcc/NSftjoPuXqv5umm2ud6KLv3y', 'Aktif');

-- --------------------------------------------------------

--
-- Table structure for table `kepala_sekolah`
--

CREATE TABLE `kepala_sekolah` (
  `nip` varchar(20) NOT NULL,
  `nama_kepala_sekolah` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('Aktif','Non-Aktif') DEFAULT 'Non-Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kepala_sekolah`
--

INSERT INTO `kepala_sekolah` (`nip`, `nama_kepala_sekolah`, `username`, `password`, `status`) VALUES
('197001012000031001', 'Drs. Supriyanto, M.Pd', 'kepala sekolah', '$2y$10$bIRY7WrJhW9B/JVMPMec3eSn0zMYau2k1AkxT4MGkO7F4Rioww9QS', 'Aktif');

-- --------------------------------------------------------

--
-- Table structure for table `master_kelas`
--

CREATE TABLE `master_kelas` (
  `nama_kelas` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_kelas`
--

INSERT INTO `master_kelas` (`nama_kelas`) VALUES
('X IPA 1'),
('X IPA 2'),
('X IPS 2');

-- --------------------------------------------------------

--
-- Table structure for table `prestasi`
--

CREATE TABLE `prestasi` (
  `id_prestasi` int(11) NOT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `nip_guru` varchar(20) DEFAULT NULL,
  `nip_admin` varchar(20) DEFAULT NULL,
  `nama_lomba` varchar(150) NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `tingkat` varchar(50) DEFAULT NULL,
  `peringkat` varchar(50) NOT NULL,
  `tahun` int(4) DEFAULT NULL,
  `tanggal_pelaksanaan` date NOT NULL,
  `file_sertifikat` varchar(255) NOT NULL,
  `file_trofi` varchar(255) DEFAULT NULL,
  `status_data` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `alasan_tolak` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prestasi`
--

INSERT INTO `prestasi` (`id_prestasi`, `nisn`, `nip_guru`, `nip_admin`, `nama_lomba`, `kategori`, `tingkat`, `peringkat`, `tahun`, `tanggal_pelaksanaan`, `file_sertifikat`, `file_trofi`, `status_data`, `alasan_tolak`, `status`) VALUES
(5, '1001', 'guru1', NULL, 'Olimpiade Sains Nasional bidang Informatika', 'Akademik', 'Nasional', 'Juara 1', 2026, '2026-05-21', '1779327726_1001_OlimpiadeSainsNasionalbidangInformatika.png', NULL, 'Rejected', NULL, 'Pending'),
(7, '1004', 'guru1', NULL, 'Lomba Debat', 'Akademik', 'Kota/Kabupaten', 'Harapan 1', 2026, '2026-06-18', '1781736668_1004_LombaDebat.jpg', NULL, 'Approved', NULL, 'Pending'),
(8, '1001', 'guru1', NULL, 'Olimpiade Sains Nasional bidang Informatika', 'Akademik', 'Kota/Kabupaten', 'Juara 1', 2026, '2026-06-04', '1782705834_sertif_1001.png', '', 'Approved', NULL, 'Pending'),
(10, '1002', 'guru1', NULL, 'OSN Matematika', 'Akademik', 'Internasional', 'Juara 3', 2026, '2026-06-01', '1782705906_sertif_1002.png', '1782705906_foto_1002.png', 'Pending', NULL, 'Pending'),
(11, '1002', 'guru1', NULL, 'Olimpiade Sains Nasional bidang Informatika', 'Akademik', 'Kota/Kabupaten', 'Juara 3', 2026, '2026-06-10', '1782707580_sertif_1002.png', '', 'Pending', NULL, 'Pending'),
(13, '1001', 'guru1', NULL, 'Lomba Debat', 'Akademik', 'Kota/Kabupaten', 'Juara 1', 2026, '2026-06-30', '1782790948_sertif_1001.pdf', '', 'Approved', NULL, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `request_reset`
--

CREATE TABLE `request_reset` (
  `id_request` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `kode_unik` varchar(10) DEFAULT NULL,
  `status_req` enum('Pending','Approved','Selesai') DEFAULT 'Pending',
  `waktu_req` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `nisn` varchar(20) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('Aktif','Lulus','Pindah','Keluar','Non-Aktif') DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`nisn`, `nama_siswa`, `kelas`, `password`, `status`) VALUES
('1001', 'Budi Santoso', 'X IPA 1', '$2y$10$LfSs7iRdvKRUEEAebUi7jOCpBtrQwtFDfGYZYPYbCnHNbwM2ARA/K', 'Aktif'),
('1002', 'Siti Aminah', 'X IPA 1', '123', 'Aktif'),
('1003', 'Rudi Hermawan', 'X IPS 2', '123', 'Aktif'),
('1004', 'Lina Marlina', 'X IPS 2', '123', 'Aktif');

-- --------------------------------------------------------

--
-- Table structure for table `super_admin`
--

CREATE TABLE `super_admin` (
  `id_super_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `super_admin`
--

INSERT INTO `super_admin` (`id_super_admin`, `username`, `password`) VALUES
(1, 'superadmin', '$2y$10$Ew4T7/3fa7HtjVUJsZVgTOOpKtDiMJX1P1ZqdjdHJBJi0fjf/vEhe');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_tu`
--
ALTER TABLE `admin_tu`
  ADD PRIMARY KEY (`nip`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`nip`);

--
-- Indexes for table `kepala_sekolah`
--
ALTER TABLE `kepala_sekolah`
  ADD PRIMARY KEY (`nip`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `master_kelas`
--
ALTER TABLE `master_kelas`
  ADD PRIMARY KEY (`nama_kelas`);

--
-- Indexes for table `prestasi`
--
ALTER TABLE `prestasi`
  ADD PRIMARY KEY (`id_prestasi`),
  ADD KEY `nisn` (`nisn`),
  ADD KEY `nip_guru` (`nip_guru`),
  ADD KEY `nip_admin` (`nip_admin`);

--
-- Indexes for table `request_reset`
--
ALTER TABLE `request_reset`
  ADD PRIMARY KEY (`id_request`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`nisn`);

--
-- Indexes for table `super_admin`
--
ALTER TABLE `super_admin`
  ADD PRIMARY KEY (`id_super_admin`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `prestasi`
--
ALTER TABLE `prestasi`
  MODIFY `id_prestasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `request_reset`
--
ALTER TABLE `request_reset`
  MODIFY `id_request` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `super_admin`
--
ALTER TABLE `super_admin`
  MODIFY `id_super_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `prestasi`
--
ALTER TABLE `prestasi`
  ADD CONSTRAINT `prestasi_ibfk_1` FOREIGN KEY (`nisn`) REFERENCES `siswa` (`nisn`),
  ADD CONSTRAINT `prestasi_ibfk_2` FOREIGN KEY (`nip_guru`) REFERENCES `guru` (`nip`),
  ADD CONSTRAINT `prestasi_ibfk_3` FOREIGN KEY (`nip_admin`) REFERENCES `admin_tu` (`nip`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
