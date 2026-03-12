<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_ajax_request()) {
    http_response_code(403);
    exit;
}

require_permission('settings.manage');
verify_csrf_or_abort();

$action = trim((string) ($_POST['action'] ?? ''));

switch ($action) {
    case 'change_password':
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword     = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($newPassword !== $confirmPassword) {
            json_response(['success' => false, 'message' => 'كلمتا المرور الجديدتان غير متطابقتين.'], 422);
        }

        if (strlen($newPassword) < 8) {
            json_response(['success' => false, 'message' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.'], 422);
        }

        $currentUser = current_user();
        if (!$currentUser) {
            json_response(['success' => false, 'message' => 'المستخدم غير موجود.'], 404);
        }

        // التحقق من كلمة المرور الحالية
        $userStmt = db()->prepare("SELECT password_hash FROM users WHERE user_id = :id LIMIT 1");
        $userStmt->execute([':id' => $currentUser['user_id']]);
        $user = $userStmt->fetch();

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            json_response(['success' => false, 'message' => 'كلمة المرور الحالية غير صحيحة.'], 401);
        }

        // تحديث كلمة المرور
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $updateStmt = db()->prepare("UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE user_id = :id");
        $updateStmt->execute([':hash' => $newHash, ':id' => $currentUser['user_id']]);

        log_activity('password_change', 'users', (int) $currentUser['user_id'], 'تغيير كلمة مرور المدير');

        json_response(['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح.']);
        break;

    default:
        json_response(['success' => false, 'message' => 'إجراء غير معروف.'], 400);
}
