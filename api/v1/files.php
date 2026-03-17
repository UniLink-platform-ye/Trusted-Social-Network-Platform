<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$me = api_require_auth();
$uid = (int)$me['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

function _base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function _normalize_path(string $p): string
{
    $p = str_replace('\\', '/', $p);
    $p = preg_replace('#/+#', '/', $p) ?: $p;
    return ltrim($p, '/');
}

function _file_type_from_ext(string $ext): string
{
    $ext = strtolower($ext);
    return match ($ext) {
        'pdf' => 'pdf',
        'jpg', 'jpeg', 'png', 'gif', 'webp' => 'image',
        'ppt', 'pptx', 'key' => 'presentation',
        'zip', 'rar', '7z' => 'archive',
        'mp4', 'mov', 'avi', 'mkv' => 'video',
        default => 'other',
    };
}

function _download_url(array $row): string
{
    $storagePath = (string)($row['storage_path'] ?? '');
    if ($storagePath === '') return '';

    // storage_path محفوظ كمسار نسبي (مثال: uploads/files/2026/03/x.pdf)
    $p = _normalize_path($storagePath);
    return rtrim(_base_url(), '/') . rtrim(APP_BASE_PATH, '/') . '/' . $p;
}

if ($method === 'GET') {
    $q = trim($_GET['q'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $courseId = (int)($_GET['course_id'] ?? 0);
    $groupId  = (int)($_GET['group_id'] ?? 0);
    $fileType = trim($_GET['file_type'] ?? '');
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));

    $where = [];
    $params = [];

    // فلترة بالرؤية: ملفات المجموعة تُعرض فقط لأعضاء المجموعة.
    // ملفات بدون group_id تعتبر عامة (داخل المنصة).
    $where[] = '(f.group_id IS NULL OR f.group_id IN (SELECT group_id FROM group_members WHERE user_id = :uid))';
    $params[':uid'] = $uid;

    if ($groupId > 0) {
        $where[] = 'f.group_id = :gid';
        $params[':gid'] = $groupId;
    }
    if ($courseId > 0) {
        $where[] = 'f.course_id = :cid';
        $params[':cid'] = $courseId;
    }
    if ($category !== '' && in_array($category, ['lecture','assignment','reference','other'], true)) {
        $where[] = 'f.category = :cat';
        $params[':cat'] = $category;
    }
    if ($fileType !== '' && in_array($fileType, ['pdf','image','presentation','archive','video','other'], true)) {
        $where[] = 'f.file_type = :ft';
        $params[':ft'] = $fileType;
    }
    if ($q !== '') {
        $where[] = '(f.original_name LIKE :q OR f.title LIKE :q2 OR f.description LIKE :q3)';
        $like = "%$q%";
        $params[':q'] = $like;
        $params[':q2'] = $like;
        $params[':q3'] = $like;
    }

    $sql = '
        SELECT
          f.*,
          u.full_name AS uploader_name,
          u.username  AS uploader_username,
          g.group_name,
          c.code AS course_code,
          c.name AS course_name
        FROM files f
        JOIN users u ON u.user_id = f.user_id
        LEFT JOIN `groups` g ON g.group_id = f.group_id
        LEFT JOIN courses c ON c.course_id = f.course_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY f.created_at DESC
        LIMIT ' . (int)$limit;

    try {
        $st = db()->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();

        foreach ($rows as &$r) {
            $r['download_url'] = _download_url($r);
        }
        unset($r);

        api_ok(['files' => $rows]);
    } catch (\Throwable $e) {
        api_error($e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    // Upload: multipart/form-data
    if (!isset($_FILES['file'])) api_error('file مطلوب');
    $file = $_FILES['file'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) api_error('فشل رفع الملف');

    $originalName = basename((string)($file['name'] ?? 'file'));
    $size = (int)($file['size'] ?? 0);
    $maxSize = 100 * 1024 * 1024; // 100MB
    if ($size <= 0) api_error('حجم الملف غير صالح');
    if ($size > $maxSize) api_error('الملف أكبر من 100MB');

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $fileType = _file_type_from_ext($ext);

    $category = trim($_POST['category'] ?? 'other');
    if (!in_array($category, ['lecture','assignment','reference','other'], true)) $category = 'other';

    $courseId = !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null;
    $groupId  = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;
    $postId   = !empty($_POST['post_id'])  ? (int)$_POST['post_id']  : null;

    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));

    // تحقق عضوية المجموعة إذا تم تمرير group_id
    if ($groupId !== null) {
        $gm = db()->prepare('SELECT 1 FROM group_members WHERE group_id=:g AND user_id=:u LIMIT 1');
        $gm->execute([':g' => $groupId, ':u' => $uid]);
        if (!$gm->fetchColumn()) api_error('لا يمكنك رفع ملف لمجموعة لست عضواً فيها', 403);
    }

    $subDir = date('Y/m');
    $uploadDir = realpath(__DIR__ . '/../../') . '/uploads/files/' . $subDir . '/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);

    $storedName = uniqid('f_', true) . ($ext ? ('.' . $ext) : '');
    $dest = $uploadDir . $storedName;
    if (!move_uploaded_file($file['tmp_name'], $dest)) api_error('فشل حفظ الملف', 500);

    // نخزن المسار نسبيًا ليعمل مع اختلاف الدومين/السيرفر
    $storagePath = 'uploads/files/' . $subDir . '/' . $storedName;

    try {
        $insertedId = 0;

        // schema الأساسي (trusted_social_admin_modules.sql) يحتوي: stored_name + storage_path
        try {
            $ins = db()->prepare(
                'INSERT INTO files (user_id, post_id, course_id, group_id, category, title, description, original_name, stored_name, file_type, file_size, storage_path, is_encrypted, download_count, created_at)
                 VALUES (:u, :p, :cid, :gid, :cat, :t, :d, :on, :sn, :ft, :fs, :sp, 0, 0, NOW())'
            );
            $ins->execute([
                ':u' => $uid,
                ':p' => $postId,
                ':cid' => $courseId,
                ':gid' => $groupId,
                ':cat' => $category,
                ':t' => ($title !== '' ? $title : null),
                ':d' => ($description !== '' ? $description : null),
                ':on' => $originalName,
                ':sn' => $storedName,
                ':ft' => $fileType,
                ':fs' => $size,
                ':sp' => $storagePath,
            ]);
            $insertedId = (int)db()->lastInsertId();
        } catch (\Throwable $e) {
            // fallback لنسخ قديمة (إن وُجدت) — لا نكسر الرفع بالكامل
            $ins = db()->prepare(
                'INSERT INTO files (user_id, post_id, original_name, stored_name, file_type, file_size, storage_path, is_encrypted, download_count, created_at)
                 VALUES (:u, :p, :on, :sn, :ft, :fs, :sp, 0, 0, NOW())'
            );
            $ins->execute([
                ':u' => $uid,
                ':p' => $postId,
                ':on' => $originalName,
                ':sn' => $storedName,
                ':ft' => $fileType,
                ':fs' => $size,
                ':sp' => $storagePath,
            ]);
            $insertedId = (int)db()->lastInsertId();
        }

        // تسجيل نشاط (يتوافق مع ENUM file_upload في schema الحالي)
        try { log_activity('file_upload', 'files', $insertedId, 'رفع ملف: ' . $originalName); } catch (\Throwable $e) {}

        // جلب السجل الجديد
        $st = db()->prepare('SELECT f.*, u.full_name AS uploader_name FROM files f JOIN users u ON u.user_id=f.user_id WHERE f.file_id=:id LIMIT 1');
        $st->execute([':id' => $insertedId]);
        $row = $st->fetch() ?: ['file_id' => $insertedId];
        $row['download_url'] = _download_url($row);

        api_ok(['file' => $row], 'تم رفع الملف بنجاح', 201);
    } catch (\Throwable $e) {
        // cleanup
        @unlink($dest);
        api_error($e->getMessage(), 500);
    }
}

if ($method === 'DELETE') {
    $fileId = (int)($_GET['id'] ?? 0);
    if ($fileId <= 0) api_error('id مطلوب');

    $st = db()->prepare('SELECT file_id, user_id, storage_path FROM files WHERE file_id=:id LIMIT 1');
    $st->execute([':id' => $fileId]);
    $f = $st->fetch();
    if (!$f) api_error('الملف غير موجود', 404);

    $ownerId = (int)$f['user_id'];
    if ($ownerId !== $uid && !in_array($me['role'], ['admin','supervisor'], true)) {
        api_error('ليس لديك صلاحية', 403);
    }

    try {
        db()->prepare('DELETE FROM files WHERE file_id=:id')->execute([':id' => $fileId]);
        try { log_activity('file_delete', 'files', $fileId, 'حذف ملف file_id=' . $fileId); } catch (\Throwable $e) {}

        $p = _normalize_path((string)($f['storage_path'] ?? ''));
        if ($p !== '') {
            $abs = realpath(__DIR__ . '/../../') . '/' . $p;
            if (is_string($abs) && file_exists($abs)) @unlink($abs);
        }
        api_ok([], 'تم حذف الملف');
    } catch (\Throwable $e) {
        api_error($e->getMessage(), 500);
    }
}

api_error('Method Not Allowed', 405);

