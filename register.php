<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) redirect('feed.php');
$error = flash('error'); $success = flash('success');
$f = ['full_name'=>'','email'=>'','academic_id'=>'','department'=>''];

if (is_post()) {
    if (!verify_csrf()) { $error='طلب غير صالح.'; }
    else {
        $f['full_name']   = trim($_POST['full_name']   ?? '');
        $f['email']       = trim($_POST['email']       ?? '');
        $f['academic_id'] = trim($_POST['academic_id'] ?? '');
        $f['department']  = trim($_POST['department']  ?? '');
        $pass   = $_POST['password']         ?? '';
        $pass2  = $_POST['password_confirm'] ?? '';
        $role   = in_array($_POST['role'] ?? '', ['student','professor']) ? $_POST['role'] : 'student';

        if (!$f['full_name'] || !$f['email'] || !$pass) $error = 'يرجى ملء جميع الحقول الإلزامية.';
        elseif (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $error = 'البريد الإلكتروني غير صالح.';
        elseif ($pass !== $pass2) $error = 'كلمتا المرور غير متطابقتين.';
        elseif (strlen($pass) < 8) $error = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.';
        else {
            // التحقق من عدم تكرار البريد
            $chk = db()->prepare('SELECT user_id FROM users WHERE email=:e LIMIT 1');
            $chk->execute([':e' => $f['email']]);
            if ($chk->fetchColumn()) {
                $error = 'هذا البريد الإلكتروني مسجّل مسبقاً.';
            } else {
                $username = explode('@', $f['email'])[0] . rand(10,99);
                $hash     = password_hash($pass, PASSWORD_BCRYPT);
                $ins = db()->prepare('INSERT INTO users (username,email,password_hash,role,full_name,academic_id,department,is_verified,status) VALUES (:u,:e,:h,:r,:fn,:ai,:dep,0,"active")');
                $ins->execute([':u'=>$username,':e'=>$f['email'],':h'=>$hash,':r'=>$role,':fn'=>$f['full_name'],':ai'=>$f['academic_id'],':dep'=>$f['department']]);
                $userId = (int)db()->lastInsertId();

                // إرسال OTP للتفعيل
                $otp = generate_and_store_otp($userId);
                send_otp_email($f['email'], $f['full_name'], $otp);

                $_SESSION['verify_user_id']  = $userId;
                $_SESSION['verify_user_email'] = $f['email'];
                flash('success','تم إنشاء حسابك! أدخل رمز التحقق المُرسَل لبريدك لتفعيل الحساب.');
                redirect('verify_email.php');
            }
        }
    }
}
$departments = ['Computer Science','Information Systems','Information Technology','Business Administration','Engineering','Medicine','Law','Arts'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>إنشاء حساب جديد | <?= e(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= user_url('assets/css/app.css') ?>">
</head>
<body>
<div class="auth-shell">

<div class="auth-right">
    <div class="auth-promo">
        <h2>انضم إلى مجتمع UniLink الأكاديمي</h2>
        <p>سجّل حسابك وابدأ التواصل الأكاديمي المنظّم مع زملائك وأساتذتك.</p>
        <div class="auth-features">
            <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-id-card"></i></div><div class="auth-feature-text"><div class="title">هوية جامعية موثّقة</div><div class="desc">حسابك مرتبط برقمك الأكاديمي الرسمي</div></div></div>
            <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-envelope-circle-check"></i></div><div class="auth-feature-text"><div class="title">تحقق عبر البريد</div><div class="desc">رمز OTP لتأكيد هويتك وتفعيل حسابك</div></div></div>
            <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-lock"></i></div><div class="auth-feature-text"><div class="title">خصوصية تامة</div><div class="desc">بياناتك محمية بالتشفير داخل الجامعة فقط</div></div></div>
        </div>
    </div>
</div>

<div class="auth-left">
    <div class="auth-card" style="max-width:480px;">
        <div class="auth-brand">
            <div class="logo-box">✍️</div>
            <h2>إنشاء حساب جديد</h2>
            <p>أدخل بياناتك الأكاديمية للتسجيل</p>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i><?= e($error) ?></div><?php endif; ?>

        <form method="post" id="regForm">
            <?= csrf_input() ?>
            <div class="form-group">
                <label class="form-label">الاسم الكامل <span style="color:var(--danger)">*</span></label>
                <input type="text" name="full_name" class="form-control" value="<?= e($f['full_name']) ?>" placeholder="محمد أحمد العلي" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">البريد الإلكتروني <span style="color:var(--danger)">*</span></label>
                <input type="email" name="email" class="form-control" value="<?= e($f['email']) ?>" placeholder="student@university.edu" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                <div class="form-group">
                    <label class="form-label">الرقم الأكاديمي</label>
                    <input type="text" name="academic_id" class="form-control" value="<?= e($f['academic_id']) ?>" placeholder="STU-2024">
                </div>
                <div class="form-group">
                    <label class="form-label">الدور</label>
                    <select name="role" class="form-select">
                        <option value="student">طالب</option>
                        <option value="professor">أستاذ</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">القسم / الكلية</label>
                <select name="department" class="form-select">
                    <option value="">-- اختر --</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= e($d) ?>" <?= $f['department']===$d?'selected':''?>><?= e($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                <div class="form-group">
                    <label class="form-label">كلمة المرور <span style="color:var(--danger)">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="8 أحرف على الأقل" required minlength="8">
                </div>
                <div class="form-group">
                    <label class="form-label">تأكيد كلمة المرور <span style="color:var(--danger)">*</span></label>
                    <input type="password" name="password_confirm" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg" id="regBtn">
                <i class="fa-solid fa-user-plus"></i> إنشاء الحساب وإرسال رمز التفعيل
            </button>
        </form>

        <div class="auth-footer">
            لديك حساب بالفعل؟ <a href="<?= user_url('login.php') ?>">سجّل الدخول</a>
        </div>
    </div>
</div>
</div>
<script>
document.getElementById('regForm').addEventListener('submit',function(){
    const b=document.getElementById('regBtn');b.disabled=true;b.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> جارٍ الإنشاء...';
});
</script>
</body>
</html>
