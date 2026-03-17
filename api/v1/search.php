<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$me = api_require_auth();
$uid = (int)$me['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method Not Allowed', 405);
}

$q = trim((string)($_GET['q'] ?? ''));
if ($q === '') api_error('q مطلوب');
$like = '%' . $q . '%';

try {
    // Users
    $uStmt = db()->prepare(
        'SELECT user_id, full_name, username, email, role, department, avatar_url
         FROM users
         WHERE status = \'active\'
           AND (full_name LIKE :q OR username LIKE :q2 OR email LIKE :q3 OR department LIKE :q4)
         ORDER BY full_name ASC
         LIMIT 20'
    );
    $uStmt->execute([':q' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like]);
    $users = $uStmt->fetchAll();

    // Groups (التي يمكن للمستخدم رؤيتها)
    $gStmt = db()->prepare(
        'SELECT g.*
         FROM `groups` g
         WHERE g.status = \'active\'
           AND (g.group_name LIKE :q OR g.description LIKE :q2)
           AND (
              g.privacy = \'public\'
              OR g.group_id IN (SELECT group_id FROM group_members WHERE user_id=:uid)
           )
         ORDER BY g.group_name ASC
         LIMIT 20'
    );
    $gStmt->execute([':q' => $like, ':q2' => $like, ':uid' => $uid]);
    $groups = $gStmt->fetchAll();

    // Posts (فقط التي يحق له رؤيتها كما في feed.php)
    $pStmt = db()->prepare(
        'SELECT p.*, u.full_name, g.group_name
         FROM posts p
         JOIN users u ON u.user_id = p.user_id
         LEFT JOIN `groups` g ON g.group_id = p.group_id
         WHERE p.status = \'active\'
           AND p.content LIKE :q
           AND (
             p.visibility = \'public\' OR
             (p.visibility = \'group\' AND p.group_id IN (SELECT group_id FROM group_members WHERE user_id=:uid))
           )
         ORDER BY p.created_at DESC
         LIMIT 20'
    );
    $pStmt->execute([':q' => $like, ':uid' => $uid]);
    $posts = $pStmt->fetchAll();

    // Courses
    $cStmt = db()->prepare(
        'SELECT course_id, code, name, department, is_active
         FROM courses
         WHERE (code LIKE :q OR name LIKE :q2 OR department LIKE :q3)
         ORDER BY code ASC
         LIMIT 20'
    );
    $cStmt->execute([':q' => $like, ':q2' => $like, ':q3' => $like]);
    $courses = $cStmt->fetchAll();

    api_ok([
        'users'   => $users,
        'groups'  => $groups,
        'posts'   => $posts,
        'courses' => $courses,
    ]);
} catch (\Throwable $e) {
    api_error($e->getMessage(), 500);
}

