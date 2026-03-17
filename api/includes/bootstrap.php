<?php
declare(strict_types=1);

/**
 * api/includes/bootstrap.php — Bootstrap مشترك لجميع نقاط API
 * يُحمّل: config, DB, helpers, JWT, notify
 * يضبط: CORS, Content-Type: JSON
 */

// ── CORS ───────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Requires ───────────────────────────────────────────────
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/jwt.php';
require_once __DIR__ . '/../../core/notify.php';
require_once __DIR__ . '/../../core/auto_join.php';
require_once __DIR__ . '/../../core/mailer.php';

// ── JSON body helper ───────────────────────────────────────
function json_body(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $d = json_decode($raw, true);
    return is_array($d) ? $d : [];
}

function api_ok(array $data = [], string $message = 'success', int $code = 200): void
{
    http_response_code($code);
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_error(string $message, int $code = 400, array $extra = []): void
{
    http_response_code($code);
    echo json_encode(array_merge(['success' => false, 'error' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
