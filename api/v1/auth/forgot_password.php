<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * POST /api/v1/auth/forgot_password
 * Body: { "email": "user@example.com" }
 *
 * Returns: { success, data: { reset_token, email_masked }, message }
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method Not Allowed', 405);
}

$body  = json_body();
$email = trim(strtolower($body['email'] ?? ''));

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    api_error('أدخل بريداً إلكترونياً صحيحاً.', 422);
}

// ── دالة تمويه البريد الإلكتروني (بدون lookbehind) ──
function mask_email(string $email): string {
    $parts = explode('@', $email, 2);
    $local = $parts[0] ?? '';
    $domain = $parts[1] ?? '';
    $visible = min(3, strlen($local));
    return substr($local, 0, $visible) . str_repeat('*', max(0, strlen($local) - $visible)) . '@' . $domain;
}

// ── البحث عن المستخدم ──────────────────────────────────
$stmt = db()->prepare('SELECT user_id, full_name, username, status FROM users WHERE email = :e LIMIT 1');
$stmt->execute([':e' => $email]);
$user = $stmt->fetch();

// رد موحّد (User Enumeration protection)
if (!$user || $user['status'] === 'deleted') {
    api_ok(
        ['reset_token' => '', 'email_masked' => mask_email($email)],
        'إذا كان البريد مسجلاً، ستصل إليك رسالة التحقق.'
    );
}

if ($user['status'] === 'suspended') {
    api_error('تم تعليق حسابك. تواصل مع الإدارة.', 403);
}

$userId   = (int) $user['user_id'];
$userName = (string) ($user['full_name'] ?? $user['username'] ?? 'مستخدم');

// ── توليد OTP وإرساله ─────────────────────────────────
$otp = generate_and_store_otp($userId);
send_otp_email($email, $userName, $otp);

// ── توليد reset_token مؤقت (10 دقائق) ───────────────
$resetToken = jwt_encode([
    'user_id' => $userId,
    'email'   => $email,
    'purpose' => 'password_reset',
    'exp'     => time() + 600,
]);

api_ok(
    ['reset_token' => $resetToken, 'email_masked' => mask_email($email)],
    'إذا كان البريد مسجلاً، ستصل إليك رسالة التحقق خلال لحظات.'
);
