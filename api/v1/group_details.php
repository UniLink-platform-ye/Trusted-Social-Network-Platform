<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$me = api_require_auth();
$uid = (int)$me['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method Not Allowed', 405);
}

$groupId = (int)($_GET['group_id'] ?? 0);
if ($groupId <= 0) api_error('group_id مطلوب');

// جلب تفاصيل المجموعة
$st = db()->prepare(
    'SELECT g.*, u.full_name AS creator_name, c.code AS course_code, c.name AS course_name
     FROM `groups` g
     JOIN users u ON u.user_id = g.created_by
     LEFT JOIN courses c ON c.course_id = g.course_id
     WHERE g.group_id = :id AND g.status = \'active\'
     LIMIT 1'
);
$st->execute([':id' => $groupId]);
$group = $st->fetch();
if (!$group) api_error('المجموعة غير موجودة', 404);

$privacy = (string)$group['privacy'];

// التحقق من إمكانية الوصول:
// - public: متاحة للجميع داخل المنصة
// - private/restricted: للأعضاء فقط أو الإدارة/المشرف
$isStaff = in_array($me['role'], ['admin','supervisor'], true);

$isMemberStmt = db()->prepare('SELECT 1 FROM group_members WHERE group_id=:g AND user_id=:u LIMIT 1');
$isMemberStmt->execute([':g' => $groupId, ':u' => $uid]);
$isMember = (bool)$isMemberStmt->fetchColumn();

if (!$isStaff && !$isMember && $privacy !== 'public') {
    api_error('هذه المجموعة خاصة ولا يمكنك الوصول لتفاصيلها', 403);
}

// جلب الأعضاء
$membersStmt = db()->prepare(
    'SELECT gm.*, u.full_name, u.email, u.role AS user_role
     FROM group_members gm
     JOIN users u ON u.user_id = gm.user_id
     WHERE gm.group_id = :g
     ORDER BY FIELD(gm.member_role, \'owner\', \'moderator\', \'member\'), u.full_name ASC'
);
$membersStmt->execute([':g' => $groupId]);
$members = $membersStmt->fetchAll();

api_ok([
    'group'   => $group,
    'members' => $members,
    'is_member' => $isMember,
]);

