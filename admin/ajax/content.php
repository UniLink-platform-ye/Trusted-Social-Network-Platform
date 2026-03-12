<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_ajax_request()) {
    http_response_code(403);
    exit;
}

require_permission('content.delete');
verify_csrf_or_abort();

$action = trim((string) ($_POST['action'] ?? ''));
$postId = (int) ($_POST['post_id'] ?? 0);

if ($postId <= 0) {
    json_response(['success' => false, 'message' => 'معرف المنشور غير صحيح.'], 422);
}

// التحقق من وجود المنشور
$postStmt = db()->prepare("SELECT post_id, user_id, content FROM posts WHERE post_id = :id LIMIT 1");
$postStmt->execute([':id' => $postId]);
$post = $postStmt->fetch();

if (!$post) {
    json_response(['success' => false, 'message' => 'المنشور غير موجود.'], 404);
}

switch ($action) {
    case 'delete_post':
        // حذف المنشور
        $stmt = db()->prepare("DELETE FROM posts WHERE post_id = :id");
        $stmt->execute([':id' => $postId]);

        log_activity(
            'post_delete',
            'posts',
            $postId,
            "تم حذف المنشور #{$postId} بواسطة المشرف"
        );

        json_response(['success' => true, 'message' => 'تم حذف المنشور بنجاح.']);
        break;

    case 'flag_post':
        // وضع علامة على المنشور
        $stmt = db()->prepare("UPDATE posts SET is_flagged = 1 WHERE post_id = :id");
        $stmt->execute([':id' => $postId]);
        json_response(['success' => true, 'message' => 'تم تحديد المنشور كمُبلَّغ عنه.']);
        break;

    case 'unflag_post':
        // رفع علامة البلاغ عن المنشور
        $stmt = db()->prepare("UPDATE posts SET is_flagged = 0 WHERE post_id = :id");
        $stmt->execute([':id' => $postId]);
        json_response(['success' => true, 'message' => 'تم رفع علامة البلاغ عن المنشور.']);
        break;

    default:
        json_response(['success' => false, 'message' => 'إجراء غير معروف.'], 400);
}
