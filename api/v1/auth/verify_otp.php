<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * POST /api/v1/auth/verify_otp
 * Body: { "pending_token": "...", "otp": "123456" }
 * Returns: { success, data: { token, user } }
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_error('Method Not Allowed', 405);

$body  = json_body();
$otp   = preg_replace('/\D/', '', $body['otp'] ?? '');
$pTok  = $body['pending_token'] ?? '';

if (!$pTok || strlen($otp) !== 6) api_error('pending_token و otp مطلوبان');

$payload = jwt_decode($pTok);
if (!$payload || ($payload['purpose'] ?? '') !== 'otp_pending') api_error('pending_token غير صالح أو منتهي', 401);

$userId = (int)$payload['user_id'];

if (!verify_otp($userId, $otp)) api_error('رمز OTP غير صحيح أو انتهت صلاحيته', 401);

// جلب بيانات المستخدم
$user = fetch_user_by_id($userId);
if (!$user || $user['status'] !== 'active') api_error('الحساب غير نشط', 403);

// تحديث last_login
db()->prepare('UPDATE users SET last_login = NOW() WHERE user_id = :id')->execute([':id' => $userId]);

// توليد JWT طويل الأمد (30 يوماً)
$token = jwt_encode([
    'user_id'    => $userId,
    'email'      => $user['email'],
    'role'       => $user['role'],
    'full_name'  => $user['full_name'],
]);

api_ok([
    'token' => $token,
    'user'  => [
        'user_id'     => $userId,
        'full_name'   => $user['full_name'],
        'username'    => $user['username'],
        'email'       => $user['email'],
        'role'        => $user['role'],
        'department'  => $user['department'] ?? '',
        'academic_id' => $user['academic_id'] ?? '',
        'avatar_url'  => $user['avatar_url'] ?? null,
        'is_verified' => (bool)$user['is_verified'],
    ],
], 'تم تسجيل الدخول بنجاح');
