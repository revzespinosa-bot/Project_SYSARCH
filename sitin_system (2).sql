-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 05, 2026 at 11:39 AM
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
(2, 'UCCS', 'LOVE YOU PAW', '2026-04-05 09:00:43');

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
(1, '12312323', 2, 0, 'no lies', '2026-04-05 07:32:50');

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
(7, '12312323', 'John A Paul', 'RESEARCH', '530', 1, '2026-04-05 09:15:31', '2026-04-05 09:15:31', 'completed');

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

INSERT INTO `sitin_reservations` (`id`, `id_number`, `student_name`, `purpose`, `lab`, `time_in`, `date`, `remaining_sessions`, `created_at`, `status`, `time_out`, `notified`) VALUES
(8, '12312323', 'John A Paul', 'RESEARCH', '530', '17:05:00', '2026-04-05', 24, '2026-04-05 09:05:52', 'completed', '2026-04-05 09:15:32', 1),
(9, '12312323', 'John A Paul', 'ACTIVITY', '520', '17:16:00', '2026-04-05', 23, '2026-04-05 09:16:13', 'approved', NULL, 1),
(10, '23821150', 'kyo b pou', 'ACTIVITY', 'LAB 544', '17:17:00', '2026-04-05', 26, '2026-04-05 09:17:17', 'rejected', NULL, 1),
(11, '12312323', 'John A Paul', 'ACTIVITY', 'LAB 544', '17:27:00', '2026-04-05', 23, '2026-04-05 09:27:03', 'approved', NULL, 0);

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
  `remaining_sessions` int(11) DEFAULT 28
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `id_number`, `last_name`, `first_name`, `middle_name`, `course`, `year_level`, `email`, `address`, `password`, `created_at`, `photo`, `remaining_sessions`) VALUES
(2, '23821150', 'pou', 'kyo', 'b', 'BSIT', '3rd', 'kyu@yeye', 'cebu', '$2y$10$J6dbZyvPKEyOYs2xIXPxZukpLLVvb65C6sK1ygG4Y5tNh6BfQBdo2', '2026-03-22 03:51:34', '69c009ff220da.jpg', 26),
(3, '12312323', 'Paul', 'John', 'A', 'BSIT', '3rd', 'John@gmail.com', 'uc', '$2y$10$UQOFHMmUZ7B5wEINVKsax.nNy2iRN5hwRfeJqXA9yMceA8Asnk4p6', '2026-04-05 06:25:09', '69d20114ee84c.jpg', 23);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=221;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `computers`
--
ALTER TABLE `computers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sitin_records`
--
ALTER TABLE `sitin_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sitin_reservations`
--
ALTER TABLE `sitin_reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
