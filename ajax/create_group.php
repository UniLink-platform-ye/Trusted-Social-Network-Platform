<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) { echo json_encode(['success'=>false,'message'=>'غير مصرح']); exit; }
if (!verify_csrf()) { echo json_encode(['success'=>false,'message'=>'طلب غير صالح']); exit; }

$me = current_user();
if (!in_array($me['role'], ['admin','supervisor','professor'])) {
    echo json_encode(['success'=>false,'message'=>'ليس لديك صلاحية إنشاء مجموعات.']); exit;
}

$action = $_POST['action'] ?? '';
if ($action === 'create_group') {
    $name    = trim($_POST['group_name']  ?? '');
    $desc    = trim($_POST['description'] ?? '');
    $type    = in_array($_POST['group_type']??'', ['course','department','activity','administrative']) ? $_POST['group_type'] : 'course';
    $privacy = in_array($_POST['privacy']??'', ['public','private']) ? $_POST['privacy'] : 'public';

    if (!$name) { echo json_encode(['success'=>false,'message'=>'اسم المجموعة مطلوب.']); exit; }

    try {
        $ins = db()->prepare('INSERT INTO groups (group_name,description,group_type,privacy,created_by,status,created_at) VALUES (:n,:d,:t,:p,:u,"active",NOW())');
        $ins->execute([':n'=>$name,':d'=>$desc,':t'=>$type,':p'=>$privacy,':u'=>(int)$me['user_id']]);
        $gid = (int)db()->lastInsertId();

        // المنشئ ينضم تلقائياً كـ admin
        db()->prepare('INSERT IGNORE INTO group_members (group_id,user_id,role,joined_at) VALUES (:g,:u,"admin",NOW())')
            ->execute([':g'=>$gid,':u'=>(int)$me['user_id']]);

        log_activity('create_group','groups',$gid,"Group: $name");
        echo json_encode(['success'=>true,'message'=>"تم إنشاء مجموعة \"$name\" بنجاح!",'group_id'=>$gid]);
    } catch (\Throwable $e) {
        echo json_encode(['success'=>false,'message'=>'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success'=>false,'message'=>'إجراء غير معروف']);
}
