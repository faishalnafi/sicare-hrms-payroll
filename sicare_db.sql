-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 22 Bulan Mei 2026 pada 04.24
-- Versi server: 10.11.6-MariaDB-log
-- Versi PHP: 8.3.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sicare_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `employee_attendance`
--

CREATE TABLE `employee_attendance` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `attendance_date` date NOT NULL,
  `clock_in` time DEFAULT NULL,
  `clock_out` time DEFAULT NULL,
  `status` varchar(30) DEFAULT 'alpa',
  `clock_in_latitude` decimal(10,7) DEFAULT NULL,
  `clock_in_longitude` decimal(10,7) DEFAULT NULL,
  `clock_out_latitude` decimal(10,7) DEFAULT NULL,
  `clock_out_longitude` decimal(10,7) DEFAULT NULL,
  `location_method` varchar(20) DEFAULT NULL COMMENT 'GPS or WIFI',
  `work_mode` varchar(10) DEFAULT 'WFO',
  `work_mode_out` varchar(10) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `correction_reason` text DEFAULT NULL,
  `corrected_by` char(36) DEFAULT NULL,
  `corrected_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- --------------------------------------------------------

--
-- Struktur dari tabel `employee_data_correction_requests`
--

CREATE TABLE `employee_data_correction_requests` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `category` varchar(50) NOT NULL,
  `field` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text NOT NULL,
  `reason` text NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- --------------------------------------------------------

--
-- Struktur dari tabel `employee_leave_requests`
--

CREATE TABLE `employee_leave_requests` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `duration` int(11) NOT NULL,
  `reason` text NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- --------------------------------------------------------

--
-- Struktur dari tabel `employee_reimbursement_claims`
--

CREATE TABLE `employee_reimbursement_claims` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `category` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text NOT NULL,
  `receipt_path` varchar(255) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- --------------------------------------------------------

--
-- Struktur dari tabel `global_settings`
--

CREATE TABLE `global_settings` (
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `group` varchar(50) DEFAULT 'general',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `global_settings`
--

INSERT INTO `global_settings` (`key`, `value`, `label`, `group`, `updated_at`) VALUES
('grace_period_min', '60', 'Toleransi Keterlambatan (menit)', 'attendance', '2026-05-22 03:16:33'),
('office_lat', '-7.456187902575536', 'Latitude Kantor Pusat', 'attendance', '2026-05-22 03:16:33'),
('office_lng', '112.44652632965536', 'Longitude Kantor Pusat', 'attendance', '2026-05-22 03:16:33'),
('office_radius_m', '50', 'Radius WFO (meter)', 'attendance', '2026-05-22 03:16:33'),
('office_wifi_prefix', '172.16.1.0', 'Prefix IP WIFI Kantor', 'attendance', '2026-05-22 03:16:33'),
('wfa_allowed', 'true', 'Izinkan Work From Anywhere (WFA)', 'wfa', '2026-05-22 03:16:33'),
('wfa_days', 'Mon,Tue,Wed,Thu,Fri', 'Hari WFA Diizinkan (kosong=semua)', 'wfa', '2026-05-22 03:16:33'),
('work_end_time', '21:00', 'Jam Pulang Standar', 'attendance', '2026-05-22 03:16:33'),
('work_start_time', '11:00', 'Jam Masuk Standar', 'attendance', '2026-05-22 03:16:33');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` char(36) NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `alamat_domisili` text DEFAULT NULL,
  `ktp_nik` varchar(20) DEFAULT NULL,
  `nama_sesuai_ktp` varchar(100) DEFAULT NULL,
  `alamat_ktp` text DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `npwp_number` varchar(30) DEFAULT NULL,
  `bpjs_tk` varchar(30) DEFAULT NULL,
  `bpjs_kes` varchar(30) DEFAULT NULL,
  `tanggal_lahir` varchar(50) DEFAULT NULL,
  `status_pernikahan` varchar(50) DEFAULT NULL,
  `jenis_kelamin` varchar(20) DEFAULT NULL,
  `annual_leave_quota` int(11) DEFAULT 12,
  `job_title` varchar(100) DEFAULT NULL,
  `base_salary` decimal(15,2) DEFAULT 0.00,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'candidate',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `employee_id`, `first_name`, `last_name`, `email`, `profile_picture`, `no_telepon`, `alamat_domisili`, `ktp_nik`, `nama_sesuai_ktp`, `alamat_ktp`, `bank_name`, `bank_account_number`, `npwp_number`, `bpjs_tk`, `bpjs_kes`, `tanggal_lahir`, `status_pernikahan`, `jenis_kelamin`, `annual_leave_quota`, `job_title`, `base_salary`, `password_hash`, `role`, `created_at`, `updated_at`) VALUES
('0f850153-f9f8-4885-8526-0fdaa39a9a05', NULL, 'Super', 'Admin', 'superadmin@mail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, NULL, 0.00, '$2y$10$gG4JoCXt7HNhb2nlWEPYIeYtBKq9PkdVuTzRqdhkLHwBpzMcnTPne', 'superadmin', '2026-05-21 09:45:48', '2026-05-21 09:45:48');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `employee_attendance`
--
ALTER TABLE `employee_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_date` (`user_id`,`attendance_date`);

--
-- Indeks untuk tabel `employee_data_correction_requests`
--
ALTER TABLE `employee_data_correction_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `employee_leave_requests`
--
ALTER TABLE `employee_leave_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `employee_reimbursement_claims`
--
ALTER TABLE `employee_reimbursement_claims`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `global_settings`
--
ALTER TABLE `global_settings`
  ADD PRIMARY KEY (`key`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
