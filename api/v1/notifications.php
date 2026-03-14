<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$me=api_require_auth(); $uid=(int)$me['user_id'];
$method=$_SERVER['REQUEST_METHOD'];

if ($method==='GET') {
    $stmt=db()->prepare('SELECT * FROM notifications WHERE user_id=:id ORDER BY created_at DESC LIMIT 50');
    $stmt->execute([':id'=>$uid]);
    $unread=db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=:id AND is_read=0');
    $unread->execute([':id'=>$uid]);
    api_ok(['notifications'=>$stmt->fetchAll(),'unread_count'=>(int)$unread->fetchColumn()]);
} elseif ($method==='POST') {
    // تحديد الكل كمقروء
    db()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=:id')->execute([':id'=>$uid]);
    api_ok([],'تم تحديث الإشعارات كمقروءة');
} else api_error('Method Not Allowed',405);
