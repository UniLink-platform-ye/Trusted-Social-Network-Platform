<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * POST /api/v1/auth/reset_password
 * Body: { "reset_token": "...", "otp": "123456", "new_password": "...", "confirm_password": "..." }
 *
 * الوظيفة: يُراجع reset_token + OTP ويُحدّث كلمة المرور.
 *
 * Returns: { "success": true, "message": "تم تغيير كلمة المرور بنجاح" }
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method Not Allowed', 405);
}

$body           = json_body();
$resetToken     = trim($body['reset_token']    ?? '');
$otp            = preg_replace('/\D/', '', $body['otp'] ?? '');
$newPassword    = $body['new_password']    ?? '';
$confirmPass    = $body['confirm_password'] ?? '';

// ── التحقق من المدخلات ────────────────────────────────
if (!$resetToken) {
    api_error('reset_token مطلوب.', 422);
}

if (strlen($otp) !== 6) {
    api_error('رمز OTP يجب أن يتكون من 6 أرقام.', 422);
}

if (strlen($newPassword) < 8) {
    api_error('كلمة المرور يجب أن تكون 8 أحرف على الأقل.', 422);
}

if ($newPassword !== $confirmPass) {
    api_error('كلمتا المرور غير متطابقتين.', 422);
}

// ── التحقق من صلاحية الـ Token ────────────────────────
$payload = jwt_decode($resetToken);
if (!$payload || ($payload['purpose'] ?? '') !== 'password_reset') {
    api_error('رمز إعادة التعيين غير صالح أو منتهي الصلاحية. أعد الطلب.', 401);
}

$userId = (int) $payload['user_id'];

// ── التحقق من OTP ────────────────────────────────────
if (!verify_otp($userId, $otp)) {
    api_error('رمز التحقق غير صحيح أو انتهت صلاحيته.', 401);
}

// ── التحقق من وجود المستخدم ──────────────────────────
$user = fetch_user_by_id($userId);
if (!$user || in_array($user['status'], ['deleted', 'suspended'])) {
    api_error('الحساب غير متاح.', 403);
}

// ── تحديث كلمة المرور ────────────────────────────────
$newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
$updateStmt = db()->prepare(
    'UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE user_id = :id'
);
$updateStmt->execute([':hash' => $newHash, ':id' => $userId]);

api_ok([], 'تم تغيير كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.');
