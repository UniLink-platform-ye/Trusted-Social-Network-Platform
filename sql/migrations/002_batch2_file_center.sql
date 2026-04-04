-- UniLink / Trusted Social Network Platform
-- Batch 2 migration: File Center metadata (course/group/category/title/description)
-- Safe to run multiple times using conditional ALTERs.

SET NAMES 'utf8mb4';
SET FOREIGN_KEY_CHECKS = 0;

-- 1) Extend files table with optional metadata
SET @has_course_id := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'files' AND COLUMN_NAME = 'course_id'
);
SET @sql := IF(@has_course_id = 0, 'ALTER TABLE `files` ADD COLUMN `course_id` INT UNSIGNED NULL DEFAULT NULL AFTER `post_id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_group_id := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'files' AND COLUMN_NAME = 'group_id'
);
SET @sql := IF(@has_group_id = 0, 'ALTER TABLE `files` ADD COLUMN `group_id` INT UNSIGNED NULL DEFAULT NULL AFTER `course_id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_category := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'files' AND COLUMN_NAME = 'category'
);
SET @sql := IF(@has_category = 0, 'ALTER TABLE `files` ADD COLUMN `category` ENUM(''lecture'',''assignment'',''reference'',''other'') NOT NULL DEFAULT ''other'' AFTER `group_id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_title := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'files' AND COLUMN_NAME = 'title'
);
SET @sql := IF(@has_title = 0, 'ALTER TABLE `files` ADD COLUMN `title` VARCHAR(255) NULL DEFAULT NULL AFTER `category`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_description := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'files' AND COLUMN_NAME = 'description'
);
SET @sql := IF(@has_description = 0, 'ALTER TABLE `files` ADD COLUMN `description` TEXT NULL DEFAULT NULL AFTER `title`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Indexes
SET @has_idx_course_cat := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'files' AND INDEX_NAME = 'idx_files_course_category'
);
SET @sql := IF(@has_idx_course_cat = 0, 'ALTER TABLE `files` ADD KEY `idx_files_course_category` (`course_id`, `category`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_idx_group_cat := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'files' AND INDEX_NAME = 'idx_files_group_category'
);
SET @sql := IF(@has_idx_group_cat = 0, 'ALTER TABLE `files` ADD KEY `idx_files_group_category` (`group_id`, `category`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Foreign keys (best-effort)
SET @has_fk_files_course := (
  SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'files' AND CONSTRAINT_NAME = 'fk_files_course'
);
SET @sql := IF(@has_fk_files_course = 0,
  'ALTER TABLE `files` ADD CONSTRAINT `fk_files_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_fk_files_group2 := (
  SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'files' AND CONSTRAINT_NAME = 'fk_files_group2'
);
SET @sql := IF(@has_fk_files_group2 = 0,
  'ALTER TABLE `files` ADD CONSTRAINT `fk_files_group2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
