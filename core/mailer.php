<?php

declare(strict_types = 1)
;

// ─────────────────────────────────────────────────────────────────────────────
// mailer.php — إرسال البريد عبر Gmail SMTP
// يستخدم SSL على المنفذ 465 (أسهل وأكثر استقراراً من STARTTLS/587)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * إرسال رمز OTP عبر Gmail SMTP
 */
function send_otp_email(string $toEmail, string $toName, string $otp): bool
{
  $subject = '🔐 رمز التحقق الخاص بك — UniLink';
  $htmlBody = build_otp_email_html($toName, $otp);
  return smtp_send($toEmail, $toName, $subject, $htmlBody);
}

/**
 * إرسال بريد ترحيب
 */
function send_welcome_email(string $toEmail, string $toName): bool
{
  $subject = '✅ مرحباً بك في UniLink!';
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
  bool $verbose = false  ): bool
{

  $host = MAIL_HOST; // smtp.gmail.com
  $port = 465; // SSL implicit — أكثر استقراراً من 587+STARTTLS
  $username = MAIL_USERNAME; // wwwbby2040@gmail.com
  $password = MAIL_PASSWORD; // App Password (مسافات مقبولة)
  $from = MAIL_FROM;
  $fromName = MAIL_FROM_NAME;
  $timeout = 20;

  $log = function (string $msg) use ($verbose): void {
    if ($verbose) {
      echo htmlspecialchars($msg) . "\n";
      flush();
    }
    error_log('[UniLink Mailer] ' . $msg);
  };

  // ── 1. فتح اتصال SSL مباشر ───────────────────────────────────────────────
  $context = stream_context_create([
    'ssl' => [
      'verify_peer' => false, // للبيئة المحلية XAMPP
      'verify_peer_name' => false,
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
  $encodedPass = base64_encode($cleanPassword);
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
  $boundary = 'UniLink_' . md5(uniqid('', true));
  $messageId = '<' . uniqid('ul_', true) . '@unilink.local>';
  $date = date('r');
  $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
  $encFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
  $encToName = '=?UTF-8?B?' . base64_encode($toName) . '?=';

  $plainText = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

  $msg = "Date: $date\r\n";
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
  $year = date('Y');
  $nameSafe = htmlspecialchars($name, ENT_QUOTES);

  // تقسيم الأرقام
  $d = str_split($otp);
  for ($i = 0; $i < 6; $i++) {
    $d[$i] = $d[$i] ?? '?';
  }

  return <<<HTML

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="color-scheme" content="light">
  <meta name="supported-color-schemes" content="light">
  <title>رمز التحقق - UniLink</title>
  <!-- لا تستخدم <style> كثيرة لأن بعض عملاء البريد يتجاهلونها -->
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Tahoma,Arial,sans-serif;direction:rtl;" bgcolor="#f1f5f9">

  <!-- غلاف خارجي -->
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f1f5f9" style="padding:20px 8px;">
    <tr>
      <td align="center">

        <!-- الحاوية الرئيسية -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
               style="max-width:540px;width:100%;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.12);">
          
          <!-- الهيدر -->
          <tr>
            <td align="center" bgcolor="#1e3a5f"
                style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);padding:24px 20px 20px;">
              <p style="margin:0;font-size:24px;font-weight:900;color:#ffffff;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
                🔐 UniLink
              </p>
              <p style="margin:6px 0 0;font-size:13px;color:#bfdbfe;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
                منصة التواصل الأكاديمي الموثوقة
              </p>
            </td>
          </tr>

          <!-- المحتوى -->
          <tr>
            <td style="padding:24px 20px 16px;">
              <p style="margin:0 0 6px;font-size:17px;font-weight:700;color:#1e293b;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
                مرحباً، {$nameSafe} 👋
              </p>
              <p style="margin:0 0 20px;font-size:14px;color:#64748b;line-height:1.7;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
                استخدم رمز التحقق أدناه لإتمام تسجيل الدخول إلى منصة 
                <strong style="color:#1e3a5f;">UniLink</strong>.
              </p>

              <!-- صناديق الأرقام (تصميم جديد أبسط ومتوافق مع الجوال) -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:8px;">
                <tr>
                  <td align="center">
                    <!-- div واحد مع dir=ltr حتى تضمن ترتيب الأرقام، بدون جداول متداخلة -->
                    <div dir="ltr" style="direction:ltr;text-align:center;display:inline-block;">

                      <!-- المجموعة الأولى -->
                      <span style="display:inline-block;width:38px;height:50px;line-height:50px;background:#1e3a5f;border-radius:10px;margin:0 2px;
                                   font-size:24px;font-weight:900;color:#ffffff;font-family:'Courier New',monospace;">
                        {$d[0]}
                      </span>
                      <span style="display:inline-block;width:38px;height:50px;line-height:50px;background:#1e3a5f;border-radius:10px;margin:0 2px;
                                   font-size:24px;font-weight:900;color:#ffffff;font-family:'Courier New',monospace;">
                        {$d[1]}
                      </span>
                      <span style="display:inline-block;width:38px;height:50px;line-height:50px;background:#1e3a5f;border-radius:10px;margin:0 2px;
                                   font-size:24px;font-weight:900;color:#ffffff;font-family:'Courier New',monospace;">
                        {$d[2]}
                      </span>

                      <!-- فاصل بسيط -->
                      <span style="display:inline-block;width:10px;height:3px;background:#cbd5e1;border-radius:2px;margin:0 6px 18px 6px;vertical-align:middle;"></span>

                      <!-- المجموعة الثانية -->
                      <span style="display:inline-block;width:38px;height:50px;line-height:50px;background:#2563eb;border-radius:10px;margin:0 2px;
                                   font-size:24px;font-weight:900;color:#ffffff;font-family:'Courier New',monospace;">
                        {$d[3]}
                      </span>
                      <span style="display:inline-block;width:38px;height:50px;line-height:50px;background:#2563eb;border-radius:10px;margin:0 2px;
                                   font-size:24px;font-weight:900;color:#ffffff;font-family:'Courier New',monospace;">
                        {$d[4]}
                      </span>
                      <span style="display:inline-block;width:38px;height:50px;line-height:50px;background:#2563eb;border-radius:10px;margin:0 2px;
                                   font-size:24px;font-weight:900;color:#ffffff;font-family:'Courier New',monospace;">
                        {$d[5]}
                      </span>

                    </div>

                    <p style="margin:8px 0 0;font-size:11px;color:#94a3b8;letter-spacing:1px;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
                      رمز التحقق — 6 أرقام
                    </p>
                  </td>
                </tr>
              </table>

              <!-- نسخة نصية للـ OTP كـ fallback (مفيدة أيضاً للموبايل) -->
              <p dir="ltr" style="direction:ltr;text-align:center;font-family:'Courier New',monospace;font-size:18px;font-weight:700;letter-spacing:4px;margin:4px 0 16px;">
                {$otp}
              </p>

              <!-- تحذير -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 16px;">
                <tr>
                  <td bgcolor="#fef9c3" style="background:#fef9c3;border:1px solid #fde047;border-radius:12px;padding:12px 14px;">
                    <p style="margin:0;font-size:13px;color:#854d0e;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
                      ⏱️ <strong>ينتهي هذا الرمز خلال 5 دقائق.</strong> لا تشاركه مع أحد.
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-size:12px;color:#94a3b8;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
                إذا لم تطلب هذا الرمز، يمكنك تجاهل هذا البريد بأمان.
              </p>
            </td>
          </tr>

          <!-- الفوتر -->
          <tr>
            <td align="center" bgcolor="#f8fafc" style="background:#f8fafc;padding:16px 20px;border-top:1px solid #e2e8f0;">
              <p style="margin:0;font-size:11px;color:#94a3b8;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
                © {$year} UniLink Platform — جميع الحقوق محفوظة
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
HTML;
}


function build_welcome_email_html(string $name): string
{
  $year = date('Y');
  $nameSafe = htmlspecialchars($name, ENT_QUOTES);

  return <<<HTML

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="color-scheme" content="light">
  <meta name="supported-color-schemes" content="light">
  <title>مرحباً بك في UniLink</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Tahoma,Arial,sans-serif;direction:rtl;" bgcolor="#f1f5f9">

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f1f5f9" style="padding:20px 8px;">
    <tr>
      <td align="center">

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
               style="max-width:560px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 28px rgba(0,0,0,.10);">

          <!-- الهيدر -->
          <tr>
            <td align="center" bgcolor="#166534"
                style="background:linear-gradient(135deg,#166534,#16a34a);padding:28px 20px;text-align:center;">
              <p style="margin:0 0 6px;color:#ecfdf5;font-size:13px;letter-spacing:1px;font-weight:600;">
                منصة UniLink
              </p>
              <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:800;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
                ✅ مرحباً بك في UniLink!
              </h1>
            </td>
          </tr>

          <!-- المحتوى -->
          <tr>
            <td style="padding:24px 20px 18px;">
              <p style="margin:0 0 10px;font-size:16px;color:#1e293b;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
                أهلاً، <strong>{$nameSafe}</strong> 👋
              </p>
              <p style="margin:0 0 14px;font-size:14px;color:#64748b;line-height:1.7;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
                تم تسجيل دخولك بنجاح إلى منصة <strong>UniLink</strong>.
              </p>
              <p style="margin:0 0 18px;font-size:13px;color:#64748b;line-height:1.7;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
                يمكنك الآن التواصل مع زملائك وأعضاء هيئة التدريس، متابعة الإعلانات الأكاديمية، والانضمام إلى المجموعات المناسبة لتخصصك.
              </p>

              <!-- زر بسيط (كود نصي متوافق مع معظم عملاء البريد) -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:10px 0 6px;">
                <tr>
                  <td align="center">
                    <a href="#" 
                       style="display:inline-block;background:#16a34a;color:#ffffff;text-decoration:none;
                              padding:10px 22px;border-radius:999px;font-size:13px;font-weight:600;
                              font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
                      الدخول إلى المنصة
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:6px 0 0;font-size:11px;color:#9ca3af;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
                إذا لم تقم أنت بتسجيل الدخول، ننصحك بتغيير كلمة المرور فوراً.
              </p>
            </td>
          </tr>

          <!-- الفوتر -->
          <tr>
            <td align="center" bgcolor="#f8fafc"
                style="background:#f8fafc;padding:14px 20px;border-top:1px solid #e2e8f0;">
              <p style="margin:0;font-size:11px;color:#94a3b8;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
                © {$year} UniLink Platform — جميع الحقوق محفوظة
              </p>
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>
HTML;
}
