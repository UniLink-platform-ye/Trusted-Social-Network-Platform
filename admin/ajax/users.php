<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

if (!is_post() || !is_ajax_request()) {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

verify_csrf_or_abort();

$action = (string) ($_POST['action'] ?? '');

function users_error(string $msg, int $code = 422): void
{
    json_response(['success' => false, 'message' => $msg], $code);
}

try {
    switch ($action) {

        case 'create_user':
            require_permission('users.create');

            $fullName   = trim((string) ($_POST['full_name']   ?? ''));
            $username   = trim(strtolower((string) ($_POST['username']   ?? '')));
            $email      = trim(strtolower((string) ($_POST['email']      ?? '')));
            $password   = (string) ($_POST['password']   ?? '');
            $role       = (string) ($_POST['role']        ?? 'student');
            $department = trim((string) ($_POST['department']  ?? ''));
            $academicId = trim((string) ($_POST['academic_id'] ?? ''));

            if ($fullName === '' || $username === '' || $email === '' || $password === '') {
                users_error('يرجى تعبئة جميع الحقول المطلوبة.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                users_error('البريد الإلكتروني غير صالح.');
            }
            if (strlen($password) < 8) {
                users_error('كلمة المرور يجب أن تكون 8 أحرف على الأقل.');
            }
            if (!array_key_exists($role, get_all_roles())) {
                users_error('الدور المحدد غير صالح.');
            }

            // التحقق من التكرار
            $check = db()->prepare(
                'SELECT user_id FROM users WHERE email = :e OR username = :u LIMIT 1'
            );
            $check->execute([':e' => $email, ':u' => $username]);
            if ($check->fetch()) {
                users_error('البريد الإلكتروني أو اسم المستخدم مستخدم مسبقاً.');
            }

            $stmt = db()->prepare(
                'INSERT INTO users (username, full_name, email, password_hash, role, department, academic_id, is_verified, status)
                 VALUES (:un, :fn, :em, :ph, :ro, :dep, :aid, 1, \'active\')'
            );
            $stmt->execute([
                ':un'  => $username,
                ':fn'  => $fullName,
                ':em'  => $email,
                ':ph'  => password_hash($password, PASSWORD_BCRYPT),
                ':ro'  => $role,
                ':dep' => $department !== '' ? $department : null,
                ':aid' => $academicId !== '' ? $academicId : null,
            ]);

            $newId = (int) db()->lastInsertId();
            log_activity('register', 'users', $newId,
                sprintf('تم إنشاء حساب %s (%s)', $fullName, $email));

            json_response(['success' => true, 'message' => 'تم إضافة المستخدم بنجاح!']);
            break;

        case 'update_user':
            require_permission('users.edit');

            $userId     = (int) ($_POST['user_id']     ?? 0);
            $fullName   = trim((string) ($_POST['full_name']   ?? ''));
            $email      = trim(strtolower((string) ($_POST['email']      ?? '')));
            $role       = (string) ($_POST['role']        ?? '');
            $department = trim((string) ($_POST['department']  ?? ''));
            $newPass    = (string) ($_POST['new_password'] ?? '');

            if ($userId <= 0 || $fullName === '' || $email === '') {
                users_error('بيانات غير صالحة.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                users_error('البريد الإلكتروني غير صالح.');
            }
            if (!array_key_exists($role, get_all_roles())) {
                users_error('الدور المحدد غير صالح.');
            }

            // محاولة تغيير دور Admin الرئيسي
            $currentUser = current_user();
            if ((int) $currentUser['user_id'] === $userId && $role !== 'admin') {
                users_error('لا يمكنك تغيير دور حسابك الخاص.');
            }

            $hashLine = '';
            $params   = [
                ':fn'  => $fullName,
                ':em'  => $email,
                ':ro'  => $role,
                ':dep' => $department !== '' ? $department : null,
                ':id'  => $userId,
            ];

            if ($newPass !== '') {
                if (strlen($newPass) < 8) {
                    users_error('كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل.');
                }
                $hashLine        = ', password_hash = :ph';
                $params[':ph']   = password_hash($newPass, PASSWORD_BCRYPT);
            }

            $stmt = db()->prepare(
                "UPDATE users
                 SET full_name = :fn, email = :em, role = :ro, department = :dep $hashLine
                 WHERE user_id = :id"
            );
            $stmt->execute($params);

            log_activity('post_edit', 'users', $userId,
                'تم تعديل بيانات المستخدم: ' . $fullName);

            json_response(['success' => true, 'message' => 'تم حفظ التعديلات بنجاح!']);
            break;

        case 'suspend_user':
            require_permission('users.suspend');

            $userId = (int) ($_POST['user_id'] ?? 0);
            if ($userId <= 0) {
                users_error('معرف المستخدم غير صالح.');
            }
            if ($userId === (int) (current_user()['user_id'] ?? 0)) {
                users_error('لا يمكنك تعليق حسابك الخاص!');
            }

            db()->prepare('UPDATE users SET status = \'suspended\' WHERE user_id = :id')
                ->execute([':id' => $userId]);

            log_activity('account_suspend', 'users', $userId, 'تم تعليق الحساب');

            json_response(['success' => true, 'message' => 'تم تعليق الحساب.']);
            break;

        case 'activate_user':
            require_permission('users.suspend');

            $userId = (int) ($_POST['user_id'] ?? 0);
            if ($userId <= 0) {
                users_error('معرف المستخدم غير صالح.');
            }

            db()->prepare('UPDATE users SET status = \'active\' WHERE user_id = :id')
                ->execute([':id' => $userId]);

            log_activity('account_suspend', 'users', $userId, 'تم تفعيل الحساب');

            json_response(['success' => true, 'message' => 'تم تفعيل الحساب.']);
            break;

        case 'delete_user':
            $currentUser = current_user();
            if (($currentUser['role'] ?? '') !== 'admin') {
                users_error('عذراً، فقط المدراء (Admins) يمكنهم حذف المستخدمين نهائياً.', 403);
            }

            $userId = (int) ($_POST['user_id'] ?? 0);
            if ($userId <= 0) {
                users_error('معرف المستخدم غير صالح.');
            }
            if ($userId === (int) $currentUser['user_id']) {
                users_error('لا يمكنك حذف حسابك الخاص!');
            }

            // لتجنب مشاكل Foreign Keys (RESTRICT) في قواعد البيانات الحالية،
            // نحذف المجموعات والملفات المملوكة للمستخدم أولاً ثم نحذف المستخدم
            // باقي الجداول مثل المنشورات والرسائل تحذف تلقائياً بفضل CASCADE ولكن للاحتياط:
            try {
                db()->beginTransaction();
                
                // 1. حذف المجموعات التي أنشأها
                db()->prepare('DELETE FROM `groups` WHERE created_by = :id')->execute([':id' => $userId]);
                
                // 2. حذف ملفاته المرفوعة
                db()->prepare('DELETE FROM `files` WHERE user_id = :id')->execute([':id' => $userId]);
                
                // 3. حذف المستخدم نفسه (ستعمل هنا ON DELETE CASCADE لبقية الجداول)
                db()->prepare('DELETE FROM users WHERE user_id = :id')->execute([':id' => $userId]);
                
                db()->commit();
                
                log_activity('account_delete', 'users', $currentUser['user_id'], "تم حذف المستخدم رقم $userId بشكل جذري مع كافة بياناته.");
                json_response(['success' => true, 'message' => 'تم حذف الحساب وكافة سجلاته بنجاح.']);
            } catch (Exception $e) {
                db()->rollBack();
                error_log('Error deleting user: ' . $e->getMessage());
                users_error('فشل عملية الحذف الجذري. قد تكون هناك سجلات مرتبطة تمنع ذلك.', 500);
            }
            break;

        case 'get_user':
            require_permission('users.view');

            $userId = (int) ($_POST['user_id'] ?? 0);
            if ($userId <= 0) {
                users_error('معرف غير صالح.');
            }

            $stmt = db()->prepare(
                'SELECT user_id, username, full_name, email, role, department,
                        academic_id, is_verified, status, last_login, created_at
                 FROM users WHERE user_id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();

            if (!$user) {
                users_error('المستخدم غير موجود.', 404);
            }

            $roles = get_all_roles();
            $user['role_label'] = $roles[$user['role']] ?? $user['role'];

            json_response(['success' => true, 'data' => $user]);
            break;

        default:
            users_error('إجراء غير معروف.', 400);
    }
} catch (Throwable $e) {
    error_log('Users AJAX error: ' . $e->getMessage());
    users_error('خطأ في الخادم. يرجى المحاولة لاحقاً.', 500);
}
