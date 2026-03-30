<?php

declare(strict_types=1);

/**
 * api/v1/branding.php — Branding API (Public, read-only)
 *
 * GET /api/v1/branding.php
 * يُرجع إعدادات الهوية البصرية الحالية للمنصة.
 *
 * الاستجابة:
 * {
 *   "success": true,
 *   "data": {
 *     "platform_name": "UniLink",
 *     "platform_tagline": "...",
 *     "primary_color": "#004D8C",
 *     ...
 *     "logo_url": "http://...",
 *     "active_template_key": "deep_blue"
 *   }
 * }
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/branding.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: public, max-age=300'); // cache 5 minutes

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

try {
    $b = get_branding();

    // بناء URL كامل للشعار
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(APP_BASE_PATH, '/');

    $logoUrl = null;
    if (!empty($b['logo_path'])) {
        $logoPath = ltrim($b['logo_path'], '/');
        $logoUrl  = $scheme . '://' . $host . $basePath . '/' . $logoPath;
    } else {
        $logoUrl = $scheme . '://' . $host . $basePath . '/img/logo.png';
    }

    echo json_encode([
        'success' => true,
        'data'    => [
            'platform_name'        => $b['platform_name'],
            'platform_tagline'     => $b['platform_tagline'],
            'primary_color'        => $b['primary_color'],
            'secondary_color'      => $b['secondary_color'],
            'accent_color'         => $b['accent_color'],
            'background_color'     => $b['background_color'],
            'text_color'           => $b['text_color'],
            'button_primary_color' => $b['button_primary_color'],
            'button_text_color'    => $b['button_text_color'],
            'card_bg_color'        => $b['card_bg_color'],
            'input_bg_color'       => $b['input_bg_color'],
            'input_border_color'   => $b['input_border_color'],
            'font_family'          => $b['font_family'],
            'logo_url'             => $logoUrl,
            'active_template_key'  => $b['active_template_key'],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    error_log('[branding API] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
