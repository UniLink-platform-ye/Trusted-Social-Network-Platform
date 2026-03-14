<?php
declare(strict_types=1);

/**
 * core/notify.php — نظام الإشعارات المشترك
 * يُستخدم من: app/ وadmin/ وapi/
 */

require_once __DIR__ . '/fcm_v1.php';

/**
 * إنشاء إشعار لمستخدم وإرساله عبر FCM (إذا كان له توكن)
 */
function create_notification(
    int    $userId,
    string $type,
    string $title,
    string $body,
    string $link    = '',
    int    $actorId = 0
): void {
    try {
        $stmt = db()->prepare(
            'INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at)
             VALUES (:u, :t, :ti, :b, :l, 0, NOW())'
        );
        $stmt->execute([
            ':u'  => $userId,
            ':t'  => $type,
            ':ti' => $title,
            ':b'  => $body,
            ':l'  => $link,
        ]);

        // إرسال FCM (لا تعطّل الكود في حالة فشله)
        try { send_push_to_user($userId, $title, $body, ['link' => $link, 'type' => $type]); }
        catch (\Throwable $fe) { error_log('FCM Error: ' . $fe->getMessage()); }

    } catch (\Throwable $e) {
        error_log('Notification DB Error: ' . $e->getMessage());
    }
}

/**
 * إشعار: منشور جديد في مجموعة
 */
function notify_new_post(int $postId, int $groupId, int $authorId, string $authorName): void
{
    if (!$groupId) return;
    try {
        $members = db()->prepare(
            'SELECT user_id FROM group_members WHERE group_id = :g AND user_id != :a'
        );
        $members->execute([':g' => $groupId, ':a' => $authorId]);
        $link = APP_BASE_PATH . '/app/feed.php';
        foreach ($members->fetchAll() as $m) {
            create_notification(
                (int)$m['user_id'],
                'new_post',
                "📝 منشور جديد من $authorName",
                "نشر $authorName منشوراً جديداً في مجموعتك",
                $link,
                $authorId
            );
        }
    } catch (\Throwable $e) { error_log($e->getMessage()); }
}

/**
 * إشعار: رسالة خاصة جديدة
 */
function notify_new_message(int $receiverId, string $senderName, int $senderId): void
{
    create_notification(
        $receiverId,
        'new_message',
        "💬 رسالة جديدة من $senderName",
        "أرسل إليك $senderName رسالة خاصة",
        APP_BASE_PATH . '/app/messages.php?with=' . $senderId,
        $senderId
    );
}

/**
 * إشعار: انضمام عضو جديد للمجموعة
 */
function notify_group_join(int $groupAdminId, string $newMemberName, int $newMemberId, string $groupName): void
{
    create_notification(
        $groupAdminId,
        'group_join',
        "👥 عضو جديد في $groupName",
        "انضم $newMemberName إلى مجموعة $groupName",
        APP_BASE_PATH . '/app/groups.php',
        $newMemberId
    );
}

/**
 * إرسال FCM لجميع أجهزة مستخدم معين (HTTP v1 عبر Service Account)
 */
function send_push_to_user(int $userId, string $title, string $body, array $data = []): void
{
    fcm_send_to_user_v1($userId, $title, $body, $data);
}

