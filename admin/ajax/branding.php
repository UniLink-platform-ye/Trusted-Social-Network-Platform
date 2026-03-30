<?php

declare(strict_types=1);

/**
 * admin/ajax/branding.php — AJAX handler لحفظ إعدادات الهوية البصرية
 *
 * POST actions:
 *   save_branding   — حفظ إعدادات الهوية
 *   upload_logo     — رفع شعار جديد
 */

require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_ajax_request()) {
    http_response_code(403);
    exit;
}

require_permission('settings.manage');

$action = trim((string) ($_POST['action'] ?? ''));

/* ── save_branding ─────────────────────────────────────── */
if ($action === 'save_branding') {
    verify_csrf_or_abort();

    // قائمة الحقول المسموح بها وأقصى طولها
    $colorFields = [
        'primary_color',
        'secondary_color',
        'accent_color',
        'background_color',
        'text_color',
        'button_primary_color',
        'button_text_color',
        'card_bg_color',
        'input_bg_color',
        'input_border_color',
    ];

    $data = [];

    // اسم المنصة والعنوان الفرعي
    $data['platform_name'] = trim(strip_tags((string) ($_POST['platform_name'] ?? 'UniLink')));
    $data['platform_tagline'] = trim(strip_tags((string) ($_POST['platform_tagline'] ?? '')));
    $data['font_family'] = trim(strip_tags((string) ($_POST['font_family'] ?? 'Cairo')));
    $data['active_template_key'] = trim(strip_tags((string) ($_POST['active_template_key'] ?? 'deep_blue')));

    if (mb_strlen($data['platform_name']) < 1 || mb_strlen($data['platform_name']) > 120) {
        json_response(['success' => false, 'message' => 'اسم المنصة يجب أن يكون بين 1 و 120 حرفاً.'], 422);
    }

    // ألوان — التحقق من صحة الـ HEX
    foreach ($colorFields as $field) {
        $val = strtoupper(trim((string) ($_POST[$field] ?? '')));
        if (!preg_match('/^#[0-9A-F]{3,6}$/', $val)) {
            json_response(['success' => false, 'message' => "قيمة لون غير صالحة في الحقل: $field"], 422);
        }
        $data[$field] = $val;
    }

    $data['updated_by'] = (int) (current_user()['user_id'] ?? 0) ?: null;

    // بناء أعمدة UPDATE
    $setClauses = [];
    $params = [];
    foreach ($data as $col => $val) {
        $setClauses[] = "`$col` = :$col";
        $params[":$col"] = $val;
    }

    try {
        // تأكد من وجود الصف id=1
        $exists = (int) db()->query('SELECT COUNT(*) FROM `branding_settings` WHERE id = 1')->fetchColumn();
        if (!$exists) {
            db()->exec("INSERT INTO `branding_settings` (id) VALUES (1)");
        }

        $sql = 'UPDATE `branding_settings` SET ' . implode(', ', $setClauses) . ' WHERE id = 1';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        log_activity('permission_change', 'branding_settings', 1, 'تحديث إعدادات الهوية البصرية');
        json_response(['success' => true, 'message' => 'تم حفظ إعدادات الهوية بنجاح ✓']);
    } catch (Throwable $e) {
        error_log('[Branding Save] ' . $e->getMessage());
        json_response(['success' => false, 'message' => 'خطأ في قاعدة البيانات. يرجى المحاولة مجدداً.'], 500);
    }
}

/* ── upload_logo ───────────────────────────────────────── */ elseif ($action === 'upload_logo') {
    verify_csrf_or_abort();

    if (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        json_response(['success' => false, 'message' => 'لم يُرسَل ملف أو حدث خطأ أثناء الرفع.'], 422);
    }

    $file = $_FILES['logo'];
    $maxSize = 4 * 1024 * 1024; // 2 MB
    $allowed = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'];
    $extMap = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/svg+xml' => 'svg', 'image/webp' => 'webp'];

    if ($file['size'] > $maxSize) {
        json_response(['success' => false, 'message' => 'حجم الملف يتجاوز 2 MB.'], 422);
    }

    $mimeType = mime_content_type($file['tmp_name']);
    if (!in_array($mimeType, $allowed, true)) {
        json_response(['success' => false, 'message' => 'نوع الملف غير مدعوم. يُسمح بـ: PNG، JPG، SVG، WebP.'], 422);
    }

    $ext = $extMap[$mimeType];
    $filename = 'logo_' . time() . '.' . $ext;
    $uploadDir = __DIR__ . '/../../uploads/branding/';
    $logoPath = 'uploads/branding/' . $filename;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        json_response(['success' => false, 'message' => 'فشل نقل الملف. تحقق من صلاحيات المجلد.'], 500);
    }

    try {
        $exists = (int) db()->query('SELECT COUNT(*) FROM `branding_settings` WHERE id = 1')->fetchColumn();
        if (!$exists) {
            db()->exec("INSERT INTO `branding_settings` (id) VALUES (1)");
        }
        db()->prepare('UPDATE `branding_settings` SET logo_path = :p WHERE id = 1')
            ->execute([':p' => $logoPath]);

        log_activity('file_upload', 'branding_settings', 1, 'رفع شعار جديد: ' . $filename);

        $logoUrl = url($logoPath);
        json_response(['success' => true, 'message' => 'تم رفع الشعار بنجاح.', 'logo_url' => $logoUrl]);
    } catch (Throwable $e) {
        error_log('[Logo Upload] ' . $e->getMessage());
        json_response(['success' => false, 'message' => 'خطأ في قاعدة البيانات.'], 500);
    }
}

/* ── default ───────────────────────────────────────────── */ else {
    json_response(['success' => false, 'message' => 'إجراء غير معروف.'], 400);
}
