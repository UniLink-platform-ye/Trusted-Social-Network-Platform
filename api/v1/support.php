<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$me = api_require_auth();
$uid = (int)$me['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $ticketId = (int)($_GET['id'] ?? 0);

    if ($ticketId > 0) {
        $st = db()->prepare(
            'SELECT t.*, u.full_name AS creator_name, a.full_name AS assigned_name
             FROM support_tickets t
             JOIN users u ON u.user_id = t.created_by
             LEFT JOIN users a ON a.user_id = t.assigned_to
             WHERE t.ticket_id=:id
               AND (t.created_by=:u OR :isStaff = 1)
             LIMIT 1'
        );
        $st->execute([
            ':id' => $ticketId,
            ':u' => $uid,
            ':isStaff' => in_array($me['role'], ['admin','supervisor'], true) ? 1 : 0,
        ]);
        $ticket = $st->fetch();
        if (!$ticket) api_error('التذكرة غير موجودة أو لا تملك صلاحية لرؤيتها', 404);

        $ms = db()->prepare(
            'SELECT m.*, u.full_name AS sender_name
             FROM support_ticket_messages m
             JOIN users u ON u.user_id = m.sender_id
             WHERE m.ticket_id=:id
             ORDER BY m.created_at ASC'
        );
        $ms->execute([':id' => $ticketId]);
        api_ok(['ticket' => $ticket, 'messages' => $ms->fetchAll()]);
    }

    // قائمة التذاكر
    if (in_array($me['role'], ['admin','supervisor'], true)) {
        $st = db()->prepare(
            'SELECT t.*, u.full_name AS creator_name, a.full_name AS assigned_name
             FROM support_tickets t
             JOIN users u ON u.user_id = t.created_by
             LEFT JOIN users a ON a.user_id = t.assigned_to
             ORDER BY t.created_at DESC
             LIMIT 100'
        );
        $st->execute();
    } else {
        $st = db()->prepare(
            'SELECT t.*, u.full_name AS creator_name, a.full_name AS assigned_name
             FROM support_tickets t
             JOIN users u ON u.user_id = t.created_by
             LEFT JOIN users a ON a.user_id = t.assigned_to
             WHERE t.created_by=:u
             ORDER BY t.created_at DESC
             LIMIT 100'
        );
        $st->execute([':u' => $uid]);
    }
    api_ok(['tickets' => $st->fetchAll()]);
}

if ($method === 'POST') {
    $b = json_body();
    $action = (string)($b['action'] ?? 'create');

    if ($action === 'create') {
        $subject = trim((string)($b['subject'] ?? ''));
        $message = trim((string)($b['message'] ?? ''));
        $priority = (string)($b['priority'] ?? 'normal');
        if (!in_array($priority, ['low','normal','high'], true)) $priority = 'normal';
        if ($subject === '' || $message === '') api_error('subject و message مطلوبة');

        try {
            db()->beginTransaction();
            $ins = db()->prepare(
                'INSERT INTO support_tickets (created_by, subject, status, priority, created_at)
                 VALUES (:u,:s,\'open\',:p,NOW())'
            );
            $ins->execute([':u' => $uid, ':s' => $subject, ':p' => $priority]);
            $ticketId = (int)db()->lastInsertId();

            $m = db()->prepare(
                'INSERT INTO support_ticket_messages (ticket_id, sender_id, message, created_at)
                 VALUES (:t,:u,:m,NOW())'
            );
            $m->execute([':t' => $ticketId, ':u' => $uid, ':m' => $message]);

            db()->commit();
            api_ok(['ticket_id' => $ticketId], 'تم إنشاء التذكرة', 201);
        } catch (\Throwable $e) {
            db()->rollBack();
            api_error($e->getMessage(), 500);
        }
    }

    if ($action === 'reply') {
        $ticketId = (int)($b['ticket_id'] ?? 0);
        $message  = trim((string)($b['message'] ?? ''));
        if ($ticketId <= 0 || $message === '') api_error('ticket_id و message مطلوبة');

        $st = db()->prepare('SELECT * FROM support_tickets WHERE ticket_id=:id LIMIT 1');
        $st->execute([':id' => $ticketId]);
        $t = $st->fetch();
        if (!$t) api_error('التذكرة غير موجودة', 404);
        $isStaff = in_array($me['role'], ['admin','supervisor'], true);
        if ((int)$t['created_by'] !== $uid && !$isStaff) api_error('ليس لديك صلاحية على هذه التذكرة', 403);

        $m = db()->prepare(
            'INSERT INTO support_ticket_messages (ticket_id, sender_id, message, created_at)
             VALUES (:t,:u,:m,NOW())'
        );
        $m->execute([':t' => $ticketId, ':u' => $uid, ':m' => $message]);

        db()->prepare(
            'UPDATE support_tickets SET updated_at = NOW(), status = CASE WHEN status = \'open\' THEN \'pending\' ELSE status END WHERE ticket_id=:id'
        )->execute([':id' => $ticketId]);

        api_ok([], 'تم إضافة الرد');
    }

    if ($action === 'set_status') {
        if (!in_array($me['role'], ['admin','supervisor'], true)) api_error('ليس لديك صلاحية', 403);
        $ticketId = (int)($b['ticket_id'] ?? 0);
        $status = (string)($b['status'] ?? '');
        $assignedTo = !empty($b['assigned_to']) ? (int)$b['assigned_to'] : null;
        if (!in_array($status, ['open','pending','closed'], true)) api_error('status غير صالح');
        if ($ticketId <= 0) api_error('ticket_id مطلوب');

        $st = db()->prepare(
            'UPDATE support_tickets
             SET status=:st, assigned_to=:asgn, updated_at=NOW(), closed_at = CASE WHEN :st2 = \'closed\' THEN NOW() ELSE closed_at END
             WHERE ticket_id=:id'
        );
        $st->execute([
            ':st' => $status,
            ':st2' => $status,
            ':asgn' => $assignedTo,
            ':id' => $ticketId,
        ]);
        api_ok([], 'تم تحديث حالة التذكرة');
    }

    api_error('action غير معروف', 400);
}

api_error('Method Not Allowed', 405);

