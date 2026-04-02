SET FOREIGN_KEY_CHECKS = 0;
SET NAMES 'utf8mb4';

CREATE DATABASE IF NOT EXISTS `trusted_social_network_platform` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trusted_social_network_platform`.`users` (
    `user_id`                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `username`                  VARCHAR(50)   NOT NULL,
    `email`                     VARCHAR(100)  NOT NULL,
    `password_hash`             VARCHAR(255)  NOT NULL,
    `role`                      ENUM('student','professor','admin','supervisor') NOT NULL DEFAULT 'student',
    `full_name`                 VARCHAR(150)  NOT NULL,
    `academic_id`               VARCHAR(30)   NULL DEFAULT NULL,
    `department`                VARCHAR(100)  NULL DEFAULT NULL,
    `avatar_url`                VARCHAR(500)  NULL DEFAULT NULL,
    `is_verified`               TINYINT(1)    NOT NULL DEFAULT 0,
    `status`                    ENUM('active','suspended','deleted') NOT NULL DEFAULT 'active',
    `otp_code`                  VARCHAR(255)  NULL DEFAULT NULL,
    `otp_expires_at`            DATETIME      NULL DEFAULT NULL,
    `remember_token_hash`       VARCHAR(255)  NULL DEFAULT NULL,
    `remember_token_expires_at` DATETIME      NULL DEFAULT NULL,
    `last_login`                DATETIME      NULL DEFAULT NULL,
    `created_at`                DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email` (`email`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trusted_social_network_platform`.`groups` (
    `group_id`      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `group_name`    VARCHAR(150)  NOT NULL,
    `description`   TEXT          NULL DEFAULT NULL,
    `type`          ENUM('course','department','activity','administrative') NOT NULL DEFAULT 'course',
    `privacy`       ENUM('public','private','restricted') NOT NULL DEFAULT 'private',
    `created_by`    INT UNSIGNED  NOT NULL,
    `members_count` INT UNSIGNED  NOT NULL DEFAULT 1,
    `status`        ENUM('active','hidden','deleted') NOT NULL DEFAULT 'active',
    `cover_url`     VARCHAR(500)  NULL DEFAULT NULL,
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`group_id`),
    INDEX `idx_groups_created_by` (`created_by`),
    CONSTRAINT `fk_groups_creator` FOREIGN KEY (`created_by`) REFERENCES `trusted_social_network_platform`.`users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trusted_social_network_platform`.`group_members` (
    `membership_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id`      INT UNSIGNED NOT NULL,
    `user_id`       INT UNSIGNED NOT NULL,
    `member_role`   ENUM('owner','moderator','member') NOT NULL DEFAULT 'member',
    `joined_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`membership_id`),
    UNIQUE KEY `uq_group_member` (`group_id`, `user_id`),
    INDEX `idx_gm_user` (`user_id`),
    CONSTRAINT `fk_gm_group` FOREIGN KEY (`group_id`) REFERENCES `trusted_social_network_platform`.`groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_gm_user`  FOREIGN KEY (`user_id`)  REFERENCES `trusted_social_network_platform`.`users`  (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trusted_social_network_platform`.`posts` (
    `post_id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        INT UNSIGNED NOT NULL,
    `group_id`       INT UNSIGNED NULL DEFAULT NULL,
    `content`        TEXT         NOT NULL,
    `type`           ENUM('post','announcement','question','lecture') NOT NULL DEFAULT 'post',
    `visibility`     ENUM('public','group','private') NOT NULL DEFAULT 'public',
    `likes_count`    INT UNSIGNED NOT NULL DEFAULT 0,
    `comments_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_flagged`     TINYINT(1)   NOT NULL DEFAULT 0,
    `status`         ENUM('active','deleted') NOT NULL DEFAULT 'active',
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`post_id`),
    INDEX `idx_posts_user`    (`user_id`),
    INDEX `idx_posts_group`   (`group_id`),
    INDEX `idx_posts_created` (`created_at`),
    CONSTRAINT `fk_posts_user`  FOREIGN KEY (`user_id`)  REFERENCES `trusted_social_network_platform`.`users`  (`user_id`) ON DELETE CASCADE    ON UPDATE CASCADE,
    CONSTRAINT `fk_posts_group` FOREIGN KEY (`group_id`) REFERENCES `trusted_social_network_platform`.`groups` (`group_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trusted_social_network_platform`.`post_likes` (
    `like_id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `post_id`    INT UNSIGNED NOT NULL,
    `user_id`    INT UNSIGNED NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`like_id`),
    UNIQUE KEY `uq_post_like` (`post_id`, `user_id`),
    CONSTRAINT `fk_likes_post` FOREIGN KEY (`post_id`) REFERENCES `trusted_social_network_platform`.`posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_likes_user` FOREIGN KEY (`user_id`) REFERENCES `trusted_social_network_platform`.`users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trusted_social_network_platform`.`files` (
    `file_id`        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`        INT UNSIGNED    NOT NULL,
    `post_id`        INT UNSIGNED    NULL DEFAULT NULL,
    `original_name`  VARCHAR(255)    NOT NULL,
    `stored_name`    VARCHAR(255)    NOT NULL,
    `file_type`      ENUM('pdf','image','presentation','archive','video','other') NOT NULL DEFAULT 'other',
    `file_size`      BIGINT UNSIGNED NOT NULL,
    `storage_path`   VARCHAR(500)    NOT NULL,
    `is_encrypted`   TINYINT(1)      NOT NULL DEFAULT 0,
    `download_count` INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`file_id`),
    INDEX `idx_files_user` (`user_id`),
    INDEX `idx_files_post` (`post_id`),
    CONSTRAINT `fk_files_user` FOREIGN KEY (`user_id`) REFERENCES `trusted_social_network_platform`.`users` (`user_id`) ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT `fk_files_post` FOREIGN KEY (`post_id`) REFERENCES `trusted_social_network_platform`.`posts` (`post_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trusted_social_network_platform`.`messages` (
    `msg_id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sender_id`   INT UNSIGNED NOT NULL,
    `receiver_id` INT UNSIGNED NOT NULL,
    `content`     TEXT         NOT NULL,
    `type`        ENUM('text','image','file') NOT NULL DEFAULT 'text',
    `is_read`     TINYINT(1)   NOT NULL DEFAULT 0,
    `file_id`     INT UNSIGNED NULL DEFAULT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`msg_id`),
    INDEX `idx_msg_sender`   (`sender_id`),
    INDEX `idx_msg_receiver` (`receiver_id`),
    INDEX `idx_msg_created`  (`created_at`),
    CONSTRAINT `fk_msg_sender`   FOREIGN KEY (`sender_id`)   REFERENCES `trusted_social_network_platform`.`users` (`user_id`) ON DELETE CASCADE   ON UPDATE CASCADE,
    CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `trusted_social_network_platform`.`users` (`user_id`) ON DELETE CASCADE   ON UPDATE CASCADE,
    CONSTRAINT `fk_msg_file`     FOREIGN KEY (`file_id`)     REFERENCES `trusted_social_network_platform`.`files` (`file_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trusted_social_network_platform`.`reports` (
    `report_id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reporter_id`      INT UNSIGNED NOT NULL,
    `post_id`          INT UNSIGNED NULL DEFAULT NULL,
    `reported_user_id` INT UNSIGNED NULL DEFAULT NULL,
    `reason`           ENUM('spam','harassment','inappropriate_content','misinformation','copyright_violation','other') NOT NULL DEFAULT 'other',
    `details`          TEXT         NULL DEFAULT NULL,
    `status`           ENUM('pending','under_review','resolved','rejected') NOT NULL DEFAULT 'pending',
    `handled_by`       INT UNSIGNED NULL DEFAULT NULL,
    `action_taken`     VARCHAR(255) NULL DEFAULT NULL,
    `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`report_id`),
    INDEX `idx_reports_reporter` (`reporter_id`),
    INDEX `idx_reports_post`     (`post_id`),
    INDEX `idx_reports_status`   (`status`),
    CONSTRAINT `fk_reports_reporter` FOREIGN KEY (`reporter_id`)      REFERENCES `trusted_social_network_platform`.`users` (`user_id`) ON DELETE CASCADE   ON UPDATE CASCADE,
    CONSTRAINT `fk_reports_post`     FOREIGN KEY (`post_id`)          REFERENCES `trusted_social_network_platform`.`posts` (`post_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_reports_reported` FOREIGN KEY (`reported_user_id`) REFERENCES `trusted_social_network_platform`.`users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_reports_handler`  FOREIGN KEY (`handled_by`)       REFERENCES `trusted_social_network_platform`.`users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trusted_social_network_platform`.`notifications` (
    `notification_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`         INT UNSIGNED NOT NULL,
    `type`            ENUM('new_message','new_post','post_like','post_comment','group_invite','report_update','announcement','account_warning') NOT NULL,
    `content`         VARCHAR(500) NOT NULL,
    `reference`       VARCHAR(255) NULL DEFAULT NULL,
    `is_read`         TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`notification_id`),
    INDEX `idx_notif_user` (`user_id`),
    INDEX `idx_notif_read` (`user_id`, `is_read`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `trusted_social_network_platform`.`users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trusted_social_network_platform`.`audit_logs` (
    `log_id`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED    NULL DEFAULT NULL,
    `action`      ENUM('login','logout','login_failed','register','post_create','post_delete','post_edit','file_upload','file_delete','report_submit','account_suspend','account_delete','permission_change','password_change') NOT NULL,
    `description` TEXT            NULL DEFAULT NULL,
    `ip_address`  VARCHAR(45)     NULL DEFAULT NULL,
    `user_agent`  VARCHAR(500)    NULL DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`),
    INDEX `idx_audit_user`   (`user_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_date`   (`created_at`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `trusted_social_network_platform`.`users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fcm_tokens (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token       TEXT         NOT NULL,
    device_type ENUM('android','ios','web') NOT NULL DEFAULT 'android',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_fcm_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_active (user_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `trusted_social_network_platform`.`users` (`username`, `email`, `password_hash`, `role`, `full_name`, `academic_id`, `department`, `is_verified`, `status`) VALUES
('admin',            'admin@unilink.local',      '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'admin',      'System Administrator', 'ADM-001',  'IT Department',      1, 'active'),
('supervisor01',     'supervisor@unilink.local', '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'supervisor', 'Main Supervisor',      'SUP-001',  'Student Affairs',    1, 'active'),
('professor01',      'professor@unilink.local',  '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'professor',  'Ahmed Khalid',         'FAC-301',  'Computer Science',   1, 'active'),
('professor02',      'professor2@unilink.local', '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'professor',  'Lina Sami',            'FAC-302',  'Information Systems',1, 'active'),
('student01',        'student@unilink.local',    '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'student',    'Rania Fahad',          'STU-2001', 'Computer Science',   1, 'active'),
('student02',        'student2@unilink.local',   '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'student',    'Khalid Abdulrahman',   'STU-2002', 'Information Systems',1, 'active'),
('student03',        'student3@unilink.local',   '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'student',    'Noor Ibrahim',         'STU-2003', 'Computer Science',   1, 'active'),
('student_suspended','suspended@unilink.local',  '$2y$10$3W/qLmgnzdpw7wQ54.o4EuZL6jfd6gW9IFQn1uv58r/H/DEnBvSZO', 'student',    'Sara Ali',             'STU-2004', 'Computer Science',   1, 'suspended')
ON DUPLICATE KEY UPDATE `password_hash`=VALUES(`password_hash`), `role`=VALUES(`role`), `is_verified`=VALUES(`is_verified`), `status`=VALUES(`status`), `updated_at`=NOW();

INSERT INTO `trusted_social_network_platform`.`groups` (`group_name`, `description`, `type`, `privacy`, `created_by`, `members_count`)
SELECT 'Data Structures Course', 'Data Structures students group - Semester 1 2024', 'course', 'private', user_id, 3
FROM `trusted_social_network_platform`.`users` WHERE username = 'professor01' LIMIT 1;

INSERT INTO `trusted_social_network_platform`.`groups` (`group_name`, `description`, `type`, `privacy`, `created_by`, `members_count`)
SELECT 'Computer Science Dept', 'General group for CS students and faculty', 'department', 'restricted', user_id, 5
FROM `trusted_social_network_platform`.`users` WHERE username = 'admin' LIMIT 1;

INSERT INTO `trusted_social_network_platform`.`groups` (`group_name`, `description`, `type`, `privacy`, `created_by`, `members_count`)
SELECT 'Programming Club', 'University programming club - activities and workshops', 'activity', 'public', user_id, 2
FROM `trusted_social_network_platform`.`users` WHERE username = 'supervisor01' LIMIT 1;

INSERT INTO `trusted_social_network_platform`.`posts` (`user_id`, `group_id`, `content`, `type`, `visibility`, `likes_count`, `comments_count`)
SELECT u.user_id, g.group_id, 'First exam is next Sunday. Review chapters 1 to 4.', 'announcement', 'group', 12, 5
FROM `trusted_social_network_platform`.`users` u JOIN `trusted_social_network_platform`.`groups` g ON g.group_name = 'Data Structures Course'
WHERE u.username = 'professor01' LIMIT 1;

INSERT INTO `trusted_social_network_platform`.`posts` (`user_id`, `group_id`, `content`, `type`, `visibility`, `likes_count`, `comments_count`)
SELECT user_id, NULL, 'Can anyone help me understand the Dijkstra algorithm?', 'question', 'public', 3, 8
FROM `trusted_social_network_platform`.`users` WHERE username = 'student01' LIMIT 1;

INSERT INTO `trusted_social_network_platform`.`posts` (`user_id`, `group_id`, `content`, `type`, `visibility`, `likes_count`, `comments_count`)
SELECT u.user_id, g.group_id, 'Reminder: Project submission deadline is end of next week.', 'announcement', 'group', 20, 2
FROM `trusted_social_network_platform`.`users` u JOIN `trusted_social_network_platform`.`groups` g ON g.group_name = 'Computer Science Dept'
WHERE u.username = 'professor02' LIMIT 1;

INSERT INTO `trusted_social_network_platform`.`posts` (`user_id`, `group_id`, `content`, `type`, `visibility`, `likes_count`, `comments_count`)
SELECT user_id, NULL, 'Welcome to UniLink platform! We are glad to have you.', 'post', 'public', 45, 12
FROM `trusted_social_network_platform`.`users` WHERE username = 'admin' LIMIT 1;

INSERT INTO `trusted_social_network_platform`.`posts` (`user_id`, `group_id`, `content`, `type`, `visibility`, `likes_count`, `comments_count`)
SELECT u.user_id, g.group_id, 'JavaScript workshop this Thursday at 6pm in the training room.', 'announcement', 'group', 8, 3
FROM `trusted_social_network_platform`.`users` u JOIN `trusted_social_network_platform`.`groups` g ON g.group_name = 'Programming Club'
WHERE u.username = 'student02' LIMIT 1;

INSERT INTO `trusted_social_network_platform`.`reports` (`reporter_id`, `post_id`, `reported_user_id`, `reason`, `details`, `status`, `handled_by`)
SELECT r.user_id, NULL, s.user_id, 'harassment', 'User sends abusive messages.', 'resolved', h.user_id
FROM `trusted_social_network_platform`.`users` r
JOIN `trusted_social_network_platform`.`users` s ON s.username = 'student_suspended'
JOIN `trusted_social_network_platform`.`users` h ON h.username = 'supervisor01'
WHERE r.username = 'student01' LIMIT 1;

INSERT INTO `trusted_social_network_platform`.`reports` (`reporter_id`, `post_id`, `reported_user_id`, `reason`, `details`, `status`, `handled_by`)
SELECT u.user_id, NULL, NULL, 'spam', 'Post contains unauthorized promotional links.', 'under_review', h.user_id
FROM `trusted_social_network_platform`.`users` u
JOIN `trusted_social_network_platform`.`users` h ON h.username = 'supervisor01'
WHERE u.username = 'student02' LIMIT 1;

INSERT INTO `trusted_social_network_platform`.`reports` (`reporter_id`, `post_id`, `reported_user_id`, `reason`, `details`, `status`, `handled_by`)
SELECT r.user_id, NULL, s.user_id, 'inappropriate_content', 'Inappropriate content in comments.', 'pending', NULL
FROM `trusted_social_network_platform`.`users` r
JOIN `trusted_social_network_platform`.`users` s ON s.username = 'student02'
WHERE r.username = 'student03' LIMIT 1;

INSERT INTO `trusted_social_network_platform`.`reports` (`reporter_id`, `post_id`, `reported_user_id`, `reason`, `details`, `status`, `handled_by`)
SELECT u.user_id, NULL, NULL, 'misinformation', 'Wrong information about the upcoming exam.', 'rejected', h.user_id
FROM `trusted_social_network_platform`.`users` u
JOIN `trusted_social_network_platform`.`users` h ON h.username = 'admin'
WHERE u.username = 'student01' LIMIT 1;

INSERT INTO `trusted_social_network_platform`.`audit_logs` (`user_id`, `action`, `description`, `ip_address`)
SELECT user_id, 'login', 'Admin login', '192.168.1.1' FROM `trusted_social_network_platform`.`users` WHERE username = 'admin' LIMIT 1;

INSERT INTO `trusted_social_network_platform`.`audit_logs` (`user_id`, `action`, `description`, `ip_address`)
SELECT user_id, 'login', 'Supervisor login', '192.168.1.2' FROM `trusted_social_network_platform`.`users` WHERE username = 'supervisor01' LIMIT 1;

INSERT INTO `trusted_social_network_platform`.`audit_logs` (`user_id`, `action`, `description`, `ip_address`)
SELECT user_id, 'account_suspend', 'Suspended student_suspended for policy violation', '192.168.1.1' FROM `trusted_social_network_platform`.`users` WHERE username = 'admin' LIMIT 1;

INSERT INTO `trusted_social_network_platform`.`audit_logs` (`user_id`, `action`, `description`, `ip_address`)
SELECT user_id, 'report_submit', 'Harassment report handled and closed', '192.168.1.2' FROM `trusted_social_network_platform`.`users` WHERE username = 'supervisor01' LIMIT 1;

INSERT INTO `trusted_social_network_platform`.`audit_logs` (`user_id`, `action`, `description`, `ip_address`)
SELECT user_id, 'register', 'Database setup complete - system ready', '127.0.0.1' FROM `trusted_social_network_platform`.`users` WHERE username = 'admin' LIMIT 1;


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


-- ============================================================
-- Migration 004 — Branding / Theme Settings
-- Created: 2026-03-30
-- Description: إضافة جدول إعدادات الهوية البصرية للمنصة
-- ============================================================



CREATE TABLE IF NOT EXISTS `branding_settings` (
  `id`                    INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `platform_name`         VARCHAR(120)     NOT NULL DEFAULT 'Trusted Social Network Platform',
  `platform_tagline`      VARCHAR(255)     NOT NULL DEFAULT 'منصة التواصل الأكاديمي الموثوقة',

  -- ── الألوان ────────────────────────────────────────────────
  `primary_color`         VARCHAR(9)       NOT NULL DEFAULT '#004D8C',
  `secondary_color`       VARCHAR(9)       NOT NULL DEFAULT '#007786',
  `accent_color`          VARCHAR(9)       NOT NULL DEFAULT '#00B4D8',
  `background_color`      VARCHAR(9)       NOT NULL DEFAULT '#FFFFFF',
  `text_color`            VARCHAR(9)       NOT NULL DEFAULT '#1E293B',
  `button_primary_color`  VARCHAR(9)       NOT NULL DEFAULT '#004D8C',
  `button_text_color`     VARCHAR(9)       NOT NULL DEFAULT '#FFFFFF',
  `card_bg_color`         VARCHAR(9)       NOT NULL DEFAULT '#F8FAFC',
  `input_bg_color`        VARCHAR(9)       NOT NULL DEFAULT '#FFFFFF',
  `input_border_color`    VARCHAR(9)       NOT NULL DEFAULT '#CBD5E1',

  -- ── الخط ───────────────────────────────────────────────────
  `font_family`           VARCHAR(80)      NOT NULL DEFAULT 'Cairo',

  -- ── الشعار ─────────────────────────────────────────────────
  `logo_path`             VARCHAR(512)              DEFAULT NULL COMMENT 'مسار الشعار نسبة إلى جذر المشروع',

  -- ── القالب النشط ───────────────────────────────────────────
  `active_template_key`   VARCHAR(60)      NOT NULL DEFAULT 'deep_blue',

  -- ── معلومات ───────────────────────────────────────────────
  `updated_by`            INT UNSIGNED              DEFAULT NULL COMMENT 'user_id للمدير الذي آخر تعديل',
  `updated_at`            DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`)
) ENGINE=InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  COMMENT = 'إعدادات الهوية البصرية للمنصة – صف واحد دائماً (id=1)';

-- ── الصف الافتراضي (القالب: Deep Blue) ──────────────────────
INSERT IGNORE INTO `branding_settings`
  (`id`, `platform_name`, `platform_tagline`,
   `primary_color`, `secondary_color`, `accent_color`,
   `background_color`, `text_color`,
   `button_primary_color`, `button_text_color`,
   `card_bg_color`, `input_bg_color`, `input_border_color`,
   `font_family`, `logo_path`, `active_template_key`)
VALUES
  (1, 'Trusted Social Network Platform', 'منصة التواصل الأكاديمي الموثوقة',
   '#004D8C', '#007786', '#00B4D8',
   '#FFFFFF', '#1E293B',
   '#004D8C', '#FFFFFF',
   '#F8FAFC', '#FFFFFF', '#CBD5E1',
   'Cairo', NULL, 'deep_blue');


