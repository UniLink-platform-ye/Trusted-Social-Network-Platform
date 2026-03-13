<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

// إذا سجّل الدخول بالفعل، اذهب للوحة التحكم
if (is_logged_in()) {
    redirect('admin/index.php');
}

// إذا لم تكن هناك جلسة OTP معلقة، ارجع للمرحلة الأولى
if (empty($_SESSION['pending_otp']['user_id'])) {
    flash('error', 'انتهت صلاحية الجلسة. يرجى تسجيل الدخول مجدداً.');
    redirect('admin/login.php');
}

$pendingUserId = (int) $_SESSION['pending_otp']['user_id'];
$pendingEmail  = (string) ($_SESSION['pending_otp']['email'] ?? '');
$remember      = (bool) ($_SESSION['pending_otp']['remember'] ?? false);

// تحجب جزء من البريد للخصوصية: abc***@domain.com
function mask_email(string $email): string
{
    [$local, $domain] = explode('@', $email . '@');
    $visible = substr($local, 0, min(3, strlen($local)));
    $masked  = $visible . str_repeat('*', max(0, strlen($local) - 3));
    return $masked . '@' . $domain;
}

$maskedEmail = mask_email($pendingEmail);

$error   = flash('error');
$success = flash('success');
$warning = flash('warning');

// ─────────────────────────────────────────────────────────────────────────────
// معالجة إدخال رمز OTP
// ─────────────────────────────────────────────────────────────────────────────
if (is_post() && isset($_POST['action'])) {

    // ── تحقق من الرمز ────────────────────────────────────────────────────────
    if ($_POST['action'] === 'verify') {
        if (!verify_csrf()) {
            $error = 'طلب غير صالح. حاول مجدداً.';

        } else {
            // دمج الأرقام الستة (قد تأتي كحقل واحد أو ستة حقول)
            $inputOtp = '';
            if (isset($_POST['otp_combined'])) {
                $inputOtp = preg_replace('/\D/', '', (string) $_POST['otp_combined']);
            } else {
                for ($i = 1; $i <= 6; $i++) {
                    $inputOtp .= preg_replace('/\D/', '', (string) ($_POST["d{$i}"] ?? ''));
                }
            }

            if (strlen($inputOtp) !== 6) {
                $error = 'يرجى إدخال رمز مكوّن من 6 أرقام.';
            } else {
                $valid = verify_otp($pendingUserId, $inputOtp);

                if ($valid) {
                    // جلب بيانات المستخدم وإنشاء الجلسة
                    $user = fetch_user_by_id($pendingUserId);
                    if ($user && $user['status'] === 'active') {
                        // تحديث وقت آخر دخول
                        $upd = db()->prepare('UPDATE users SET last_login = NOW() WHERE user_id = :id');
                        $upd->execute([':id' => $pendingUserId]);

                        if ($remember) {
                            create_remember_token($pendingUserId);
                        }

                        set_login_session($user);
                        log_activity('login', 'users', $pendingUserId, 'OTP verified — Admin panel login');

                        // مسح بيانات الـ OTP المعلق
                        unset($_SESSION['pending_otp']);

                        flash('success', 'تم التحقق بنجاح! مرحباً ' . ($user['full_name'] ?? $user['username']));
                        redirect('admin/index.php');

                    } else {
                        $error = 'الحساب غير نشط أو محذوف.';
                        unset($_SESSION['pending_otp']);
                    }
                } else {
                    $error = 'رمز التحقق غير صحيح أو انتهت صلاحيته. حاول مجدداً أو اطلب رمزاً جديداً.';
                    log_activity('login_failed', 'users', $pendingUserId, 'Wrong OTP attempt');
                }
            }
        }
    }

    // ── إعادة إرسال OTP ──────────────────────────────────────────────────────
    elseif ($_POST['action'] === 'resend') {
        if (!verify_csrf()) {
            $error = 'طلب غير صالح.';
        } else {
            $user = fetch_user_by_id($pendingUserId);
            if ($user) {
                $newOtp = generate_and_store_otp($pendingUserId);
                $sent   = send_otp_email($pendingEmail, $user['full_name'] ?? $user['username'], $newOtp);
                if ($sent) {
                    $success = 'تم إرسال رمز جديد إلى بريدك الإلكتروني.';
                } else {
                    $error = 'فشل إرسال البريد. تحقق من الـ Logs.';
                }
            } else {
                $error = 'لم يُعثر على الحساب.';
            }
        }
    }

    // ── إلغاء والعودة لتسجيل الدخول ─────────────────────────────────────────
    elseif ($_POST['action'] === 'cancel') {
        unset($_SESSION['pending_otp']);
        redirect('admin/login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e(APP_LOCALE); ?>" dir="<?= e(APP_DIR); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق برمز OTP | <?= e(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= e(admin_url('assets/css/style.css')); ?>">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Cairo', sans-serif; }

        .otp-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);
            padding: 1.5rem;
        }

        .otp-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
        }

        /* Header */
        .otp-header { text-align: center; margin-bottom: 2rem; }
        .otp-icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, #1e3a5f, #2563eb);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
        }
        .otp-header h2 { margin: 0 0 .4rem; font-size: 1.4rem; font-weight: 800; color: #0f172a; }
        .otp-header p  { margin: 0; font-size: .85rem; color: #64748b; }
        .otp-email-badge {
            display: inline-flex; align-items: center; gap: .35rem;
            background: #eff6ff; border: 1px solid #bfdbfe;
            border-radius: 20px; padding: .3rem .9rem;
            font-size: .8rem; font-weight: 600; color: #1d4ed8;
            margin-top: .6rem;
        }

        /* Alert */
        .otp-alert {
            padding: .75rem 1rem; border-radius: 10px; font-size: .84rem;
            margin-bottom: 1.2rem; display: flex; align-items: center; gap: .5rem;
        }
        .otp-alert.error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .otp-alert.success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .otp-alert.warning { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }

        /* OTP Input Boxes */
        .otp-inputs {
            display: flex;
            justify-content: center;
            gap: .6rem;
            margin: 1.5rem 0;
            direction: ltr;
        }
        .otp-digit {
            width: 52px; height: 60px;
            text-align: center; font-size: 1.6rem; font-weight: 800;
            border: 2px solid #e2e8f0; border-radius: 12px;
            background: #f8fafc; font-family: monospace;
            transition: border-color .2s, box-shadow .2s;
            outline: none; color: #1e293b;
        }
        .otp-digit:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.15);
            background: #fff;
        }
        .otp-digit.filled { border-color: #16a34a; background: #f0fdf4; }

        /* Timer */
        .otp-timer {
            text-align: center; margin-bottom: 1rem;
            font-size: .84rem; color: #64748b;
        }
        .otp-timer #countdown {
            font-weight: 700;
            color: #2563eb;
        }
        .otp-timer #countdown.expired { color: #dc2626; }

        /* Buttons */
        .btn-verify {
            width: 100%; padding: .85rem; background: #2563eb; color: #fff;
            border: none; border-radius: 10px; font-size: .95rem; font-weight: 700;
            cursor: pointer; font-family: inherit; transition: background .2s, transform .1s;
            display: flex; align-items: center; justify-content: center; gap: .5rem;
            margin-bottom: .75rem;
        }
        .btn-verify:hover { background: #1d4ed8; }
        .btn-verify:disabled { background: #93c5fd; cursor: not-allowed; }

        .btn-row { display: flex; gap: .5rem; }
        .btn-resend, .btn-cancel {
            flex: 1; padding: .7rem; border-radius: 10px;
            font-size: .85rem; font-weight: 600; cursor: pointer;
            font-family: inherit; transition: .2s; border: 1.5px solid;
        }
        .btn-resend {
            background: #fff; color: #2563eb; border-color: #bfdbfe;
        }
        .btn-resend:hover { background: #eff6ff; }
        .btn-resend:disabled { color: #94a3b8; border-color: #e2e8f0; cursor: not-allowed; }

        .btn-cancel {
            background: #fff; color: #64748b; border-color: #e2e8f0;
        }
        .btn-cancel:hover { background: #f8fafc; }

        /* Footer */
        .otp-footer { text-align: center; margin-top: 1.5rem; font-size: .76rem; color: #94a3b8; }
    </style>
</head>
<body>
<div class="otp-shell">
    <div class="otp-card">

        <!-- Header -->
        <div class="otp-header">
            <div class="otp-icon">📧</div>
            <h2>التحقق برمز OTP</h2>
            <p>تحقق من بريدك الإلكتروني</p>
            <div class="otp-email-badge">
                <i class="fa-solid fa-envelope-circle-check"></i>
                <?= e($maskedEmail); ?>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="otp-alert error"><i class="fa-solid fa-circle-xmark"></i> <?= e($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="otp-alert success"><i class="fa-solid fa-circle-check"></i> <?= e($success); ?></div>
        <?php endif; ?>
        <?php if ($warning): ?>
            <div class="otp-alert warning"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($warning); ?></div>
        <?php endif; ?>

        <!-- Verify Form -->
        <form method="post" action="" id="otpForm">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="verify">

            <!-- حقل مخفي يجمع الرمز (للـ Submit) -->
            <input type="hidden" name="otp_combined" id="otpCombined">

            <!-- صناديق الأرقام الستة -->
            <div class="otp-inputs" id="otpInputs">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <input type="text" class="otp-digit" id="d<?= $i ?>"
                           maxlength="1" inputmode="numeric" autocomplete="one-time-code"
                           pattern="[0-9]" <?= $i === 1 ? 'autofocus' : ''; ?>>
                <?php endfor; ?>
            </div>

            <!-- عداد تنازلي -->
            <div class="otp-timer">
                ينتهي الرمز خلال: <span id="countdown">05:00</span>
            </div>

            <button type="submit" class="btn-verify" id="verifyBtn">
                <i class="fa-solid fa-shield-check"></i>
                تحقق من الرمز
            </button>
        </form>

        <!-- إعادة إرسال + إلغاء -->
        <div class="btn-row">
            <form method="post" action="" style="flex:1;">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="resend">
                <button type="submit" class="btn-resend" id="resendBtn" disabled
                        title="سيُتاح بعد انتهاء العداد أو بعد 60 ثانية">
                    <i class="fa-solid fa-paper-plane"></i>
                    إعادة الإرسال (<span id="resendTimer">60</span>s)
                </button>
            </form>

            <form method="post" action="" style="flex:1;">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="btn-cancel">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    العودة للدخول
                </button>
            </form>
        </div>

        <div class="otp-footer">
            UniLink Admin Panel &copy; <?= date('Y'); ?>
        </div>
    </div>
</div>

<script>
// ── إدارة صناديق الأرقام ──────────────────────────────────────────────────
const digits  = document.querySelectorAll('.otp-digit');
const combined = document.getElementById('otpCombined');

digits.forEach((input, idx) => {
    input.addEventListener('input', function () {
        // قبول رقم واحد فقط
        this.value = this.value.replace(/\D/, '').slice(-1);
        this.classList.toggle('filled', this.value !== '');

        // الانتقال للتالي
        if (this.value && idx < digits.length - 1) {
            digits[idx + 1].focus();
        }

        // تحديث الحقل المخفي
        combined.value = [...digits].map(d => d.value).join('');
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Backspace' && !this.value && idx > 0) {
            digits[idx - 1].focus();
            digits[idx - 1].value = '';
            digits[idx - 1].classList.remove('filled');
            combined.value = [...digits].map(d => d.value).join('');
        }
        // السماح بـ Tab
    });

    // لصق الرمز دفعة واحدة
    input.addEventListener('paste', function (e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const nums  = paste.replace(/\D/g, '').slice(0, 6);
        nums.split('').forEach((ch, i) => {
            if (digits[i]) {
                digits[i].value = ch;
                digits[i].classList.add('filled');
            }
        });
        combined.value = [...digits].map(d => d.value).join('');
        // الانتقال لآخر رقم
        digits[Math.min(nums.length, digits.length - 1)].focus();
    });
});

// ── عداد تنازلي 5 دقائق ──────────────────────────────────────────────────
let totalSeconds = 5 * 60;
const countdownEl = document.getElementById('countdown');

const timer = setInterval(() => {
    totalSeconds--;
    if (totalSeconds <= 0) {
        clearInterval(timer);
        countdownEl.textContent = 'انتهت الصلاحية';
        countdownEl.classList.add('expired');
        document.getElementById('verifyBtn').disabled = true;
        return;
    }
    const m = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
    const s = String(totalSeconds % 60).padStart(2, '0');
    countdownEl.textContent = `${m}:${s}`;
}, 1000);

// ── عداد إعادة الإرسال (60 ثانية) ───────────────────────────────────────
let resendSeconds = 60;
const resendBtn = document.getElementById('resendBtn');
const resendEl  = document.getElementById('resendTimer');

const resendTimer = setInterval(() => {
    resendSeconds--;
    resendEl.textContent = resendSeconds;
    if (resendSeconds <= 0) {
        clearInterval(resendTimer);
        resendBtn.disabled = false;
        resendBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> إعادة إرسال الرمز';
    }
}, 1000);

// ── تعطيل الزر لحظة الإرسال ──────────────────────────────────────────────
document.getElementById('otpForm').addEventListener('submit', function () {
    combined.value = [...digits].map(d => d.value).join('');
    const btn = document.getElementById('verifyBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جارٍ التحقق...';
});
</script>
</body>
</html>
