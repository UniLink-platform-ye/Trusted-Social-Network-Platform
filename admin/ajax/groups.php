<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_ajax_request()) {
    http_response_code(403);
    exit;
}

require_permission('groups.manage');
verify_csrf_or_abort();

$action  = trim((string) ($_POST['action'] ?? ''));
$groupId = (int) ($_POST['group_id'] ?? 0);

if ($groupId <= 0) {
    json_response(['success' => false, 'message' => 'معرف المجموعة غير صحيح.'], 422);
}

// التحقق من وجود المجموعة
$groupStmt = db()->prepare("SELECT group_id, group_name FROM `groups` WHERE group_id = :id LIMIT 1");
$groupStmt->execute([':id' => $groupId]);
$group = $groupStmt->fetch();

if (!$group) {
    json_response(['success' => false, 'message' => 'المجموعة غير موجودة.'], 404);
}

switch ($action) {
    case 'delete_group':
        // حذف أعضاء المجموعة ثم المجموعة نفسها
        $db = db();
        try {
            $db->beginTransaction();

            // حذف الأعضاء
            $stmt = $db->prepare("DELETE FROM group_members WHERE group_id = :id");
            $stmt->execute([':id' => $groupId]);

            // حذف المجموعة
            $stmt = $db->prepare("DELETE FROM `groups` WHERE group_id = :id");
            $stmt->execute([':id' => $groupId]);

            $db->commit();

            log_activity(
                'post_delete',
                'groups',
                $groupId,
                "تم حذف المجموعة #{$groupId} ({$group['group_name']}) بواسطة المشرف"
            );

            json_response(['success' => true, 'message' => 'تم حذف المجموعة بنجاح.']);
        } catch (Throwable $e) {
            $db->rollBack();
            error_log('Group delete error: ' . $e->getMessage());
            json_response(['success' => false, 'message' => 'حدث خطأ أثناء الحذف.'], 500);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'إجراء غير معروف.'], 400);
}
