<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$me = api_require_auth(); $uid = (int)$me['user_id'];

$search = trim($_GET['q'] ?? '');
$sql = "SELECT user_id, full_name, username, role, department, avatar_url FROM users WHERE status='active' AND user_id != :uid";

if ($search) {
    $sql .= " AND (full_name LIKE :q OR username LIKE :q OR department LIKE :q OR email LIKE :q)";
}
$sql .= " ORDER BY full_name ASC LIMIT 50";

$st = db()->prepare($sql);
$st->bindValue(':uid', $uid);
if ($search) $st->bindValue(':q', "%$search%");
$st->execute();

api_ok(['users' => $st->fetchAll()]);
