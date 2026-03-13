<?php

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// mailer.php — إرسال البريد عبر Gmail SMTP
// يستخدم SSL على المنفذ 465 (أسهل وأكثر استقراراً من STARTTLS/587)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * إرسال رمز OTP عبر Gmail SMTP
 */
function send_otp_email(string $toEmail, string $toName, string $otp): bool
{
    $subject  = '🔐 رمز التحقق الخاص بك — UniLink';
    $htmlBody = build_otp_email_html($toName, $otp);
    return smtp_send($toEmail, $toName, $subject, $htmlBody);
}

/**
 * إرسال بريد ترحيب
 */
function send_welcome_email(string $toEmail, string $toName): bool
{
    $subject  = '✅ مرحباً بك في UniLink!';
    $htmlBody = build_welcome_email_html($toName);
    return smtp_send($toEmail, $toName, $subject, $htmlBody);
}

// ─────────────────────────────────────────────────────────────────────────────
// SMTP Core — SSL port 465 (implicit TLS, no STARTTLS handshake needed)
// ─────────────────────────────────────────────────────────────────────────────

function smtp_send(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    bool   $verbose = false
): bool {

    $host      = MAIL_HOST;        // smtp.gmail.com
    $port      = 465;              // SSL implicit — أكثر استقراراً من 587+STARTTLS
    $username  = MAIL_USERNAME;    // wwwbby2040@gmail.com
    $password  = MAIL_PASSWORD;    // App Password (مسافات مقبولة)
    $from      = MAIL_FROM;
    $fromName  = MAIL_FROM_NAME;
    $timeout   = 20;

    $log = function(string $msg) use ($verbose): void {
        if ($verbose) {
            echo htmlspecialchars($msg) . "\n";
            flush();
        }
        error_log('[UniLink Mailer] ' . $msg);
    };

    // ── 1. فتح اتصال SSL مباشر ───────────────────────────────────────────────
    $context = stream_context_create([
        'ssl' => [
            'verify_peer'       => false,  // للبيئة المحلية XAMPP
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ]
    ]);

    $log("Connecting to ssl://$host:$port ...");
    $socket = @stream_socket_client(
        "ssl://$host:$port",
        $errno,
        $errstr,
        $timeout,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!$socket) {
        $log("FAILED to connect: [$errno] $errstr");
        return false;
    }
    stream_set_timeout($socket, $timeout);
    $log("Connected ✓");

    // ── 2. قراءة ترحيب الخادم (220) ─────────────────────────────────────────
    $greeting = smtp_read($socket);
    $log("S: $greeting");
    if (!str_starts_with($greeting, '220')) {
        $log("Bad greeting — expected 220");
        fclose($socket);
        return false;
    }

    // ── 3. EHLO ──────────────────────────────────────────────────────────────
    smtp_write($socket, "EHLO localhost");
    $log("C: EHLO localhost");
    $ehlo = smtp_read_multi($socket);
    $log("S: $ehlo");
    if (!str_starts_with($ehlo, '250')) {
        $log("EHLO failed");
        fclose($socket);
        return false;
    }

    // ── 4. AUTH LOGIN ─────────────────────────────────────────────────────────
    smtp_write($socket, "AUTH LOGIN");
    $log("C: AUTH LOGIN");
    $r1 = smtp_read($socket);
    $log("S: $r1");
    if (!str_starts_with($r1, '334')) {
        $log("AUTH LOGIN not accepted: $r1");
        fclose($socket);
        return false;
    }

    // إرسال اسم المستخدم
    $encodedUser = base64_encode($username);
    smtp_write($socket, $encodedUser);
    $log("C: [username base64]");
    $r2 = smtp_read($socket);
    $log("S: $r2");
    if (!str_starts_with($r2, '334')) {
        $log("Username rejected: $r2");
        fclose($socket);
        return false;
    }

    // إرسال كلمة المرور — App Password بدون مسافات
    $cleanPassword = str_replace(' ', '', $password);
    $encodedPass   = base64_encode($cleanPassword);
    smtp_write($socket, $encodedPass);
    $log("C: [password base64]");
    $r3 = smtp_read($socket);
    $log("S: $r3");
    if (!str_starts_with($r3, '235')) {
        $log("Authentication FAILED: $r3 — Check App Password");
        fclose($socket);
        return false;
    }
    $log("Authenticated ✓");

    // ── 5. MAIL FROM ──────────────────────────────────────────────────────────
    smtp_write($socket, "MAIL FROM:<$from>");
    $log("C: MAIL FROM:<$from>");
    $r4 = smtp_read($socket);
    $log("S: $r4");
    if (!str_starts_with($r4, '250')) {
        $log("MAIL FROM rejected: $r4");
        fclose($socket);
        return false;
    }

    // ── 6. RCPT TO ────────────────────────────────────────────────────────────
    smtp_write($socket, "RCPT TO:<$toEmail>");
    $log("C: RCPT TO:<$toEmail>");
    $r5 = smtp_read($socket);
    $log("S: $r5");
    if (!str_starts_with($r5, '250')) {
        $log("RCPT TO rejected: $r5");
        fclose($socket);
        return false;
    }

    // ── 7. DATA ───────────────────────────────────────────────────────────────
    smtp_write($socket, "DATA");
    $log("C: DATA");
    $r6 = smtp_read($socket);
    $log("S: $r6");
    if (!str_starts_with($r6, '354')) {
        $log("DATA rejected: $r6");
        fclose($socket);
        return false;
    }

    // بناء الرسالة
    $boundary        = 'UniLink_' . md5(uniqid('', true));
    $messageId       = '<' . uniqid('ul_', true) . '@unilink.local>';
    $date            = date('r');
    $encSubject      = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encFromName     = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
    $encToName       = '=?UTF-8?B?' . base64_encode($toName) . '?=';

    $plainText = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

    $msg  = "Date: $date\r\n";
    $msg .= "Message-ID: $messageId\r\n";
    $msg .= "From: $encFromName <$from>\r\n";
    $msg .= "To: $encToName <$toEmail>\r\n";
    $msg .= "Subject: $encSubject\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $msg .= "X-Mailer: UniLink/1.0\r\n";
    $msg .= "\r\n";
    $msg .= "--$boundary\r\n";
    $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $msg .= chunk_split(base64_encode($plainText)) . "\r\n";
    $msg .= "--$boundary\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
    $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $msg .= chunk_split(base64_encode($htmlBody)) . "\r\n";
    $msg .= "--$boundary--\r\n";
    $msg .= "\r\n.\r\n"; // نهاية DATA

    fwrite($socket, $msg);
    $r7 = smtp_read($socket);
    $log("S: $r7");
    if (!str_starts_with($r7, '250')) {
        $log("Message rejected: $r7");
        fclose($socket);
        return false;
    }
    $log("Message accepted ✓");

    // ── 8. QUIT ──────────────────────────────────────────────────────────────
    smtp_write($socket, "QUIT");
    fclose($socket);
    $log("Done — email sent to $toEmail");

    return true;
}

// ─────────────────────────────────────────────────────────────────────────────
// دوال SMTP المساعدة
// ─────────────────────────────────────────────────────────────────────────────

function smtp_write($socket, string $cmd): void
{
    fwrite($socket, $cmd . "\r\n");
}

/** قراءة سطر واحد */
function smtp_read($socket): string
{
    $response = '';
    while ($line = fgets($socket, 512)) {
        $response .= $line;
        if (strlen($line) >= 4 && $line[3] !== '-') {
            break;
        }
    }
    return rtrim($response);
}

/** قراءة استجابة متعددة الأسطر (250-… 250 ) */
function smtp_read_multi($socket): string
{
    $full = '';
    while ($line = fgets($socket, 512)) {
        $full .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }
    return rtrim($full);
}

// ─────────────────────────────────────────────────────────────────────────────
// قوالب HTML للبريد
// ─────────────────────────────────────────────────────────────────────────────

function build_otp_email_html(string $name, string $otp): string
{
    $year         = date('Y');
    $otpFormatted = substr($otp, 0, 3) . ' ' . substr($otp, 3, 3);
    $nameSafe     = htmlspecialchars($name, ENT_QUOTES);

    return <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Tahoma,Arial,sans-serif;direction:rtl;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 20px;">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1);">
      <tr>
        <td style="background:linear-gradient(135deg,#1e3a5f,#2563eb);padding:36px 40px;text-align:center;">
          <h1 style="margin:0;color:#fff;font-size:26px;font-weight:800;">🔒 UniLink</h1>
          <p style="margin:6px 0 0;color:#bfdbfe;font-size:13px;">منصة التواصل الأكاديمي الموثوقة</p>
        </td>
      </tr>
      <tr>
        <td style="padding:40px 40px 32px;">
          <p style="margin:0 0 8px;font-size:16px;color:#1e293b;font-weight:600;">مرحباً، {$nameSafe} 👋</p>
          <p style="margin:0 0 28px;font-size:14px;color:#64748b;line-height:1.7;">
            استخدم رمز التحقق أدناه لإتمام تسجيل الدخول إلى منصة <strong>UniLink</strong>.
          </p>
          <div style="text-align:center;margin:0 0 28px;">
            <div style="display:inline-block;background:#eff6ff;border:2px solid #3b82f6;border-radius:14px;padding:20px 48px;">
              <p style="margin:0 0 4px;font-size:11px;color:#3b82f6;font-weight:700;letter-spacing:1px;">رمز التحقق</p>
              <p style="margin:0;font-size:40px;font-weight:900;color:#1e3a5f;letter-spacing:10px;font-family:monospace;">{$otpFormatted}</p>
            </div>
          </div>
          <div style="background:#fef9c3;border:1px solid #fde047;border-radius:10px;padding:14px 18px;margin:0 0 16px;">
            <p style="margin:0;font-size:13px;color:#854d0e;">⏱️ <strong>ينتهي هذا الرمز خلال 5 دقائق.</strong> لا تشاركه مع أحد.</p>
          </div>
        </td>
      </tr>
      <tr>
        <td style="background:#f8fafc;padding:16px 40px;text-align:center;border-top:1px solid #e2e8f0;">
          <p style="margin:0;font-size:11px;color:#94a3b8;">© {$year} UniLink — منصة التواصل الأكاديمي</p>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
}

function build_welcome_email_html(string $name): string
{
    $year     = date('Y');
    $nameSafe = htmlspecialchars($name, ENT_QUOTES);
    return <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Tahoma,Arial,sans-serif;direction:rtl;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 20px;">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;">
      <tr>
        <td style="background:linear-gradient(135deg,#166534,#16a34a);padding:36px 40px;text-align:center;">
          <h1 style="margin:0;color:#fff;font-size:24px;font-weight:800;">✅ مرحباً بك في UniLink!</h1>
        </td>
      </tr>
      <tr>
        <td style="padding:40px;">
          <p style="margin:0 0 12px;font-size:16px;color:#1e293b;">أهلاً، <strong>{$nameSafe}</strong>!</p>
          <p style="margin:0;font-size:14px;color:#64748b;line-height:1.7;">تم تسجيل دخولك بنجاح إلى منصة UniLink.</p>
        </td>
      </tr>
      <tr>
        <td style="background:#f8fafc;padding:16px 40px;text-align:center;border-top:1px solid #e2e8f0;">
          <p style="margin:0;font-size:11px;color:#94a3b8;">© {$year} UniLink Platform</p>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
}
