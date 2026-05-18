-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2026 at 06:06 AM
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
-- Database: `sitin_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$examplehashedpassword');

-- --------------------------------------------------------

--
-- Table structure for table `admin_profile`
--

CREATE TABLE `admin_profile` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_profile`
--

INSERT INTO `admin_profile` (`id`, `username`, `full_name`, `email`, `phone`) VALUES
(1, 'admin', 'Administrator', 'admin@sitin_system.local', 'N/A');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `created_at`) VALUES
(1, 'UCCS', 'PLEASE GO TO SCHOOL IN MONDAY', '2026-04-05 06:51:31'),
(2, 'UCCS', 'LOVE YOU PAW', '2026-04-05 09:00:43'),
(3, 'UCCS', 'PLEASE TAKE CARE ALWAYS NO WOMEN NO CRY', '2026-04-06 03:18:18'),
(4, 'UCCS', 'TIME CHECK', '2026-05-11 03:50:22');

-- --------------------------------------------------------

--
-- Table structure for table `computers`
--

CREATE TABLE `computers` (
  `id` int(11) NOT NULL,
  `lab_name` varchar(50) NOT NULL,
  `computer_name` varchar(50) NOT NULL,
  `status` enum('available','in_use','maintenance') DEFAULT 'available',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `computers`
--

INSERT INTO `computers` (`id`, `lab_name`, `computer_name`, `status`, `created_at`) VALUES
(2, '524', 'pc 1', 'in_use', '2026-05-11 11:46:46'),
(3, '524', 'pc 2', 'in_use', '2026-05-11 11:47:15'),
(4, '526', 'pc1', 'in_use', '2026-05-11 11:47:38'),
(5, '528', 'pc 1', 'in_use', '2026-05-11 11:54:03');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('reservation_enabled', '1');

-- --------------------------------------------------------

--
-- Table structure for table `lab_software`
--

CREATE TABLE `lab_software` (
  `id` int(11) NOT NULL,
  `lab_name` varchar(50) NOT NULL,
  `software_name` varchar(150) NOT NULL,
  `version` varchar(50) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `history_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `id_number`, `history_id`, `rating`, `comment`, `created_at`) VALUES
(1, '12312323', 2, 0, 'no lies', '2026-04-05 07:32:50'),
(2, '12312311', 10, 5, 'You are so beautiful admin', '2026-04-06 03:36:43');

-- --------------------------------------------------------

--
-- Table structure for table `lab_reservations`
--

CREATE TABLE `lab_reservations` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `lab` varchar(50) NOT NULL,
  `computer_number` varchar(20) NOT NULL,
  `reservation_date` date NOT NULL,
  `time_from` time NOT NULL,
  `time_to` time NOT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `lab_name` varchar(50) NOT NULL,
  `computer_name` varchar(50) NOT NULL,
  `purpose` varchar(100) NOT NULL,
  `reservation_date` date NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sitin_history`
--

CREATE TABLE `sitin_history` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `lab` varchar(50) NOT NULL,
  `computer_name` varchar(50) DEFAULT NULL,
  `sessions_used` int(11) NOT NULL DEFAULT 1,
  `time_in` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_out` timestamp NULL DEFAULT NULL,
  `status` enum('completed','cancelled') DEFAULT 'completed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitin_history`
--

INSERT INTO `sitin_history` (`id`, `id_number`, `student_name`, `purpose`, `lab`, `sessions_used`, `time_in`, `time_out`, `status`) VALUES
(1, '23821150', 'kyo b pou', 'RESEARCH', '524', 1, '2026-03-24 16:59:09', '2026-03-24 16:59:09', 'completed'),
(2, '12312323', 'John Paul', 'RESEARCH', '0', 1, '2026-04-05 06:36:05', '2026-04-05 06:36:05', 'completed'),
(3, '23821150', 'kyo pou', 'RESEARCH', '530', 1, '0000-00-00 00:00:00', '2026-04-05 07:47:03', 'completed'),
(4, '12312323', 'John Paul', 'ACTIVITY', '520', 1, '0000-00-00 00:00:00', '2026-04-05 07:47:05', 'completed'),
(5, '12312323', 'John A Paul', 'ACTIVITY', '530', 1, '0000-00-00 00:00:00', '2026-04-05 07:51:39', 'completed'),
(6, '12312323', 'John A Paul', 'ACTIVITY', '0', 1, '2026-04-05 08:56:51', '2026-04-05 08:56:51', 'completed'),
(7, '12312323', 'John A Paul', 'RESEARCH', '530', 1, '2026-04-05 09:15:31', '2026-04-05 09:15:31', 'completed'),
(8, '12312323', 'John A Paul', 'ACTIVITY', '0', 1, '2026-04-06 03:09:53', '2026-04-06 03:09:53', 'completed'),
(9, '12312323', 'John A Paul', 'ACTIVITY', '520', 1, '2026-04-06 03:09:56', '2026-04-06 03:09:56', 'completed'),
(10, '12312311', 'Fritche  Torino', 'RESEARCH', '0', 1, '2026-04-06 03:35:53', '2026-04-06 03:35:53', 'completed'),
(11, '12312312', 'Rai Gomez', 'ACTIVITY', '520', 1, '2026-04-06 03:40:26', '2026-04-06 03:40:26', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `sitin_records`
--

CREATE TABLE `sitin_records` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `login_time` datetime DEFAULT NULL,
  `logout_time` datetime DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('active','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sitin_reservations`
--

CREATE TABLE `sitin_reservations` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `lab` varchar(50) NOT NULL,
  `computer_name` varchar(50) DEFAULT NULL,
  `time_in` time NOT NULL,
  `date` date NOT NULL,
  `remaining_sessions` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected','cancelled','completed') DEFAULT 'pending',
  `time_out` timestamp NULL DEFAULT NULL,
  `notified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitin_reservations`
--

INSERT INTO `sitin_reservations` (`id`, `id_number`, `student_name`, `purpose`, `lab`, `computer_name`, `time_in`, `date`, `remaining_sessions`, `created_at`, `status`, `time_out`, `notified`) VALUES
(8, '12312323', 'John A Paul', 'RESEARCH', '530', NULL, '17:05:00', '2026-04-05', 24, '2026-04-05 09:05:52', 'completed', '2026-04-05 09:15:32', 1),
(9, '12312323', 'John A Paul', 'ACTIVITY', '520', NULL, '17:16:00', '2026-04-05', 23, '2026-04-05 09:16:13', 'completed', '2026-04-06 03:09:56', 1),
(10, '23821150', 'kyo b pou', 'ACTIVITY', 'LAB 544', NULL, '17:17:00', '2026-04-05', 26, '2026-04-05 09:17:17', 'rejected', NULL, 1),
(11, '12312323', 'John A Paul', 'ACTIVITY', 'LAB 544', NULL, '17:27:00', '2026-04-05', 23, '2026-04-05 09:27:03', 'completed', '2026-04-06 03:09:53', 0),
(12, '12312312', 'Rai Gomez', 'ACTIVITY', '520', NULL, '11:16:59', '2026-04-06', 1, '2026-04-06 03:16:59', 'completed', '2026-04-06 03:40:26', 0),
(13, '12312311', 'Fritche  Torino', 'RESEARCH', 'LAB 544', NULL, '11:34:00', '2026-04-06', 28, '2026-04-06 03:34:20', 'completed', '2026-04-06 03:35:53', 1),
(14, '12312311', 'Fritche  Torino', 'Java Programming', '524', NULL, '10:50:00', '2026-04-13', 27, '2026-04-13 02:50:35', 'rejected', NULL, 1),
(15, '12312333', 'Christina  Gutierrez', 'Java Programming', '524', NULL, '11:07:00', '2026-04-13', 28, '2026-04-13 03:07:17', 'rejected', NULL, 1),
(16, '23821150', 'kyo b pou', 'C Programming', '524', 'pc 1', '11:18:00', '2026-05-11', 26, '2026-05-11 03:18:19', 'approved', NULL, 1),
(17, '12312311', 'Fritche ceazar  Cyberjorg', 'Research/Homework', '526', 'pc1', '11:20:00', '2026-05-11', 30, '2026-05-11 03:20:13', 'approved', NULL, 0),
(18, '12312323', 'John A Paul', 'C Programming', '528', 'pc 1', '11:52:00', '2026-05-11', 21, '2026-05-11 03:52:23', 'approved', NULL, 0),
(19, '12312313', 'Patrick F Sagac', 'Networking', '524', 'pc 2', '11:53:00', '2026-05-11', 28, '2026-05-11 03:53:24', 'approved', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo` varchar(255) DEFAULT NULL,
  `remaining_sessions` int(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `id_number`, `last_name`, `first_name`, `middle_name`, `course`, `year_level`, `email`, `address`, `password`, `created_at`, `photo`, `remaining_sessions`) VALUES
(2, '23821150', 'pou', 'kyo', 'b', 'BSIT', '3rd', 'kyu@yeye', 'cebu', '$2y$10$J6dbZyvPKEyOYs2xIXPxZukpLLVvb65C6sK1ygG4Y5tNh6BfQBdo2', '2026-03-22 03:51:34', '69c009ff220da.jpg', 26),
(3, '12312323', 'Paul', 'John', 'A', 'BSIT', '3rd', 'John@gmail.com', 'uc', '$2y$10$UQOFHMmUZ7B5wEINVKsax.nNy2iRN5hwRfeJqXA9yMceA8Asnk4p6', '2026-04-05 06:25:09', '69d20114ee84c.jpg', 21),
(4, '12312312', 'Gomez', 'Rai', 'B', 'BSIT', '3rd', 'Gomez@gmail.com', 'UC', '$2y$10$X538lRte55cmOOvM6SyeBexZm1kMLbsnJcSRYVqgYBAlekv4ONBNy', '2026-04-06 03:13:34', '69d3257303315.png', 27),
(5, '12312313', 'Sagac', 'Patrick', 'F', 'BSIT', '3rd', 'Sagac@gmail.com', 'UC', '$2y$10$yDJmIf/Dt7fu2cfdXGP5P.svWgM.s2CBov883V/zxHAKtaFL/uzty', '2026-04-06 03:19:22', '69d326c8c256d.png', 28),
(6, '12312311', 'Cyberjorg', 'Fritche ceazar', '', 'BSIT', '3rd', 'Torino@gmail.com', 'UC', '$2y$10$ywJxKjY0udGA1rs4MXg0n.S6.0N6/03O8/./Y/nZr6adl9/Eamph2', '2026-04-06 03:29:32', '69dc59dea98e6.png', 30),
(7, '12312333', 'Gutierrez', 'Christina', '', 'BSMT', '4th Year', 'Yoyo@gmail.com', 'UC', '$2y$10$1ioxeoCAlLjN1QDxzsDxx.425rD6jAMfVGfWqdFFIhYj.GNp/kblu', '2026-04-13 03:06:12', '69dc5dbc15fc9.png', 28);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `admin_profile`
--
ALTER TABLE `admin_profile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `computers`
--
ALTER TABLE `computers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_reservations`
--
ALTER TABLE `lab_reservations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sitin_history`
--
ALTER TABLE `sitin_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`id_number`);

--
-- Indexes for table `sitin_records`
--
ALTER TABLE `sitin_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_number` (`id_number`);

--
-- Indexes for table `sitin_reservations`
--
ALTER TABLE `sitin_reservations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_number` (`id_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_profile`
--
ALTER TABLE `admin_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=273;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `computers`
--
ALTER TABLE `computers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lab_reservations`
--
ALTER TABLE `lab_reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sitin_history`
--
ALTER TABLE `sitin_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sitin_records`
--
ALTER TABLE `sitin_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sitin_reservations`
--
ALTER TABLE `sitin_reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sitin_records`
--
ALTER TABLE `sitin_records`
  ADD CONSTRAINT `sitin_records_ibfk_1` FOREIGN KEY (`id_number`) REFERENCES `students` (`id_number`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
