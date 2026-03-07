<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

// إذا كان المستخدم مسجلاً دخوله، أعِده للوحة التحكم
if (is_logged_in()) {
    redirect('admin/index.php');
}

$error    = flash('error');
$success  = flash('success');
$formEmail = '';

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
            [$ok, $errMsg] = attempt_login($formEmail, $password, $remember);

            if ($ok) {
                redirect('admin/index.php');
            } else {
                $error = $errMsg;
                log_activity('login_failed', 'users', null,
                    'Failed login attempt for email: ' . $formEmail);
            }
        }
    }
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
        .login-brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-brand img { width: 64px; margin-bottom: .75rem; }
        .login-brand h2 { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0; }
        .login-brand p  { color: #64748b; font-size: .85rem; margin: 0; }
        .form-group { margin-bottom: 1.1rem; }
        .form-label { display: block; font-size: .82rem; font-weight: 600; color: #374151; margin-bottom: .4rem; }
        .form-control {
            width: 100%; padding: .7rem 1rem; border: 1.5px solid #e2e8f0;
            border-radius: 10px; font-size: .9rem; font-family: inherit;
            transition: border-color .2s; box-sizing: border-box;
            background: #f8fafc;
        }
        .form-control:focus { outline: none; border-color: #2563eb; background: #fff; }
        .login-alert {
            padding: .75rem 1rem; border-radius: 10px; font-size: .85rem;
            margin-bottom: 1.2rem; display: flex; align-items: center; gap: .5rem;
        }
        .login-alert.error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .login-alert.success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .btn-login {
            width: 100%; padding: .8rem; background: #2563eb; color: #fff;
            border: none; border-radius: 10px; font-size: .95rem; font-weight: 700;
            cursor: pointer; font-family: inherit; transition: background .2s, transform .1s;
        }
        .btn-login:hover { background: #1d4ed8; }
        .btn-login:active { transform: scale(.98); }
        .remember-row { display: flex; align-items: center; gap: .5rem; margin-bottom: 1.2rem; font-size: .85rem; color: #374151; }
        .remember-row input { accent-color: #2563eb; width: 15px; height: 15px; }
        .login-footer { text-align: center; margin-top: 1.5rem; font-size: .78rem; color: #94a3b8; }
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

        <?php if ($error): ?>
            <div class="login-alert error"><i class="fa-solid fa-circle-xmark"></i> <?= e($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="login-alert success"><i class="fa-solid fa-circle-check"></i> <?= e($success); ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <?= csrf_input(); ?>

            <div class="form-group">
                <label class="form-label" for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?= e($formEmail); ?>" placeholder="admin@unilink.local" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">كلمة المرور</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="••••••••" required>
            </div>

            <div class="remember-row">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember">تذكرني</label>
            </div>

            <button type="submit" class="btn-login">
                <i class="fa-solid fa-right-to-bracket"></i>
                تسجيل الدخول
            </button>
        </form>

        <div class="login-footer">
            UniLink Admin Panel &copy; <?= date('Y'); ?>
        </div>
    </div>
</div>
</body>
</html>
