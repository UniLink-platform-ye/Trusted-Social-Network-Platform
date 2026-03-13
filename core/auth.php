<?php

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// auth.php  —  مبني على هيكلية 01_schema.sql (unilink_db)
//
// جدول users يحتوي على:
//   role ENUM('student','professor','admin','supervisor')
//   otp_code, otp_expires_at  ← للتحقق الثنائي OTP
//   last_login, status, is_verified
// ─────────────────────────────────────────────────────────────────────────────

function fetch_user_by_id(int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT * FROM users WHERE user_id = :id AND status != \'deleted\' LIMIT 1'
    );
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function current_user(): ?array
{
    // نتحقق بـ isset لأن user_id قد يكون 0
    if (!isset($_SESSION['user']['user_id'])) {
        return null;
    }
    $userId = (int) $_SESSION['user']['user_id'];

    static $cached    = null;
    static $cachedId  = 0;

    if ($cached !== null && $cachedId === $userId) {
        return $cached;
    }

    $user = fetch_user_by_id($userId);
    if (!$user || $user['status'] !== 'active') {
        logout_user(false);
        return null;
    }

    // تحديث بيانات الجلسة من قاعدة البيانات
    $_SESSION['user']['role']      = $user['role'];
    $_SESSION['user']['full_name'] = $user['full_name'];
    $_SESSION['user']['email']     = $user['email'];

    $cached   = $user;
    $cachedId = $userId;

    return $cached;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function set_login_session(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'user_id'   => (int) $user['user_id'],
        'role'      => $user['role'],          // ENUM مباشرة من قاعدة البيانات
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'username'  => $user['username'],
    ];
    // مسح cache الصلاحيات عند كل جلسة جديدة
    unset($_SESSION['permission_cache']);
}

// ─────────────────────────────────────────────────────────────────────────────
// OTP — يعتمد على حقلَي otp_code و otp_expires_at في جدول users
// ─────────────────────────────────────────────────────────────────────────────

function generate_and_store_otp(int $userId): string
{
    $otp       = (string) random_int(100000, 999999);
    $otpHash   = password_hash($otp, PASSWORD_BCRYPT);
    $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 دقائق

    $stmt = db()->prepare(
        'UPDATE users SET otp_code = :otp, otp_expires_at = :expires WHERE user_id = :id'
    );
    $stmt->execute([
        ':otp'     => $otpHash,
        ':expires' => $expiresAt,
        ':id'      => $userId,
    ]);

    return $otp; // يُرسَل إلى بريد المستخدم
}

function verify_otp(int $userId, string $inputOtp): bool
{
    $stmt = db()->prepare(
        'SELECT otp_code, otp_expires_at FROM users WHERE user_id = :id LIMIT 1'
    );
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch();

    if (!$row || !$row['otp_code'] || !$row['otp_expires_at']) {
        return false;
    }

    // التحقق من انتهاء الصلاحية
    if (strtotime((string) $row['otp_expires_at']) < time()) {
        clear_otp($userId);
        return false;
    }

    // التحقق من تطابق الرمز
    if (!password_verify($inputOtp, (string) $row['otp_code'])) {
        return false;
    }

    clear_otp($userId);
    return true;
}

function clear_otp(int $userId): void
{
    $stmt = db()->prepare(
        'UPDATE users SET otp_code = NULL, otp_expires_at = NULL WHERE user_id = :id'
    );
    $stmt->execute([':id' => $userId]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Remember Me Token
// ─────────────────────────────────────────────────────────────────────────────

function create_remember_token(int $userId): void
{
    $token     = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . REMEMBER_DAYS . ' days'));

    try {
        // الأعمدة تُضاف عبر 03_remember_token.sql على Amazon RDS
        $stmt = db()->prepare(
            'UPDATE users SET remember_token_hash = :hash, remember_token_expires_at = :expires WHERE user_id = :id'
        );
        $stmt->execute([
            ':hash'    => $tokenHash,
            ':expires' => $expiresAt,
            ':id'      => $userId,
        ]);

        setcookie(REMEMBER_COOKIE, $userId . ':' . $token, [
            'expires'  => strtotime($expiresAt),
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } catch (\PDOException $e) {
        // الأعمدة غير موجودة بعد — تجاهل Remember Me بصمت
        error_log('Remember token columns missing. Run 03_remember_token.sql: ' . $e->getMessage());
    }
}

function clear_remember_token(?int $userId): void
{
    if ($userId !== null) {
        try {
            $stmt = db()->prepare(
                'UPDATE users SET remember_token_hash = NULL, remember_token_expires_at = NULL WHERE user_id = :id'
            );
            $stmt->execute([':id' => $userId]);
        } catch (\PDOException $e) {
            // الأعمدة غير موجودة بعد — تجاهل بصمت
            error_log('Remember token clear skipped: ' . $e->getMessage());
        }
    }

    // حذف الـ Cookie دائماً بغض النظر عن قاعدة البيانات
    setcookie(REMEMBER_COOKIE, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function restore_remember_session(): void
{
    if (!empty($_SESSION['user']['user_id'])) {
        return;
    }

    $cookie = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if (!$cookie || !str_contains($cookie, ':')) {
        return;
    }

    [$userId, $token] = explode(':', $cookie, 2);
    $userId = (int) $userId;

    if ($userId <= 0 || $token === '') {
        return;
    }

    $stmt = db()->prepare(
        'SELECT * FROM users WHERE user_id = :id AND status = \'active\' LIMIT 1'
    );
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if (!$user || empty($user['remember_token_hash']) || empty($user['remember_token_expires_at'])) {
        return;
    }

    if (strtotime((string) $user['remember_token_expires_at']) < time()) {
        clear_remember_token($userId);
        return;
    }

    $incomingHash = hash('sha256', $token);
    if (!hash_equals((string) $user['remember_token_hash'], $incomingHash)) {
        clear_remember_token($userId);
        return;
    }

    set_login_session($user);
}

// ─────────────────────────────────────────────────────────────────────────────
// تسجيل الدخول — المرحلة الأولى فقط (بريد + كلمة مرور)
// OTP يُعالَج في مرحلة ثانية منفصلة
// ─────────────────────────────────────────────────────────────────────────────

function attempt_login(string $email, string $password, bool $remember): array
{
    $stmt = db()->prepare(
        'SELECT * FROM users WHERE email = :email LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        return [false, 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'];
    }

    // ─── توافق $2b$ (Node.js bcrypt) مع PHP password_verify ────────────────
    // PHP تدعم $2y$ — نحوّل $2b$ إليها لأنهما متطابقتان في الخوارزمية
    $hash = (string) $user['password_hash'];
    if (str_starts_with($hash, '$2b$')) {
        $hash = '$2y$' . substr($hash, 4);
    }

    if (!password_verify($password, $hash)) {
        return [false, 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'];
    }

    if ($user['status'] === 'suspended') {
        return [false, 'تم تعليق حسابك. يرجى التواصل مع الإدارة.'];
    }

    if ($user['status'] === 'deleted') {
        return [false, 'هذا الحساب محذوف.'];
    }

    // تحديث وقت آخر دخول
    $update = db()->prepare('UPDATE users SET last_login = NOW() WHERE user_id = :id');
    $update->execute([':id' => $user['user_id']]);

    if ($remember) {
        create_remember_token((int) $user['user_id']);
    } else {
        clear_remember_token((int) $user['user_id']);
    }

    set_login_session($user);

    log_activity('login', 'users', (int) $user['user_id'], 'User logged in to admin panel');

    return [true, null, $user];
}

function logout_user(bool $log = true): void
{
    $userId = (int) ($_SESSION['user']['user_id'] ?? 0);

    if ($log && $userId > 0) {
        log_activity('logout', 'users', $userId, 'User logged out from admin panel');
    }

    clear_remember_token($userId > 0 ? $userId : null);
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'يرجى تسجيل الدخول أولاً.');
        redirect('admin/login.php');
    }
}
