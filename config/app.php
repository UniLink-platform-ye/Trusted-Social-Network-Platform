<?php

declare(strict_types=1)
;

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => true, // HTTPS على Amazon
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

date_default_timezone_set('Asia/Riyadh');

const APP_NAME = 'UniLink – Trusted Social Network';
const APP_BASE_PATH = '/Trusted-Social-Network-Platform';
const APP_LOCALE = 'ar';
const APP_DIR = 'rtl';

// ── التبديل التلقائي لبيئة العمل (Local vs Production) ───────────────────────
$isLocal = true;

if (isset($_SERVER['HTTP_HOST'])) {
    $host = strtolower($_SERVER['HTTP_HOST']);
    // إذا كان المضيف localhost أو 127.0.0.1 أو الآي بي الخاص بالمحاكي 10.0.2.2 أو آي بي محلي
    if (str_contains($host, 'localhost') || str_contains($host, '127.0.0.1') || str_contains($host, '10.0.2.2') || str_starts_with($host, '192.168.1.20')) {
        $isLocal = true;
    }
} elseif (php_sapi_name() === 'cli') {
    // إذا كان يعمل عبر الـ CLI (مثل Cron jobs) ويوجد في مسار XAMPP
    if (str_contains(strtolower(__DIR__), 'xampp') || str_contains(strtolower(__DIR__), 'htdocs')) {
        $isLocal = true;
    }
}

if ($isLocal) {
    // إعدادات XAMPP المحلية
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'trusted_social_network_platform');
    define('DB_USER', 'root');
    define('DB_PASS', 'root');
} else {
    // إعدادات البيئة الحقيقية - Amazon RDS
    define('DB_HOST', 'unilink-platform.c6pgq44asn04.us-east-1.rds.amazonaws.com');
    define('DB_PORT', '3306');
    define('DB_NAME', 'trusted_social_network_platform');
    define('DB_USER', 'admin');
    define('DB_PASS', 'meera999444');
}
// ───────────────────────────────────────────────────────────────────────────

const REMEMBER_COOKIE = 'unilink_remember';
const REMEMBER_DAYS = 14;

// ── Gmail SMTP — OTP ───────────────────────────────────────────────────────
const MAIL_HOST = 'smtp.gmail.com';
const MAIL_PORT = 587;
const MAIL_USERNAME = 'uniklinikplatform@gmail.com';
const MAIL_PASSWORD = 'tpzg hyzk tbye fkfo';
const MAIL_FROM = 'uniklinikplatform@gmail.com';
const MAIL_FROM_NAME = 'UniLink Platform';


// ── JWT — REST API ─────────────────────────────────────────────────────────
const JWT_SECRET = 'unilink_jwt_s3cr3t_2026_change_in_prod';
const JWT_EXPIRY = 30 * 24 * 3600; // 30 يوماً بالثواني
// ───────────────────────────────────────────────────────────────────────────

// ── Firebase FCM — Push Notifications (HTTP v1) ───────────────────────────
// تعتمد الطريقة الجديدة على ملف Service Account JSON
const FCM_PROJECT_ID = 'trusted-social-platform';
define('FCM_SA_FILE', __DIR__ . '/../../trusted-social-platform-firebase-adminsdk-fbsvc-e40bbe1ca1.json');
// ───────────────────────────────────────────────────────────────────────────
