<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    $role = current_user()['role'] ?? '';
    redirect(in_array($role, ['admin','supervisor']) ? 'admin/index.php' : 'feed.php');
}

$error = flash('error'); $success = flash('success');
$formEmail = '';

/* ── المرحلة الأولى: التحقق من البريد وكلمة المرور ── */
if (is_post()) {
    if (!verify_csrf()) {
        $error = 'طلب غير صالح.';
    } else {
        $formEmail = trim((string)($_POST['email'] ?? ''));
        $password  = (string)($_POST['password'] ?? '');
        $remember  = !empty($_POST['remember']);

        if (!$formEmail || !$password) {
            $error = 'يرجى ملء جميع الحقول.';
        } else {
            $stmt = db()->prepare('SELECT * FROM users WHERE email = :e LIMIT 1');
            $stmt->execute([':e' => $formEmail]);
            $user = $stmt->fetch();

            $ok = false; $errMsg = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
            if ($user) {
                $hash = (string)$user['password_hash'];
                if (str_starts_with($hash, '$2b$')) $hash = '$2y$' . substr($hash, 4);
                if (password_verify($password, $hash)) {
                    if ($user['status'] === 'suspended') $errMsg = 'تم تعليق حسابك. يرجى التواصل مع الإدارة.';
                    elseif ($user['status'] === 'deleted') $errMsg = 'هذا الحساب محذوف.';
                    elseif (!$user['is_verified']) $errMsg = 'حسابك غير مفعّل. يرجى مراجعة بريدك لتفعيل الحساب.';
                    else $ok = true;
                }
            }

            if ($ok) {
                $_SESSION['pending_otp'] = ['user_id' => (int)$user['user_id'], 'email' => $user['email'], 'remember' => $remember];
                $otp = generate_and_store_otp((int)$user['user_id']);
                send_otp_email($user['email'], $user['full_name'] ?? $user['username'], $otp);
                flash('success', 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.');
                redirect('otp_verify.php');
            } else {
                $error = $errMsg;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>تسجيل الدخول | <?= e(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= user_url('assets/css/app.css') ?>">
</head>
<body>
<div class="auth-shell">

<!-- Right: Promo Panel -->
<div class="auth-right">
    <div class="auth-promo">
        <h2>🎓 منصة UniLink<br>للتواصل الأكاديمي</h2>
        <p>بيئة جامعية رقمية آمنة تجمع الطلاب والأساتذة والإدارة في منصة واحدة منظّمة وموثوقة.</p>
        <div class="auth-features">
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <div class="auth-feature-text">
                    <div class="title">تحقق ثنائي آمن (OTP)</div>
                    <div class="desc">حماية متقدمة لحسابك عبر البريد الجامعي</div>
                </div>
            </div>
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fa-solid fa-users"></i></div>
                <div class="auth-feature-text">
                    <div class="title">مجموعات أكاديمية منظّمة</div>
                    <div class="desc">مقررات، أقسام، أنشطة طلابية في مكان واحد</div>
                </div>
            </div>
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fa-solid fa-folder-open"></i></div>
                <div class="auth-feature-text">
                    <div class="title">مستودع الملفات الأكاديمية</div>
                    <div class="desc">محاضرات وواجبات منظّمة وسهلة الوصول</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Left: Login Card -->
<div class="auth-left">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="logo-box">🔗</div>
            <h2><?= e(APP_NAME) ?></h2>
            <p>سجّل دخولك للمتابعة</p>
        </div>

        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:.6rem 1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem;font-size:.8rem;color:#1d4ed8;font-weight:600;">
            <i class="fa-solid fa-shield-halved"></i> تسجيل دخول آمن بالتحقق الثنائي OTP
        </div>

        <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i><?= e($success) ?></div><?php endif; ?>

        <form method="post" id="loginForm">
            <?= csrf_input() ?>
            <div class="form-group">
                <label class="form-label"><i class="fa-regular fa-envelope" style="color:var(--primary);"></i> البريد الإلكتروني</label>
                <input type="email" name="email" class="form-control" value="<?= e($formEmail) ?>" placeholder="example@university.edu" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-lock" style="color:var(--primary);"></i> كلمة المرور</label>
                <div style="position:relative;">
                    <input type="password" name="password" id="passInput" class="form-control" placeholder="••••••••" required style="padding-left:2.5rem;">
                    <button type="button" onclick="togglePass()" style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;">
                        <i class="fa-regular fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1.2rem;font-size:.84rem;">
                <input type="checkbox" id="remember" name="remember" value="1" style="accent-color:var(--primary);">
                <label for="remember" style="color:#374151;cursor:pointer;">تذكرني لمدة 14 يوماً</label>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg" id="loginBtn">
                <i class="fa-solid fa-right-to-bracket"></i> متابعة — إرسال رمز OTP
            </button>
        </form>

        <div class="auth-footer">
            ليس لديك حساب؟ <a href="<?= user_url('register.php') ?>">سجّل الآن</a>
        </div>
    </div>
</div>
</div>
<script>
function togglePass(){
    const i=document.getElementById('passInput'),e=document.getElementById('eyeIcon');
    i.type=i.type==='password'?'text':'password';
    e.classList.toggle('fa-eye');e.classList.toggle('fa-eye-slash');
}
document.getElementById('loginForm').addEventListener('submit',function(){
    const b=document.getElementById('loginBtn');
    b.disabled=true;b.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> جارٍ التحقق...';
});
</script>
</body>
</html>
