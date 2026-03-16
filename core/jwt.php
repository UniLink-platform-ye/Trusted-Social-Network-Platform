<?php
declare(strict_types=1);

/**
 * core/jwt.php — JSON Web Token (بدون مكتبات خارجية)
 */

function _b64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function _b64url_decode(string $data): string
{
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
}

/**
 * توليد JWT token
 */
function jwt_encode(array $payload): string
{
    $header  = _b64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_EXPIRY;
    $body    = _b64url_encode(json_encode($payload));
    $sig     = _b64url_encode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    return "$header.$body.$sig";
}

/**
 * التحقق من JWT وإرجاع الـ payload، أو null إذا فشل
 */
function jwt_decode(string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    [$header, $body, $sig] = $parts;
    $expected = _b64url_encode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    if (!hash_equals($expected, $sig)) return null;

    $payload = json_decode(_b64url_decode($body), true);
    if (!is_array($payload)) return null;
    if (($payload['exp'] ?? 0) < time()) return null; // منتهي الصلاحية

    return $payload;
}

/**
 * استخراج JWT من Authorization header
 */
function jwt_from_request(): ?string
{
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

    // حل مشكلة Apache والسيرفرات التي تسحب ترويسة Authorization
    if (empty($auth) && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
        return $m[1];
    }
    return null;
}

/**
 * الحصول على المستخدم الحالي من JWT (في سياق API)
 */
function api_user(): ?array
{
    $token = jwt_from_request();
    if (!$token) return null;
    $payload = jwt_decode($token);
    if (!$payload || empty($payload['user_id'])) return null;
    return $payload;
}

/**
 * يُطلب تسجيل الدخول عبر API أو يُرجع خطأ 401
 */
function api_require_auth(): array
{
    $user = api_user();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login and provide a valid Bearer token.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    return $user;
}
