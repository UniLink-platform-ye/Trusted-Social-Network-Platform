<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$me = api_require_auth();
$uid = (int)$me['user_id'];
$role = (string)$me['role'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST' && $method !== 'GET') {
    api_error('Method Not Allowed', 405);
}

if ($method === 'GET') {
    $groupId = (int)($_GET['group_id'] ?? 0);
    if ($groupId <= 0) api_error('group_id مطلوب');

    $st = db()->prepare(
        'SELECT g.*, u.full_name AS creator_name
         FROM `groups` g
         JOIN users u ON u.user_id = g.created_by
         WHERE g.group_id=:id LIMIT 1'
    );
    $st->execute([':id' => $groupId]);
    $g = $st->fetch();
    if (!$g) api_error('المجموعة غير موجودة', 404);

    // يسمح بالوصول إذا كان المستخدم مالكاً أو مشرفاً/مديراً
    $ownerId = (int)$g['created_by'];
    if ($ownerId !== $uid && !in_array($role, ['admin','supervisor'], true)) {
        api_error('ليس لديك صلاحية', 403);
    }

    $members = db()->prepare(
        'SELECT gm.*, u.full_name, u.email, u.role
         FROM group_members gm
         JOIN users u ON u.user_id = gm.user_id
         WHERE gm.group_id=:id
         ORDER BY gm.member_role DESC, u.full_name ASC'
    );
    $members->execute([':id' => $groupId]);

    api_ok(['group' => $g, 'members' => $members->fetchAll()]);
}

if ($method === 'POST') {
    $b = json_body();
    $action = (string)($b['action'] ?? '');

    if ($action === 'create') {
        if (!in_array($role, ['admin','supervisor','professor'], true)) api_error('ليس لديك صلاحية إنشاء المجموعات', 403);

        $name = trim((string)($b['group_name'] ?? ''));
        $desc = trim((string)($b['description'] ?? ''));
        $type = (string)($b['group_type'] ?? 'course');
        $privacy = (string)($b['privacy'] ?? 'private');
        $courseId = !empty($b['course_id']) ? (int)$b['course_id'] : null;

        if ($name === '') api_error('group_name مطلوب');
        if (!in_array($type, ['course','department','activity','administrative'], true)) $type = 'course';
        if (!in_array($privacy, ['public','private','restricted'], true)) $privacy = 'private';

        // إذا كان أستاذاً ويربط بمقرر، نتحقق أن المقرر ضمن مقرراته (إذا كانت professor_courses موجودة)
        if ($courseId !== null && $role === 'professor') {
            try {
                $chk = db()->prepare('SELECT 1 FROM professor_courses WHERE professor_user_id=:u AND course_id=:c LIMIT 1');
                $chk->execute([':u' => $uid, ':c' => $courseId]);
                if (!$chk->fetchColumn()) api_error('لا يمكنك إنشاء مجموعة لمقرر غير مرتبط بك', 403);
            } catch (\Throwable $e) {
                // إذا لم يوجد الجدول نتجاهل التحقق (توافق للخلف)
            }
        }

        $ins = db()->prepare(
            'INSERT INTO `groups` (group_name, description, type, privacy, course_id, created_by, members_count, status, created_at)
             VALUES (:n,:d,:t,:p,:cid,:u,1,\'active\',NOW())'
        );
        $ins->execute([
            ':n' => $name,
            ':d' => $desc !== '' ? $desc : null,
            ':t' => $type,
            ':p' => $privacy,
            ':cid' => $courseId,
            ':u' => $uid,
        ]);
        $gid = (int)db()->lastInsertId();

        db()->prepare(
            'INSERT IGNORE INTO group_members (group_id,user_id,member_role,joined_at) VALUES (:g,:u,\'owner\',NOW())'
        )->execute([':g' => $gid, ':u' => $uid]);

        api_ok(['group_id' => $gid], 'تم إنشاء المجموعة', 201);
    }

    if (in_array($action, ['update','delete','add_member','remove_member'], true)) {
        $groupId = (int)($b['group_id'] ?? 0);
        if ($groupId <= 0) api_error('group_id مطلوب');

        $st = db()->prepare('SELECT * FROM `groups` WHERE group_id=:id LIMIT 1');
        $st->execute([':id' => $groupId]);
        $g = $st->fetch();
        if (!$g) api_error('المجموعة غير موجودة', 404);
        $ownerId = (int)$g['created_by'];

        $isOwnerOrStaff = ($ownerId === $uid) || in_array($role, ['admin','supervisor'], true);
        if (!$isOwnerOrStaff) api_error('ليس لديك صلاحية على هذه المجموعة', 403);

        if ($action === 'update') {
            $fields = [];
            $params = [':id' => $groupId];
            foreach (['group_name','description','privacy'] as $k) {
                if (array_key_exists($k, $b)) {
                    $val = is_string($b[$k] ?? null) ? trim((string)$b[$k]) : ($b[$k] ?? null);
                    $fields[] = "$k = :$k";
                    $params[":$k"] = $val === '' ? null : $val;
                }
            }
            if (array_key_exists('course_id', $b)) {
                $courseId = !empty($b['course_id']) ? (int)$b['course_id'] : null;
                $fields[] = 'course_id = :cid';
                $params[':cid'] = $courseId;
            }
            if (!$fields) api_error('لا توجد بيانات للتحديث');
            $sql = 'UPDATE `groups` SET ' . implode(', ', $fields) . ', updated_at=NOW() WHERE group_id=:id';
            db()->prepare($sql)->execute[$params];
            api_ok([], 'تم تحديث بيانات المجموعة');
        }

        if ($action === 'delete') {
            db()->prepare('UPDATE `groups` SET status=\'deleted\', updated_at=NOW() WHERE group_id=:id')->execute([':id' => $groupId]);
            api_ok([], 'تم حذف المجموعة');
        }

        if ($action === 'add_member') {
            $userId = (int)($b['user_id'] ?? 0);
            if ($userId <= 0) api_error('user_id مطلوب');
            db()->prepare(
                'INSERT IGNORE INTO group_members (group_id,user_id,member_role,joined_at) VALUES (:g,:u,\'member\',NOW())'
            )->execute([':g' => $groupId, ':u' => $userId]);
            api_ok([], 'تم إضافة العضو');
        }

        if ($action === 'remove_member') {
            $userId = (int)($b['user_id'] ?? 0);
            if ($userId <= 0) api_error('user_id مطلوب');
            db()->prepare('DELETE FROM group_members WHERE group_id=:g AND user_id=:u')->execute([':g' => $groupId, ':u' => $userId]);
            api_ok([], 'تم إزالة العضو');
        }
    }

    api_error('action غير معروف', 400);
}

