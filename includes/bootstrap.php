<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../admin/includes/helpers.php';
require_once __DIR__ . '/../admin/includes/auth.php';
require_once __DIR__ . '/../admin/includes/mailer.php';

restore_remember_session();

/** إعادة توجيه المستخدم غير المسجّل لصفحة الدخول */
function require_user_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'يرجى تسجيل الدخول أولاً.');
        redirect('login.php');
    }
}

/** رابط الواجهة الأمامية */
function user_url(string $path = ''): string
{
    return rtrim(APP_BASE_PATH, '/') . '/' . ltrim($path, '/');
}
