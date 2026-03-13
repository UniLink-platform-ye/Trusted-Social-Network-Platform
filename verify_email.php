<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) redirect('feed.php');
if (empty($_SESSION['verify_user_id'])) { flash('error','انتهت الجلسة.'); redirect('register.php'); }

$uid   = (int)$_SESSION['verify_user_id'];
$email = (string)($_SESSION['verify_user_email'] ?? '');
$mask  = preg_replace('/(?<=.{3}).(?=.*@)/u', '*', $email);
$error = flash('error'); $success = flash('success');

if (is_post() && verify_csrf()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'verify') {
        $otp = preg_replace('/\D/','', implode('', array_map(fn($i)=>$_POST["d{$i}"]??'', range(1,6))));
        if (strlen($otp)!==6) { $error='أدخل الرمز المكوّن من 6 أرقام.'; }
        elseif (!verify_otp($uid, $otp)) { $error='رمز غير صحيح أو انتهت صلاحيته.'; }
        else {
            db()->prepare('UPDATE users SET is_verified=1 WHERE user_id=:id')->execute([':id'=>$uid]);
            $user = fetch_user_by_id($uid);
            if ($user) {
                set_login_session($user);
                log_activity('register','users',$uid,'User registered and verified');
                unset($_SESSION['verify_user_id'],$_SESSION['verify_user_email']);
                flash('success','🎉 تم تفعيل حسابك! مرحباً بك في UniLink.');
                redirect('feed.php');
            }
        }
    } elseif ($action === 'resend') {
        $u = fetch_user_by_id($uid);
        if ($u) { $o=generate_and_store_otp($uid); send_otp_email($email,$u['full_name']??$u['username'],$o); $success='تم إرسال رمز جديد.'; }
    } elseif ($action === 'cancel') {
        db()->prepare('DELETE FROM users WHERE user_id=:id AND is_verified=0')->execute([':id'=>$uid]);
        unset($_SESSION['verify_user_id'],$_SESSION['verify_user_email']);
        redirect('register.php');
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>تفعيل البريد الإلكتروني | <?= e(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= user_url('assets/css/app.css') ?>">
<style>
.otp-inputs{display:flex;justify-content:center;gap:.6rem;margin:1.5rem 0;direction:ltr;}
.otp-digit{width:52px;height:60px;text-align:center;font-size:1.6rem;font-weight:800;border:2px solid var(--border);border-radius:12px;background:#f8fafc;font-family:monospace;outline:none;transition:.2s;}
.otp-digit:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.15);background:#fff;}
.otp-digit.filled{border-color:var(--success);background:#f0fdf4;}
</style>
</head>
<body>
<div class="auth-shell" style="align-items:center;justify-content:center;">
<div class="auth-card" style="max-width:420px;">
    <div class="auth-brand">
        <div class="logo-box" style="background:linear-gradient(135deg,#065f46,#16a34a);">✉️</div>
        <h2>تفعيل البريد الإلكتروني</h2>
        <p>تم إرسال رمز التفعيل إلى <strong><?= e($mask) ?></strong></p>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i><?= e($success) ?></div><?php endif; ?>

    <form method="post" id="vForm">
        <?= csrf_input() ?><input type="hidden" name="action" value="verify">
        <input type="hidden" name="otp_combined" id="oc">
        <div class="otp-inputs">
            <?php for($i=1;$i<=6;$i++): ?>
            <input type="text" class="otp-digit" id="d<?=$i?>" maxlength="1" inputmode="numeric" <?=$i===1?'autofocus':''?>>
            <?php endfor; ?>
        </div>
        <button type="submit" class="btn btn-success btn-block btn-lg">
            <i class="fa-solid fa-circle-check"></i> تفعيل الحساب
        </button>
    </form>

    <div style="display:flex;gap:.5rem;margin-top:.75rem;">
        <form method="post" style="flex:1">
            <?= csrf_input() ?><input type="hidden" name="action" value="resend">
            <button type="submit" class="btn btn-outline btn-block" id="rb" disabled>إعادة الإرسال (<span id="rt">60</span>s)</button>
        </form>
        <form method="post" style="flex:1">
            <?= csrf_input() ?><input type="hidden" name="action" value="cancel">
            <button type="submit" class="btn btn-block" style="background:var(--bg);border:1.5px solid var(--border);">الغاء التسجيل</button>
        </form>
    </div>
</div>
</div>
<script>
const ds=[...document.querySelectorAll('.otp-digit')],oc=document.getElementById('oc');
ds.forEach((inp,i)=>{
    inp.addEventListener('input',function(){this.value=this.value.replace(/\D/,'').slice(-1);this.classList.toggle('filled',!!this.value);if(this.value&&i<5)ds[i+1].focus();oc.value=ds.map(d=>d.value).join('');});
    inp.addEventListener('keydown',function(e){if(e.key==='Backspace'&&!this.value&&i>0){ds[i-1].focus();ds[i-1].value='';ds[i-1].classList.remove('filled');oc.value=ds.map(d=>d.value).join('');}});
    inp.addEventListener('paste',function(e){e.preventDefault();const p=(e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);p.split('').forEach((c,j)=>{if(ds[j]){ds[j].value=c;ds[j].classList.add('filled');}});oc.value=ds.map(d=>d.value).join('');ds[Math.min(p.length,5)].focus();});
});
document.getElementById('vForm').addEventListener('submit',function(){oc.value=ds.map(d=>d.value).join('');});
let rs=60,rb=document.getElementById('rb'),rt=document.getElementById('rt');
const ti=setInterval(()=>{rs--;rt.textContent=rs;if(rs<=0){clearInterval(ti);rb.disabled=false;rb.innerHTML='إعادة إرسال الرمز';}},1000);
</script>
</body>
</html>
