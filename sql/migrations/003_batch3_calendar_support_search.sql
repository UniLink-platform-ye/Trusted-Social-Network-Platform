-- UniLink / Trusted Social Network Platform
-- Batch 3 migration: academic calendar + support tickets + search indexes
-- Safe to run multiple times.

SET NAMES 'utf8mb4';
SET FOREIGN_KEY_CHECKS = 0;

-- 1) Academic events (calendar)
CREATE TABLE IF NOT EXISTS `academic_events` (
  `event_id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_user_id`  INT UNSIGNED NOT NULL,
  `course_id`      INT UNSIGNED NULL DEFAULT NULL,
  `group_id`       INT UNSIGNED NULL DEFAULT NULL,
  `event_type`     ENUM('lecture','exam','meeting','task','other') NOT NULL DEFAULT 'other',
  `title`          VARCHAR(200) NOT NULL,
  `description`    TEXT NULL DEFAULT NULL,
  `location`       VARCHAR(200) NULL DEFAULT NULL,
  `start_at`       DATETIME NOT NULL,
  `end_at`         DATETIME NULL DEFAULT NULL,
  `all_day`        TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  KEY `idx_events_owner_start` (`owner_user_id`,`start_at`),
  KEY `idx_events_group_start` (`group_id`,`start_at`),
  KEY `idx_events_course_start` (`course_id`,`start_at`),
  CONSTRAINT `fk_events_owner`  FOREIGN KEY (`owner_user_id`) REFERENCES `users`   (`user_id`)  ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_events_group`  FOREIGN KEY (`group_id`)      REFERENCES `groups`  (`group_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_events_course` FOREIGN KEY (`course_id`)     REFERENCES `courses` (`course_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Support tickets
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `ticket_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_by`  INT UNSIGNED NOT NULL,
  `subject`     VARCHAR(200) NOT NULL,
  `status`      ENUM('open','pending','closed') NOT NULL DEFAULT 'open',
  `priority`    ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
  `assigned_to` INT UNSIGNED NULL DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `closed_at`   DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`ticket_id`),
  KEY `idx_tickets_creator_status` (`created_by`,`status`),
  KEY `idx_tickets_assigned` (`assigned_to`,`status`),
  CONSTRAINT `fk_tickets_creator`  FOREIGN KEY (`created_by`)  REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tickets_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL  ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `support_ticket_messages` (
  `msg_id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id`  INT UNSIGNED NOT NULL,
  `sender_id`  INT UNSIGNED NOT NULL,
  `message`    TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`msg_id`),
  KEY `idx_ticket_msgs_ticket` (`ticket_id`,`created_at`),
  CONSTRAINT `fk_ticket_msgs_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`ticket_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ticket_msgs_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`           (`user_id`)   ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Search-related indexes (lightweight)
-- Users: names + department
SET @has_idx_users_name := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_name'
);
SET @sql := IF(@has_idx_users_name = 0, 'ALTER TABLE `users` ADD KEY `idx_users_name` (`full_name`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_idx_users_dept := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_dept'
);
SET @sql := IF(@has_idx_users_dept = 0, 'ALTER TABLE `users` ADD KEY `idx_users_dept` (`department`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Groups: names
SET @has_idx_groups_name := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'groups' AND INDEX_NAME = 'idx_groups_name'
);
SET @sql := IF(@has_idx_groups_name = 0, 'ALTER TABLE `groups` ADD KEY `idx_groups_name` (`group_name`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

