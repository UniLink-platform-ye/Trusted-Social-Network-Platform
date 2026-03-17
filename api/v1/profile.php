<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$me = api_require_auth();
$uid = (int)$me['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

function _profile_fetch(int $uid): array
{
    $st = db()->prepare('SELECT user_id, username, email, role, full_name, academic_id, department, avatar_url, year_level, batch_year, is_verified, status, created_at, updated_at FROM users WHERE user_id=:id LIMIT 1');
    $st->execute([':id' => $uid]);
    $u = $st->fetch();
    if (!$u) api_error('المستخدم غير موجود', 404);
    return $u;
}

if ($method === 'GET') {
    try {
        api_ok(['user' => _profile_fetch($uid)]);
    } catch (\Throwable $e) {
        // في حال لم تُطبَّق migrations بعد (year_level/batch_year)، نرجع بأقل مجموعة حقول
        $st = db()->prepare('SELECT user_id, username, email, role, full_name, academic_id, department, avatar_url, is_verified, status, created_at, updated_at FROM users WHERE user_id=:id LIMIT 1');
        $st->execute([':id' => $uid]);
        $u = $st->fetch();
        if (!$u) api_error('المستخدم غير موجود', 404);
        api_ok(['user' => $u]);
    }
}

if ($method === 'POST' || $method === 'PUT') {
    $b = json_body();

    // تحديث ذاتي فقط — بدون تغيير role/status عبر هذا الـ endpoint.
    $allowed = [
        'full_name'   => 'full_name',
        'department'  => 'department',
        'academic_id' => 'academic_id',
        'avatar_url'  => 'avatar_url',
        'year_level'  => 'year_level',
        'batch_year'  => 'batch_year',
    ];

    $set = [];
    $params = [':id' => $uid];

    $academicChanged = false;

    foreach ($allowed as $inKey => $col) {
        if (!array_key_exists($inKey, $b)) continue;

        if (in_array($inKey, ['year_level', 'batch_year'], true)) {
            $val = $b[$inKey];
            $val = ($val === null || $val === '') ? null : (int)$val;
        } else {
            $val = is_string($b[$inKey] ?? null) ? trim((string)$b[$inKey]) : ($b[$inKey] ?? null);
        }

        $set[] = "`$col` = :$inKey";
        $params[":$inKey"] = $val;

        if (in_array($inKey, ['department','academic_id','year_level','batch_year'], true)) {
            $academicChanged = true;
        }
    }

    if (!$set) api_error('لا توجد بيانات للتحديث');

    try {
        $sql = 'UPDATE users SET ' . implode(', ', $set) . ' WHERE user_id = :id';
        db()->prepare($sql)->execute($params);

        if ($academicChanged) {
            auto_join_apply($uid);
        }

        api_ok(['user' => _profile_fetch($uid)], 'تم تحديث الملف الشخصي');
    } catch (\Throwable $e) {
        api_error($e->getMessage(), 500);
    }
}

api_error('Method Not Allowed', 405);

