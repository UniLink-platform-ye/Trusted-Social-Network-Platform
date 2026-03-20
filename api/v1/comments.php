<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$me = api_require_auth();
$uid = (int)$me['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $postId = (int)($_GET['post_id'] ?? 0);
    if ($postId <= 0) api_error('post_id مطلوب');

    try {
        $st = db()->prepare('
            SELECT c.*, u.full_name, u.avatar_url, u.role 
            FROM post_comments c
            JOIN users u ON u.user_id = c.user_id
            WHERE c.post_id = :pid
            ORDER BY c.created_at ASC
        ');
        $st->execute([':pid' => $postId]);
        api_ok(['comments' => $st->fetchAll()]);
    } catch (\Throwable $e) {
        api_error($e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $b = json_body();
    $postId = (int)($b['post_id'] ?? 0);
    $content = trim((string)($b['content'] ?? ''));

    if ($postId <= 0) api_error('post_id مطلوب');
    if ($content === '') api_error('محتوى التعليق فارغ');

    try {
        $db = db();
        $db->beginTransaction();

        $ins = $db->prepare('INSERT INTO post_comments (post_id, user_id, content, created_at) VALUES (:p, :u, :c, NOW())');
        $ins->execute([':p' => $postId, ':u' => $uid, ':c' => $content]);
        $commentId = (int)$db->lastInsertId();

        $db->prepare('UPDATE posts SET comments_count = comments_count + 1 WHERE post_id = :p')->execute([':p' => $postId]);
        
        $db->commit();

        // إشعار صاحب المنشور
        $stmt = $db->prepare('SELECT user_id, content FROM posts WHERE post_id = :p LIMIT 1');
        $stmt->execute([':p' => $postId]);
        $post = $stmt->fetch();
        if ($post && (int)$post['user_id'] !== $uid) {
            create_notification(
                (int)$post['user_id'],
                'post_comment',
                'تعليق جديد',
                'قام ' . $me['full_name'] . ' بالتعليق على منشورك',
                APP_BASE_PATH . '/app/feed.php',
                $uid
            );
        }

        api_ok(['comment_id' => $commentId], 'تم إضافة التعليق بنجاح', 201);
    } catch (\Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        api_error($e->getMessage(), 500);
    }
}

if ($method === 'DELETE') {
    $commentId = (int)($_GET['id'] ?? 0);
    if ($commentId <= 0) api_error('id مطلوب');

    try {
        $db = db();
        $stmt = $db->prepare('
            SELECT c.*, p.user_id AS post_owner_id 
            FROM post_comments c 
            JOIN posts p ON p.post_id = c.post_id 
            WHERE c.comment_id = :id LIMIT 1
        ');
        $stmt->execute([':id' => $commentId]);
        $comment = $stmt->fetch();

        if (!$comment) api_error('التعليق غير موجود', 404);

        $isCommentOwner = (int)$comment['user_id'] === $uid;
        $isPostOwner = (int)$comment['post_owner_id'] === $uid;
        $isAdmin = in_array($me['role'], ['admin', 'supervisor'], true);

        if (!$isCommentOwner && !$isPostOwner && !$isAdmin) {
            api_error('ليس لديك صلاحية لحذف هذا التعليق', 403);
        }

        $db->beginTransaction();
        $db->prepare('DELETE FROM post_comments WHERE comment_id = :id')->execute([':id' => $commentId]);
        $db->prepare('UPDATE posts SET comments_count = GREATEST(0, comments_count - 1) WHERE post_id = :p')->execute([':p' => $comment['post_id']]);
        $db->commit();

        api_ok([], 'تم حذف التعليق بنجاح');
    } catch (\Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        api_error($e->getMessage(), 500);
    }
}

api_error('Method Not Allowed', 405);
