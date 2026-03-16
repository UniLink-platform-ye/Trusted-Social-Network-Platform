<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * POST /api/v1/auth/resend_otp
 * Body (أحد الخيارين):
 *   1) { "pending_token": "..." }  — من شاشة OTP لإعادة إرسال الرمز
 *   2) { "email": "...", "password": "..." } — لحساب غير مفعّل من شاشة تسجيل الدخول
 * Returns: { success, data: { pending_token, email_masked? } }
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_error('Method Not Allowed', 405);

$body = json_body();
$pTok  = $body['pending_token'] ?? '';
$email = trim($body['email'] ?? '');
$pass  = $body['password'] ?? '';

$userId = null;
$userEmail = null;
$userName = '';

// ─── الطريقة 1: إعادة إرسال من شاشة OTP (لدينا pending_token) ───
if ($pTok !== '') {
    $payload = jwt_decode($pTok);
    if (!$payload || ($payload['purpose'] ?? '') !== 'otp_pending') {
        api_error('انتهت صلاحية الجلسة. أعد تسجيل الدخول أو اطلب إعادة الإرسال من شاشة الدخول.', 401);
    }
    $userId   = (int)$payload['user_id'];
    $userEmail = $payload['email'] ?? '';
    $user = fetch_user_by_id($userId);
    if (!$user || $user['status'] !== 'active') api_error('الحساب غير متاح', 403);
    $userName = $user['full_name'] ?? $user['username'] ?? '';
}
// ─── الطريقة 2: تفعيل حساب غير مفعّل (email + password) ───
elseif ($email !== '' && $pass !== '') {
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :e LIMIT 1');
    $stmt->execute([':e' => $email]);
    $user = $stmt->fetch();
    if (!$user) api_error('البريد الإلكتروني أو كلمة المرور غير صحيحة', 401);
    $hash = (string)$user['password_hash'];
    if (str_starts_with($hash, '$2b$')) $hash = '$2y$' . substr($hash, 4);
    if (!password_verify($pass, $hash)) api_error('البريد الإلكتروني أو كلمة المرور غير صحيحة', 401);
    if ($user['status'] === 'suspended') api_error('تم تعليق حسابك', 403);
    if ($user['is_verified']) api_error('حسابك مفعّل بالفعل. استخدم تسجيل الدخول.', 400);
    $userId   = (int)$user['user_id'];
    $userEmail = (string)$user['email'];
    $userName  = $user['full_name'] ?? $user['username'] ?? '';
}
else {
    api_error('أرسل إما pending_token أو email مع password', 400);
}

$otp = generate_and_store_otp($userId);
send_otp_email($userEmail, $userName, $otp);

$newPendingToken = jwt_encode([
    'user_id' => $userId,
    'email'   => $userEmail,
    'purpose' => 'otp_pending',
    'exp'     => time() + 600,
]);

$data = ['pending_token' => $newPendingToken];
if ($userEmail !== '') {
    $data['email_masked'] = preg_replace('/(?<=.{3}).(?=.*@)/u', '*', $userEmail);
}

api_ok($data, 'تم إرسال رمز جديد إلى بريدك. صالح لـ 5 دقائق.');
