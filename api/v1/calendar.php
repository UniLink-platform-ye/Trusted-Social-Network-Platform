<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$me = api_require_auth();
$uid = (int)$me['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $from = $_GET['from'] ?? null;
    $to   = $_GET['to'] ?? null;
    $groupId  = (int)($_GET['group_id'] ?? 0);
    $courseId = (int)($_GET['course_id'] ?? 0);

    $where = ['(ae.owner_user_id = :uid OR (ae.group_id IN (SELECT group_id FROM group_members WHERE user_id=:uid2)))'];
    $params = [':uid' => $uid, ':uid2' => $uid];

    if ($groupId > 0) {
        $where[] = 'ae.group_id = :gid';
        $params[':gid'] = $groupId;
    }
    if ($courseId > 0) {
        $where[] = 'ae.course_id = :cid';
        $params[':cid'] = $courseId;
    }
    if ($from) {
        $where[] = 'ae.start_at >= :from';
        $params[':from'] = $from;
    }
    if ($to) {
        $where[] = 'ae.start_at <= :to';
        $params[':to'] = $to;
    }

    $sql = '
        SELECT ae.*, g.group_name, c.code AS course_code, c.name AS course_name
        FROM academic_events ae
        LEFT JOIN `groups` g ON g.group_id = ae.group_id
        LEFT JOIN courses c ON c.course_id = ae.course_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY ae.start_at ASC
        LIMIT 200';

    try {
        $st = db()->prepare($sql);
        $st->execute($params);
        api_ok(['events' => $st->fetchAll()]);
    } catch (\Throwable $e) {
        api_error($e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $b = json_body();
    $title = trim((string)($b['title'] ?? ''));
    $type  = (string)($b['event_type'] ?? 'other');
    $start = (string)($b['start_at'] ?? '');
    $end   = $b['end_at'] ?? null;
    $allDay = !empty($b['all_day']) ? 1 : 0;
    $courseId = !empty($b['course_id']) ? (int)$b['course_id'] : null;
    $groupId  = !empty($b['group_id']) ? (int)$b['group_id'] : null;

    if ($title === '' || $start === '') api_error('title و start_at مطلوبة');
    if (!in_array($type, ['lecture','exam','meeting','task','other'], true)) $type = 'other';

    if ($groupId !== null) {
        $gm = db()->prepare('SELECT 1 FROM group_members WHERE group_id=:g AND user_id=:u LIMIT 1');
        $gm->execute([':g' => $groupId, ':u' => $uid]);
        if (!$gm->fetchColumn()) api_error('لا يمكنك إضافة حدث لمجموعة لست عضواً فيها', 403);
    }

    try {
        $ins = db()->prepare(
            'INSERT INTO academic_events (owner_user_id, course_id, group_id, event_type, title, description, location, start_at, end_at, all_day, created_at)
             VALUES (:u,:cid,:gid,:t,:title,:descr,:loc,:start,:end,:all, NOW())'
        );
        $ins->execute([
            ':u'     => $uid,
            ':cid'   => $courseId,
            ':gid'   => $groupId,
            ':t'     => $type,
            ':title' => $title,
            ':descr' => trim((string)($b['description'] ?? '')) ?: null,
            ':loc'   => trim((string)($b['location'] ?? '')) ?: null,
            ':start' => $start,
            ':end'   => $end ?: null,
            ':all'   => $allDay,
        ]);
        $id = (int)db()->lastInsertId();
        api_ok(['event_id' => $id], 'تم إنشاء الحدث', 201);
    } catch (\Throwable $e) {
        api_error($e->getMessage(), 500);
    }
}

if ($method === 'PUT') {
    parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
    $id = (int)($q['id'] ?? 0);
    if ($id <= 0) api_error('id مطلوب');

    $st = db()->prepare('SELECT * FROM academic_events WHERE event_id=:id LIMIT 1');
    $st->execute([':id' => $id]);
    $ev = $st->fetch();
    if (!$ev) api_error('الحدث غير موجود', 404);
    if ((int)$ev['owner_user_id'] !== $uid && !in_array($me['role'], ['admin','supervisor'], true)) {
        api_error('ليس لديك صلاحية', 403);
    }

    $b = json_body();
    $fields = [];
    $params = [':id' => $id];

    foreach (['title','description','location'] as $k) {
        if (array_key_exists($k, $b)) {
            $fields[] = "$k = :$k";
            $v = is_string($b[$k] ?? null) ? trim((string)$b[$k]) : ($b[$k] ?? null);
            $params[":$k"] = $v === '' ? null : $v;
        }
    }
    if (array_key_exists('event_type', $b)) {
        $t = (string)$b['event_type'];
        if (in_array($t, ['lecture','exam','meeting','task','other'], true)) {
            $fields[] = 'event_type = :event_type';
            $params[':event_type'] = $t;
        }
    }
    if (array_key_exists('start_at', $b)) {
        $fields[] = 'start_at = :start_at';
        $params[':start_at'] = (string)$b['start_at'];
    }
    if (array_key_exists('end_at', $b)) {
        $fields[] = 'end_at = :end_at';
        $params[':end_at'] = $b['end_at'] ?: null;
    }
    if (array_key_exists('all_day', $b)) {
        $fields[] = 'all_day = :all_day';
        $params[':all_day'] = !empty($b['all_day']) ? 1 : 0;
    }
    if (!$fields) api_error('لا توجد بيانات للتحديث');

    try {
        $sql = 'UPDATE academic_events SET ' . implode(', ', $fields) . ' WHERE event_id = :id';
        db()->prepare($sql)->execute($params);
        api_ok([], 'تم تحديث الحدث');
    } catch (\Throwable $e) {
        api_error($e->getMessage(), 500);
    }
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) api_error('id مطلوب');

    $st = db()->prepare('SELECT owner_user_id FROM academic_events WHERE event_id=:id LIMIT 1');
    $st->execute([':id' => $id]);
    $ev = $st->fetch();
    if (!$ev) api_error('الحدث غير موجود', 404);
    if ((int)$ev['owner_user_id'] !== $uid && !in_array($me['role'], ['admin','supervisor'], true)) {
        api_error('ليس لديك صلاحية', 403);
    }

    db()->prepare('DELETE FROM academic_events WHERE event_id=:id')->execute([':id' => $id]);
    api_ok([], 'تم حذف الحدث');
}

api_error('Method Not Allowed', 405);

