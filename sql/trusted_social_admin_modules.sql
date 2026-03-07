-- 01_schema.sql
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES 'utf8mb4';

CREATE DATABASE IF NOT EXISTS `unilink_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `unilink_db`;

CREATE TABLE IF NOT EXISTS `users` (
    `user_id`        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `username`       VARCHAR(50)   NOT NULL,
    `email`          VARCHAR(100)  NOT NULL,
    `password_hash`  VARCHAR(255)  NOT NULL,
    `role`           ENUM('student','professor','admin','supervisor')
                                   NOT NULL DEFAULT 'student',
    `full_name`      VARCHAR(150)  NOT NULL,
    `academic_id`    VARCHAR(30)   NULL DEFAULT NULL,
    `department`     VARCHAR(100)  NULL DEFAULT NULL,
    `avatar_url`     VARCHAR(500)  NULL DEFAULT NULL,
    `is_verified`    TINYINT(1)    NOT NULL DEFAULT 0,
    `status`         ENUM('active','suspended','deleted')
                                   NOT NULL DEFAULT 'active',
    `otp_code`       VARCHAR(255)  NULL DEFAULT NULL,
    `otp_expires_at` DATETIME      NULL DEFAULT NULL,
    `last_login`     DATETIME      NULL DEFAULT NULL,
    `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `groups` (
    `group_id`      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `group_name`    VARCHAR(150)  NOT NULL,
    `description`   TEXT          NULL DEFAULT NULL,
    `type`          ENUM('course','department','activity','administrative')
                                  NOT NULL DEFAULT 'course',
    `privacy`       ENUM('public','private','restricted')
                                  NOT NULL DEFAULT 'private',
    `created_by`    INT UNSIGNED  NOT NULL,
    `members_count` INT UNSIGNED  NOT NULL DEFAULT 1,
    `cover_url`     VARCHAR(500)  NULL DEFAULT NULL,
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`group_id`),
    INDEX `idx_groups_created_by` (`created_by`),
    CONSTRAINT `fk_groups_creator`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `group_members` (
    `membership_id` INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `group_id`      INT UNSIGNED  NOT NULL,
    `user_id`       INT UNSIGNED  NOT NULL,
    `member_role`   ENUM('owner','moderator','member')
                                  NOT NULL DEFAULT 'member',
    `joined_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`membership_id`),
    UNIQUE KEY `uq_group_member` (`group_id`, `user_id`),
    INDEX `idx_gm_user` (`user_id`),
    CONSTRAINT `fk_gm_group`
        FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_gm_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `posts` (
    `post_id`        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`        INT UNSIGNED  NOT NULL,
    `group_id`       INT UNSIGNED  NULL DEFAULT NULL,
    `content`        TEXT          NOT NULL,
    `type`           ENUM('post','announcement','question','lecture')
                                   NOT NULL DEFAULT 'post',
    `visibility`     ENUM('public','group','private')
                                   NOT NULL DEFAULT 'public',
    `likes_count`    INT UNSIGNED  NOT NULL DEFAULT 0,
    `comments_count` INT UNSIGNED  NOT NULL DEFAULT 0,
    `is_flagged`     TINYINT(1)    NOT NULL DEFAULT 0,
    `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`post_id`),
    INDEX `idx_posts_user`    (`user_id`),
    INDEX `idx_posts_group`   (`group_id`),
    INDEX `idx_posts_created` (`created_at`),
    CONSTRAINT `fk_posts_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_posts_group`
        FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `post_likes` (
    `like_id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `post_id`    INT UNSIGNED NOT NULL,
    `user_id`    INT UNSIGNED NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`like_id`),
    UNIQUE KEY `uq_post_like` (`post_id`, `user_id`),
    CONSTRAINT `fk_likes_post`
        FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_likes_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `messages` (
    `msg_id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sender_id`   INT UNSIGNED NOT NULL,
    `receiver_id` INT UNSIGNED NOT NULL,
    `content`     TEXT         NOT NULL,
    `type`        ENUM('text','image','file')
                               NOT NULL DEFAULT 'text',
    `is_read`     TINYINT(1)   NOT NULL DEFAULT 0,
    `file_id`     INT UNSIGNED NULL DEFAULT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`msg_id`),
    INDEX `idx_msg_sender`   (`sender_id`),
    INDEX `idx_msg_receiver` (`receiver_id`),
    INDEX `idx_msg_created`  (`created_at`),
    CONSTRAINT `fk_msg_sender`
        FOREIGN KEY (`sender_id`)   REFERENCES `users` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_msg_receiver`
        FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `files` (
    `file_id`        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`        INT UNSIGNED  NOT NULL,
    `post_id`        INT UNSIGNED  NULL DEFAULT NULL,
    `original_name`  VARCHAR(255)  NOT NULL,
    `stored_name`    VARCHAR(255)  NOT NULL,
    `file_type`      ENUM('pdf','image','presentation','archive','video','other')
                                   NOT NULL DEFAULT 'other',
    `file_size`      BIGINT UNSIGNED NOT NULL,
    `storage_path`   VARCHAR(500)  NOT NULL,
    `is_encrypted`   TINYINT(1)    NOT NULL DEFAULT 0,
    `download_count` INT UNSIGNED  NOT NULL DEFAULT 0,
    `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`file_id`),
    INDEX `idx_files_user` (`user_id`),
    INDEX `idx_files_post` (`post_id`),
    CONSTRAINT `fk_files_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_files_post`
        FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `messages`
    ADD CONSTRAINT `fk_msg_file`
        FOREIGN KEY (`file_id`) REFERENCES `files` (`file_id`)
        ON DELETE SET NULL ON UPDATE CASCADE;

CREATE TABLE IF NOT EXISTS `reports` (
    `report_id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reporter_id`      INT UNSIGNED NOT NULL,
    `post_id`          INT UNSIGNED NULL DEFAULT NULL,
    `reported_user_id` INT UNSIGNED NULL DEFAULT NULL,
    `reason`           ENUM('spam','harassment','inappropriate_content',
                            'misinformation','copyright_violation','other')
                                     NOT NULL DEFAULT 'other',
    `details`          TEXT          NULL DEFAULT NULL,
    `status`           ENUM('pending','under_review','resolved','rejected')
                                     NOT NULL DEFAULT 'pending',
    `handled_by`       INT UNSIGNED  NULL DEFAULT NULL,
    `action_taken`     VARCHAR(255)  NULL DEFAULT NULL,
    `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`report_id`),
    INDEX `idx_reports_reporter`  (`reporter_id`),
    INDEX `idx_reports_post`      (`post_id`),
    INDEX `idx_reports_status`    (`status`),
    CONSTRAINT `fk_reports_reporter`
        FOREIGN KEY (`reporter_id`)      REFERENCES `users` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reports_post`
        FOREIGN KEY (`post_id`)          REFERENCES `posts` (`post_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_reports_reported`
        FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`user_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_reports_handler`
        FOREIGN KEY (`handled_by`)       REFERENCES `users` (`user_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
    `notification_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`         INT UNSIGNED NOT NULL,
    `type`            ENUM('new_message','new_post','post_like','post_comment',
                           'group_invite','report_update','announcement','account_warning')
                                   NOT NULL,
    `content`         VARCHAR(500) NOT NULL,
    `reference`       VARCHAR(255) NULL DEFAULT NULL,
    `is_read`         TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`notification_id`),
    INDEX `idx_notif_user` (`user_id`),
    INDEX `idx_notif_read` (`user_id`, `is_read`),
    CONSTRAINT `fk_notif_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `log_id`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED    NULL DEFAULT NULL,
    `action`     ENUM('login','logout','login_failed','register',
                      'post_create','post_delete','post_edit',
                      'file_upload','file_delete','report_submit',
                      'account_suspend','account_delete',
                      'permission_change','password_change')
                                 NOT NULL,
    `description` TEXT           NULL DEFAULT NULL,
    `ip_address`  VARCHAR(45)    NULL DEFAULT NULL,
    `user_agent`  VARCHAR(500)   NULL DEFAULT NULL,
    `created_at`  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`log_id`),
    INDEX `idx_audit_user`   (`user_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_date`   (`created_at`),
    CONSTRAINT `fk_audit_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- 02_admin_panel_additions.sql
-- إضافات مطلوبة لتشغيل لوحة تحكم UniLink
-- تُنفَّذ على قاعدة البيانات: unilink_db (Amazon RDS)
--
-- الهدف: إضافة حقول Remember Token إلى جدول users
--        (01_schema.sql لا يحتوي عليها، لكن auth.php يحتاجها)
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 1. إضافة حقول Remember Me Token إلى جدول users
--    (مطلوبة لميزة "تذكرني" في لوحة التحكم)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `remember_token_hash`
        VARCHAR(255) NULL DEFAULT NULL
        COMMENT 'SHA-256 hash of the remember-me token'
        AFTER `otp_expires_at`,

    ADD COLUMN IF NOT EXISTS `remember_token_expires_at`
        DATETIME NULL DEFAULT NULL
        COMMENT 'Expiry datetime for the remember-me token'
        AFTER `remember_token_hash`;

-- ─────────────────────────────────────────────────────────────
-- 2. تحديث ENUM حقل action في audit_logs
--    لإضافة 'permission_change' إذا لم تكن موجودة
--    (بعض إصدارات MySQL لا تدعم ADD COLUMN IF NOT EXISTS
--     للـ ENUM، لذا نستخدم طريقة آمنة)
-- ─────────────────────────────────────────────────────────────
-- ملاحظة: 01_schema.sql يحتوي بالفعل على 'permission_change'
-- في الـ ENUM، لذا هذا السطر للتأكيد فقط ويمكن تجاهله.
-- ALTER TABLE `audit_logs` MODIFY COLUMN `action` ENUM(...);

-- ─────────────────────────────────────────────────────────────
-- 3. إدخال بيانات تجريبية — مستخدم admin افتراضي
--    كلمة المرور: Admin@1234 (مشفرة بـ bcrypt)
-- ─────────────────────────────────────────────────────────────
INSERT INTO `users`
    (`username`, `email`, `password_hash`, `role`, `full_name`,
     `academic_id`, `department`, `is_verified`, `status`)
VALUES
    ('admin',
     'admin@unilink.local',
     '$2y$10$2RBv4yn8qw/EgaZA/p8yC.1QjML25TD7Qp4IA6gsrVot16G/ZV6Zm',
     'admin',
     'System Administrator',
     'ADM-001',
     'IT Department',
     1,
     'active')
ON DUPLICATE KEY UPDATE
    `role` = 'admin',
    `is_verified` = 1,
    `status` = 'active';

INSERT INTO `users`
    (`username`, `email`, `password_hash`, `role`, `full_name`,
     `academic_id`, `department`, `is_verified`, `status`)
VALUES
    ('supervisor01',
     'supervisor@unilink.local',
     '$2y$10$2RBv4yn8qw/EgaZA/p8yC.1QjML25TD7Qp4IA6gsrVot16G/ZV6Zm',
     'supervisor',
     'Main Supervisor',
     'SUP-001',
     'Student Affairs',
     1,
     'active')
ON DUPLICATE KEY UPDATE
    `role` = 'supervisor',
    `is_verified` = 1,
    `status` = 'active';

INSERT INTO `users`
    (`username`, `email`, `password_hash`, `role`, `full_name`,
     `academic_id`, `department`, `is_verified`, `status`)
VALUES
    ('professor01',
     'professor@unilink.local',
     '$2y$10$2RBv4yn8qw/EgaZA/p8yC.1QjML25TD7Qp4IA6gsrVot16G/ZV6Zm',
     'professor',
     'Demo Professor',
     'PROF-001',
     'Computer Science',
     1,
     'active')
ON DUPLICATE KEY UPDATE
    `role` = 'professor',
    `is_verified` = 1,
    `status` = 'active';

INSERT INTO `users`
    (`username`, `email`, `password_hash`, `role`, `full_name`,
     `academic_id`, `department`, `is_verified`, `status`)
VALUES
    ('student01',
     'student@unilink.local',
     '$2y$10$2RBv4yn8qw/EgaZA/p8yC.1QjML25TD7Qp4IA6gsrVot16G/ZV6Zm',
     'student',
     'Demo Student',
     'STU-2024-001',
     'Computer Science',
     1,
     'active')
ON DUPLICATE KEY UPDATE
    `role` = 'student',
    `is_verified` = 1,
    `status` = 'active';

-- ─────────────────────────────────────────────────────────────
-- 4. سجل نشاط تجريبي للتأكد من عمل audit_logs
-- ─────────────────────────────────────────────────────────────
INSERT INTO `audit_logs` (`user_id`, `action`, `description`, `ip_address`)
SELECT user_id, 'login', 'Initial admin seed completed — system ready.', '127.0.0.1'
FROM users WHERE username = 'admin' LIMIT 1;

-- ============================================================
-- كلمة المرور الافتراضية لجميع الحسابات: Admin@1234
-- يُرجى تغييرها فور تسجيل الدخول الأول
-- ============================================================
