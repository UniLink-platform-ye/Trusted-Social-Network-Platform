<?php

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// rbac.php  —  مبني على هيكلية 01_schema.sql
//
// نظام الصلاحيات يعتمد على ENUM مباشر في جدول users:
//   role ENUM('student','professor','admin','supervisor')
//
// الصلاحيات محددة بشكل ثابت (static) حسب كل دور،
// مع إمكانية التوسع لاحقاً بإضافة جداول roles/permissions (المرحلة 3+).
// ─────────────────────────────────────────────────────────────────────────────

/**
 * خريطة الصلاحيات الثابتة لكل دور
 * مبنية على متطلبات القسم 3.2.1 من وثيقة التحليل
 */
const ROLE_PERMISSIONS = [
    'admin' => [
        'dashboard.view',
        'users.view', 'users.create', 'users.edit', 'users.delete', 'users.suspend',
        'roles.view', 'roles.manage',
        'reports.view', 'reports.review', 'reports.resolve',
        'content.view', 'content.delete',
        'logs.view',
        'export.reports',
        'settings.manage',
        'groups.manage',
        'files.manage',
        'announcements.create',
    ],
    'supervisor' => [
        'dashboard.view',
        'users.view', 'users.edit', 'users.suspend',
        'roles.view',
        'reports.view', 'reports.review', 'reports.resolve',
        'content.view', 'content.delete',
        'logs.view',
        'export.reports',
        'groups.manage',
    ],
    'professor' => [
        'dashboard.view',
        'reports.view',
        'content.view',
        'files.upload', 'files.download',
        'groups.view', 'groups.post',
        'announcements.create',
    ],
    'student' => [
        'dashboard.view',
        'files.download',
        'groups.view', 'groups.post',
    ],
];

function get_user_permissions(?int $userId = null): array
{
    $user = current_user();
    if (!$user) {
        return [];
    }

    $role = $user['role'] ?? 'student';

    // Cache في الجلسة لتجنب إعادة الحساب
    $cacheKey = 'role_' . $role;
    if (isset($_SESSION['permission_cache'][$cacheKey])) {
        return $_SESSION['permission_cache'][$cacheKey];
    }

    $permissions = [];
    foreach ((ROLE_PERMISSIONS[$role] ?? []) as $key) {
        $permissions[$key] = true;
    }

    $_SESSION['permission_cache'][$cacheKey] = $permissions;

    return $permissions;
}

function clear_permission_cache(?int $userId = null): void
{
    unset($_SESSION['permission_cache']);
}

function is_admin_user(?array $user = null): bool
{
    $user = $user ?? current_user();
    return $user && $user['role'] === 'admin';
}

function is_supervisor_or_above(?array $user = null): bool
{
    $user = $user ?? current_user();
    return $user && in_array($user['role'], ['admin', 'supervisor'], true);
}

function user_can(string $permissionKey, ?int $userId = null): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }

    // admin يملك كل الصلاحيات
    if ($user['role'] === 'admin') {
        return true;
    }

    $permissions = get_user_permissions();
    return (bool) ($permissions[$permissionKey] ?? false);
}

function require_permission(string $permissionKey): void
{
    require_login();

    if (!user_can($permissionKey)) {
        if (is_ajax_request()) {
            json_response(['success' => false, 'message' => 'غير مصرح بهذا الإجراء.'], 403);
        }

        http_response_code(403);
        echo '403 Forbidden — ليس لديك صلاحية للوصول إلى هذه الصفحة.';
        exit;
    }
}

function require_admin(): void
{
    require_login();

    if (!is_admin_user()) {
        if (is_ajax_request()) {
            json_response(['success' => false, 'message' => 'مطلوب صلاحية المدير.'], 403);
        }

        http_response_code(403);
        echo '403 Forbidden — هذه الصفحة خاصة بالمديرين فقط.';
        exit;
    }
}

/**
 * يُرجع الأدوار المتاحة للعرض في الواجهة
 */
function get_all_roles(): array
{
    return [
        'admin'      => 'مدير النظام',
        'supervisor' => 'مشرف',
        'professor'  => 'أستاذ',
        'student'    => 'طالب',
    ];
}

/**
 * يُرجع قائمة جميع الصلاحيات التعريفية للعرض في لوحة التحكم
 */
function get_all_permissions_map(): array
{
    return [
        'dashboard.view'       => ['label_ar' => 'عرض لوحة التحكم',          'category' => 'dashboard'],
        'users.view'           => ['label_ar' => 'عرض المستخدمين',            'category' => 'users'],
        'users.create'         => ['label_ar' => 'إضافة مستخدمين',            'category' => 'users'],
        'users.edit'           => ['label_ar' => 'تعديل المستخدمين',          'category' => 'users'],
        'users.delete'         => ['label_ar' => 'حذف المستخدمين',            'category' => 'users'],
        'users.suspend'        => ['label_ar' => 'تعليق المستخدمين',          'category' => 'users'],
        'roles.view'           => ['label_ar' => 'عرض الأدوار',               'category' => 'roles'],
        'roles.manage'         => ['label_ar' => 'إدارة الأدوار',             'category' => 'roles'],
        'reports.view'         => ['label_ar' => 'عرض البلاغات',              'category' => 'reports'],
        'reports.review'       => ['label_ar' => 'مراجعة البلاغات',           'category' => 'reports'],
        'reports.resolve'      => ['label_ar' => 'حل البلاغات',               'category' => 'reports'],
        'content.view'         => ['label_ar' => 'عرض المحتوى المبلغ عنه',   'category' => 'content'],
        'content.delete'       => ['label_ar' => 'حذف المحتوى المسيء',        'category' => 'content'],
        'logs.view'            => ['label_ar' => 'عرض سجلات النشاط',          'category' => 'logs'],
        'export.reports'       => ['label_ar' => 'تصدير التقارير',             'category' => 'exports'],
        'settings.manage'      => ['label_ar' => 'إدارة الإعدادات',           'category' => 'settings'],
        'groups.view'          => ['label_ar' => 'عرض المجموعات',             'category' => 'groups'],
        'groups.post'          => ['label_ar' => 'النشر في المجموعات',        'category' => 'groups'],
        'groups.manage'        => ['label_ar' => 'إدارة المجموعات',           'category' => 'groups'],
        'files.upload'         => ['label_ar' => 'رفع الملفات',               'category' => 'files'],
        'files.download'       => ['label_ar' => 'تنزيل الملفات',             'category' => 'files'],
        'files.manage'         => ['label_ar' => 'إدارة الملفات',             'category' => 'files'],
        'announcements.create' => ['label_ar' => 'إنشاء الإعلانات',           'category' => 'content'],
    ];
}
