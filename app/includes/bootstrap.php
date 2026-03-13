<?php
declare(strict_types=1);

// ── core/ (مكتبة مشتركة) ─────────────────────────────────
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/mailer.php';

restore_remember_session();

// ── دوال خاصة بالواجهة الأمامية ─────────────────────────

/** URL كامل داخل app/ */
function user_url(string $path = ''): string
{
    return rtrim(APP_BASE_PATH, '/') . '/app/' . ltrim($path, '/');
}

/** Redirect داخل app/ */
function app_redirect(string $page, string $query = ''): void
{
    $qs = $query ? '?' . $query : '';
    redirect('app/' . ltrim($page, '/') . $qs);
}

/** يتحقق من تسجيل الدخول وإلا يحوّل لصفحة login */
function require_user_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'يرجى تسجيل الدخول أولاً.');
        redirect('app/login.php');
    }
}
