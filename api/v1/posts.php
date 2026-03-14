<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$me = api_require_auth(); $uid = (int)$me['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// POST — إنشاء منشور
if ($method === 'POST') {
    $b       = json_body();
    $content = trim($b['content'] ?? '');
    $type    = in_array($b['post_type']??'',['post','announcement','question','lecture']) ? $b['post_type'] : 'post';
    $groupId = !empty($b['group_id']) ? (int)$b['group_id'] : null;
    $vis     = $groupId ? 'group' : 'public';
    if (!$content) api_error('محتوى المنشور مطلوب');
    $ins = db()->prepare('INSERT INTO posts (user_id,group_id,content,post_type,visibility,status,created_at) VALUES (:u,:g,:c,:t,:v,"active",NOW())');
    $ins->execute([':u'=>$uid,':g'=>$groupId,':c'=>$content,':t'=>$type,':v'=>$vis]);
    $postId = (int)db()->lastInsertId();
    if ($groupId) notify_new_post($postId, $groupId, $uid, $me['full_name']??'');
    api_ok(['post_id'=>$postId], 'تم نشر المنشور بنجاح', 201);
}
// DELETE — حذف منشور
elseif ($method === 'DELETE') {
    $postId = (int)($_GET['id']??0);
    $stmt = db()->prepare('SELECT user_id FROM posts WHERE post_id=:id LIMIT 1');
    $stmt->execute([':id'=>$postId]); $post=$stmt->fetch();
    if (!$post) api_error('المنشور غير موجود', 404);
    if ((int)$post['user_id']!==$uid && !in_array($me['role'],['admin','supervisor'])) api_error('ليس لديك صلاحية', 403);
    db()->prepare('UPDATE posts SET status="deleted" WHERE post_id=:id')->execute([':id'=>$postId]);
    api_ok([], 'تم حذف المنشور');
}
else api_error('Method Not Allowed', 405);
