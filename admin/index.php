<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_login();

$pages = [
    'dashboard' => [
        'title'    => 'Dashboard',
        'title_ar' => 'لوحة المعلومات',
        'file'     => __DIR__ . '/pages/dashboard.php',
        'icon'     => 'fa-solid fa-chart-pie',
        'perm'     => 'dashboard.view',
    ],
    'users' => [
        'title'    => 'Users',
        'title_ar' => 'إدارة المستخدمين',
        'file'     => __DIR__ . '/pages/users.php',
        'icon'     => 'fa-solid fa-users',
        'perm'     => 'users.view',
    ],
    'user_details' => [
        'title'    => 'User Details',
        'title_ar' => 'تفاصيل المستخدم',
        'file'     => __DIR__ . '/pages/user_details.php',
        'icon'     => 'fa-solid fa-user',
        'perm'     => 'users.view',
    ],
    'permissions' => [
        'title'    => 'Permissions',
        'title_ar' => 'الأدوار والصلاحيات',
        'file'     => __DIR__ . '/pages/permissions.php',
        'icon'     => 'fa-solid fa-user-shield',
        'perm'     => 'roles.view',
    ],
    'reports' => [
        'title'    => 'Reports Moderation',
        'title_ar' => 'إدارة البلاغات',
        'file'     => __DIR__ . '/pages/reports.php',
        'icon'     => 'fa-solid fa-flag',
        'perm'     => 'reports.view',
    ],
    'content_moderation' => [
        'title'    => 'Content Moderation',
        'title_ar' => 'إشراف المحتوى',
        'file'     => __DIR__ . '/pages/content_moderation.php',
        'icon'     => 'fa-solid fa-shield-halved',
        'perm'     => 'content.view',
    ],
    'groups_moderation' => [
        'title'    => 'Groups Moderation',
        'title_ar' => 'إشراف المجموعات',
        'file'     => __DIR__ . '/pages/groups_moderation.php',
        'icon'     => 'fa-solid fa-people-group',
        'perm'     => 'groups.manage',
    ],
    'activity_logs' => [
        'title'    => 'Activity Logs',
        'title_ar' => 'سجلات النشاط',
        'file'     => __DIR__ . '/pages/activity_logs.php',
        'icon'     => 'fa-solid fa-list-check',
        'perm'     => 'logs.view',
    ],
    'sessions_monitoring' => [
        'title'    => 'Sessions Monitoring',
        'title_ar' => 'مراقبة الجلسات',
        'file'     => __DIR__ . '/pages/sessions_monitoring.php',
        'icon'     => 'fa-solid fa-satellite-dish',
        'perm'     => 'logs.view',
    ],
    'statistics' => [
        'title'    => 'Statistics & Reports',
        'title_ar' => 'إحصائيات وتقارير',
        'file'     => __DIR__ . '/pages/statistics.php',
        'icon'     => 'fa-solid fa-chart-bar',
        'perm'     => 'export.reports',
    ],
    'settings' => [
        'title'    => 'Settings',
        'title_ar' => 'الإعدادات',
        'file'     => __DIR__ . '/pages/settings.php',
        'icon'     => 'fa-solid fa-gear',
        'perm'     => 'settings.manage',
    ],
];

$activePage = $_GET['page'] ?? 'dashboard';
if (!isset($pages[$activePage])) {
    $activePage = 'dashboard';
}

// التحقق من صلاحية الصفحة الحالية
$requiredPerm = $pages[$activePage]['perm'] ?? null;
if ($requiredPerm && !user_can($requiredPerm)) {
    $activePage = 'dashboard';
}

$pageConfig  = $pages[$activePage];
$pageTitle   = $pageConfig['title'];
$pageTitleAr = $pageConfig['title_ar'];
$pageScripts = [];
$currentUser = current_user();

// تبديل اللغة
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
    // إعادة التوجيه بدون بارامتر lang
    $cleanUrl = strtok($_SERVER['REQUEST_URI'], '?');
    redirect(ltrim($cleanUrl, '/'));
}
$lang = $_SESSION['lang'] ?? 'ar';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
?>
<div class="app-main" id="appMain">
    <?php require __DIR__ . '/partials/topbar.php'; ?>
    <main class="app-content">
        <?php require $pageConfig['file']; ?>
    </main>
    <?php require __DIR__ . '/partials/footer.php'; ?>
</div>
