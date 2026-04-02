<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = db();
    $sql = "CREATE TABLE IF NOT EXISTS `post_comments` (
      `comment_id` int unsigned NOT NULL AUTO_INCREMENT,
      `post_id` int unsigned NOT NULL,
      `user_id` int unsigned NOT NULL,
      `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`comment_id`),
      KEY `idx_comments_post` (`post_id`),
      KEY `idx_comments_user` (`user_id`),
      CONSTRAINT `fk_comments_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
      CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql);
    echo "Table post_comments created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
