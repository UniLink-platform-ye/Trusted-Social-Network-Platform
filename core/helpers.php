<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(APP_BASE_PATH, '/') . '/' . ltrim($path, '/');
}

function admin_url(string $path = ''): string
{
    return url('admin/' . ltrim($path, '/'));
}

function asset_url(string $path): string
{
    return admin_url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): void
{
    $location = preg_match('/^https?:\/\//i', $path) ? $path : url($path);
    header('Location: ' . $location);
    exit;
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function is_ajax_request(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $value = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $value;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $inputToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    return is_string($inputToken) && $sessionToken !== '' && hash_equals($sessionToken, $inputToken);
}

function verify_csrf_or_abort(): void
{
    if (!verify_csrf()) {
        if (is_ajax_request()) {
            json_response(['success' => false, 'message' => 'Invalid CSRF token.'], 419);
        }

        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function request_ip(): string
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return (string) $_SERVER['HTTP_CLIENT_IP'];
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }

    return (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
}

function format_datetime(?string $datetime): string
{
    if (!$datetime) {
        return '-';
    }

    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return '-';
    }

    return date('Y-m-d h:i A', $timestamp);
}

function log_activity(
    string $actionType,
    ?string $targetTable,
    ?int $targetId,
    string $description,
    array $metadata = []
): void {
    // الـ ENUM المقبولة في audit_logs (01_schema.sql)
    $validActions = [
        'login', 'logout', 'login_failed', 'register',
        'post_create', 'post_delete', 'post_edit',
        'file_upload', 'file_delete', 'report_submit',
        'account_suspend', 'account_delete',
        'permission_change', 'password_change',
    ];

    // إذا كان الـ action غير موجود في الـ ENUM، نستخدم أقرب قيمة أو نتخطى
    if (!in_array($actionType, $validActions, true)) {
        // نتجاهل الكتابة بصمت لتجنب خطأ DB
        return;
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO audit_logs (user_id, action, description, ip_address, user_agent)
             VALUES (:user_id, :action, :description, :ip, :ua)'
        );
        $stmt->execute([
            ':user_id'     => $_SESSION['user']['user_id'] ?? null,
            ':action'      => $actionType,
            ':description' => $description,
            ':ip'          => request_ip(),
            ':ua'          => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    } catch (Throwable $exception) {
        error_log('Activity log error: ' . $exception->getMessage());
    }
}

function role_badge_class(string $role): string
{
    $map = [
        'admin'      => 'badge-primary',
        'supervisor' => 'badge-info',
        'professor'  => 'badge-warning',
        'student'    => 'badge-muted',
    ];

    return $map[$role] ?? 'badge-muted';
}

function role_color(string $role): string
{
    $map = [
        'admin'      => '#2563eb',
        'supervisor' => '#0891b2',
        'professor'  => '#d97706',
        'student'    => '#64748b',
    ];

    return $map[$role] ?? '#64748b';
}

function status_label(string $status): string
{
    $map = [
        'active'      => 'نشط',
        'suspended'   => 'موقوف',
        'deleted'     => 'محذوف',
        'pending'     => 'معلق',
        'under_review'=> 'قيد المراجعة',
        'resolved'    => 'محلول',
        'rejected'    => 'مرفوض',
    ];

    return $map[$status] ?? $status;
}

function status_badge_class(string $status): string
{
    $map = [
        'active' => 'badge-success',
        'suspended' => 'badge-danger',
        'deleted' => 'badge-dark',
        'pending' => 'badge-warning',
        'reviewed' => 'badge-info',
        'resolved' => 'badge-success',
    ];

    return $map[$status] ?? 'badge-muted';
}

function build_pagination(int $totalRows, int $currentPage, int $perPage): array
{
    $totalPages = max(1, (int) ceil($totalRows / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));

    return [
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'prev' => max(1, $currentPage - 1),
        'next' => min($totalPages, $currentPage + 1),
    ];
}

function query_value(string $key, string $default = ''): string
{
    return trim((string) ($_GET[$key] ?? $default));
}

function selected(string $left, string $right): string
{
    return $left === $right ? 'selected' : '';
}
