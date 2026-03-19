-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: unilink-platform.c6pgq44asn04.us-east-1.rds.amazonaws.com    Database: trusted_social_network_platform
-- ------------------------------------------------------
-- Server version	8.4.7

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

-- SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ '';

--
-- Table structure for table `academic_events`
--

DROP TABLE IF EXISTS `academic_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_events` (
  `event_id` int unsigned NOT NULL AUTO_INCREMENT,
  `owner_user_id` int unsigned NOT NULL,
  `course_id` int unsigned DEFAULT NULL,
  `group_id` int unsigned DEFAULT NULL,
  `event_type` enum('lecture','exam','meeting','task','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime DEFAULT NULL,
  `all_day` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  KEY `idx_events_owner_start` (`owner_user_id`,`start_at`),
  KEY `idx_events_group_start` (`group_id`,`start_at`),
  KEY `idx_events_course_start` (`course_id`,`start_at`),
  CONSTRAINT `fk_events_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_events_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_events_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_events`
--

LOCK TABLES `academic_events` WRITE;
/*!40000 ALTER TABLE `academic_events` DISABLE KEYS */;
INSERT INTO `academic_events` VALUES (1,16,NULL,NULL,'exam','ميرا',NULL,NULL,'0000-00-00 00:00:00',NULL,1,'2026-03-17 23:09:39','2026-03-17 23:09:39'),(2,17,NULL,NULL,'lecture','محاضرة تعويضيه',NULL,NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00',0,'2026-03-18 01:05:32','2026-03-18 01:05:32');
/*!40000 ALTER TABLE `academic_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `log_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `action` enum('login','logout','login_failed','register','post_create','post_delete','post_edit','file_upload','file_delete','report_submit','account_suspend','account_delete','permission_change','password_change') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_date` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'login','Admin login','192.168.1.1',NULL,'2026-03-08 02:46:00'),(2,2,'login','Supervisor login','192.168.1.2',NULL,'2026-03-08 02:46:02'),(3,1,'account_suspend','Suspended student_suspended for policy violation','192.168.1.1',NULL,'2026-03-08 02:46:03'),(4,2,'report_submit','Harassment report handled and closed','192.168.1.2',NULL,'2026-03-08 02:46:04'),(5,1,'register','Database setup complete - system ready','127.0.0.1',NULL,'2026-03-08 02:46:06'),(6,NULL,'login_failed','Failed login attempt for email: admin@unilink.local','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-08 02:48:23'),(7,NULL,'login_failed','Failed login attempt for email: admin@unilink.local','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-08 02:48:48'),(8,NULL,'login_failed','Failed login attempt for email: admin@unilink.local','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-08 02:56:23'),(9,1,'login','User logged in to admin panel','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-08 03:01:53'),(10,1,'login','User logged in to admin panel','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-08 03:23:15'),(11,1,'login','User logged in to admin panel','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 02:09:05'),(12,1,'login','User logged in to admin panel','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 02:09:48'),(13,1,'login','User logged in to admin panel','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 14:08:53'),(14,1,'logout','User logged out from admin panel','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 14:10:49'),(15,1,'login','User logged in to admin panel','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 14:12:58'),(16,1,'account_suspend','تم تعليق الحساب','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 14:28:13'),(17,1,'login','User logged in to admin panel','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-13 20:00:11'),(18,1,'logout','User logged out from admin panel','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-13 22:04:04'),(19,1,'logout','User logged out from admin panel','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-13 23:16:07'),(20,10,'login','OTP verified — Admin panel login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-13 23:18:52'),(21,9,'login','OTP verified — Admin panel login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-13 23:27:06'),(22,9,'login','OTP verified — Admin panel login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-13 23:29:46'),(23,9,'logout','User logged out from admin panel','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-14 00:02:58'),(24,9,'login','OTP verified — Admin panel login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-14 00:03:36'),(25,9,'logout','User logged out from admin panel','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-14 00:08:40'),(26,9,'logout','User logged out from admin panel','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-14 00:10:53'),(27,10,'logout','User logged out from admin panel','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-14 00:20:00'),(28,10,'login','OTP verified — Admin panel login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-14 00:22:15'),(29,1,'logout','User logged out from admin panel','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-14 01:47:29'),(30,NULL,'register','User registered and verified','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-14 02:38:42'),(31,10,'login','OTP verified — Admin panel login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-15 22:42:08'),(32,10,'account_suspend','تم تعليق الحساب','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-15 22:46:41'),(33,10,'account_suspend','تم تعليق الحساب','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-15 22:46:52'),(34,10,'account_suspend','تم تفعيل الحساب','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-15 22:47:01'),(35,10,'account_suspend','تم تفعيل الحساب','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-15 22:47:44'),(36,10,'post_edit','تم تعديل بيانات المستخدم: احمد احمد','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-15 22:48:20'),(37,10,'account_suspend','تم تعليق الحساب','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','2026-03-16 23:36:06'),(38,10,'login','OTP verified — Admin panel login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-16 23:54:00'),(39,10,'account_delete','تم حذف المستخدم رقم 14 بشكل جذري مع كافة بياناته.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-17 00:02:51'),(40,10,'account_delete','تم حذف المستخدم رقم 13 بشكل جذري مع كافة بياناته.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-17 00:06:00'),(41,10,'account_delete','تم حذف المستخدم رقم 15 بشكل جذري مع كافة بياناته.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-17 00:10:13'),(42,10,'account_delete','تم حذف المستخدم رقم 12 بشكل جذري مع كافة بياناته.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-17 00:24:30'),(43,NULL,'file_upload','رفع ملف: Kotobati - السلام_عليك_يا_صاحبي_،_أدهم_شرقاوي.pdf','192.168.1.101','Dart/3.6 (dart:io)','2026-03-17 23:02:19'),(44,NULL,'file_upload','رفع ملف: MEERA_TASK_REPORT.pdf','192.168.1.102','Dart/3.6 (dart:io)','2026-03-18 00:54:02');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `courses` (
  `course_id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`course_id`),
  UNIQUE KEY `uq_courses_code` (`code`),
  KEY `idx_courses_dept` (`department`),
  KEY `idx_courses_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fcm_tokens`
--

DROP TABLE IF EXISTS `fcm_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fcm_tokens` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `token` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_type` enum('android','ios','web') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'android',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_active` (`user_id`,`is_active`),
  CONSTRAINT `fk_fcm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fcm_tokens`
--

LOCK TABLES `fcm_tokens` WRITE;
/*!40000 ALTER TABLE `fcm_tokens` DISABLE KEYS */;
INSERT INTO `fcm_tokens` VALUES (3,16,'ezbEmUY2QPyg3OHlieCzWl:APA91bHQmtXsuEcRTq-AmNx_bjxL3kKTyOyFMJwD7Q0M5Qj5GPBMoEvRXstSQsvbei18dqSxk-UA9M2jHItcdyDZiRa-R-aV9Qtv0wcuU1ZmiiFEoBQeKX0','android',1,'2026-03-17 03:31:53','2026-03-17 03:31:53'),(4,16,'dqujTWcqTUqimLKSMvB27B:APA91bGBvpXA89nop8D1-IUaCDA6Vm8v6wvcS_Hv_XJR8jKxy_quAAUrOK2ELXE2uGLRbaKx-ULL_i-dJ53x9agPVtwvyKZx0kMyr7-qWEz9aGXSOIg5xSw','android',1,'2026-03-17 22:57:26','2026-03-17 22:57:26'),(5,17,'e_lDSZSoTvq-ieU-w2yFMJ:APA91bFVtxChnAsJiMhDmAqkNjQcwkZdNkSULuNEfxvikeTUaR3Xn87Riy70bv890F2cYzSrn8nj4d6TTk2vdaEj0NlqeZgQB4Wv3nW_KwS_DJUx45opLSg','android',1,'2026-03-18 00:44:23','2026-03-18 00:44:23');
/*!40000 ALTER TABLE `fcm_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `files` (
  `file_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `post_id` int unsigned DEFAULT NULL,
  `course_id` int unsigned DEFAULT NULL,
  `group_id` int unsigned DEFAULT NULL,
  `category` enum('lecture','assignment','reference','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` enum('pdf','image','presentation','archive','video','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `file_size` bigint unsigned NOT NULL,
  `storage_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT '0',
  `download_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`file_id`),
  KEY `idx_files_user` (`user_id`),
  KEY `idx_files_post` (`post_id`),
  KEY `idx_files_course_category` (`course_id`,`category`),
  KEY `idx_files_group_category` (`group_id`,`category`),
  CONSTRAINT `fk_files_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_files_group2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_files_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_files_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `files`
--

LOCK TABLES `files` WRITE;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
INSERT INTO `files` VALUES (1,16,NULL,NULL,NULL,'lecture','تصميم منطقي',NULL,'Kotobati - السلام_عليك_يا_صاحبي_،_أدهم_شرقاوي.pdf','f_69b9dd6fd0c548.00311943.pdf','pdf',2565067,'uploads/files/2026/03/f_69b9dd6fd0c548.00311943.pdf',0,0,'2026-03-17 23:02:19'),(2,17,NULL,NULL,NULL,'other','my task',NULL,'MEERA_TASK_REPORT.pdf','f_69b9f79d9def68.88782765.pdf','pdf',564758,'uploads/files/2026/03/f_69b9f79d9def68.88782765.pdf',0,0,'2026-03-18 00:54:01');
/*!40000 ALTER TABLE `files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_auto_join_rules`
--

DROP TABLE IF EXISTS `group_auto_join_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_auto_join_rules` (
  `rule_id` int unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int unsigned NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `academic_id_prefix` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year_level` int DEFAULT NULL,
  `batch_year` int DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rule_id`),
  KEY `idx_rules_group` (`group_id`),
  KEY `idx_rules_active` (`is_active`),
  KEY `idx_rules_dept` (`department`),
  CONSTRAINT `fk_rules_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_auto_join_rules`
--

LOCK TABLES `group_auto_join_rules` WRITE;
/*!40000 ALTER TABLE `group_auto_join_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_auto_join_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_members`
--

DROP TABLE IF EXISTS `group_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_members` (
  `membership_id` int unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `member_role` enum('owner','moderator','member') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `joined_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`membership_id`),
  UNIQUE KEY `uq_group_member` (`group_id`,`user_id`),
  KEY `idx_gm_user` (`user_id`),
  CONSTRAINT `fk_gm_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_gm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_members`
--

LOCK TABLES `group_members` WRITE;
/*!40000 ALTER TABLE `group_members` DISABLE KEYS */;
INSERT INTO `group_members` VALUES (8,2,16,'member','2026-03-17 22:58:58'),(10,1,16,'member','2026-03-17 22:59:53'),(12,1,17,'member','2026-03-18 00:45:29'),(13,2,17,'member','2026-03-18 00:46:24'),(14,3,17,'member','2026-03-18 00:46:25');
/*!40000 ALTER TABLE `group_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups` (
  `group_id` int unsigned NOT NULL AUTO_INCREMENT,
  `group_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('course','department','activity','administrative') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'course',
  `course_id` int unsigned DEFAULT NULL,
  `privacy` enum('public','private','restricted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'private',
  `created_by` int unsigned NOT NULL,
  `members_count` int unsigned NOT NULL DEFAULT '1',
  `status` enum('active','hidden','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `cover_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`),
  KEY `idx_groups_created_by` (`created_by`),
  KEY `idx_groups_course` (`course_id`),
  KEY `idx_groups_name` (`group_name`),
  CONSTRAINT `fk_groups_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_groups_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groups`
--

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
INSERT INTO `groups` VALUES (1,'Data Structures Course','Data Structures students group - Semester 1 2024','course',NULL,'private',3,3,'active',NULL,'2026-03-08 02:45:24','2026-03-08 02:45:24'),(2,'Computer Science Dept','General group for CS students and faculty','department',NULL,'restricted',1,5,'active',NULL,'2026-03-08 02:45:26','2026-03-08 02:45:26'),(3,'Programming Club','University programming club - activities and workshops','activity',NULL,'public',2,2,'active',NULL,'2026-03-08 02:45:29','2026-03-08 02:45:29');
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `msg_id` int unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int unsigned NOT NULL,
  `receiver_id` int unsigned NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('text','image','file') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `file_id` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`msg_id`),
  KEY `idx_msg_sender` (`sender_id`),
  KEY `idx_msg_receiver` (`receiver_id`),
  KEY `idx_msg_created` (`created_at`),
  KEY `fk_msg_file` (`file_id`),
  CONSTRAINT `fk_msg_file` FOREIGN KEY (`file_id`) REFERENCES `files` (`file_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `notification_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `type` enum('new_message','new_post','post_like','post_comment','group_invite','report_update','announcement','account_warning') COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `idx_notif_user` (`user_id`),
  KEY `idx_notif_read` (`user_id`,`is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_likes`
--

DROP TABLE IF EXISTS `post_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_likes` (
  `like_id` int unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`like_id`),
  UNIQUE KEY `uq_post_like` (`post_id`,`user_id`),
  KEY `fk_likes_user` (`user_id`),
  CONSTRAINT `fk_likes_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_likes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_likes`
--

LOCK TABLES `post_likes` WRITE;
/*!40000 ALTER TABLE `post_likes` DISABLE KEYS */;
/*!40000 ALTER TABLE `post_likes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `post_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `group_id` int unsigned DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('post','announcement','question','lecture') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'post',
  `visibility` enum('public','group','private') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `likes_count` int unsigned NOT NULL DEFAULT '0',
  `comments_count` int unsigned NOT NULL DEFAULT '0',
  `is_flagged` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`),
  KEY `idx_posts_user` (`user_id`),
  KEY `idx_posts_group` (`group_id`),
  KEY `idx_posts_created` (`created_at`),
  CONSTRAINT `fk_posts_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_posts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES (1,3,1,'First exam is next Sunday. Review chapters 1 to 4.','announcement','group',12,5,0,'active','2026-03-08 02:45:32','2026-03-08 02:45:32'),(2,5,NULL,'Can anyone help me understand the Dijkstra algorithm?','question','public',3,8,0,'active','2026-03-08 02:45:34','2026-03-08 02:45:34'),(3,4,2,'Reminder: Project submission deadline is end of next week.','announcement','group',20,2,0,'active','2026-03-08 02:45:36','2026-03-08 02:45:36'),(4,1,NULL,'Welcome to UniLink platform! We are glad to have you.','post','public',45,12,0,'active','2026-03-08 02:45:39','2026-03-08 02:45:39'),(5,6,3,'JavaScript workshop this Thursday at 6pm in the training room.','announcement','group',8,3,0,'active','2026-03-08 02:45:43','2026-03-08 02:45:43'),(8,16,NULL,'منشور تجريبي','post','public',0,0,0,'active','2026-03-18 01:28:06','2026-03-18 01:28:06'),(9,16,NULL,'منشور تجريبي','post','public',0,0,0,'active','2026-03-18 01:28:06','2026-03-18 01:28:06'),(10,16,NULL,'منشور','post','public',0,0,0,'active','2026-03-18 01:28:40','2026-03-18 01:28:40');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `professor_courses`
--

DROP TABLE IF EXISTS `professor_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `professor_courses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `professor_user_id` int unsigned NOT NULL,
  `course_id` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prof_course` (`professor_user_id`,`course_id`),
  KEY `idx_prof_course_prof` (`professor_user_id`),
  KEY `idx_prof_course_course` (`course_id`),
  CONSTRAINT `fk_prof_course_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_prof_course_prof` FOREIGN KEY (`professor_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `professor_courses`
--

LOCK TABLES `professor_courses` WRITE;
/*!40000 ALTER TABLE `professor_courses` DISABLE KEYS */;
/*!40000 ALTER TABLE `professor_courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reports` (
  `report_id` int unsigned NOT NULL AUTO_INCREMENT,
  `reporter_id` int unsigned NOT NULL,
  `post_id` int unsigned DEFAULT NULL,
  `reported_user_id` int unsigned DEFAULT NULL,
  `reason` enum('spam','harassment','inappropriate_content','misinformation','copyright_violation','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `details` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','under_review','resolved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `handled_by` int unsigned DEFAULT NULL,
  `action_taken` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  KEY `idx_reports_reporter` (`reporter_id`),
  KEY `idx_reports_post` (`post_id`),
  KEY `idx_reports_status` (`status`),
  KEY `fk_reports_reported` (`reported_user_id`),
  KEY `fk_reports_handler` (`handled_by`),
  CONSTRAINT `fk_reports_handler` FOREIGN KEY (`handled_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_reports_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_reports_reported` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_reports_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reports`
--

LOCK TABLES `reports` WRITE;
/*!40000 ALTER TABLE `reports` DISABLE KEYS */;
INSERT INTO `reports` VALUES (1,5,NULL,8,'harassment','User sends abusive messages.','resolved',2,NULL,'2026-03-08 02:45:48','2026-03-08 02:45:48'),(2,6,NULL,NULL,'spam','Post contains unauthorized promotional links.','under_review',2,NULL,'2026-03-08 02:45:53','2026-03-08 02:45:53'),(3,7,NULL,6,'inappropriate_content','Inappropriate content in comments.','pending',NULL,NULL,'2026-03-08 02:45:55','2026-03-08 02:45:55'),(4,5,NULL,NULL,'misinformation','Wrong information about the upcoming exam.','rejected',1,NULL,'2026-03-08 02:45:59','2026-03-08 02:45:59');
/*!40000 ALTER TABLE `reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_ticket_messages`
--

DROP TABLE IF EXISTS `support_ticket_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_ticket_messages` (
  `msg_id` int unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` int unsigned NOT NULL,
  `sender_id` int unsigned NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`msg_id`),
  KEY `idx_ticket_msgs_ticket` (`ticket_id`,`created_at`),
  KEY `fk_ticket_msgs_sender` (`sender_id`),
  CONSTRAINT `fk_ticket_msgs_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ticket_msgs_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`ticket_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_ticket_messages`
--

LOCK TABLES `support_ticket_messages` WRITE;
/*!40000 ALTER TABLE `support_ticket_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_ticket_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_tickets` (
  `ticket_id` int unsigned NOT NULL AUTO_INCREMENT,
  `created_by` int unsigned NOT NULL,
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('open','pending','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `priority` enum('low','normal','high') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `assigned_to` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `closed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`ticket_id`),
  KEY `idx_tickets_creator_status` (`created_by`,`status`),
  KEY `idx_tickets_assigned` (`assigned_to`,`status`),
  CONSTRAINT `fk_tickets_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tickets_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_tickets`
--

LOCK TABLES `support_tickets` WRITE;
/*!40000 ALTER TABLE `support_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('student','professor','admin','supervisor') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student',
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_id` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year_level` int DEFAULT NULL,
  `batch_year` int DEFAULT NULL,
  `avatar_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','suspended','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `otp_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `remember_token_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token_expires_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_name` (`full_name`),
  KEY `idx_users_dept` (`department`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@unilink.local','$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO','admin','System Administrator','ADM-001','IT Department',NULL,NULL,NULL,1,'active','$2y$10$jQ/sI/XZ.mLDguq0HLyZgO/KVQoSskpXCKHekaJMKj3C.Z8Mj74Om','2026-03-15 09:29:35',NULL,NULL,'2026-03-13 20:00:07','2026-03-08 02:45:17','2026-03-15 21:29:51'),(2,'supervisor01','supervisor@unilink.local','$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO','supervisor','Main Supervisor','SUP-001','Student Affairs',NULL,NULL,NULL,1,'active',NULL,NULL,NULL,NULL,NULL,'2026-03-08 02:45:17','2026-03-08 03:01:13'),(3,'professor01','professor@unilink.local','$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO','professor','Ahmed Khalid','FAC-301','Computer Science',NULL,NULL,NULL,1,'active',NULL,NULL,NULL,NULL,NULL,'2026-03-08 02:45:17','2026-03-08 03:01:13'),(4,'professor02','professor2@unilink.local','$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO','professor','Lina Sami','FAC-302','Information Systems',NULL,NULL,NULL,1,'suspended',NULL,NULL,NULL,NULL,NULL,'2026-03-08 02:45:17','2026-03-12 14:28:12'),(5,'student01','student@unilink.local','$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO','student','Rania Fahad','STU-2001','Computer Science',NULL,NULL,NULL,1,'active',NULL,NULL,NULL,NULL,NULL,'2026-03-08 02:45:17','2026-03-08 03:01:13'),(6,'student02','student2@unilink.local','$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO','student','Khalid Abdulrahman','STU-2002','Information Systems',NULL,NULL,NULL,1,'active',NULL,NULL,NULL,NULL,NULL,'2026-03-08 02:45:17','2026-03-08 03:01:13'),(7,'student03','student3@unilink.local','$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO','student','Noor Ibrahim','STU-2003','Computer Science',NULL,NULL,NULL,1,'active',NULL,NULL,NULL,NULL,NULL,'2026-03-08 02:45:17','2026-03-08 03:01:13'),(8,'student_suspended','suspended@unilink.local','$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO','student','Sara Ali','STU-2004','Computer Science',NULL,NULL,NULL,1,'suspended',NULL,NULL,NULL,NULL,NULL,'2026-03-08 02:45:17','2026-03-08 03:01:13'),(9,'user1','spacew168@gmail.com','$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO','student','First User','STU-1001','Computer Science',NULL,NULL,NULL,1,'active',NULL,NULL,NULL,NULL,'2026-03-14 00:03:36','2026-03-13 20:54:41','2026-03-14 00:08:41'),(10,'user2','merfah3@gmail.com','$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO','admin','Secondary Administrator','ADM-002','IT Department',NULL,NULL,NULL,1,'active',NULL,NULL,'4ce009f6b89b54d7828016b63a0d2bdbbb6ba5201664429d612691e754f59628','2026-03-31 02:53:48','2026-03-16 23:53:58','2026-03-13 20:54:41','2026-03-16 23:53:59'),(16,'nobyte1399','nobyte13@gmail.com','$2y$10$nuoiH85j7vEdu9oXqTTbZuCauZV9yl5BS0WNht0JK0bPcs1bjaKM2','student','ميرا فهمي','SUB-0910','Engineering',NULL,NULL,NULL,1,'active',NULL,NULL,NULL,NULL,'2026-03-17 22:57:05','2026-03-17 03:30:14','2026-03-17 22:57:05'),(17,'os303saleh42','os303saleh@gmail.com','$2y$10$XG7dtySz81e3J0bZmI83bup4qfWgwyg72/PoBnWpQdvxijWVNjQ.q','professor','عدنان حيدر','suk-32201','IT',NULL,NULL,NULL,1,'active',NULL,NULL,NULL,NULL,'2026-03-18 00:44:16','2026-03-18 00:11:02','2026-03-18 00:44:16');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'trusted_social_network_platform'
--
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-19  4:35:43
