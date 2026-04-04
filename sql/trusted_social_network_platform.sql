-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 04, 2026 at 10:42 PM
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
-- Database: `trusted_social_network_platform`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_events`
--

CREATE TABLE `academic_events` (
  `event_id` int(10) UNSIGNED NOT NULL,
  `owner_user_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED DEFAULT NULL,
  `group_id` int(10) UNSIGNED DEFAULT NULL,
  `event_type` enum('lecture','exam','meeting','task','other') NOT NULL DEFAULT 'other',
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime DEFAULT NULL,
  `all_day` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_events`
--

INSERT INTO `academic_events` (`event_id`, `owner_user_id`, `course_id`, `group_id`, `event_type`, `title`, `description`, `location`, `start_at`, `end_at`, `all_day`, `created_at`, `updated_at`) VALUES
(1, 16, NULL, NULL, 'exam', 'ميرا', NULL, NULL, '0000-00-00 00:00:00', NULL, 1, '2026-03-17 23:09:39', '2026-03-17 23:09:39'),
(2, 17, NULL, NULL, 'lecture', 'محاضرة تعويضيه', NULL, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '2026-03-18 01:05:32', '2026-03-18 01:05:32'),
(3, 17, NULL, NULL, 'lecture', 'محاضرة تعويضية', NULL, NULL, '2026-03-21 20:03:00', '2026-03-23 22:03:00', 0, '2026-03-21 20:03:51', '2026-03-21 20:03:51');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` enum('login','logout','login_failed','register','post_create','post_delete','post_edit','file_upload','file_delete','report_submit','account_suspend','account_delete','permission_change','password_change') NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'Admin login', '192.168.1.1', NULL, '2026-03-08 02:46:00'),
(2, 2, 'login', 'Supervisor login', '192.168.1.2', NULL, '2026-03-08 02:46:02'),
(3, 1, 'account_suspend', 'Suspended student_suspended for policy violation', '192.168.1.1', NULL, '2026-03-08 02:46:03'),
(4, 2, 'report_submit', 'Harassment report handled and closed', '192.168.1.2', NULL, '2026-03-08 02:46:04'),
(5, 1, 'register', 'Database setup complete - system ready', '127.0.0.1', NULL, '2026-03-08 02:46:06'),
(6, NULL, 'login_failed', 'Failed login attempt for email: admin@unilink.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 02:48:23'),
(7, NULL, 'login_failed', 'Failed login attempt for email: admin@unilink.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 02:48:48'),
(8, NULL, 'login_failed', 'Failed login attempt for email: admin@unilink.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 02:56:23'),
(9, 1, 'login', 'User logged in to admin panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 03:01:53'),
(10, 1, 'login', 'User logged in to admin panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-08 03:23:15'),
(11, 1, 'login', 'User logged in to admin panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 02:09:05'),
(12, 1, 'login', 'User logged in to admin panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 02:09:48'),
(13, 1, 'login', 'User logged in to admin panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 14:08:53'),
(14, 1, 'logout', 'User logged out from admin panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 14:10:49'),
(15, 1, 'login', 'User logged in to admin panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 14:12:58'),
(16, 1, 'account_suspend', 'تم تعليق الحساب', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 14:28:13'),
(17, 1, 'login', 'User logged in to admin panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-13 20:00:11'),
(18, 1, 'logout', 'User logged out from admin panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-13 22:04:04'),
(19, 1, 'logout', 'User logged out from admin panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-13 23:16:07'),
(20, 10, 'login', 'OTP verified — Admin panel login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-13 23:18:52'),
(21, 9, 'login', 'OTP verified — Admin panel login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-13 23:27:06'),
(22, 9, 'login', 'OTP verified — Admin panel login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 23:29:46'),
(23, 9, 'logout', 'User logged out from admin panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-14 00:02:58'),
(24, 9, 'login', 'OTP verified — Admin panel login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-14 00:03:36'),
(25, 9, 'logout', 'User logged out from admin panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-14 00:08:40'),
(26, 9, 'logout', 'User logged out from admin panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-14 00:10:53'),
(27, 10, 'logout', 'User logged out from admin panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-14 00:20:00'),
(28, 10, 'login', 'OTP verified — Admin panel login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-14 00:22:15'),
(29, 1, 'logout', 'User logged out from admin panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-14 01:47:29'),
(30, NULL, 'register', 'User registered and verified', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-14 02:38:42'),
(31, 10, 'login', 'OTP verified — Admin panel login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-15 22:42:08'),
(32, 10, 'account_suspend', 'تم تعليق الحساب', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-15 22:46:41'),
(33, 10, 'account_suspend', 'تم تعليق الحساب', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-15 22:46:52'),
(34, 10, 'account_suspend', 'تم تفعيل الحساب', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-15 22:47:01'),
(35, 10, 'account_suspend', 'تم تفعيل الحساب', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-15 22:47:44'),
(36, 10, 'post_edit', 'تم تعديل بيانات المستخدم: احمد احمد', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-15 22:48:20'),
(37, 10, 'account_suspend', 'تم تعليق الحساب', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-16 23:36:06'),
(38, 10, 'login', 'OTP verified — Admin panel login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 23:54:00'),
(39, 10, 'account_delete', 'تم حذف المستخدم رقم 14 بشكل جذري مع كافة بياناته.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 00:02:51'),
(40, 10, 'account_delete', 'تم حذف المستخدم رقم 13 بشكل جذري مع كافة بياناته.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 00:06:00'),
(41, 10, 'account_delete', 'تم حذف المستخدم رقم 15 بشكل جذري مع كافة بياناته.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 00:10:13'),
(42, 10, 'account_delete', 'تم حذف المستخدم رقم 12 بشكل جذري مع كافة بياناته.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 00:24:30'),
(43, NULL, 'file_upload', 'رفع ملف: Kotobati - السلام_عليك_يا_صاحبي_،_أدهم_شرقاوي.pdf', '192.168.1.101', 'Dart/3.6 (dart:io)', '2026-03-17 23:02:19'),
(44, NULL, 'file_upload', 'رفع ملف: MEERA_TASK_REPORT.pdf', '192.168.1.102', 'Dart/3.6 (dart:io)', '2026-03-18 00:54:02'),
(45, NULL, 'report_submit', 'تقديم بلاغ عبر API', '192.168.43.1', 'Dart/3.6 (dart:io)', '2026-03-21 19:32:34'),
(46, NULL, 'report_submit', 'تقديم بلاغ عبر API', '192.168.43.1', 'Dart/3.6 (dart:io)', '2026-03-21 19:32:49'),
(47, NULL, 'login_failed', 'Wrong OTP', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-30 18:24:00'),
(48, 10, 'login', 'OTP login success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-30 18:24:39');

-- --------------------------------------------------------

--
-- Table structure for table `branding_settings`
--

CREATE TABLE `branding_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `platform_name` varchar(120) NOT NULL DEFAULT 'Trusted Social Network Platform',
  `platform_tagline` varchar(255) NOT NULL DEFAULT 'منصة التواصل الأكاديمي الموثوقة',
  `primary_color` varchar(9) NOT NULL DEFAULT '#004D8C',
  `secondary_color` varchar(9) NOT NULL DEFAULT '#007786',
  `accent_color` varchar(9) NOT NULL DEFAULT '#00B4D8',
  `background_color` varchar(9) NOT NULL DEFAULT '#FFFFFF',
  `text_color` varchar(9) NOT NULL DEFAULT '#1E293B',
  `button_primary_color` varchar(9) NOT NULL DEFAULT '#004D8C',
  `button_text_color` varchar(9) NOT NULL DEFAULT '#FFFFFF',
  `card_bg_color` varchar(9) NOT NULL DEFAULT '#F8FAFC',
  `input_bg_color` varchar(9) NOT NULL DEFAULT '#FFFFFF',
  `input_border_color` varchar(9) NOT NULL DEFAULT '#CBD5E1',
  `font_family` varchar(80) NOT NULL DEFAULT 'Cairo',
  `logo_path` varchar(512) DEFAULT NULL COMMENT 'مسار الشعار نسبة إلى جذر المشروع',
  `active_template_key` varchar(60) NOT NULL DEFAULT 'deep_blue',
  `updated_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'user_id للمدير الذي آخر تعديل',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='إعدادات الهوية البصرية للمنصة – صف واحد دائماً (id=1)';

--
-- Dumping data for table `branding_settings`
--

INSERT INTO `branding_settings` (`id`, `platform_name`, `platform_tagline`, `primary_color`, `secondary_color`, `accent_color`, `background_color`, `text_color`, `button_primary_color`, `button_text_color`, `card_bg_color`, `input_bg_color`, `input_border_color`, `font_family`, `logo_path`, `active_template_key`, `updated_by`, `updated_at`) VALUES
(1, 'Trusted Social Network Platform', 'منصة التواصل الأكاديمي الموثوقة', '#004D8C', '#007786', '#00B4D8', '#FFFFFF', '#1E293B', '#004D8C', '#FFFFFF', '#F8FAFC', '#FFFFFF', '#CBD5E1', 'Cairo', NULL, 'deep_blue', NULL, '2026-04-04 21:27:29');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(10) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(200) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fcm_tokens`
--

CREATE TABLE `fcm_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` text NOT NULL,
  `device_type` enum('android','ios','web') NOT NULL DEFAULT 'android',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fcm_tokens`
--

INSERT INTO `fcm_tokens` (`id`, `user_id`, `token`, `device_type`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 16, 'ezbEmUY2QPyg3OHlieCzWl:APA91bHQmtXsuEcRTq-AmNx_bjxL3kKTyOyFMJwD7Q0M5Qj5GPBMoEvRXstSQsvbei18dqSxk-UA9M2jHItcdyDZiRa-R-aV9Qtv0wcuU1ZmiiFEoBQeKX0', 'android', 1, '2026-03-17 03:31:53', '2026-03-17 03:31:53'),
(4, 16, 'dqujTWcqTUqimLKSMvB27B:APA91bGBvpXA89nop8D1-IUaCDA6Vm8v6wvcS_Hv_XJR8jKxy_quAAUrOK2ELXE2uGLRbaKx-ULL_i-dJ53x9agPVtwvyKZx0kMyr7-qWEz9aGXSOIg5xSw', 'android', 1, '2026-03-17 22:57:26', '2026-03-17 22:57:26'),
(5, 17, 'e_lDSZSoTvq-ieU-w2yFMJ:APA91bFVtxChnAsJiMhDmAqkNjQcwkZdNkSULuNEfxvikeTUaR3Xn87Riy70bv890F2cYzSrn8nj4d6TTk2vdaEj0NlqeZgQB4Wv3nW_KwS_DJUx45opLSg', 'android', 1, '2026-03-18 00:44:23', '2026-03-18 00:44:23'),
(6, 16, 'd_DajGJiT-ao1wKP3Ad24D:APA91bEPcdegnBRh2tK8epPdrjHArKsIUZ9YFh2fpwhLDG3jURxiR4_KYm9LghZQKQ8gdNwBXjSEZZl--xsGlewbMjEV9bJsNuXfMTSbPbNJ3futqcvp3Qo', 'android', 0, '2026-03-21 01:22:41', '2026-03-21 01:40:30'),
(7, 17, 'd_DajGJiT-ao1wKP3Ad24D:APA91bFf4ODzzIOM7mB5XuUJOuMmLqdcBM9EHODW26QYnH_iukPVv1yn9IuYAtFxtl2gRVCgcRhQ07WVs0U-QYmKuMvq-FgxELnOF3a8488UdNOiNuNAOUw', 'android', 1, '2026-03-21 16:26:30', '2026-03-21 16:26:30'),
(8, 17, 'fixZ6fP3Ta2dmuzYBXwm_a:APA91bFJHjLb9yAxOLzH6zg9zgcHTrGaaEx3pCJza1QZ6jP2a1lIjYwFtlRUXwimaR_EOSQwuGEoJcTfIsjU5BK_Ja0hLHUPPsicXuGzNQHk3GqalrS9F6U', 'android', 1, '2026-03-21 20:59:31', '2026-03-21 20:59:31'),
(9, 16, 'd_DajGJiT-ao1wKP3Ad24D:APA91bFveexlChMgAxAN8I5RpqFcU0GCgWkFiAItsQYnvmAWTfbE0sKg_xYsCwWGB0KdebI5L-Q7yZfUkJbLGGperiqQUn71BNb1xr8KsjhI2h08pawsSn8', 'android', 1, '2026-03-21 21:01:57', '2026-03-21 21:01:57');

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `file_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED DEFAULT NULL,
  `course_id` int(10) UNSIGNED DEFAULT NULL,
  `group_id` int(10) UNSIGNED DEFAULT NULL,
  `category` enum('lecture','assignment','reference','other') NOT NULL DEFAULT 'other',
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_type` enum('pdf','image','presentation','archive','video','other') NOT NULL DEFAULT 'other',
  `file_size` bigint(20) UNSIGNED NOT NULL,
  `storage_path` varchar(500) NOT NULL,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `download_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`file_id`, `user_id`, `post_id`, `course_id`, `group_id`, `category`, `title`, `description`, `original_name`, `stored_name`, `file_type`, `file_size`, `storage_path`, `is_encrypted`, `download_count`, `created_at`) VALUES
(1, 16, NULL, NULL, NULL, 'lecture', 'تصميم منطقي', NULL, 'Kotobati - السلام_عليك_يا_صاحبي_،_أدهم_شرقاوي.pdf', 'f_69b9dd6fd0c548.00311943.pdf', 'pdf', 2565067, 'uploads/files/2026/03/f_69b9dd6fd0c548.00311943.pdf', 0, 0, '2026-03-17 23:02:19'),
(2, 17, NULL, NULL, NULL, 'other', 'my task', NULL, 'MEERA_TASK_REPORT.pdf', 'f_69b9f79d9def68.88782765.pdf', 'pdf', 564758, 'uploads/files/2026/03/f_69b9f79d9def68.88782765.pdf', 0, 0, '2026-03-18 00:54:01');

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `group_id` int(10) UNSIGNED NOT NULL,
  `group_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('course','department','activity','administrative') NOT NULL DEFAULT 'course',
  `course_id` int(10) UNSIGNED DEFAULT NULL,
  `privacy` enum('public','private','restricted') NOT NULL DEFAULT 'private',
  `created_by` int(10) UNSIGNED NOT NULL,
  `members_count` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `status` enum('active','hidden','deleted') NOT NULL DEFAULT 'active',
  `cover_url` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`group_id`, `group_name`, `description`, `type`, `course_id`, `privacy`, `created_by`, `members_count`, `status`, `cover_url`, `created_at`, `updated_at`) VALUES
(1, 'Data Structures Course', 'Data Structures students group - Semester 1 2024', 'course', NULL, 'private', 3, 3, 'active', NULL, '2026-03-08 02:45:24', '2026-03-08 02:45:24'),
(2, 'Computer Science Dept', 'General group for CS students and faculty', 'department', NULL, 'restricted', 1, 5, 'active', NULL, '2026-03-08 02:45:26', '2026-03-08 02:45:26'),
(3, 'Programming Club', 'University programming club - activities and workshops', 'activity', NULL, 'public', 2, 2, 'active', NULL, '2026-03-08 02:45:29', '2026-03-08 02:45:29'),
(4, 'cs', 'قناه خاصة بالأمن السيبراني', 'course', NULL, 'public', 17, 1, 'active', NULL, '2026-03-21 19:29:57', '2026-03-21 19:29:57');

-- --------------------------------------------------------

--
-- Table structure for table `group_auto_join_rules`
--

CREATE TABLE `group_auto_join_rules` (
  `rule_id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `academic_id_prefix` varchar(20) DEFAULT NULL,
  `year_level` int(11) DEFAULT NULL,
  `batch_year` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `membership_id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `member_role` enum('owner','moderator','member') NOT NULL DEFAULT 'member',
  `joined_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`membership_id`, `group_id`, `user_id`, `member_role`, `joined_at`) VALUES
(8, 2, 16, 'member', '2026-03-17 22:58:58'),
(10, 1, 16, 'member', '2026-03-17 22:59:53'),
(12, 1, 17, 'member', '2026-03-18 00:45:29'),
(13, 2, 17, 'member', '2026-03-18 00:46:24'),
(14, 3, 17, 'member', '2026-03-18 00:46:25'),
(15, 4, 17, 'owner', '2026-03-21 19:29:57');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `msg_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `receiver_id` int(10) UNSIGNED NOT NULL,
  `content` text NOT NULL,
  `type` enum('text','image','file') NOT NULL DEFAULT 'text',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `file_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`msg_id`, `sender_id`, `receiver_id`, `content`, `type`, `is_read`, `file_id`, `created_at`) VALUES
(5, 17, 16, 'الوة', 'text', 1, NULL, '2026-03-21 23:59:41');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('new_message','new_post','post_like','post_comment','group_invite','report_update','announcement','account_warning') NOT NULL,
  `content` varchar(500) NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED DEFAULT NULL,
  `content` text NOT NULL,
  `type` enum('post','announcement','question','lecture') NOT NULL DEFAULT 'post',
  `visibility` enum('public','group','private') NOT NULL DEFAULT 'public',
  `likes_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `comments_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_flagged` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','deleted') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`post_id`, `user_id`, `group_id`, `content`, `type`, `visibility`, `likes_count`, `comments_count`, `is_flagged`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 'First exam is next Sunday. Review chapters 1 to 4.', 'announcement', 'group', 12, 5, 0, 'active', '2026-03-08 02:45:32', '2026-03-08 02:45:32'),
(2, 5, NULL, 'Can anyone help me understand the Dijkstra algorithm?', 'question', 'public', 3, 8, 0, 'active', '2026-03-08 02:45:34', '2026-03-08 02:45:34'),
(3, 4, 2, 'Reminder: Project submission deadline is end of next week.', 'announcement', 'group', 20, 2, 0, 'active', '2026-03-08 02:45:36', '2026-03-08 02:45:36'),
(4, 1, NULL, 'Welcome to UniLink platform! We are glad to have you.', 'post', 'public', 45, 12, 0, 'active', '2026-03-08 02:45:39', '2026-03-08 02:45:39'),
(5, 6, 3, 'JavaScript workshop this Thursday at 6pm in the training room.', 'announcement', 'group', 8, 3, 0, 'active', '2026-03-08 02:45:43', '2026-03-08 02:45:43'),
(8, 16, NULL, 'منشور تجريبي', 'post', 'public', 0, 0, 0, 'active', '2026-03-18 01:28:06', '2026-03-18 01:28:06'),
(9, 16, NULL, 'منشور تجريبي', 'post', 'public', 0, 0, 0, 'active', '2026-03-18 01:28:06', '2026-03-18 01:28:06'),
(10, 16, NULL, 'منشور', 'post', 'public', 0, 0, 0, 'active', '2026-03-18 01:28:40', '2026-03-18 01:28:40');

-- --------------------------------------------------------

--
-- Table structure for table `post_comments`
--

CREATE TABLE `post_comments` (
  `comment_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `like_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `professor_courses`
--

CREATE TABLE `professor_courses` (
  `id` int(10) UNSIGNED NOT NULL,
  `professor_user_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(10) UNSIGNED NOT NULL,
  `reporter_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED DEFAULT NULL,
  `reported_user_id` int(10) UNSIGNED DEFAULT NULL,
  `reason` enum('spam','harassment','inappropriate_content','misinformation','copyright_violation','other') NOT NULL DEFAULT 'other',
  `details` text DEFAULT NULL,
  `status` enum('pending','under_review','resolved','rejected') NOT NULL DEFAULT 'pending',
  `handled_by` int(10) UNSIGNED DEFAULT NULL,
  `action_taken` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `reporter_id`, `post_id`, `reported_user_id`, `reason`, `details`, `status`, `handled_by`, `action_taken`, `created_at`, `updated_at`) VALUES
(1, 5, NULL, 8, 'harassment', 'User sends abusive messages.', 'resolved', 2, NULL, '2026-03-08 02:45:48', '2026-03-08 02:45:48'),
(2, 6, NULL, NULL, 'spam', 'Post contains unauthorized promotional links.', 'under_review', 2, NULL, '2026-03-08 02:45:53', '2026-03-08 02:45:53'),
(3, 7, NULL, 6, 'inappropriate_content', 'Inappropriate content in comments.', 'pending', NULL, NULL, '2026-03-08 02:45:55', '2026-03-08 02:45:55'),
(4, 5, NULL, NULL, 'misinformation', 'Wrong information about the upcoming exam.', 'rejected', 1, NULL, '2026-03-08 02:45:59', '2026-03-08 02:45:59'),
(5, 17, 10, NULL, 'copyright_violation', NULL, 'pending', NULL, NULL, '2026-03-21 19:32:34', '2026-03-21 19:32:34'),
(6, 17, 10, NULL, 'inappropriate_content', NULL, 'pending', NULL, NULL, '2026-03-21 19:32:49', '2026-03-21 19:32:49');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `subject` varchar(200) NOT NULL,
  `status` enum('open','pending','closed') NOT NULL DEFAULT 'open',
  `priority` enum('low','normal','high') NOT NULL DEFAULT 'normal',
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `closed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_ticket_messages`
--

CREATE TABLE `support_ticket_messages` (
  `msg_id` int(10) UNSIGNED NOT NULL,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','professor','admin','supervisor') NOT NULL DEFAULT 'student',
  `full_name` varchar(150) NOT NULL,
  `academic_id` varchar(30) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `year_level` int(11) DEFAULT NULL,
  `batch_year` int(11) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','suspended','deleted') NOT NULL DEFAULT 'active',
  `otp_code` varchar(255) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `remember_token_hash` varchar(255) DEFAULT NULL,
  `remember_token_expires_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `role`, `full_name`, `academic_id`, `department`, `year_level`, `batch_year`, `avatar_url`, `is_verified`, `status`, `otp_code`, `otp_expires_at`, `remember_token_hash`, `remember_token_expires_at`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@unilink.local', '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'admin', 'System Administrator', 'ADM-001', 'IT Department', NULL, NULL, NULL, 1, 'active', '$2y$10$jQ/sI/XZ.mLDguq0HLyZgO/KVQoSskpXCKHekaJMKj3C.Z8Mj74Om', '2026-03-15 09:29:35', NULL, NULL, '2026-03-13 20:00:07', '2026-03-08 02:45:17', '2026-03-15 21:29:51'),
(2, 'supervisor01', 'supervisor@unilink.local', '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'supervisor', 'Main Supervisor', 'SUP-001', 'Student Affairs', NULL, NULL, NULL, 1, 'active', NULL, NULL, NULL, NULL, NULL, '2026-03-08 02:45:17', '2026-03-08 03:01:13'),
(3, 'professor01', 'professor@unilink.local', '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'professor', 'Ahmed Khalid', 'FAC-301', 'Computer Science', NULL, NULL, NULL, 1, 'active', NULL, NULL, NULL, NULL, NULL, '2026-03-08 02:45:17', '2026-03-08 03:01:13'),
(4, 'professor02', 'professor2@unilink.local', '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'professor', 'Lina Sami', 'FAC-302', 'Information Systems', NULL, NULL, NULL, 1, 'suspended', NULL, NULL, NULL, NULL, NULL, '2026-03-08 02:45:17', '2026-03-12 14:28:12'),
(5, 'student01', 'student@unilink.local', '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'student', 'Rania Fahad', 'STU-2001', 'Computer Science', NULL, NULL, NULL, 1, 'active', NULL, NULL, NULL, NULL, NULL, '2026-03-08 02:45:17', '2026-03-08 03:01:13'),
(6, 'student02', 'student2@unilink.local', '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'student', 'Khalid Abdulrahman', 'STU-2002', 'Information Systems', NULL, NULL, NULL, 1, 'active', NULL, NULL, NULL, NULL, NULL, '2026-03-08 02:45:17', '2026-03-08 03:01:13'),
(7, 'student03', 'student3@unilink.local', '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'student', 'Noor Ibrahim', 'STU-2003', 'Computer Science', NULL, NULL, NULL, 1, 'active', NULL, NULL, NULL, NULL, NULL, '2026-03-08 02:45:17', '2026-03-08 03:01:13'),
(8, 'student_suspended', 'suspended@unilink.local', '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'student', 'Sara Ali', 'STU-2004', 'Computer Science', NULL, NULL, NULL, 1, 'suspended', NULL, NULL, NULL, NULL, NULL, '2026-03-08 02:45:17', '2026-03-08 03:01:13'),
(9, 'user1', 'spacew168@gmail.com', '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'student', 'First User', 'STU-1001', 'Computer Science', NULL, NULL, NULL, 1, 'active', NULL, NULL, NULL, NULL, '2026-03-14 00:03:36', '2026-03-13 20:54:41', '2026-03-14 00:08:41'),
(10, 'user2', 'merfah3@gmail.com', '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'admin', 'Secondary Administrator', 'ADM-002', 'IT Department', NULL, NULL, NULL, 1, 'active', NULL, NULL, NULL, NULL, '2026-03-30 18:24:39', '2026-03-13 20:54:41', '2026-03-30 18:24:39'),
(16, 'nobyte1399', 'nobyte13@gmail.com', '$2y$10$nuoiH85j7vEdu9oXqTTbZuCauZV9yl5BS0WNht0JK0bPcs1bjaKM2', 'student', 'ميرا فهمي', 'SUB-0910', 'Engineering', NULL, NULL, NULL, 1, 'active', NULL, NULL, NULL, NULL, '2026-03-22 00:01:54', '2026-03-17 03:30:14', '2026-03-22 00:01:54'),
(17, 'os303saleh42', 'os303saleh@gmail.com', '$2y$10$XG7dtySz81e3J0bZmI83bup4qfWgwyg72/PoBnWpQdvxijWVNjQ.q', 'professor', 'عدنان حيدر', 'suk-32201', 'IT', NULL, NULL, NULL, 1, 'active', NULL, NULL, NULL, NULL, '2026-03-21 23:59:23', '2026-03-18 00:11:02', '2026-03-21 23:59:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_events`
--
ALTER TABLE `academic_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `idx_events_owner_start` (`owner_user_id`,`start_at`),
  ADD KEY `idx_events_group_start` (`group_id`,`start_at`),
  ADD KEY `idx_events_course_start` (`course_id`,`start_at`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_action` (`action`),
  ADD KEY `idx_audit_date` (`created_at`);

--
-- Indexes for table `branding_settings`
--
ALTER TABLE `branding_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `uq_courses_code` (`code`),
  ADD KEY `idx_courses_dept` (`department`),
  ADD KEY `idx_courses_active` (`is_active`);

--
-- Indexes for table `fcm_tokens`
--
ALTER TABLE `fcm_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_active` (`user_id`,`is_active`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `idx_files_user` (`user_id`),
  ADD KEY `idx_files_post` (`post_id`),
  ADD KEY `idx_files_course_category` (`course_id`,`category`),
  ADD KEY `idx_files_group_category` (`group_id`,`category`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `idx_groups_created_by` (`created_by`),
  ADD KEY `idx_groups_course` (`course_id`),
  ADD KEY `idx_groups_name` (`group_name`);

--
-- Indexes for table `group_auto_join_rules`
--
ALTER TABLE `group_auto_join_rules`
  ADD PRIMARY KEY (`rule_id`),
  ADD KEY `idx_rules_group` (`group_id`),
  ADD KEY `idx_rules_active` (`is_active`),
  ADD KEY `idx_rules_dept` (`department`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`membership_id`),
  ADD UNIQUE KEY `uq_group_member` (`group_id`,`user_id`),
  ADD KEY `idx_gm_user` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`msg_id`),
  ADD KEY `idx_msg_sender` (`sender_id`),
  ADD KEY `idx_msg_receiver` (`receiver_id`),
  ADD KEY `idx_msg_created` (`created_at`),
  ADD KEY `fk_msg_file` (`file_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notif_user` (`user_id`),
  ADD KEY `idx_notif_read` (`user_id`,`is_read`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `idx_posts_user` (`user_id`),
  ADD KEY `idx_posts_group` (`group_id`),
  ADD KEY `idx_posts_created` (`created_at`);

--
-- Indexes for table `post_comments`
--
ALTER TABLE `post_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `idx_comments_post` (`post_id`),
  ADD KEY `idx_comments_user` (`user_id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `uq_post_like` (`post_id`,`user_id`),
  ADD KEY `fk_likes_user` (`user_id`);

--
-- Indexes for table `professor_courses`
--
ALTER TABLE `professor_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_prof_course` (`professor_user_id`,`course_id`),
  ADD KEY `idx_prof_course_prof` (`professor_user_id`),
  ADD KEY `idx_prof_course_course` (`course_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_reports_reporter` (`reporter_id`),
  ADD KEY `idx_reports_post` (`post_id`),
  ADD KEY `idx_reports_status` (`status`),
  ADD KEY `fk_reports_reported` (`reported_user_id`),
  ADD KEY `fk_reports_handler` (`handled_by`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `idx_tickets_creator_status` (`created_by`,`status`),
  ADD KEY `idx_tickets_assigned` (`assigned_to`,`status`);

--
-- Indexes for table `support_ticket_messages`
--
ALTER TABLE `support_ticket_messages`
  ADD PRIMARY KEY (`msg_id`),
  ADD KEY `idx_ticket_msgs_ticket` (`ticket_id`,`created_at`),
  ADD KEY `fk_ticket_msgs_sender` (`sender_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_status` (`status`),
  ADD KEY `idx_users_name` (`full_name`),
  ADD KEY `idx_users_dept` (`department`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_events`
--
ALTER TABLE `academic_events`
  MODIFY `event_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `branding_settings`
--
ALTER TABLE `branding_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fcm_tokens`
--
ALTER TABLE `fcm_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `file_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `group_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `group_auto_join_rules`
--
ALTER TABLE `group_auto_join_rules`
  MODIFY `rule_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `membership_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `msg_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `post_comments`
--
ALTER TABLE `post_comments`
  MODIFY `comment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `like_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `professor_courses`
--
ALTER TABLE `professor_courses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `ticket_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_ticket_messages`
--
ALTER TABLE `support_ticket_messages`
  MODIFY `msg_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_events`
--
ALTER TABLE `academic_events`
  ADD CONSTRAINT `fk_events_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_events_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_events_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `fcm_tokens`
--
ALTER TABLE `fcm_tokens`
  ADD CONSTRAINT `fk_fcm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `fk_files_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_files_group2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_files_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_files_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `fk_groups_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_groups_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `group_auto_join_rules`
--
ALTER TABLE `group_auto_join_rules`
  ADD CONSTRAINT `fk_rules_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `fk_gm_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_gm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_msg_file` FOREIGN KEY (`file_id`) REFERENCES `files` (`file_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `fk_posts_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_posts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `post_comments`
--
ALTER TABLE `post_comments`
  ADD CONSTRAINT `fk_comments_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `fk_likes_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_likes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `professor_courses`
--
ALTER TABLE `professor_courses`
  ADD CONSTRAINT `fk_prof_course_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prof_course_prof` FOREIGN KEY (`professor_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_handler` FOREIGN KEY (`handled_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reports_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reports_reported` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reports_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `fk_tickets_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tickets_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `support_ticket_messages`
--
ALTER TABLE `support_ticket_messages`
  ADD CONSTRAINT `fk_ticket_msgs_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ticket_msgs_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`ticket_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
