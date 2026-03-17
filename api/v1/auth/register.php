<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * POST /api/v1/auth/register
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_error('Method Not Allowed', 405);

$b = json_body();
$fullName   = trim($b['full_name']   ?? '');
$email      = trim($b['email']       ?? '');
$password   = $b['password']         ?? '';
$role       = in_array($b['role']??'', ['student','professor']) ? $b['role'] : 'student';
$department = trim($b['department']  ?? '');
$academicId = trim($b['academic_id'] ?? '');
$yearLevel  = isset($b['year_level']) ? (int)$b['year_level'] : null;
$batchYear  = isset($b['batch_year']) ? (int)$b['batch_year'] : null;

if (!$fullName || !$email || !$password) api_error('full_name و email و password مطلوبة');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) api_error('البريد الإلكتروني غير صالح');
if (strlen($password) < 8) api_error('كلمة المرور يجب أن تكون 8 أحرف على الأقل');

$chk = db()->prepare('SELECT user_id FROM users WHERE email=:e LIMIT 1');
$chk->execute([':e' => $email]);
if ($chk->fetchColumn()) api_error('البريد الإلكتروني مسجّل مسبقاً', 409);

$username = explode('@', $email)[0] . rand(10,99);
$hash     = password_hash($password, PASSWORD_BCRYPT);
$inserted = false;
try {
    // يدعم الدفعة 1 (year_level, batch_year). إذا لم تكن الأعمدة موجودة بعد، سنfallback.
    $ins = db()->prepare('INSERT INTO users (username,email,password_hash,role,full_name,academic_id,department,year_level,batch_year,is_verified,status) VALUES (:u,:e,:h,:r,:fn,:ai,:dep,:yl,:by,0,"active")');
    $ins->execute([
        ':u'=>$username,':e'=>$email,':h'=>$hash,':r'=>$role,':fn'=>$fullName,
        ':ai'=>$academicId,':dep'=>$department,
        ':yl'=>$yearLevel,':by'=>$batchYear,
    ]);
    $inserted = true;
} catch (\Throwable $e) {
    $ins = db()->prepare('INSERT INTO users (username,email,password_hash,role,full_name,academic_id,department,is_verified,status) VALUES (:u,:e,:h,:r,:fn,:ai,:dep,0,"active")');
    $ins->execute([':u'=>$username,':e'=>$email,':h'=>$hash,':r'=>$role,':fn'=>$fullName,':ai'=>$academicId,':dep'=>$department]);
    $inserted = true;
}
$userId = (int)db()->lastInsertId();

$otp = generate_and_store_otp($userId);
send_otp_email($email, $fullName, $otp);

// Auto-Join (best-effort)
try { auto_join_apply($userId); } catch (\Throwable $e) {}

$pendingToken = jwt_encode(['user_id'=>$userId,'email'=>$email,'purpose'=>'otp_pending','exp'=>time()+600]);
api_ok(['pending_token' => $pendingToken], 'تم إنشاء الحساب. أدخل رمز OTP المُرسَل لبريدك لتفعيل الحساب.', 201);
