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
