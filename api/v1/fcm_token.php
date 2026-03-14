<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$me=api_require_auth(); $uid=(int)$me['user_id'];
$method=$_SERVER['REQUEST_METHOD'];

if ($method==='POST') {
    // حفظ FCM token لجهاز المستخدم (يُستدعى من Flutter عند تسجيل الدخول)
    $b=json_body(); $token=trim($b['fcm_token']??''); $device=$b['device_type']??'android';
    if (!$token) api_error('fcm_token مطلوب');
    if (!in_array($device,['android','ios','web'])) $device='android';
    try {
        // upsert: إذا موجود حدّث، وإلا أضف
        $st=db()->prepare('INSERT INTO fcm_tokens (user_id,token,device_type,is_active,created_at) VALUES (:u,:t,:d,1,NOW()) ON DUPLICATE KEY UPDATE user_id=:u2,is_active=1,updated_at=NOW()');
        $st->execute([':u'=>$uid,':t'=>$token,':d'=>$device,':u2'=>$uid]);
        api_ok([],'تم حفظ FCM token بنجاح');
    } catch(\Throwable $e) { api_error($e->getMessage(),500); }
} elseif ($method==='DELETE') {
    // إزالة FCM token عند تسجيل الخروج
    $b=json_body(); $token=$b['fcm_token']??'';
    db()->prepare('UPDATE fcm_tokens SET is_active=0 WHERE user_id=:u AND token=:t')->execute([':u'=>$uid,':t'=>$token]);
    api_ok([],'تم إلغاء تسجيل الجهاز');
} else api_error('Method Not Allowed',405);
