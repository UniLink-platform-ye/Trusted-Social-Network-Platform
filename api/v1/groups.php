<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$me = api_require_auth(); $uid=(int)$me['user_id'];
$method=$_SERVER['REQUEST_METHOD'];

if ($method==='GET') {
    // جلب المجموعات
    $filter=$_GET['filter']??'all';
    $sql="SELECT g.*,u.full_name AS creator_name,(SELECT COUNT(*) FROM group_members gm2 WHERE gm2.group_id=g.group_id) AS member_count,(SELECT COUNT(*) FROM group_members gm3 WHERE gm3.group_id=g.group_id AND gm3.user_id=:uid) AS is_member FROM groups g LEFT JOIN users u ON u.user_id=g.created_by WHERE g.status='active'";
    if ($filter==='mine') $sql.=" AND g.group_id IN(SELECT group_id FROM group_members WHERE user_id=:uid2)";
    $sql.=" ORDER BY member_count DESC LIMIT 50";
    $st=db()->prepare($sql);
    $st->bindValue(':uid',$uid,PDO::PARAM_INT);
    if ($filter==='mine') $st->bindValue(':uid2',$uid,PDO::PARAM_INT);
    $st->execute();
    api_ok(['groups'=>$st->fetchAll()]);
}
elseif ($method==='POST') {
    $b=json_body(); $action=$b['action']??'';
    $groupId=(int)($b['group_id']??0);
    if (!$groupId) api_error('group_id مطلوب');
    if ($action==='join') {
        db()->prepare('INSERT IGNORE INTO group_members (group_id,user_id,member_role,joined_at) VALUES (:g,:u,"member",NOW())')->execute([':g'=>$groupId,':u'=>$uid]);
        api_ok([],'تم الانضمام للمجموعة');
    } elseif ($action==='leave') {
        db()->prepare('DELETE FROM group_members WHERE group_id=:g AND user_id=:u')->execute([':g'=>$groupId,':u'=>$uid]);
        api_ok([],'تم مغادرة المجموعة');
    } else api_error('action غير صالح');
}
else api_error('Method Not Allowed',405);
