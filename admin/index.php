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
    'permissions' => [
        'title'    => 'Permissions',
        'title_ar' => 'إدارة الصلاحيات',
        'file'     => __DIR__ . '/pages/permissions.php',
        'icon'     => 'fa-solid fa-user-shield',
        'perm'     => 'roles.view',
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
