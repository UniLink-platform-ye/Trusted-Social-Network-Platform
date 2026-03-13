<?php

declare(strict_types = 1)
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

// ── Amazon RDS ─────────────────────────────────────────────────────────────
const DB_HOST = 'unilink-platform.c6pgq44asn04.us-east-1.rds.amazonaws.com';
const DB_PORT = '3306';
const DB_NAME = 'trusted_social_network_platform';
const DB_USER = 'admin';
const DB_PASS = 'meera999444';
// ───────────────────────────────────────────────────────────────────────────

const REMEMBER_COOKIE = 'unilink_remember';
const REMEMBER_DAYS = 14;

// ── Gmail SMTP — OTP ───────────────────────────────────────────────────────
const MAIL_HOST = 'smtp.gmail.com';
const MAIL_PORT = 587;
const MAIL_USERNAME = 'wwwbby2040@gmail.com';
const MAIL_PASSWORD = 'cewr ojlr azsi fhur';
const MAIL_FROM = 'wwwbby2040@gmail.com';
const MAIL_FROM_NAME = 'UniLink Platform';
// ───────────────────────────────────────────────────────────────────────────
