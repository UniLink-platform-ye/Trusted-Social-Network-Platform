<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$me = api_require_auth();
$uid = (int)$me['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $b = json_body();
    $postId   = !empty($b['post_id']) ? (int)$b['post_id'] : null;
    $userId   = !empty($b['reported_user_id']) ? (int)$b['reported_user_id'] : null;
    $reason   = (string)($b['reason'] ?? 'other');
    $details  = trim((string)($b['details'] ?? ''));

    if ($postId === null && $userId === null) api_error('post_id أو reported_user_id مطلوب');
    $allowedReasons = ['spam','harassment','inappropriate_content','misinformation','copyright_violation','other'];
    if (!in_array($reason, $allowedReasons, true)) $reason = 'other';

    try {
        $ins = db()->prepare(
            'INSERT INTO reports (reporter_id, post_id, reported_user_id, reason, details, status, created_at)
             VALUES (:rep,:p,:u,:r,:d,\'pending\',NOW())'
        );
        $ins->execute([
            ':rep' => $uid,
            ':p'   => $postId,
            ':u'   => $userId,
            ':r'   => $reason,
            ':d'   => $details !== '' ? $details : null,
        ]);
        $id = (int)db()->lastInsertId();
        try { log_activity('report_submit','reports',$id,'تقديم بلاغ عبر API'); } catch (\Throwable $e) {}
        api_ok(['report_id' => $id], 'تم إرسال البلاغ', 201);
    } catch (\Throwable $e) {
        api_error($e->getMessage(), 500);
    }
}

// إدارة البلاغات — للمشرف/المدير فقط
if ($method === 'GET') {
    if (!in_array($me['role'], ['admin','supervisor'], true)) api_error('ليس لديك صلاحية', 403);

    $status = $_GET['status'] ?? null;
    $where = [];
    $params = [];

    if ($status && in_array($status, ['pending','under_review','resolved','rejected'], true)) {
        $where[] = 'r.status = :st';
        $params[':st'] = $status;
    }

    $sql = '
        SELECT r.*, reporter.full_name AS reporter_name,
               reported.full_name AS reported_name,
               p.content AS post_content
        FROM reports r
        JOIN users reporter ON reporter.user_id = r.reporter_id
        LEFT JOIN users reported ON reported.user_id = r.reported_user_id
        LEFT JOIN posts p ON p.post_id = r.post_id';

    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY r.created_at DESC LIMIT 100';

    try {
        $st = db()->prepare($sql);
        $st->execute($params);
        api_ok(['reports' => $st->fetchAll()]);
    } catch (\Throwable $e) {
        api_error($e->getMessage(), 500);
    }
}

if ($method === 'PUT') {
    if (!in_array($me['role'], ['admin','supervisor'], true)) api_error('ليس لديك صلاحية', 403);

    parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
    $id = (int)($q['id'] ?? 0);
    if ($id <= 0) api_error('id مطلوب');

    $b = json_body();
    $status = (string)($b['status'] ?? '');
    $actionTaken = trim((string)($b['action_taken'] ?? ''));

    if (!in_array($status, ['pending','under_review','resolved','rejected'], true)) {
        api_error('status غير صالح');
    }

    try {
        $st = db()->prepare(
            'UPDATE reports SET status=:st, handled_by=:hb, action_taken=:act, updated_at=NOW() WHERE report_id=:id'
        );
        $st->execute([
            ':st'  => $status,
            ':hb'  => $uid,
            ':act' => $actionTaken !== '' ? $actionTaken : null,
            ':id'  => $id,
        ]);
        try { log_activity('report_submit','reports',$id,'تحديث حالة بلاغ عبر API'); } catch (\Throwable $e) {}
        api_ok([], 'تم تحديث حالة البلاغ');
    } catch (\Throwable $e) {
        api_error($e->getMessage(), 500);
    }
}

api_error('Method Not Allowed', 405);

