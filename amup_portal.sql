-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 23, 2026 at 07:22 AM
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
-- Database: `amup_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_calendar`
--

CREATE TABLE `academic_calendar` (
  `id` int(11) NOT NULL,
  `session` varchar(20) DEFAULT NULL,
  `semester` enum('First','Second') DEFAULT NULL,
  `event_title` varchar(150) NOT NULL,
  `event_date` date NOT NULL,
  `event_type` enum('resumption','suspension','exam','registration','other') DEFAULT 'other',
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_calendar`
--

INSERT INTO `academic_calendar` (`id`, `session`, `semester`, `event_title`, `event_date`, `event_type`, `description`, `created_at`) VALUES
(1, '2024/2025', 'First', 'First Semester Resumption', '2024-09-16', 'resumption', 'First semester academic activities begin', '2026-05-20 15:40:42'),
(2, '2024/2025', 'First', 'First Semester Examinations', '2024-12-09', 'exam', 'First semester exams commence', '2026-05-20 15:40:42'),
(3, '2024/2025', 'First', 'First Semester Ends / Suspension', '2024-12-20', 'suspension', 'Vacation begins', '2026-05-20 15:40:42'),
(4, '2024/2025', 'Second', 'Second Semester Resumption', '2025-01-20', 'resumption', 'Second semester academic activities begin', '2026-05-20 15:40:42'),
(5, '2024/2025', 'Second', 'Second Semester Examinations', '2025-05-05', 'exam', 'Second semester exams commence', '2026-05-20 15:40:42'),
(6, '2024/2025', 'Second', 'Session Ends', '2025-06-30', 'suspension', 'Session vacation begins', '2026-05-20 15:40:42');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(60) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `full_name`, `created_at`) VALUES
(1, 'admin', 'admin@amup.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Portal Administrator', '2026-05-20 15:40:41');

-- --------------------------------------------------------

--
-- Table structure for table `bursars`
--

CREATE TABLE `bursars` (
  `id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bursars`
--

INSERT INTO `bursars` (`id`, `full_name`, `email`, `username`, `password`, `phone`, `status`, `created_at`) VALUES
(1, 'Bursar Office', 'bursar@amup.edu.ng', 'bursar', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'active', '2026-05-21 06:45:18');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_title` varchar(150) NOT NULL,
  `credit_units` int(11) NOT NULL DEFAULT 3,
  `department` varchar(100) DEFAULT NULL,
  `level` varchar(10) DEFAULT NULL,
  `semester` enum('First','Second') NOT NULL,
  `degree` varchar(20) DEFAULT 'B.Sc',
  `duration_years` int(11) DEFAULT 4
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_code`, `course_title`, `credit_units`, `department`, `level`, `semester`, `degree`, `duration_years`) VALUES
(1, 'CSC 101', 'Introduction to Computer Science', 3, 'Computer Science', '100L', 'First', 'B.Sc', 4),
(2, 'CSC 102', 'Computer Programming I', 3, 'Computer Science', '100L', 'First', 'B.Sc', 4),
(3, 'MTH 101', 'Elementary Mathematics I', 3, 'Computer Science', '100L', 'First', 'B.Sc', 4),
(4, 'ENG 101', 'Use of English', 2, 'Computer Science', '100L', 'First', 'B.Sc', 4),
(5, 'GNS 101', 'Nigerian People & Culture', 2, 'Computer Science', '100L', 'First', 'B.Sc', 4),
(6, 'CSC 111', 'Computer Programming II', 3, 'Computer Science', '100L', 'Second', 'B.Sc', 4),
(7, 'MTH 112', 'Elementary Mathematics II', 3, 'Computer Science', '100L', 'Second', 'B.Sc', 4),
(8, 'PHY 101', 'General Physics I', 3, 'Computer Science', '100L', 'Second', 'B.Sc', 4),
(9, 'STA 101', 'Introduction to Statistics', 3, 'Computer Science', '100L', 'Second', 'B.Sc', 4);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `faculty` varchar(100) DEFAULT NULL,
  `degree` varchar(20) DEFAULT 'B.Sc',
  `duration_years` int(11) DEFAULT 4,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `faculty`, `degree`, `duration_years`, `created_at`) VALUES
(1, 'Mass Communication', 'Faculty of Social and Management Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(2, 'Political Science', 'Faculty of Social and Management Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(3, 'Public Administration', 'Faculty of Social and Management Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(4, 'Peace Studies and Conflict Resolution', 'Faculty of Social and Management Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(5, 'International Relation and Diplomatic Studies', 'Faculty of Social and Management Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(6, 'Industrial Relation and Personal Management', 'Faculty of Social and Management Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(7, 'Economics', 'Faculty of Social and Management Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(8, 'Business Administration', 'Faculty of Social and Management Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(9, 'Accounting', 'Faculty of Social and Management Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(10, 'Entrepreneurship', 'Faculty of Social and Management Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(11, 'Taxation', 'Faculty of Social and Management Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(12, 'Criminology', 'Faculty of Social and Management Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(13, 'Psychology', 'Faculty of Social and Management Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(14, 'Nursing', 'Faculty of Health Science', 'B.Sc', 5, '2026-05-22 11:28:17'),
(15, 'Medical Laboratory Science', 'Faculty of Health Science', 'B.Sc', 4, '2026-05-22 11:28:17'),
(16, 'Public Health', 'Faculty of Health Science', 'B.Sc', 4, '2026-05-22 11:28:17'),
(17, 'Law', 'Faculty of Law', 'LLB', 5, '2026-05-22 11:28:17'),
(18, 'Biotechnology', 'Faculty of Basic Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(19, 'Microbiology', 'Faculty of Basic Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(20, 'Industrial Chemistry', 'Faculty of Basic Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(21, 'Biochemistry', 'Faculty of Basic Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(22, 'Computer Science', 'Faculty of Basic Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(23, 'Software Engineering and Digital Entrepreneurship', 'Faculty of Basic Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(24, 'Cyber Security', 'Faculty of Basic Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(25, 'Forensic Science', 'Faculty of Basic Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(26, 'Physics with Electronics', 'Faculty of Basic Sciences', 'B.Sc', 4, '2026-05-22 11:28:17'),
(27, 'Medicine & Surgery', 'College of Medicine', 'MBBS', 6, '2026-05-22 11:28:17'),
(28, 'Pharmacy', 'College of Medicine', 'B.Sc', 5, '2026-05-22 11:28:17'),
(29, 'Anatomy', 'College of Medicine', 'B.Sc', 4, '2026-05-22 11:28:17'),
(30, 'Physiology', 'College of Medicine', 'B.Sc', 4, '2026-05-22 11:28:17'),
(31, 'Radiography', 'College of Medicine', 'B.Sc', 4, '2026-05-22 11:28:17');

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fee_type` varchar(80) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `session` varchar(20) DEFAULT NULL,
  `semester` enum('First','Second','Full Year') DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `status` enum('paid','partial','unpaid') DEFAULT 'unpaid',
  `receipt_no` varchar(50) DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fees`
--

INSERT INTO `fees` (`id`, `student_id`, `fee_type`, `amount`, `amount_paid`, `session`, `semester`, `due_date`, `paid_date`, `status`, `receipt_no`, `remark`, `created_at`) VALUES
(1, 1, 'School Fees', 150000.00, 150000.00, '2024/2025', 'First', '2024-09-30', NULL, 'paid', NULL, NULL, '2026-05-20 15:40:42'),
(2, 1, 'Acceptance Fee', 25000.00, 25000.00, '2024/2025', 'Full Year', '2024-10-01', NULL, 'paid', NULL, NULL, '2026-05-20 15:40:42'),
(3, 1, 'Library Fee', 5000.00, 0.00, '2024/2025', 'First', '2024-09-30', NULL, 'unpaid', NULL, NULL, '2026-05-20 15:40:42'),
(4, 1, 'Sports Fee', 3000.00, 0.00, '2024/2025', 'Full Year', '2024-09-30', NULL, 'unpaid', NULL, NULL, '2026-05-20 15:40:42'),
(5, 1, 'School Fees', 150000.00, 0.00, '2024/2025', 'Second', '2025-01-31', NULL, 'unpaid', NULL, NULL, '2026-05-20 15:40:42'),
(6, 2, 'School Fees', 150000.00, 150000.00, '2024/2025', 'First', '2026-06-20', '2026-05-21', 'paid', NULL, '', '2026-05-21 01:19:28'),
(7, 2, 'Acceptance Fee', 25000.00, 0.00, '2024/2025', 'Full Year', '2026-06-04', NULL, 'unpaid', NULL, NULL, '2026-05-21 01:19:28'),
(8, 2, 'Library Fee', 5000.00, 0.00, '2024/2025', 'Full Year', '2026-06-20', NULL, 'unpaid', NULL, NULL, '2026-05-21 01:19:28'),
(9, 2, 'Sports Fee', 3000.00, 0.00, '2024/2025', 'Full Year', '2026-06-20', NULL, 'unpaid', NULL, NULL, '2026-05-21 01:19:28'),
(10, 2, 'Acceptance Fee', 249999.99, 250000.00, '2024/2025', 'First', '2026-05-21', '2026-05-21', 'paid', NULL, '', '2026-05-21 02:02:18');

-- --------------------------------------------------------

--
-- Table structure for table `fee_templates`
--

CREATE TABLE `fee_templates` (
  `id` int(11) NOT NULL,
  `fee_name` varchar(100) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `session` varchar(20) NOT NULL,
  `semester` enum('First','Second','Full Year') DEFAULT 'Full Year',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_templates`
--

INSERT INTO `fee_templates` (`id`, `fee_name`, `amount`, `session`, `semester`, `is_active`, `created_at`) VALUES
(1, 'School Fees', 150000.00, '2024/2025', 'First', 1, '2026-05-21 02:00:39'),
(2, 'Acceptance Fee', 25000.00, '2024/2025', 'Full Year', 1, '2026-05-21 02:00:39'),
(3, 'Library Fee', 5000.00, '2024/2025', 'Full Year', 1, '2026-05-21 02:00:39'),
(4, 'Sports Fee', 3000.00, '2024/2025', 'Full Year', 1, '2026-05-21 02:00:39'),
(5, 'Chapel/Mass Levy', 2000.00, '2024/2025', 'Full Year', 1, '2026-05-21 02:00:39'),
(6, 'Examination Fee', 10000.00, '2024/2025', 'First', 1, '2026-05-21 02:00:39'),
(7, 'ID Card Fee', 1500.00, '2024/2025', 'Full Year', 1, '2026-05-21 02:00:39'),
(8, 'Medical Fee', 5000.00, '2024/2025', 'Full Year', 1, '2026-05-21 02:00:39');

-- --------------------------------------------------------

--
-- Table structure for table `hods`
--

CREATE TABLE `hods` (
  `id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `username` varchar(60) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `department` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hods`
--

INSERT INTO `hods` (`id`, `full_name`, `email`, `username`, `password`, `department`, `phone`, `status`, `created_at`) VALUES
(1, 'Dr. Emeka Eze', 'hod.cs@amup.edu.ng', 'hod_cs', '$2y$10$utcW8Al6xItgTwhUQh1mQeEhWRQRiEJ6zz5k5DzhxsNw4IhCDCLHG', 'Computer Science', NULL, 'active', '2026-05-21 06:49:22'),
(2, 'Prof. Adaeze Okafor', 'hod.ee@amup.edu.ng', 'hod_ee', '$2y$10$utcW8Al6xItgTwhUQh1mQeEhWRQRiEJ6zz5k5DzhxsNw4IhCDCLHG', 'Electrical Engineering', NULL, 'active', '2026-05-21 06:49:22'),
(3, 'Dr. Chidi Nwosu', 'hod.me@amup.edu.ng', 'hod_me', '$2y$10$utcW8Al6xItgTwhUQh1mQeEhWRQRiEJ6zz5k5DzhxsNw4IhCDCLHG', 'Mechanical Engineering', NULL, 'active', '2026-05-21 06:49:22');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `student_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, NULL, 'Welcome to 2024/2025 Academic Session', 'All students are required to complete their registration before 30th September 2024.', 'info', 1, '2026-05-20 15:40:42'),
(2, 1, 'Fee Payment Reminder', 'You have outstanding fees for First Semester. Please visit the bursary.', 'warning', 0, '2026-05-20 15:40:42'),
(3, 2, 'Registration Successful', 'Welcome to Ave Maria University Piyanko Portal. Please complete your profile and clear all outstanding fees.', 'success', 1, '2026-05-21 01:19:28'),
(4, 2, '💰 New Fee Added', 'A new fee of ₦249,999.99 for Acceptance Fee has been added to your account.', 'warning', 1, '2026-05-21 02:02:18'),
(5, 2, '✅ Fee Payment Confirmed', 'Your fee payment has been confirmed and updated by the admin. Your account is now cleared.', 'success', 1, '2026-05-21 02:03:00'),
(6, 2, '✅ Fee Payment Confirmed', 'Your fee payment has been confirmed and updated by the admin. Your account is now cleared.', 'success', 1, '2026-05-21 02:03:28'),
(7, 1, 'Congratulations! 🎓', 'You have been marked as graduated! Congratulations on completing your programme.', 'success', 0, '2026-05-22 07:15:00'),
(8, 1, 'Account Activated', 'Your account has been reactivated. Welcome back!', 'success', 0, '2026-05-22 07:16:21');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `session` varchar(20) DEFAULT NULL,
  `semester` enum('First','Second') DEFAULT NULL,
  `ca_score` decimal(5,2) DEFAULT 0.00,
  `exam_score` decimal(5,2) DEFAULT 0.00,
  `attendance` decimal(5,2) DEFAULT NULL,
  `total_score` decimal(5,2) GENERATED ALWAYS AS (`ca_score` + `exam_score`) STORED,
  `grade` varchar(2) DEFAULT NULL,
  `grade_point` decimal(3,1) DEFAULT NULL,
  `cgp` decimal(5,2) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`id`, `student_id`, `course_id`, `session`, `semester`, `ca_score`, `exam_score`, `attendance`, `grade`, `grade_point`, `cgp`, `created_at`) VALUES
(1, 1, 1, '2024/2025', 'First', 28.00, 62.00, NULL, 'B', 3.5, NULL, '2026-05-20 15:40:42'),
(2, 1, 2, '2024/2025', 'First', 25.00, 55.00, NULL, 'C', 2.5, NULL, '2026-05-20 15:40:42'),
(3, 1, 3, '2024/2025', 'First', 30.00, 60.00, NULL, 'B', 3.5, NULL, '2026-05-20 15:40:42'),
(4, 1, 4, '2024/2025', 'First', 18.00, 50.00, NULL, 'C', 2.5, NULL, '2026-05-20 15:40:42'),
(5, 1, 5, '2024/2025', 'First', 28.00, 67.00, NULL, 'A', 4.0, NULL, '2026-05-20 15:40:42');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `role` varchar(60) DEFAULT 'Lecturer',
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `reg_number` varchar(30) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `level` varchar(10) DEFAULT '100L',
  `session` varchar(20) DEFAULT '2024/2025',
  `gender` enum('Male','Female','Other') DEFAULT 'Male',
  `dob` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','suspended','graduated') DEFAULT 'active',
  `exam_eligible` tinyint(1) DEFAULT 1,
  `suspension_reason` text DEFAULT NULL,
  `suspension_date` date DEFAULT NULL,
  `resumption_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `photo` varchar(255) DEFAULT NULL,
  `graduation_session` varchar(20) DEFAULT NULL,
  `graduation_year` int(11) DEFAULT NULL,
  `graduated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `reg_number`, `full_name`, `email`, `phone`, `department`, `level`, `session`, `gender`, `dob`, `address`, `password`, `status`, `exam_eligible`, `suspension_reason`, `suspension_date`, `resumption_date`, `created_at`, `photo`, `graduation_session`, `graduation_year`, `graduated_at`) VALUES
(1, 'AMUP/2024/001', 'Chukwuemeka Daniel Obi', 'daniel.obi@student.amup.edu.ng', '08012345678', 'Computer Science', '100L', '2024/2025', 'Male', NULL, NULL, '$2y$10$TKh8H1.PnjIU9O51BUByEOsKb.fFMbXFqIVmQFpCFd4gqpnHpXFFC', 'active', 1, 'Outstanding fees', '0000-00-00', '0000-00-00', '2026-05-20 15:40:42', NULL, '2021-2025', NULL, '2026-05-22 13:15:00'),
(2, 'AMUP/2024/003', 'mikel', 'mike222@mail.com', '0809234567', 'Electrical Engineering', '100L', '2024/2025', 'Male', NULL, NULL, '$2y$10$6vR21nQ0Vqp3zu4ZRMtjbeOz4GrHP05hnd0x76TeACdwd4jqXLpai', 'active', 1, NULL, NULL, NULL, '2026-05-21 01:19:28', 'amup-2024-003_1779442119.jpeg', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_suspensions`
--

CREATE TABLE `student_suspensions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `suspended_at` datetime DEFAULT current_timestamp(),
  `lifted_at` datetime DEFAULT NULL,
  `lifted_by` varchar(100) DEFAULT NULL,
  `status` enum('active','lifted') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_calendar`
--
ALTER TABLE `academic_calendar`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bursars`
--
ALTER TABLE `bursars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `fee_templates`
--
ALTER TABLE `fee_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hods`
--
ALTER TABLE `hods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reg_number` (`reg_number`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `student_suspensions`
--
ALTER TABLE `student_suspensions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_calendar`
--
ALTER TABLE `academic_calendar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bursars`
--
ALTER TABLE `bursars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `fee_templates`
--
ALTER TABLE `fee_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `hods`
--
ALTER TABLE `hods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_suspensions`
--
ALTER TABLE `student_suspensions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_suspensions`
--
ALTER TABLE `student_suspensions`
  ADD CONSTRAINT `student_suspensions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
