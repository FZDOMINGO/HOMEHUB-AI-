-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2025 at 06:01 PM
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
-- Database: `homehub`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` enum('user','property','booking','system','admin') NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_activity_log`
--

INSERT INTO `admin_activity_log` (`id`, `admin_id`, `action`, `target_type`, `target_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-23 15:23:10'),
(2, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-23 15:49:00'),
(3, 1, 'logout', 'system', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-23 16:33:09'),
(4, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-23 16:40:12'),
(5, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-23 17:21:17'),
(6, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-23 17:26:23'),
(7, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-23 18:12:37'),
(8, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-23 18:13:32'),
(9, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-23 18:37:58'),
(10, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-23 18:39:13'),
(11, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 12:41:38'),
(12, 1, 'logout', 'system', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 12:44:05'),
(13, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 12:45:54'),
(14, 1, 'logout', 'system', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 12:51:55'),
(15, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 12:52:16'),
(16, 1, 'logout', 'system', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 12:53:30'),
(17, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 12:55:53'),
(18, 1, 'logout', 'system', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 12:56:02'),
(19, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 12:56:07'),
(20, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 13:45:55'),
(21, NULL, 'login_failed', 'system', NULL, '{\"username\":\"landlord@example.com\",\"reason\":\"user_not_found\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 14:49:13'),
(22, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 14:49:16'),
(23, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 14:49:16'),
(24, 1, 'logout', 'system', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 15:21:30'),
(25, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 15:22:51'),
(26, 1, 'logout', 'system', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 15:24:31'),
(27, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 15:31:30'),
(28, 1, 'logout', 'system', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 15:33:59'),
(29, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 15:38:26'),
(30, 1, 'login_success', 'system', NULL, '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-24 15:53:46');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','moderator','support') DEFAULT 'moderator',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `permissions`, `is_active`, `last_login`, `failed_login_attempts`, `locked_until`, `profile_image`, `phone`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 'admin', 'admin@homehub.com', '$2y$10$HCo2xKEtWXvHUUAeQopojeWmxXP3NXLgNEv/TTZLTwiFxHXjhfLVi', 'System Administrator', 'super_admin', '{\"manage_users\":true,\"manage_properties\":true,\"manage_bookings\":true,\"view_analytics\":true,\"manage_admins\":true,\"system_settings\":true,\"moderate_content\":true,\"handle_reports\":true}', 1, '2025-10-24 15:53:46', 0, NULL, NULL, NULL, '2025-10-23 15:20:11', '2025-10-24 15:53:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ai_recommendations`
--

CREATE TABLE `ai_recommendations` (
  `id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `recommendation_type` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `application_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `lease_start_date` date DEFAULT NULL,
  `lease_end_date` date DEFAULT NULL,
  `monthly_rent` decimal(10,2) DEFAULT NULL,
  `security_deposit` decimal(10,2) DEFAULT NULL,
  `application_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  `status` enum('active','ended','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_requests`
--

CREATE TABLE `booking_requests` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `visit_date` datetime DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_visits`
--

CREATE TABLE `booking_visits` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `visit_date` date NOT NULL,
  `visit_time` time NOT NULL,
  `number_of_visitors` int(11) DEFAULT 1,
  `phone_number` varchar(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed','cancelled','conflict') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `browsing_history`
--

CREATE TABLE `browsing_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `view_duration` int(11) DEFAULT NULL COMMENT 'Seconds spent viewing',
  `scroll_depth` int(11) DEFAULT NULL COMMENT 'Percentage of page scrolled',
  `images_viewed` int(11) DEFAULT 0,
  `contact_clicked` tinyint(1) DEFAULT 0,
  `saved` tinyint(1) DEFAULT 0,
  `source` varchar(50) DEFAULT NULL COMMENT 'search, recommendation, featured, etc',
  `search_query` text DEFAULT NULL COMMENT 'Original search query if applicable',
  `device_type` enum('desktop','mobile','tablet') DEFAULT 'desktop',
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landlords`
--

CREATE TABLE `landlords` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `company_address` text DEFAULT NULL,
  `business_phone` varchar(20) DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landlords`
--

INSERT INTO `landlords` (`id`, `user_id`, `company_name`, `company_address`, `business_phone`, `tax_id`, `verification_status`) VALUES
(1, 2, 'Property Masters LLC', '123 Business Ave, Suite 101, Cityville', '555-BUSINESS', NULL, 'pending'),
(2, 4, NULL, NULL, NULL, NULL, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `subject` varchar(255) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  `related_property_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `tenant_id`, `landlord_id`, `property_id`, `message`, `subject`, `sender_id`, `receiver_id`, `message_text`, `sent_at`, `read_at`, `related_property_id`) VALUES
(4, 1, 1, 11, '1', '1', 1, 2, '1', '2025-10-14 17:09:10', NULL, NULL),
(5, 1, 1, 11, 'HQHQSHWDGVFYHUKDCXKVGCFD', 'I WANT TO VISIT THIS', 1, 2, 'HQHQSHWDGVFYHUKDCXKVGCFD', '2025-10-14 17:09:51', NULL, NULL),
(6, 1, 1, 11, 'hi', 'hi', 1, 2, 'hi', '2025-10-20 15:58:31', NULL, NULL),
(7, 1, 1, 12, 'IM GOING TO VISIT THIS OKAY?!?!', 'IM GOING TO VISIT THIS OKAY?!?!', 1, 2, 'IM GOING TO VISIT THIS OKAY?!?!', '2025-10-20 17:32:11', NULL, NULL),
(8, 1, 1, 13, 'KAMUSTA', 'KAMUSTA', 1, 2, 'KAMUSTA', '2025-10-20 17:38:12', NULL, NULL),
(9, 1, 1, 13, 'E', 'E', 1, 2, 'E', '2025-10-23 16:38:33', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `content`, `related_id`, `status`, `created_at`, `read_at`, `is_read`) VALUES
(1, 1, 'visit_scheduled', 'Visit scheduled for 2BR Condo in Makati CBD on Sept 20, 2025 at 2:00 PM', NULL, 'Approved', '2025-10-14 13:47:22', '2025-10-14 14:48:16', 1),
(2, 1, 'ai_recommendation', 'We found a perfect match: \"Beachside Condo Pasay\" - 92% compatibility', NULL, NULL, '2025-10-14 11:47:22', '2025-10-14 14:48:18', 1),
(3, 1, 'booking_cancelled', 'Your booking for \"Studio Apartment Makati\" has been cancelled by the landlord', NULL, 'Cancelled', '2025-10-12 14:47:22', '2025-10-14 14:48:19', 1),
(4, 2, 'visit_request', 'New viewing request for your property on 2025-10-24', 11, NULL, '2025-10-14 16:55:46', NULL, 1),
(5, 2, 'visit_request', 'New viewing request for your property on 2025-11-08', 11, NULL, '2025-10-14 16:57:14', NULL, 1),
(6, 2, 'visit_request', 'New viewing request for your property on 2025-10-24', 11, NULL, '2025-10-14 17:04:03', NULL, 1),
(7, 2, 'message', 'New message from Tenant Profile: 1', NULL, NULL, '2025-10-14 17:09:10', NULL, 1),
(8, 2, 'message', 'New message from Tenant Profile: I WANT TO VISIT THIS', NULL, NULL, '2025-10-14 17:09:51', NULL, 1),
(9, 1, 'visit_update', 'Your visit request for \"Sample\" on Oct 24, 2025 at 12:00 AM has been approved!', 3, NULL, '2025-10-14 17:18:25', '2025-10-14 17:19:01', 1),
(10, 1, 'visit_update', 'Your visit request for \"Sample\" on Oct 24, 2025 at 12:00 AM has been rejected.', 1, NULL, '2025-10-14 17:18:28', '2025-10-14 17:19:00', 1),
(11, 1, 'visit_update', 'Your visit request for \"Sample\" on Nov 8, 2025 at 12:00 AM has been rejected.', 2, NULL, '2025-10-14 17:18:31', '2025-10-14 17:19:00', 1),
(12, 2, 'visit_request', 'New viewing request for your property on 2025-11-08', 11, NULL, '2025-10-14 17:20:22', NULL, 1),
(13, 1, 'visit_update', 'Your visit request for \"Sample\" on Nov 8, 2025 at 12:00 AM has been rejected.', 4, NULL, '2025-10-14 17:20:40', '2025-10-14 17:21:07', 1),
(14, 1, 'visit_update', 'Your scheduled visit for \"Sample\" on Oct 24, 2025 at 12:00 AM has been cancelled.', 3, NULL, '2025-10-20 15:53:17', NULL, 1),
(15, 2, 'visit_request', 'New viewing request for your property on 2025-10-23', 11, NULL, '2025-10-20 15:58:22', NULL, 1),
(16, 2, 'message', 'New message from Tenant Profile: hi', NULL, NULL, '2025-10-20 15:58:31', NULL, 1),
(17, 2, 'visit_request', 'New viewing request for your property on 2025-11-08', 11, NULL, '2025-10-20 16:01:32', NULL, 1),
(18, 1, 'visit_update', 'Your visit request for \"Sample\" on Oct 23, 2025 at 12:00 AM has been approved!', 5, NULL, '2025-10-20 16:04:31', NULL, 1),
(19, 1, 'visit_update', 'Your visit request for \"Sample\" on Nov 8, 2025 at 12:00 AM has been rejected.', 6, NULL, '2025-10-20 16:05:03', NULL, 1),
(20, 1, 'visit_update', 'Your scheduled visit for \"Sample\" on Oct 23, 2025 at 12:00 AM has been cancelled.', 5, NULL, '2025-10-20 16:06:22', NULL, 1),
(21, 2, 'reservation', 'New reservation request for Sample: ₱5,000.00 reservation fee, move-in Nov 1, 2025 for 12 months. Employment: unemployed, Income: ₱5,000.00/month', 3, NULL, '2025-10-20 17:27:47', NULL, 1),
(22, 2, 'visit_request', 'New viewing request for your property on 2025-10-22', 12, NULL, '2025-10-20 17:31:52', NULL, 1),
(23, 2, 'message', 'New message from Tenant Profile: IM GOING TO VISIT THIS OKAY?!?!', NULL, NULL, '2025-10-20 17:32:11', NULL, 1),
(24, 2, 'reservation', 'New reservation request for MJS HOUSE: ₱10,000.00 reservation fee, move-in Nov 8, 2025 for 12 months. Employment: employed, Income: ₱40,000.00/month', 4, NULL, '2025-10-20 17:32:41', NULL, 1),
(25, 1, 'visit_update', 'Your visit request for \"MJS HOUSE\" on Oct 22, 2025 at 12:00 AM has been approved!', 7, NULL, '2025-10-20 17:33:10', NULL, 1),
(26, 2, 'visit_request', 'New viewing request for your property on 2025-11-08', 13, NULL, '2025-10-20 17:37:28', NULL, 1),
(27, 2, 'reservation', 'New reservation request for DOBSTER PROPERTY: ₱50,000.00 reservation fee, move-in Nov 8, 2025 for 12 months. Employment: self_employed, Income: ₱100,000.00/month', 5, NULL, '2025-10-20 17:38:00', NULL, 1),
(28, 2, 'message', 'New message from Tenant Profile: KAMUSTA', NULL, NULL, '2025-10-20 17:38:12', NULL, 1),
(29, 1, 'visit_update', 'Your visit request for \"DOBSTER PROPERTY\" on Nov 8, 2025 at 12:00 AM has been rejected.', 8, NULL, '2025-10-20 17:39:06', NULL, 1),
(30, 2, 'suspended', 'Your property listing has been suspended. Reason: Suspected fraud: BAD', 13, 'unread', '2025-10-23 16:32:25', NULL, 1),
(31, 2, 'suspended', 'Your property listing has been suspended. Reason: Inappropriate content: BAD', 13, 'unread', '2025-10-23 16:32:35', NULL, 1),
(32, 2, 'suspended', 'Your property listing has been suspended. Reason: Property no longer available: NO', 12, 'unread', '2025-10-23 16:32:56', NULL, 1),
(33, 2, 'visit_request', 'New viewing request for your property on 2025-10-29', 13, NULL, '2025-10-23 16:38:26', NULL, 1),
(34, 2, 'message', 'New message from Tenant Profile: E', NULL, NULL, '2025-10-23 16:38:33', NULL, 1),
(35, 1, 'visit_update', 'Your visit request for \"DOBSTER PROPERTY\" on Oct 29, 2025 at 12:00 AM has been approved!', 9, NULL, '2025-10-23 16:39:11', NULL, 1),
(36, 2, 'visit_request', 'New viewing request for your property on 2025-10-25', 14, NULL, '2025-10-23 18:38:14', NULL, 1),
(37, 1, 'visit_update', 'Your visit request for \"Sample Property\" on Oct 25, 2025 at 12:00 AM has been approved!', 10, NULL, '2025-10-23 18:39:09', NULL, 0),
(38, 2, 'suspended', 'Your property listing has been suspended. Reason: Suspected fraud: BAD', 14, 'unread', '2025-10-24 13:51:52', NULL, 1),
(39, 2, 'suspended', 'Your property listing has been suspended. Reason: Poor quality photos: BAD', 14, 'unread', '2025-10-24 13:52:04', NULL, 1),
(40, 2, 'suspended', 'Your property listing has been suspended. Reason: Misleading information: BAD', 14, 'unread', '2025-10-24 14:00:54', NULL, 1),
(41, 2, 'suspended', 'Your property listing has been suspended. Reason: Poor quality photos: BAD', 14, 'unread', '2025-10-24 14:11:53', NULL, 1),
(42, 2, 'suspended', 'Your property listing has been suspended. Reason: Inappropriate content: bad', 14, 'unread', '2025-10-24 14:15:20', NULL, 1),
(43, 2, 'approved', 'Your property listing has been approved and is now live.', 14, 'unread', '2025-10-24 14:19:23', NULL, 0),
(44, 2, 'suspended', 'Your property listing has been suspended. Reason: Misleading information: e', 14, 'unread', '2025-10-24 14:19:28', NULL, 0),
(45, 2, 'approved', 'Your property listing has been approved and is now live.', 14, 'unread', '2025-10-24 14:19:39', NULL, 0),
(46, 2, 'suspended', 'Your property listing has been suspended. Reason: Misleading information: e', 14, 'unread', '2025-10-24 14:27:56', NULL, 0),
(47, 2, 'suspended', 'Your property listing has been suspended. Reason: Misleading information: e', 14, 'unread', '2025-10-24 14:48:14', NULL, 0),
(48, 4, 'suspended', 'Your property listing has been suspended. Reason: Misleading information: q', 15, 'unread', '2025-10-24 15:31:52', NULL, 0),
(49, 4, 'suspended', 'Your property listing has been suspended. Reason: Inappropriate content: e', 15, 'unread', '2025-10-24 15:38:38', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `platform_settings`
--

CREATE TABLE `platform_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `category` varchar(50) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `platform_settings`
--

INSERT INTO `platform_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `category`, `description`, `is_public`, `updated_by`, `updated_at`) VALUES
(1, 'site_name', 'HomeHub', 'string', 'general', 'Name of the platform', 1, NULL, '2025-10-24 12:53:25'),
(2, 'site_tagline', 'Find Your Perfect Home', 'string', 'general', 'Platform tagline', 1, NULL, '2025-10-24 12:53:25'),
(3, 'maintenance_mode', 'false', 'boolean', 'system', 'Enable maintenance mode', 0, NULL, '2025-10-23 15:20:11'),
(4, 'max_property_images', '10', 'number', 'properties', 'Maximum images per property', 0, NULL, '2025-10-23 15:20:11'),
(5, 'booking_commission', '5.0', 'number', 'financial', 'Platform commission percentage', 0, NULL, '2025-10-23 15:20:11'),
(6, 'support_email', 'support@homehub.com', 'string', 'contact', 'Support email address', 1, NULL, '2025-10-23 15:20:11'),
(30, 'contact_email', 'admin@homehub.com', 'string', 'contact', 'Contact email address', 1, NULL, '2025-10-24 12:53:25'),
(31, 'support_phone', '+2 (555) 123-4567', 'string', 'contact', 'Support phone number', 1, NULL, '2025-10-24 12:53:25');

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `zip_code` varchar(20) NOT NULL,
  `property_type` enum('apartment','house','condo','room','commercial') NOT NULL,
  `bedrooms` int(11) NOT NULL,
  `bathrooms` decimal(3,1) NOT NULL,
  `square_feet` int(11) DEFAULT NULL,
  `rent_amount` decimal(10,2) NOT NULL,
  `deposit_amount` decimal(10,2) NOT NULL,
  `availability_date` date DEFAULT NULL,
  `status` enum('available','suspended') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_amenities`
--

CREATE TABLE `property_amenities` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `amenity_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_demand_forecast`
--

CREATE TABLE `property_demand_forecast` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `forecast_date` date NOT NULL,
  `forecast_period` enum('week','month','quarter') NOT NULL,
  `predicted_views` int(11) DEFAULT NULL,
  `predicted_inquiries` int(11) DEFAULT NULL,
  `predicted_applications` int(11) DEFAULT NULL,
  `suggested_rent_min` decimal(10,2) DEFAULT NULL,
  `suggested_rent_optimal` decimal(10,2) DEFAULT NULL,
  `suggested_rent_max` decimal(10,2) DEFAULT NULL,
  `demand_score` decimal(5,4) DEFAULT NULL COMMENT '0-1: low to high demand',
  `competition_level` enum('low','medium','high') DEFAULT NULL,
  `days_to_rent_estimate` int(11) DEFAULT NULL,
  `model_version` varchar(20) DEFAULT NULL,
  `confidence_level` decimal(5,4) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_images`
--

CREATE TABLE `property_images` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_reservations`
--

CREATE TABLE `property_reservations` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `move_in_date` date NOT NULL,
  `lease_duration` int(11) NOT NULL,
  `reservation_fee` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `employment_status` varchar(50) DEFAULT NULL,
  `monthly_income` decimal(10,2) DEFAULT 0.00,
  `requirements` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','expired','completed','cancelled','conflict') DEFAULT 'pending',
  `reservation_date` datetime DEFAULT current_timestamp(),
  `expiration_date` date DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `completion_date` datetime DEFAULT NULL,
  `documents_submitted` tinyint(1) DEFAULT 0,
  `lease_signed` tinyint(1) DEFAULT 0,
  `payment_confirmed` tinyint(1) DEFAULT 0,
  `cancellation_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `property_reservations`
--
DELIMITER $$
CREATE TRIGGER `after_reservation_status_update` AFTER UPDATE ON `property_reservations` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO reservation_timeline (reservation_id, status, action, description)
        VALUES (NEW.id, NEW.status, CONCAT('Status changed to ', NEW.status), 
                CONCAT('Reservation status changed from ', OLD.status, ' to ', NEW.status));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `property_reviews`
--

CREATE TABLE `property_reviews` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `rating` decimal(3,1) NOT NULL,
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_vectors`
--

CREATE TABLE `property_vectors` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `price_normalized` decimal(5,4) DEFAULT NULL COMMENT '0-1 normalized price',
  `location_score` decimal(5,4) DEFAULT NULL COMMENT 'Location desirability score',
  `size_normalized` decimal(5,4) DEFAULT NULL COMMENT 'Normalized square footage',
  `quiet_score` decimal(5,4) DEFAULT NULL COMMENT '0-1: quiet area score',
  `family_friendly_score` decimal(5,4) DEFAULT NULL COMMENT '0-1: family suitability',
  `work_from_home_score` decimal(5,4) DEFAULT NULL COMMENT '0-1: WFH suitability',
  `amenities_vector` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Vector of amenity presence (0-1 for each)' CHECK (json_valid(`amenities_vector`)),
  `public_transport_score` decimal(5,4) DEFAULT NULL,
  `parking_score` decimal(5,4) DEFAULT NULL,
  `feature_vector` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Complete normalized feature vector' CHECK (json_valid(`feature_vector`)),
  `vector_version` int(11) DEFAULT 1 COMMENT 'Version of vectorization algorithm',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_views`
--

CREATE TABLE `property_views` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `views` int(11) NOT NULL DEFAULT 1,
  `view_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recommendation_cache`
--

CREATE TABLE `recommendation_cache` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `recommended_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of property IDs with scores' CHECK (json_valid(`recommended_properties`)),
  `algorithm_version` varchar(20) DEFAULT NULL,
  `confidence_score` decimal(5,4) DEFAULT NULL COMMENT 'Overall confidence in recommendations',
  `based_on_interactions` int(11) DEFAULT NULL COMMENT 'Number of interactions used',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_valid` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rental_analytics`
--

CREATE TABLE `rental_analytics` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `views_count` int(11) DEFAULT 0,
  `inquiries_count` int(11) DEFAULT 0,
  `visit_requests_count` int(11) DEFAULT 0,
  `applications_count` int(11) DEFAULT 0,
  `view_to_inquiry_rate` decimal(5,4) DEFAULT NULL,
  `inquiry_to_visit_rate` decimal(5,4) DEFAULT NULL,
  `visit_to_application_rate` decimal(5,4) DEFAULT NULL,
  `average_market_price` decimal(10,2) DEFAULT NULL COMMENT 'Average price in area',
  `days_on_market` int(11) DEFAULT NULL,
  `was_rented` tinyint(1) DEFAULT 0,
  `final_rent_amount` decimal(10,2) DEFAULT NULL,
  `month` int(11) DEFAULT NULL,
  `quarter` int(11) DEFAULT NULL,
  `season` enum('spring','summer','fall','winter') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reported_content`
--

CREATE TABLE `reported_content` (
  `id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `reporter_type` enum('tenant','landlord') NOT NULL,
  `target_type` enum('property','user','message','review') NOT NULL,
  `target_id` int(11) NOT NULL,
  `reason` enum('spam','inappropriate','fraud','fake_listing','harassment','other') NOT NULL,
  `description` text DEFAULT NULL,
  `evidence` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`evidence`)),
  `status` enum('pending','under_review','resolved','dismissed') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_documents`
--

CREATE TABLE `reservation_documents` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `document_path` varchar(255) NOT NULL,
  `uploaded_date` datetime DEFAULT current_timestamp(),
  `verified` tinyint(1) DEFAULT 0,
  `verified_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_timeline`
--

CREATE TABLE `reservation_timeline` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_properties`
--

CREATE TABLE `saved_properties` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `search_queries`
--

CREATE TABLE `search_queries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `query_text` text DEFAULT NULL,
  `filters_applied` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'All filters applied in search' CHECK (json_valid(`filters_applied`)),
  `results_count` int(11) DEFAULT NULL,
  `results_clicked` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of property IDs clicked' CHECK (json_valid(`results_clicked`)),
  `searched_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `similarity_scores`
--

CREATE TABLE `similarity_scores` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `cosine_similarity` decimal(5,4) DEFAULT NULL COMMENT '0-1: similarity score',
  `feature_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Breakdown by feature category' CHECK (json_valid(`feature_breakdown`)),
  `match_score` decimal(5,4) DEFAULT NULL COMMENT '0-1: final weighted match score',
  `match_percentage` int(11) DEFAULT NULL COMMENT '0-100: user-friendly percentage',
  `rank_for_tenant` int(11) DEFAULT NULL COMMENT 'Rank among all properties for this tenant',
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_valid` tinyint(1) DEFAULT 1 COMMENT 'FALSE if preferences/property changed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'HomeHub', '2025-10-23 17:18:31', '2025-10-23 17:18:31'),
(2, 'site_tagline', 'Find Your Perfect Home', '2025-10-23 17:18:31', '2025-10-23 17:18:31'),
(3, 'maintenance_mode', '0', '2025-10-23 17:18:31', '2025-10-23 17:24:51'),
(4, 'maintenance_message', 'Testing maintenance mode - please check back in a few minutes!', '2025-10-23 17:18:31', '2025-10-23 17:24:10'),
(5, 'admin_email', 'admin@homehub.com', '2025-10-23 17:18:31', '2025-10-23 17:18:31');

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `income` decimal(10,2) DEFAULT NULL,
  `preferred_location` varchar(255) DEFAULT NULL,
  `max_budget` decimal(10,2) DEFAULT NULL,
  `move_in_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenants`
--

INSERT INTO `tenants` (`id`, `user_id`, `date_of_birth`, `occupation`, `income`, `preferred_location`, `max_budget`, `move_in_date`) VALUES
(1, 1, '1990-01-15', 'Software Developer', 75000.00, 'Downtown', 1800.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tenant_preferences`
--

CREATE TABLE `tenant_preferences` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `min_budget` decimal(10,2) DEFAULT 0.00,
  `max_budget` decimal(10,2) NOT NULL,
  `budget_flexibility` int(11) DEFAULT 10 COMMENT 'Flexibility percentage',
  `preferred_cities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of preferred cities' CHECK (json_valid(`preferred_cities`)),
  `preferred_areas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of specific areas' CHECK (json_valid(`preferred_areas`)),
  `max_distance_from_work` decimal(5,2) DEFAULT NULL COMMENT 'Max distance in km',
  `work_location_lat` decimal(10,8) DEFAULT NULL,
  `work_location_lng` decimal(11,8) DEFAULT NULL,
  `preferred_property_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of property types' CHECK (json_valid(`preferred_property_types`)),
  `min_bedrooms` int(11) DEFAULT 1,
  `max_bedrooms` int(11) DEFAULT 5,
  `min_bathrooms` decimal(3,1) DEFAULT 1.0,
  `lifestyle_quiet_active` int(11) DEFAULT 5 COMMENT '1-10 scale: 1=quiet, 10=active',
  `lifestyle_family_single` int(11) DEFAULT 5 COMMENT '1-10 scale: 1=single, 10=family',
  `lifestyle_work_home` int(11) DEFAULT 5 COMMENT '1-10 scale: 1=work from office, 10=work from home',
  `pet_friendly_required` tinyint(1) DEFAULT 0,
  `furnished_preference` enum('furnished','unfurnished','either') DEFAULT 'either',
  `amenities_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Object with amenity weights' CHECK (json_valid(`amenities_preferences`)),
  `near_public_transport` tinyint(1) DEFAULT 0,
  `parking_required` tinyint(1) DEFAULT 0,
  `lease_duration_min` int(11) DEFAULT 6 COMMENT 'Minimum months',
  `lease_duration_max` int(11) DEFAULT 12 COMMENT 'Maximum months',
  `move_in_date` date DEFAULT NULL,
  `preference_vector` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Normalized vector representation' CHECK (json_valid(`preference_vector`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `phone`, `status`, `profile_image`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'tenant@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tenant', 'Profile', '555-123-4567', 'active', NULL, '2025-10-14 13:55:12', '2025-10-24 15:25:53', '2025-10-24 15:25:53'),
(2, 'landlord@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Landlord', 'Profile', '555-987-6543', 'active', NULL, '2025-10-14 13:55:12', '2025-10-24 15:30:59', '2025-10-24 15:30:59'),
(4, 'mayriellej@gmail.com', '$2y$10$Vpqro8CMVznFvOgGuW9jyu5E3c0R.KpF9IASCkLoxDZ7o1PQNqXVe', 'Mayrielle', 'Joy Latigo', '09193364858', 'active', NULL, '2025-10-24 15:24:42', '2025-10-24 15:34:05', '2025-10-24 15:34:05');

-- --------------------------------------------------------

--
-- Table structure for table `user_interactions`
--

CREATE TABLE `user_interactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `interaction_type` enum('view','save','unsave','contact','reserve','visit_request','search','filter_apply','share','review') NOT NULL,
  `weight` decimal(3,2) DEFAULT 1.00 COMMENT 'Importance weight: view=1.0, save=2.0, reserve=5.0',
  `interaction_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional context data' CHECK (json_valid(`interaction_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_similarity`
--

CREATE TABLE `user_similarity` (
  `id` int(11) NOT NULL,
  `user_id_1` int(11) NOT NULL,
  `user_id_2` int(11) NOT NULL,
  `interaction_similarity` decimal(5,4) DEFAULT NULL COMMENT 'Based on interaction patterns',
  `preference_similarity` decimal(5,4) DEFAULT NULL COMMENT 'Based on preferences',
  `overall_similarity` decimal(5,4) DEFAULT NULL COMMENT 'Weighted combination',
  `common_properties_viewed` int(11) DEFAULT 0,
  `common_properties_saved` int(11) DEFAULT 0,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_valid` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_action` (`admin_id`,`action`),
  ADD KEY `idx_target` (`target_type`,`target_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `landlord_id` (`landlord_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `booking_requests`
--
ALTER TABLE `booking_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `booking_visits`
--
ALTER TABLE `booking_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `browsing_history`
--
ALTER TABLE `browsing_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_time` (`user_id`,`viewed_at`),
  ADD KEY `idx_property_time` (`property_id`,`viewed_at`);

--
-- Indexes for table `landlords`
--
ALTER TABLE `landlords`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `related_property_id` (`related_property_id`),
  ADD KEY `fk_tenant` (`tenant_id`),
  ADD KEY `fk_landlord` (`landlord_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `platform_settings`
--
ALTER TABLE `platform_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `landlord_id` (`landlord_id`),
  ADD KEY `idx_status_created` (`status`,`created_at`),
  ADD KEY `idx_city_price` (`city`,`rent_amount`),
  ADD KEY `idx_bedrooms_bathrooms` (`bedrooms`,`bathrooms`);

--
-- Indexes for table `property_amenities`
--
ALTER TABLE `property_amenities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `property_demand_forecast`
--
ALTER TABLE `property_demand_forecast`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_property_forecast` (`property_id`,`forecast_date`,`forecast_period`),
  ADD KEY `idx_property_forecast` (`property_id`,`forecast_date`);

--
-- Indexes for table `property_images`
--
ALTER TABLE `property_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `property_reservations`
--
ALTER TABLE `property_reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `idx_reservation_status` (`status`),
  ADD KEY `idx_reservation_dates` (`reservation_date`,`expiration_date`),
  ADD KEY `idx_tenant_reservations` (`tenant_id`,`status`);

--
-- Indexes for table `property_reviews`
--
ALTER TABLE `property_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `property_vectors`
--
ALTER TABLE `property_vectors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_property` (`property_id`);

--
-- Indexes for table `property_views`
--
ALTER TABLE `property_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `property_id` (`property_id`,`view_date`);

--
-- Indexes for table `recommendation_cache`
--
ALTER TABLE `recommendation_cache`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_valid` (`user_id`,`is_valid`,`expires_at`);

--
-- Indexes for table `rental_analytics`
--
ALTER TABLE `rental_analytics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_property_period` (`property_id`,`period_start`,`period_end`),
  ADD KEY `idx_time_metrics` (`period_start`,`was_rented`);

--
-- Indexes for table `reported_content`
--
ALTER TABLE `reported_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_target` (`target_type`,`target_id`);

--
-- Indexes for table `reservation_documents`
--
ALTER TABLE `reservation_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`);

--
-- Indexes for table `reservation_timeline`
--
ALTER TABLE `reservation_timeline`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `saved_properties`
--
ALTER TABLE `saved_properties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_saved_property` (`tenant_id`,`property_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `search_queries`
--
ALTER TABLE `search_queries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_search` (`user_id`,`searched_at`);
ALTER TABLE `search_queries` ADD FULLTEXT KEY `idx_query_text` (`query_text`);

--
-- Indexes for table `similarity_scores`
--
ALTER TABLE `similarity_scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tenant_property` (`tenant_id`,`property_id`),
  ADD KEY `idx_tenant_score` (`tenant_id`,`match_score`,`is_valid`),
  ADD KEY `idx_property_score` (`property_id`,`match_score`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_setting` (`setting_key`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tenant_preferences`
--
ALTER TABLE `tenant_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tenant` (`tenant_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_interactions`
--
ALTER TABLE `user_interactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_interaction` (`user_id`,`interaction_type`,`created_at`),
  ADD KEY `idx_property_interaction` (`property_id`,`interaction_type`);

--
-- Indexes for table `user_similarity`
--
ALTER TABLE `user_similarity`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_pair` (`user_id_1`,`user_id_2`),
  ADD KEY `idx_user1_similarity` (`user_id_1`,`overall_similarity`,`is_valid`),
  ADD KEY `idx_user2_similarity` (`user_id_2`,`overall_similarity`,`is_valid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_requests`
--
ALTER TABLE `booking_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `booking_visits`
--
ALTER TABLE `booking_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `browsing_history`
--
ALTER TABLE `browsing_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `landlords`
--
ALTER TABLE `landlords`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `platform_settings`
--
ALTER TABLE `platform_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `property_amenities`
--
ALTER TABLE `property_amenities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `property_demand_forecast`
--
ALTER TABLE `property_demand_forecast`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_images`
--
ALTER TABLE `property_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `property_reservations`
--
ALTER TABLE `property_reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `property_reviews`
--
ALTER TABLE `property_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_vectors`
--
ALTER TABLE `property_vectors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_views`
--
ALTER TABLE `property_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `recommendation_cache`
--
ALTER TABLE `recommendation_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rental_analytics`
--
ALTER TABLE `rental_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reported_content`
--
ALTER TABLE `reported_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservation_documents`
--
ALTER TABLE `reservation_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservation_timeline`
--
ALTER TABLE `reservation_timeline`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `saved_properties`
--
ALTER TABLE `saved_properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `search_queries`
--
ALTER TABLE `search_queries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `similarity_scores`
--
ALTER TABLE `similarity_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tenant_preferences`
--
ALTER TABLE `tenant_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_interactions`
--
ALTER TABLE `user_interactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_similarity`
--
ALTER TABLE `user_similarity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD CONSTRAINT `ai_recommendations_ibfk_1` FOREIGN KEY (`landlord_id`) REFERENCES `landlords` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_requests`
--
ALTER TABLE `booking_requests`
  ADD CONSTRAINT `booking_requests_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_requests_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_visits`
--
ALTER TABLE `booking_visits`
  ADD CONSTRAINT `booking_visits_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_visits_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `browsing_history`
--
ALTER TABLE `browsing_history`
  ADD CONSTRAINT `browsing_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `browsing_history_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `landlords`
--
ALTER TABLE `landlords`
  ADD CONSTRAINT `landlords_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_landlord` FOREIGN KEY (`landlord_id`) REFERENCES `landlords` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`related_property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`landlord_id`) REFERENCES `landlords` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_amenities`
--
ALTER TABLE `property_amenities`
  ADD CONSTRAINT `property_amenities_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_demand_forecast`
--
ALTER TABLE `property_demand_forecast`
  ADD CONSTRAINT `property_demand_forecast_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_images`
--
ALTER TABLE `property_images`
  ADD CONSTRAINT `property_images_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_reservations`
--
ALTER TABLE `property_reservations`
  ADD CONSTRAINT `property_reservations_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `property_reservations_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_reviews`
--
ALTER TABLE `property_reviews`
  ADD CONSTRAINT `property_reviews_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `property_reviews_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_vectors`
--
ALTER TABLE `property_vectors`
  ADD CONSTRAINT `property_vectors_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_views`
--
ALTER TABLE `property_views`
  ADD CONSTRAINT `property_views_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recommendation_cache`
--
ALTER TABLE `recommendation_cache`
  ADD CONSTRAINT `recommendation_cache_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rental_analytics`
--
ALTER TABLE `rental_analytics`
  ADD CONSTRAINT `rental_analytics_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservation_documents`
--
ALTER TABLE `reservation_documents`
  ADD CONSTRAINT `reservation_documents_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `property_reservations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservation_timeline`
--
ALTER TABLE `reservation_timeline`
  ADD CONSTRAINT `reservation_timeline_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `property_reservations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservation_timeline_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `saved_properties`
--
ALTER TABLE `saved_properties`
  ADD CONSTRAINT `saved_properties_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_properties_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `search_queries`
--
ALTER TABLE `search_queries`
  ADD CONSTRAINT `search_queries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `similarity_scores`
--
ALTER TABLE `similarity_scores`
  ADD CONSTRAINT `similarity_scores_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `similarity_scores_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tenants`
--
ALTER TABLE `tenants`
  ADD CONSTRAINT `tenants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tenant_preferences`
--
ALTER TABLE `tenant_preferences`
  ADD CONSTRAINT `tenant_preferences_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_interactions`
--
ALTER TABLE `user_interactions`
  ADD CONSTRAINT `user_interactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_interactions_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_similarity`
--
ALTER TABLE `user_similarity`
  ADD CONSTRAINT `user_similarity_ibfk_1` FOREIGN KEY (`user_id_1`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_similarity_ibfk_2` FOREIGN KEY (`user_id_2`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
