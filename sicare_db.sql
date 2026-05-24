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
  `ip_address` varchar(45) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `correction_reason` text DEFAULT NULL,
  `corrected_by` char(36) DEFAULT NULL,
  `corrected_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `employee_attendance`
--

INSERT INTO `employee_attendance` (`id`, `user_id`, `attendance_date`, `clock_in`, `clock_out`, `status`, `clock_in_latitude`, `clock_in_longitude`, `clock_out_latitude`, `clock_out_longitude`, `location_method`, `work_mode`, `ip_address`, `notes`, `correction_reason`, `corrected_by`, `corrected_at`, `created_at`, `updated_at`) VALUES
('199f89f2-96e7-49ec-bcdd-4d2342fb81c6', '123e4567-e89b-12d3-a456-426614174000', '2026-05-22', '09:38:29', NULL, 'awal', -7.4626821, 112.4386198, NULL, NULL, 'GPS', 'WFA', '::1', NULL, NULL, NULL, NULL, '2026-05-22 02:38:29', '2026-05-22 02:38:29'),
('5280c0cf-d542-4f15-b031-ca63ae6e3725', '3a5109e3-5dbf-432f-9ade-8413827ae159', '2026-05-22', '10:04:34', NULL, 'terlambat', -7.4626155, 112.4386335, NULL, NULL, 'GPS', 'WFA', '::1', NULL, NULL, NULL, NULL, '2026-05-22 03:04:34', '2026-05-22 03:04:34');

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

--
-- Dumping data untuk tabel `employee_data_correction_requests`
--

INSERT INTO `employee_data_correction_requests` (`id`, `user_id`, `category`, `field`, `old_value`, `new_value`, `reason`, `file_path`, `status`, `rejection_reason`, `created_at`, `updated_at`) VALUES
('76ba871c-4aa9-49a2-bcc5-3bb63592071c', '123e4567-e89b-12d3-a456-426614174000', 'kependudukan', 'nama_sesuai_ktp', NULL, 'Faishal Nafi Rabbani', 'test', 'f9172a1212b3fce796b338f92bff006b.jpg', 'approved', NULL, '2026-05-22 02:35:26', '2026-05-22 02:36:03');

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

--
-- Dumping data untuk tabel `employee_leave_requests`
--

INSERT INTO `employee_leave_requests` (`id`, `user_id`, `leave_type`, `start_date`, `end_date`, `duration`, `reason`, `attachment_path`, `status`, `rejection_reason`, `created_at`, `updated_at`) VALUES
('16060a4a-fc94-4dd1-8812-d3171a2e14c0', '123e4567-e89b-12d3-a456-426614174000', 'cuti sakit', '2026-05-27', '2026-05-28', 2, 'besaran', 'd8250cbc1819dd791d4c6c33f65a3fba.jpg', 'pending', NULL, '2026-05-22 02:45:57', '2026-05-22 02:45:57'),
('371fa181-0693-4c66-8848-0af4c96f5258', '3a5109e3-5dbf-432f-9ade-8413827ae159', 'cuti sakit', '2026-05-27', '2026-05-28', 2, 'apagitu', '96833f9595429ce203aec9297666902d.png', 'pending', NULL, '2026-05-22 03:06:23', '2026-05-22 03:06:23'),
('d4885b9c-32ff-4fd4-a01a-4e95aa3c71e1', '123e4567-e89b-12d3-a456-426614174000', 'izin khusus', '2026-05-23', '2026-05-24', 2, 'Cuti Bersama', NULL, 'approved', NULL, '2026-05-22 02:43:17', '2026-05-22 02:45:25');

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

--
-- Dumping data untuk tabel `employee_reimbursement_claims`
--

INSERT INTO `employee_reimbursement_claims` (`id`, `user_id`, `category`, `amount`, `description`, `receipt_path`, `status`, `rejection_reason`, `created_at`, `updated_at`) VALUES
('6bd7ffeb-5b20-4b43-96f1-7232e9f89220', '3a5109e3-5dbf-432f-9ade-8413827ae159', 'operasional', 60000.00, 'mmm', '420e1c6025d34b8b4d04af1738fce194.jpg', 'pending', NULL, '2026-05-22 03:07:21', '2026-05-22 03:07:21'),
('e7d71e11-7de1-4ada-8a7b-28bcde869085', '123e4567-e89b-12d3-a456-426614174000', 'makan', 30000.00, 'Jajan', '2fa9e6c2ac88d49481052926b2bd6abd.jpg', 'approved', NULL, '2026-05-22 02:43:46', '2026-05-22 02:44:20');

-- --------------------------------------------------------

--
-- Struktur dari tabel `hr_settings`
--

CREATE TABLE `hr_settings` (
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `group` varchar(50) DEFAULT 'general',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `hr_settings`
--

INSERT INTO `hr_settings` (`key`, `value`, `label`, `group`, `updated_at`) VALUES
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

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `employee_id`, `first_name`, `last_name`, `email`, `profile_picture`, `no_telepon`, `alamat_domisili`, `ktp_nik`, `nama_sesuai_ktp`, `alamat_ktp`, `bank_name`, `bank_account_number`, `npwp_number`, `bpjs_tk`, `bpjs_kes`, `tanggal_lahir`, `status_pernikahan`, `jenis_kelamin`, `annual_leave_quota`, `job_title`, `base_salary`, `password_hash`, `role`, `created_at`, `updated_at`) VALUES
('0f850153-f9f8-4885-8526-0fdaa39a9a05', NULL, 'Super', 'Admin', 'superadmin@mail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, NULL, 0.00, '$2y$10$gG4JoCXt7HNhb2nlWEPYIeYtBKq9PkdVuTzRqdhkLHwBpzMcnTPne', 'superadmin', '2026-05-21 09:45:48', '2026-05-21 09:45:48'),
('103ac93e-3e5f-4516-b072-92343b7fbba9', NULL, 'HR Ops', 'Admin', 'hrops@mail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, NULL, 0.00, '$2y$10$lnUgafMJbJmgR.6eyN4JBuZUL.GvzQT5Jnr.XAEKzNiMeIXDQxpf.', 'hr_ops', '2026-05-21 09:45:49', '2026-05-21 13:37:11'),
('123e4567-e89b-12d3-a456-426614174000', 'LOSS2026', 'Faishal', 'Nafi', 'dummy@mail.com', NULL, NULL, NULL, NULL, 'Faishal Nafi Rabbani', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, NULL, 0.00, '.pqDzmc3Ll.DL2cAsOcEYNBcLcD0FuIXHQU65W4XngeUwoNEu', 'employee', '2026-05-21 23:08:12', '2026-05-22 02:49:34'),
('212160be-4f24-46ab-bbe3-360ffce355fe', NULL, 'Hiring', 'Manager', 'hiring@mail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, NULL, 0.00, '$2y$10$1L8Q90o/zMXAcQMI1W.a0ezXtorA9bSZuXetIlK/D.BAyOBGlc/ve', 'hiring_manager', '2026-05-21 09:45:49', '2026-05-21 09:45:49'),
('3a5109e3-5dbf-432f-9ade-8413827ae159', NULL, 'Faishal Nafi\'', NULL, 'faishalnafi50@gmail.com', 'https://lh3.googleusercontent.com/a/ACg8ocK5lDf8t2v-q5Pv2Mk3HB0WqScHCiap5IJCUQFctgIyGgOIwJhfrw=s96-c', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, NULL, 0.00, '$2y$10$UTPBXDHeyVV6fxbVY7JN7eVPTk60HJ0Rax.pykSVLSweOAjNuZikS', 'employee', '2026-05-22 02:50:23', '2026-05-22 02:56:38'),
('81298f6c-5a1e-4270-b51a-7a39aba5aaea', NULL, 'Senior', 'Recruiter', 'recruiter@mail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, NULL, 0.00, '$2y$10$JaZ7NToWCc74Tfjxu5SmV.YXR1Vq5a5q8d6Z.C1l.wCsYEUP6LIqS', 'recruiter', '2026-05-21 09:45:49', '2026-05-21 09:45:49'),
('d94171a7-52c8-4965-b5d4-9515ffc3171c', NULL, 'Chief', 'Executive', 'executive@mail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, NULL, 0.00, '$2y$10$ACaMXmzyVnPuHYwVVaGDbOH4oqFi6HSqFvTRlYz0hBFJs6nckRgNe', 'executive', '2026-05-21 09:45:49', '2026-05-21 09:45:49');

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
-- Indeks untuk tabel `hr_settings`
--
ALTER TABLE `hr_settings`
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
