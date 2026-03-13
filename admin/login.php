<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

// إذا كان المستخدم مسجلاً دخوله بالفعل، أعِده للوحة التحكم
if (is_logged_in()) {
    redirect('admin/index.php');
}

$error   = flash('error');
$success = flash('success');
$formEmail = '';

// ─────────────────────────────────────────────────────────────────────────────
// معالجة النموذج (المرحلة الأولى: بريد + كلمة مرور)
// ─────────────────────────────────────────────────────────────────────────────
if (is_post()) {
    if (!verify_csrf()) {
        $error = 'طلب غير صالح. حاول مجدداً.';
    } else {
        $formEmail = trim((string) ($_POST['email'] ?? ''));
        $password  = (string) ($_POST['password'] ?? '');
        $remember  = !empty($_POST['remember']);

        if ($formEmail === '' || $password === '') {
            $error = 'يرجى إدخال البريد الإلكتروني وكلمة المرور.';
        } else {
            [$ok, $errMsg, $user] = attempt_login_phase1($formEmail, $password, $remember);

            if ($ok) {
                // حفظ بيانات اللوغين مؤقتاً قبل OTP
                $_SESSION['pending_otp'] = [
                    'user_id'  => (int) $user['user_id'],
                    'email'    => $user['email'],
                    'remember' => $remember,
                ];

                // توليد وإرسال OTP
                $otp = generate_and_store_otp((int) $user['user_id']);
                $sent = send_otp_email(
                    $user['email'],
                    $user['full_name'] ?? $user['username'],
                    $otp
                );

                if ($sent) {
                    flash('success', 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.');
                } else {
                    // إذا فشل الإرسال، نُكمل على أي حال مع رسالة تحذير
                    flash('warning', 'تعذّر إرسال البريد. تحقق من الـ Logs.');
                    error_log("OTP email send failed for user_id=" . $user['user_id']);
                }

                redirect('admin/otp_verify.php');
            } else {
                $error = $errMsg;
                log_activity('login_failed', 'users', null,
                    'Failed login attempt for email: ' . $formEmail);
            }
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// المرحلة الأولى فقط: التحقق من بريد + كلمة مرور — بدون إنشاء جلسة
// ─────────────────────────────────────────────────────────────────────────────
function attempt_login_phase1(string $email, string $password, bool $remember): array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        return [false, 'البريد الإلكتروني أو كلمة المرور غير صحيحة.', null];
    }

    // توافق $2b$ (Node.js) مع PHP password_verify
    $hash = (string) $user['password_hash'];
    if (str_starts_with($hash, '$2b$')) {
        $hash = '$2y$' . substr($hash, 4);
    }

    if (!password_verify($password, $hash)) {
        return [false, 'البريد الإلكتروني أو كلمة المرور غير صحيحة.', null];
    }

    if ($user['status'] === 'suspended') {
        return [false, 'تم تعليق حسابك. يرجى التواصل مع الإدارة.', null];
    }

    if ($user['status'] === 'deleted') {
        return [false, 'هذا الحساب محذوف.', null];
    }

    return [true, null, $user];
}
?>
<!DOCTYPE html>
<html lang="<?= e(APP_LOCALE); ?>" dir="<?= e(APP_DIR); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | <?= e(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= e(admin_url('assets/css/style.css')); ?>">
    <style>
        .login-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);
            padding: 1.5rem;
        }
        .login-card {
            background: #fff;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
        }
        .login-brand { text-align: center; margin-bottom: 2rem; }
        .login-brand img { width: 64px; margin-bottom: .75rem; }
        .login-brand h2 { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0; }
        .login-brand p  { color: #64748b; font-size: .85rem; margin: .25rem 0 0; }
        .form-group { margin-bottom: 1.1rem; }
        .form-label { display: block; font-size: .82rem; font-weight: 600; color: #374151; margin-bottom: .4rem; }
        .form-control {
            width: 100%; padding: .7rem 1rem; border: 1.5px solid #e2e8f0;
            border-radius: 10px; font-size: .9rem; font-family: inherit;
            transition: border-color .2s; box-sizing: border-box; background: #f8fafc;
        }
        .form-control:focus { outline: none; border-color: #2563eb; background: #fff; }
        .login-alert {
            padding: .75rem 1rem; border-radius: 10px; font-size: .85rem;
            margin-bottom: 1.2rem; display: flex; align-items: center; gap: .5rem;
        }
        .login-alert.error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .login-alert.success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .login-alert.warning { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .btn-login {
            width: 100%; padding: .8rem; background: #2563eb; color: #fff;
            border: none; border-radius: 10px; font-size: .95rem; font-weight: 700;
            cursor: pointer; font-family: inherit; transition: background .2s, transform .1s;
            display: flex; align-items: center; justify-content: center; gap: .5rem;
        }
        .btn-login:hover { background: #1d4ed8; }
        .btn-login:active { transform: scale(.98); }
        .btn-login:disabled { background: #93c5fd; cursor: not-allowed; }
        .remember-row { display: flex; align-items: center; gap: .5rem; margin-bottom: 1.2rem; font-size: .85rem; color: #374151; }
        .remember-row input { accent-color: #2563eb; width: 15px; height: 15px; }
        .login-footer { text-align: center; margin-top: 1.5rem; font-size: .78rem; color: #94a3b8; }
        .security-badge {
            display: flex; align-items: center; justify-content: center; gap: .4rem;
            background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px;
            padding: .5rem .75rem; color: #1d4ed8; font-size: .78rem; font-weight: 600;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body style="margin:0; font-family:'Cairo',sans-serif;">
<div class="login-shell">
    <div class="login-card">
        <div class="login-brand">
            <img src="<?= e(url('img/logo.png')); ?>" alt="UniLink Logo" onerror="this.style.display='none'">
            <h2><?= e(APP_NAME); ?></h2>
            <p>لوحة التحكم الإدارية</p>
        </div>

        <div class="security-badge">
            <i class="fa-solid fa-shield-halved"></i>
            تسجيل دخول آمن بالتحقق الثنائي (OTP)
        </div>

        <?php if ($error): ?>
            <div class="login-alert error"><i class="fa-solid fa-circle-xmark"></i> <?= e($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="login-alert success"><i class="fa-solid fa-circle-check"></i> <?= e($success); ?></div>
        <?php endif; ?>

        <form method="post" action="" id="loginForm">
            <?= csrf_input(); ?>

            <div class="form-group">
                <label class="form-label" for="email">
                    <i class="fa-regular fa-envelope" style="color:#2563eb;margin-left:.3rem;"></i>
                    البريد الإلكتروني
                </label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?= e($formEmail); ?>" placeholder="admin@unilink.local" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">
                    <i class="fa-solid fa-lock" style="color:#2563eb;margin-left:.3rem;"></i>
                    كلمة المرور
                </label>
                <div style="position:relative;">
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="••••••••" required style="padding-left:2.5rem;">
                    <button type="button" onclick="togglePass()" title="إظهار / إخفاء"
                            style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;">
                        <i class="fa-regular fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="remember-row">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember">تذكرني لمدة 14 يوماً</label>
            </div>

            <button type="submit" class="btn-login" id="submitBtn">
                <i class="fa-solid fa-right-to-bracket"></i>
                متابعة — سيُرسَل رمز OTP للبريد
            </button>
        </form>

        <div class="login-footer">
            UniLink Admin Panel &copy; <?= date('Y'); ?>
        </div>
    </div>
</div>

<script>
function togglePass() {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pwd.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جارٍ التحقق...';
});
</script>
</body>
</html>
