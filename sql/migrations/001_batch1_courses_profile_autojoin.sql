-- UniLink / Trusted Social Network Platform
-- Batch 1 migration: courses + auto-join rules + profile fields
-- Safe to run multiple times (uses IF NOT EXISTS and conditional ALTERs)

SET NAMES 'utf8mb4';
SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────────────────
-- 1) Courses
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `courses` (
  `course_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`        VARCHAR(30)  NOT NULL,
  `name`        VARCHAR(200) NOT NULL,
  `department`  VARCHAR(100) NULL DEFAULT NULL,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`course_id`),
  UNIQUE KEY `uq_courses_code` (`code`),
  KEY `idx_courses_dept` (`department`),
  KEY `idx_courses_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `professor_courses` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `professor_user_id` INT UNSIGNED NOT NULL,
  `course_id`         INT UNSIGNED NOT NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prof_course` (`professor_user_id`, `course_id`),
  KEY `idx_prof_course_prof` (`professor_user_id`),
  KEY `idx_prof_course_course` (`course_id`),
  CONSTRAINT `fk_prof_course_prof`  FOREIGN KEY (`professor_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_prof_course_course` FOREIGN KEY (`course_id`)         REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- 2) Add optional academic fields to users (year_level, batch_year)
--    Use conditional ALTER to stay compatible with MySQL variants.
-- ─────────────────────────────────────────────────────────────────────────────
SET @has_year_level := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'year_level'
);
SET @sql := IF(@has_year_level = 0, 'ALTER TABLE `users` ADD COLUMN `year_level` INT NULL DEFAULT NULL AFTER `department`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_batch_year := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'batch_year'
);
SET @sql := IF(@has_batch_year = 0, 'ALTER TABLE `users` ADD COLUMN `batch_year` INT NULL DEFAULT NULL AFTER `year_level`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ─────────────────────────────────────────────────────────────────────────────
-- 3) Add course_id to groups (optional linkage for professor-created groups)
-- ─────────────────────────────────────────────────────────────────────────────
SET @has_groups_course := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'groups' AND COLUMN_NAME = 'course_id'
);
SET @sql := IF(@has_groups_course = 0, 'ALTER TABLE `groups` ADD COLUMN `course_id` INT UNSIGNED NULL DEFAULT NULL AFTER `type`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_groups_course_idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'groups' AND INDEX_NAME = 'idx_groups_course'
);
SET @sql := IF(@has_groups_course_idx = 0, 'ALTER TABLE `groups` ADD KEY `idx_groups_course` (`course_id`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add FK if missing (best-effort; ignore if already exists or if engines differ)
SET @has_groups_course_fk := (
  SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'groups' AND CONSTRAINT_NAME = 'fk_groups_course'
);
SET @sql := IF(@has_groups_course_fk = 0,
  'ALTER TABLE `groups` ADD CONSTRAINT `fk_groups_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ─────────────────────────────────────────────────────────────────────────────
-- 4) Auto-join rules
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `group_auto_join_rules` (
  `rule_id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id`           INT UNSIGNED NOT NULL,
  `department`         VARCHAR(100) NULL DEFAULT NULL,
  `academic_id_prefix` VARCHAR(20)  NULL DEFAULT NULL,
  `year_level`         INT          NULL DEFAULT NULL,
  `batch_year`         INT          NULL DEFAULT NULL,
  `is_active`          TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rule_id`),
  KEY `idx_rules_group` (`group_id`),
  KEY `idx_rules_active` (`is_active`),
  KEY `idx_rules_dept` (`department`),
  CONSTRAINT `fk_rules_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
