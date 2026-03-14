<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * POST /api/v1/auth/login
 * Body: { "email": "...", "password": "..." }
 * Returns: { success, message } — يُرسل OTP للبريد
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_error('Method Not Allowed', 405);

$body  = json_body();
$email = trim($body['email'] ?? '');
$pass  = $body['password'] ?? '';

if (!$email || !$pass) api_error('email و password مطلوبان');

$stmt = db()->prepare('SELECT * FROM users WHERE email = :e LIMIT 1');
$stmt->execute([':e' => $email]);
$user = $stmt->fetch();

if (!$user) api_error('البريد الإلكتروني أو كلمة المرور غير صحيحة', 401);

$hash = (string)$user['password_hash'];
if (str_starts_with($hash, '$2b$')) $hash = '$2y$' . substr($hash, 4);
if (!password_verify($pass, $hash)) api_error('البريد الإلكتروني أو كلمة المرور غير صحيحة', 401);

if ($user['status'] === 'suspended') api_error('تم تعليق حسابك', 403);
if (!$user['is_verified'])           api_error('حسابك غير مفعّل. يرجى تفعيله عبر البريد', 403);

// توليد OTP وحفظه
$otp = generate_and_store_otp((int)$user['user_id']);
send_otp_email($user['email'], $user['full_name'] ?? $user['username'], $otp);

// نُعيد user_id مشفراً مؤقتاً (pending token) — ينتهي خلال 10 دقائق
$pendingToken = jwt_encode([
    'user_id' => (int)$user['user_id'],
    'email'   => $user['email'],
    'purpose' => 'otp_pending',
    'exp'     => time() + 600, // 10 دقائق فقط
]);

api_ok(
    ['pending_token' => $pendingToken, 'email_masked' => preg_replace('/(?<=.{3}).(?=.*@)/u', '*', $email)],
    'تم إرسال رمز التحقق OTP إلى بريدك. صالح لـ 5 دقائق.'
);
