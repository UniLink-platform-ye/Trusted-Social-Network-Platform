<?php
declare(strict_types=1);

/**
 * core/fcm_v1.php — Firebase Cloud Messaging HTTP v1 API
 *
 * يستخدم Service Account لتوليد OAuth2 access token
 * ثم يُرسل الإشعارات عبر FCM HTTP v1 API
 *
 * Project ID: trusted-social-platform
 * Service Account: firebase-adminsdk-fbsvc@trusted-social-platform.iam.gserviceaccount.com
 */

define('FCM_PROJECT_ID',    'trusted-social-platform');
define('FCM_SA_FILE',       __DIR__ . '/../../../mobile/trusted-social-platform-firebase-adminsdk-fbsvc-e40bbe1ca1.json');
define('FCM_V1_ENDPOINT',   'https://fcm.googleapis.com/v1/projects/' . FCM_PROJECT_ID . '/messages:send');
define('FCM_OAUTH_SCOPE',   'https://www.googleapis.com/auth/firebase.messaging');
define('FCM_TOKEN_URL',     'https://oauth2.googleapis.com/token');

// Cache للـ access token في الـ session/memory (صالح 55 دقيقة)
$_FCM_TOKEN_CACHE = ['token' => null, 'expires_at' => 0];

/**
 * توليد JWT خاص بـ Google Service Account (RS256)
 */
function _fcm_make_jwt(): string
{
    $sa = json_decode(file_get_contents(FCM_SA_FILE), true);
    if (!$sa) throw new \RuntimeException('Cannot read Service Account file');

    $now    = time();
    $header = _b64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claims = _b64url_encode(json_encode([
        'iss'   => $sa['client_email'],
        'sub'   => $sa['client_email'],
        'scope' => FCM_OAUTH_SCOPE,
        'aud'   => FCM_TOKEN_URL,
        'iat'   => $now,
        'exp'   => $now + 3600,
    ]));

    $data = "$header.$claims";
    $key  = openssl_pkey_get_private($sa['private_key']);
    if (!$key) throw new \RuntimeException('Cannot load private key');

    openssl_sign($data, $sig, $key, OPENSSL_ALGO_SHA256);
    return "$data." . _b64url_encode($sig);
}

// base64url encode helper (إعادة استخدام إذا لم تكن معرّفة)
if (!function_exists('_b64url_encode')) {
    function _b64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

/**
 * الحصول على OAuth2 Access Token (مع cache)
 */
function fcm_get_access_token(): string
{
    global $_FCM_TOKEN_CACHE;

    // إذا الكاش صالح رجّعه
    if ($_FCM_TOKEN_CACHE['token'] && time() < $_FCM_TOKEN_CACHE['expires_at']) {
        return $_FCM_TOKEN_CACHE['token'];
    }

    // توليد JWT وتبادله بـ access token
    $jwt  = _fcm_make_jwt();
    $body = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion'  => $jwt,
    ]);

    $ch = curl_init(FCM_TOKEN_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) throw new \RuntimeException("OAuth2 error $code: $res");

    $data = json_decode($res, true);
    $_FCM_TOKEN_CACHE = [
        'token'      => $data['access_token'],
        'expires_at' => time() + (int)($data['expires_in'] ?? 3600) - 300, // 5 دقائق هامش
    ];
    return $_FCM_TOKEN_CACHE['token'];
}

/**
 * إرسال إشعار FCM عبر HTTP v1 API
 *
 * @param string $fcmToken  توكن جهاز المستخدم
 * @param string $title     عنوان الإشعار
 * @param string $body      نص الإشعار
 * @param array  $data      بيانات إضافية (deep link, type, ...)
 * @return bool
 */
function fcm_send_v1(string $fcmToken, string $title, string $body, array $data = []): bool
{
    if (!$fcmToken) return false;
    if (!file_exists(FCM_SA_FILE)) {
        error_log('FCM Service Account file not found: ' . FCM_SA_FILE);
        return false;
    }
    if (!extension_loaded('openssl')) {
        error_log('FCM: openssl extension required');
        return false;
    }

    try {
        $accessToken = fcm_get_access_token();

        $payload = json_encode([
            'message' => [
                'token'        => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'android'      => [
                    'priority'     => 'high',
                    'notification' => [
                        'sound'        => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'channel_id'   => 'unilink_channel',
                    ],
                ],
                'data' => array_map('strval', array_merge(
                    $data,
                    ['click_action' => 'FLUTTER_NOTIFICATION_CLICK']
                )),
            ],
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init(FCM_V1_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) return true;

        error_log("FCM v1 HTTP $code: $res");
        return false;

    } catch (\Throwable $e) {
        error_log('FCM v1 Exception: ' . $e->getMessage());
        return false;
    }
}

/**
 * إرسال إشعار FCM لجميع أجهزة مستخدم (v1)
 */
function fcm_send_to_user_v1(int $userId, string $title, string $body, array $data = []): void
{
    try {
        $stmt = db()->prepare(
            'SELECT token FROM fcm_tokens WHERE user_id = :uid AND is_active = 1'
        );
        $stmt->execute([':uid' => $userId]);
        $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tokens as $token) {
            fcm_send_v1((string)$token, $title, $body, $data);
        }
    } catch (\Throwable $e) {
        error_log('fcm_send_to_user_v1: ' . $e->getMessage());
    }
}
