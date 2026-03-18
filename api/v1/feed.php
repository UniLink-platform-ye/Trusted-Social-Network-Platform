<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$me = api_require_auth(); $uid = (int)$me['user_id'];
$page = max(1,(int)($_GET['page']??1)); $limit=15; $offset=($page-1)*$limit;
$groupId = (int)($_GET['group_id'] ?? 0);

try {
    $sql = "
        SELECT p.*, u.full_name, u.username, u.role, u.avatar_url, g.group_name
        FROM posts p
        JOIN users u ON u.user_id=p.user_id
        LEFT JOIN `groups` g ON g.group_id=p.group_id
        WHERE p.status='active'
          AND (p.visibility='public'
               OR (p.visibility='group' AND p.group_id IN
                   (SELECT gm.group_id FROM group_members gm WHERE gm.user_id=:uid)))
    ";
    if ($groupId > 0) {
        $sql .= " AND p.group_id = :gid";
    }
    $sql .= " ORDER BY p.created_at DESC LIMIT :lim OFFSET :off";

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':uid',$uid,PDO::PARAM_INT);
    if ($groupId > 0) $stmt->bindValue(':gid',$groupId,PDO::PARAM_INT);
    $stmt->bindValue(':lim',$limit,PDO::PARAM_INT);
    $stmt->bindValue(':off',$offset,PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll();
    api_ok(['posts'=>$posts,'page'=>$page,'per_page'=>$limit,'has_more'=>count($posts)===$limit]);
} catch(\Throwable $e) { api_error($e->getMessage(), 500); }
