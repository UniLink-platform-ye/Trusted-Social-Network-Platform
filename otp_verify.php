<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) redirect('feed.php');
if (empty($_SESSION['pending_otp']['user_id'])) {
    flash('error','انتهت صلاحية الجلسة. يرجى تسجيل الدخول مجدداً.');
    redirect('login.php');
}

$pendingUserId = (int)$_SESSION['pending_otp']['user_id'];
$pendingEmail  = (string)($_SESSION['pending_otp']['email'] ?? '');
$remember      = (bool)($_SESSION['pending_otp']['remember'] ?? false);
$masked = preg_replace('/(?<=.{3}).(?=.*@)/u', '*', $pendingEmail);

$error = flash('error'); $success = flash('success');

if (is_post()) {
    if (!verify_csrf()) { $error = 'طلب غير صالح.'; }
    else {
        $action = $_POST['action'] ?? '';

        if ($action === 'verify') {
            $otp = preg_replace('/\D/', '', implode('', array_map(fn($i) => $_POST["d{$i}"] ?? '', range(1,6))));
            if (strlen($otp) !== 6) { $error = 'يرجى إدخال الرمز المكوّن من 6 أرقام.'; }
            elseif (!verify_otp($pendingUserId, $otp)) { $error = 'رمز غير صحيح أو انتهت صلاحيته.'; log_activity('login_failed','users',$pendingUserId,'Wrong OTP'); }
            else {
                $user = fetch_user_by_id($pendingUserId);
                if ($user && $user['status'] === 'active') {
                    db()->prepare('UPDATE users SET last_login=NOW() WHERE user_id=:id')->execute([':id'=>$pendingUserId]);
                    if ($remember) create_remember_token($pendingUserId);
                    set_login_session($user);
                    log_activity('login','users',$pendingUserId,'OTP login success');
                    unset($_SESSION['pending_otp']);
                    $role = $user['role'] ?? '';
                    flash('success','أهلاً بك، ' . ($user['full_name'] ?? $user['username']));
                    redirect(in_array($role,['admin','supervisor']) ? 'admin/index.php' : 'feed.php');
                }
            }
        } elseif ($action === 'resend') {
            $u = fetch_user_by_id($pendingUserId);
            if ($u) { $otp = generate_and_store_otp($pendingUserId); send_otp_email($pendingEmail,$u['full_name']??$u['username'],$otp); $success = 'تم إرسال رمز جديد.'; }
        } elseif ($action === 'cancel') { unset($_SESSION['pending_otp']); redirect('login.php'); }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>التحقق برمز OTP | <?= e(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= user_url('assets/css/app.css') ?>">
<style>
.otp-inputs{display:flex;justify-content:center;gap:.6rem;margin:1.5rem 0;direction:ltr;}
.otp-digit{width:52px;height:60px;text-align:center;font-size:1.6rem;font-weight:800;
border:2px solid var(--border);border-radius:12px;background:#f8fafc;font-family:monospace;
outline:none;color:var(--text);transition:.2s;}
.otp-digit:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.15);background:#fff;}
.otp-digit.filled{border-color:var(--success);background:#f0fdf4;}
.countdown.exp{color:var(--danger);font-weight:700;}
</style>
</head>
<body>
<div class="auth-shell" style="align-items:center;justify-content:center;">
<div class="auth-card" style="max-width:420px;">
    <div class="auth-brand">
        <div class="logo-box">📧</div>
        <h2>التحقق برمز OTP</h2>
        <p>تم إرسال الرمز إلى <strong><?= e($masked) ?></strong></p>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i><?= e($success) ?></div><?php endif; ?>

    <form method="post" id="otpForm">
        <?= csrf_input() ?><input type="hidden" name="action" value="verify">
        <input type="hidden" name="otp_combined" id="otpCombined">

        <div class="otp-inputs">
            <?php for($i=1;$i<=6;$i++): ?>
            <input type="text" class="otp-digit" id="d<?=$i?>" maxlength="1" inputmode="numeric" <?=$i===1?'autofocus':''?>>
            <?php endfor; ?>
        </div>

        <div style="text-align:center;margin-bottom:1rem;font-size:.85rem;color:var(--text-muted);">
            ينتهي الرمز خلال: <span id="countdown" class="countdown">05:00</span>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" id="verifyBtn">
            <i class="fa-solid fa-shield-check"></i> تحقق من الرمز
        </button>
    </form>

    <div style="display:flex;gap:.5rem;margin-top:.75rem;">
        <form method="post" style="flex:1">
            <?= csrf_input() ?><input type="hidden" name="action" value="resend">
            <button type="submit" class="btn btn-outline btn-block" id="resendBtn" disabled>
                <i class="fa-solid fa-paper-plane"></i> إعادة الإرسال (<span id="rt">60</span>s)
            </button>
        </form>
        <form method="post" style="flex:1">
            <?= csrf_input() ?><input type="hidden" name="action" value="cancel">
            <button type="submit" class="btn btn-block" style="background:var(--bg);border:1.5px solid var(--border);">
                <i class="fa-solid fa-arrow-right"></i> رجوع
            </button>
        </form>
    </div>
</div>
</div>
<script>
const digits=[...document.querySelectorAll('.otp-digit')];
const comb=document.getElementById('otpCombined');
digits.forEach((inp,i)=>{
    inp.addEventListener('input',function(){
        this.value=this.value.replace(/\D/,'').slice(-1);
        this.classList.toggle('filled',!!this.value);
        if(this.value&&i<5)digits[i+1].focus();
        comb.value=digits.map(d=>d.value).join('');
    });
    inp.addEventListener('keydown',function(e){
        if(e.key==='Backspace'&&!this.value&&i>0){digits[i-1].focus();digits[i-1].value='';digits[i-1].classList.remove('filled');comb.value=digits.map(d=>d.value).join('');}
    });
    inp.addEventListener('paste',function(e){
        e.preventDefault();
        const p=(e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
        p.split('').forEach((c,j)=>{if(digits[j]){digits[j].value=c;digits[j].classList.add('filled');}});
        comb.value=digits.map(d=>d.value).join('');
        digits[Math.min(p.length,5)].focus();
    });
});
let secs=300,cd=document.getElementById('countdown');
const t=setInterval(()=>{secs--;if(secs<=0){clearInterval(t);cd.textContent='انتهت الصلاحية';cd.classList.add('exp');document.getElementById('verifyBtn').disabled=true;return;}
cd.textContent=String(Math.floor(secs/60)).padStart(2,'0')+':'+String(secs%60).padStart(2,'0');},1000);
let rs=60,re=document.getElementById('resendBtn'),rt=document.getElementById('rt');
const rt2=setInterval(()=>{rs--;rt.textContent=rs;if(rs<=0){clearInterval(rt2);re.disabled=false;re.innerHTML='<i class="fa-solid fa-paper-plane"></i> إعادة إرسال الرمز';}},1000);
document.getElementById('otpForm').addEventListener('submit',function(){
    comb.value=digits.map(d=>d.value).join('');
    const b=document.getElementById('verifyBtn');b.disabled=true;b.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> جارٍ التحقق...';
});
</script>
</body>
</html>
