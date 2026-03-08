-- ============================================================
-- trusted_social_network_platform — Full Setup Script
-- المشروع: UniLink – Trusted Social Network Platform
-- ============================================================
-- كيفية الاستخدام:
--   mysql -u root -p < trusted_social_admin_modules.sql
--   أو استيراده عبر phpMyAdmin / MySQL Workbench
--
-- كلمة مرور جميع الحسابات التجريبية: Admin@1234
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES 'utf8mb4';
SET time_zone = '+03:00';

-- ─────────────────────────────────────────────────────────────
-- إنشاء قاعدة البيانات
-- ─────────────────────────────────────────────────────────────
CREATE DATABASE IF NOT EXISTS `trusted_social_network_platform`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `trusted_social_network_platform`;

-- ─────────────────────────────────────────────────────────────
-- 1. جدول المستخدمين
--    يشمل: حقول الأدوار (ENUM) + OTP + Remember Me
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `user_id`                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `username`                 VARCHAR(50)   NOT NULL,
    `email`                    VARCHAR(100)  NOT NULL,
    `password_hash`            VARCHAR(255)  NOT NULL,
    `role`                     ENUM('student','professor','admin','supervisor')
                                             NOT NULL DEFAULT 'student',
    `full_name`                VARCHAR(150)  NOT NULL,
    `academic_id`              VARCHAR(30)   NULL DEFAULT NULL,
    `department`               VARCHAR(100)  NULL DEFAULT NULL,
    `avatar_url`               VARCHAR(500)  NULL DEFAULT NULL,
    `is_verified`              TINYINT(1)    NOT NULL DEFAULT 0,
    `status`                   ENUM('active','suspended','deleted')
                                             NOT NULL DEFAULT 'active',
    `otp_code`                 VARCHAR(255)  NULL DEFAULT NULL,
    `otp_expires_at`           DATETIME      NULL DEFAULT NULL,
    `remember_token_hash`      VARCHAR(255)  NULL DEFAULT NULL
                               COMMENT 'SHA-256 hash of the remember-me token',
    `remember_token_expires_at` DATETIME     NULL DEFAULT NULL
                               COMMENT 'Expiry datetime for the remember-me token',
    `last_login`               DATETIME      NULL DEFAULT NULL,
    `created_at`               DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`               DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                       ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email`    (`email`),
    INDEX `idx_users_role`   (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 2. جدول المجموعات
-- ─────────────────────────────────────────────────────────────
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
    `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                            ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`group_id`),
    INDEX `idx_groups_created_by` (`created_by`),
    CONSTRAINT `fk_groups_creator`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 3. جدول أعضاء المجموعات
-- ─────────────────────────────────────────────────────────────
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

-- ─────────────────────────────────────────────────────────────
-- 4. جدول المنشورات
-- ─────────────────────────────────────────────────────────────
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
    `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                             ON UPDATE CURRENT_TIMESTAMP,

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

-- ─────────────────────────────────────────────────────────────
-- 5. جدول الإعجابات
-- ─────────────────────────────────────────────────────────────
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

-- ─────────────────────────────────────────────────────────────
-- 6. جدول الملفات
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `files` (
    `file_id`        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`        INT UNSIGNED    NOT NULL,
    `post_id`        INT UNSIGNED    NULL DEFAULT NULL,
    `original_name`  VARCHAR(255)    NOT NULL,
    `stored_name`    VARCHAR(255)    NOT NULL,
    `file_type`      ENUM('pdf','image','presentation','archive','video','other')
                                     NOT NULL DEFAULT 'other',
    `file_size`      BIGINT UNSIGNED NOT NULL,
    `storage_path`   VARCHAR(500)    NOT NULL,
    `is_encrypted`   TINYINT(1)      NOT NULL DEFAULT 0,
    `download_count` INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

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

-- ─────────────────────────────────────────────────────────────
-- 7. جدول الرسائل
-- ─────────────────────────────────────────────────────────────
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
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_msg_file`
        FOREIGN KEY (`file_id`)     REFERENCES `files` (`file_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 8. جدول البلاغات
-- ─────────────────────────────────────────────────────────────
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
    `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                               ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`report_id`),
    INDEX `idx_reports_reporter` (`reporter_id`),
    INDEX `idx_reports_post`     (`post_id`),
    INDEX `idx_reports_status`   (`status`),
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

-- ─────────────────────────────────────────────────────────────
-- 9. جدول الإشعارات
-- ─────────────────────────────────────────────────────────────
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

-- ─────────────────────────────────────────────────────────────
-- 10. جدول سجلات النشاط (Audit Logs)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `log_id`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED    NULL DEFAULT NULL,
    `action`      ENUM('login','logout','login_failed','register',
                       'post_create','post_delete','post_edit',
                       'file_upload','file_delete','report_submit',
                       'account_suspend','account_delete',
                       'permission_change','password_change')
                                  NOT NULL,
    `description` TEXT            NULL DEFAULT NULL,
    `ip_address`  VARCHAR(45)     NULL DEFAULT NULL,
    `user_agent`  VARCHAR(500)    NULL DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

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
-- البيانات التجريبية (Seed Data)
-- كلمة المرور لجميع الحسابات: Admin@1234
-- الهاش: $2y$10$2RBv4yn8qw/EgaZA/p8yC.1QjML25TD7Qp4IA6gsrVot16G/ZV6Zm
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- مستخدمو النظام
-- ─────────────────────────────────────────────────────────────
INSERT INTO `users`
    (`username`, `email`, `password_hash`, `role`, `full_name`,
     `academic_id`, `department`, `is_verified`, `status`)
VALUES
-- مدير النظام
(   'admin',
    'admin@unilink.local',
    '$2y$10$2RBv4yn8qw/EgaZA/p8yC.1QjML25TD7Qp4IA6gsrVot16G/ZV6Zm',
    'admin', 'System Administrator', 'ADM-001', 'IT Department', 1, 'active'),

-- مشرف
(   'supervisor01',
    'supervisor@unilink.local',
    '$2y$10$2RBv4yn8qw/EgaZA/p8yC.1QjML25TD7Qp4IA6gsrVot16G/ZV6Zm',
    'supervisor', 'Main Supervisor', 'SUP-001', 'Student Affairs', 1, 'active'),

-- أستاذ 1
(   'professor01',
    'professor@unilink.local',
    '$2y$10$2RBv4yn8qw/EgaZA/p8yC.1QjML25TD7Qp4IA6gsrVot16G/ZV6Zm',
    'professor', 'د. أحمد خالد النجار', 'FAC-301', 'قسم علوم الحاسوب', 1, 'active'),

-- أستاذ 2
(   'professor02',
    'professor2@unilink.local',
    '$2y$10$2RBv4yn8qw/EgaZA/p8yC.1QjML25TD7Qp4IA6gsrVot16G/ZV6Zm',
    'professor', 'د. لينا سامي الرشيد', 'FAC-302', 'قسم نظم المعلومات', 1, 'active'),

-- طالب 1
(   'student01',
    'student@unilink.local',
    '$2y$10$2RBv4yn8qw/EgaZA/p8yC.1QjML25TD7Qp4IA6gsrVot16G/ZV6Zm',
    'student', 'رانيا فهد الزهراني', 'STU-2001', 'قسم علوم الحاسوب', 1, 'active'),

-- طالب 2
(   'student02',
    'student2@unilink.local',
    '$2y$10$2RBv4yn8qw/EgaZA/p8yC.1QjML25TD7Qp4IA6gsrVot16G/ZV6Zm',
    'student', 'خالد عبدالرحمن البلوي', 'STU-2002', 'قسم نظم المعلومات', 1, 'active'),

-- طالب 3
(   'student03',
    'student3@unilink.local',
    '$2y$10$2RBv4yn8qw/EgaZA/p8yC.1QjML25TD7Qp4IA6gsrVot16G/ZV6Zm',
    'student', 'نور إبراهيم الحمدان', 'STU-2003', 'قسم علوم الحاسوب', 1, 'active'),

-- طالب موقوف
(   'student_suspended',
    'suspended@unilink.local',
    '$2y$10$2RBv4yn8qw/EgaZA/p8yC.1QjML25TD7Qp4IA6gsrVot16G/ZV6Zm',
    'student', 'سارة علي المطيري', 'STU-2004', 'قسم علوم الحاسوب', 1, 'suspended')

ON DUPLICATE KEY UPDATE
    `password_hash` = VALUES(`password_hash`),
    `role`          = VALUES(`role`),
    `is_verified`   = VALUES(`is_verified`),
    `status`        = VALUES(`status`),
    `updated_at`    = NOW();

-- ─────────────────────────────────────────────────────────────
-- مجموعات تجريبية
-- ─────────────────────────────────────────────────────────────
INSERT INTO `groups`
    (`group_name`, `description`, `type`, `privacy`, `created_by`, `members_count`)
VALUES
(   'مقرر هياكل البيانات',
    'مجموعة طلاب مقرر هياكل البيانات — الفصل الأول 2024',
    'course', 'private',
    (SELECT user_id FROM users WHERE username = 'professor01' LIMIT 1), 3),

(   'قسم علوم الحاسوب',
    'مجموعة عامة لطلاب وأعضاء هيئة التدريس في القسم',
    'department', 'restricted',
    (SELECT user_id FROM users WHERE username = 'admin' LIMIT 1), 5),

(   'نادي البرمجة',
    'نادي البرمجة الجامعي — أنشطة وورش عمل',
    'activity', 'public',
    (SELECT user_id FROM users WHERE username = 'supervisor01' LIMIT 1), 2)

ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- ─────────────────────────────────────────────────────────────
-- منشورات تجريبية
-- ─────────────────────────────────────────────────────────────
INSERT INTO `posts`
    (`user_id`, `group_id`, `content`, `type`, `visibility`, `likes_count`, `comments_count`)
VALUES
(   (SELECT user_id FROM users WHERE username = 'professor01' LIMIT 1),
    (SELECT group_id FROM `groups` WHERE group_name = 'مقرر هياكل البيانات' LIMIT 1),
    'الاختبار الأول سيكون يوم الأحد القادم، راجعوا فصول 1 إلى 4 من الكتاب المقرر.',
    'announcement', 'group', 12, 5),

(   (SELECT user_id FROM users WHERE username = 'student01' LIMIT 1),
    NULL,
    'هل من يستطيع مساعدتي في فهم خوارزمية Dijkstra؟',
    'question', 'public', 3, 8),

(   (SELECT user_id FROM users WHERE username = 'professor02' LIMIT 1),
    (SELECT group_id FROM `groups` WHERE group_name = 'قسم علوم الحاسوب' LIMIT 1),
    'تذكير: آخر موعد لتسليم المشاريع هو نهاية الأسبوع القادم.',
    'announcement', 'group', 20, 2),

(   (SELECT user_id FROM users WHERE username = 'admin' LIMIT 1),
    NULL,
    'مرحباً بكم جميعاً في منصة UniLink! نسعد بانضمامكم.',
    'post', 'public', 45, 12),

(   (SELECT user_id FROM users WHERE username = 'student02' LIMIT 1),
    (SELECT group_id FROM `groups` WHERE group_name = 'نادي البرمجة' LIMIT 1),
    'ورشة عمل JavaScript يوم الخميس الساعة 6 مساءً في قاعة التدريب.',
    'announcement', 'group', 8, 3);

-- ─────────────────────────────────────────────────────────────
-- بلاغات تجريبية
-- ─────────────────────────────────────────────────────────────
INSERT INTO `reports`
    (`reporter_id`, `post_id`, `reported_user_id`, `reason`, `details`, `status`, `handled_by`)
VALUES
(   (SELECT user_id FROM users WHERE username = 'student01' LIMIT 1),
    NULL,
    (SELECT user_id FROM users WHERE username = 'student_suspended' LIMIT 1),
    'harassment',
    'المستخدم يرسل رسائل مزعجة ومسيئة.',
    'resolved',
    (SELECT user_id FROM users WHERE username = 'supervisor01' LIMIT 1)),

(   (SELECT user_id FROM users WHERE username = 'student02' LIMIT 1),
    (SELECT post_id FROM posts LIMIT 1 OFFSET 1),
    NULL,
    'spam',
    'هذا المنشور يحتوي على روابط دعائية غير مرخصة.',
    'under_review',
    (SELECT user_id FROM users WHERE username = 'supervisor01' LIMIT 1)),

(   (SELECT user_id FROM users WHERE username = 'student03' LIMIT 1),
    NULL,
    (SELECT user_id FROM users WHERE username = 'student02' LIMIT 1),
    'inappropriate_content',
    'محتوى غير لائق في التعليقات.',
    'pending',
    NULL),

(   (SELECT user_id FROM users WHERE username = 'student01' LIMIT 1),
    (SELECT post_id FROM posts LIMIT 1 OFFSET 0),
    NULL,
    'misinformation',
    'معلومات خاطئة عن الاختبار القادم.',
    'rejected',
    (SELECT user_id FROM users WHERE username = 'admin' LIMIT 1));

-- ─────────────────────────────────────────────────────────────
-- سجلات نشاط تجريبية
-- ─────────────────────────────────────────────────────────────
INSERT INTO `audit_logs` (`user_id`, `action`, `description`, `ip_address`)
VALUES
(   (SELECT user_id FROM users WHERE username = 'admin' LIMIT 1),
    'login', 'تسجيل دخول مدير النظام', '192.168.1.1'),

(   (SELECT user_id FROM users WHERE username = 'supervisor01' LIMIT 1),
    'login', 'تسجيل دخول المشرف', '192.168.1.2'),

(   (SELECT user_id FROM users WHERE username = 'admin' LIMIT 1),
    'account_suspend',
    'تعليق حساب student_suspended بسبب مخالفة السياسات',
    '192.168.1.1'),

(   (SELECT user_id FROM users WHERE username = 'supervisor01' LIMIT 1),
    'report_submit',
    'تم معالجة بلاغ المضايقة وإغلاقه',
    '192.168.1.2'),

(   (SELECT user_id FROM users WHERE username = 'admin' LIMIT 1),
    'register',
    'اكتمل إعداد قاعدة البيانات بنجاح — النظام جاهز',
    '127.0.0.1');

-- ============================================================
-- ✅ تم الإعداد بنجاح!
--
-- بيانات الدخول:
--   admin@unilink.local       / Admin@1234  (مدير النظام)
--   supervisor@unilink.local  / Admin@1234  (مشرف)
--   professor@unilink.local   / Admin@1234  (أستاذ)
--   student@unilink.local     / Admin@1234  (طالب)
-- ============================================================
