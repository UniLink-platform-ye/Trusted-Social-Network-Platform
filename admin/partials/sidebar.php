<?php declare(strict_types=1);

$menuGroups = [
    'main' => [
        'label' => 'الرئيسية',
        'items' => [
            'dashboard'          => ['label' => 'Dashboard',          'label_ar' => 'لوحة المعلومات',     'icon' => 'fa-solid fa-chart-pie'],
        ],
    ],
    'users_section' => [
        'label' => 'المستخدمون',
        'items' => [
            'users'       => ['label' => 'Users',         'label_ar' => 'إدارة المستخدمين',   'icon' => 'fa-solid fa-users'],
            'permissions' => ['label' => 'Roles & Perms', 'label_ar' => 'الأدوار والصلاحيات', 'icon' => 'fa-solid fa-user-shield'],
        ],
    ],
    'moderation_section' => [
        'label' => 'الإشراف',
        'items' => [
            'reports'            => ['label' => 'Reports',            'label_ar' => 'إدارة البلاغات',     'icon' => 'fa-solid fa-flag'],
            'content_moderation' => ['label' => 'Content',            'label_ar' => 'إشراف المحتوى',      'icon' => 'fa-solid fa-shield-halved'],
            'groups_moderation'  => ['label' => 'Groups',             'label_ar' => 'إشراف المجموعات',    'icon' => 'fa-solid fa-people-group'],
        ],
    ],
    'analytics_section' => [
        'label' => 'التحليلات',
        'items' => [
            'activity_logs'       => ['label' => 'Activity Logs', 'label_ar' => 'سجلات النشاط',     'icon' => 'fa-solid fa-list-check'],
            'sessions_monitoring' => ['label' => 'Sessions',      'label_ar' => 'مراقبة الجلسات',   'icon' => 'fa-solid fa-satellite-dish'],
            'statistics'          => ['label' => 'Statistics',    'label_ar' => 'إحصائيات وتقارير', 'icon' => 'fa-solid fa-chart-bar'],
        ],
    ],
    'system_section' => [
        'label' => 'النظام',
        'items' => [
            'settings'           => ['label' => 'Settings',           'label_ar' => 'الإعدادات',           'icon' => 'fa-solid fa-gear'],
        ],
    ],
];

// الصلاحيات لكل صفحة
$pagePerms = [
    'dashboard'           => 'dashboard.view',
    'users'               => 'users.view',
    'permissions'         => 'roles.view',
    'sessions_monitoring' => 'logs.view',
    'reports'             => 'reports.view',
    'content_moderation'  => 'content.view',
    'groups_moderation'   => 'groups.manage',
    'activity_logs'       => 'logs.view',
    'statistics'          => 'export.reports',
    'settings'            => 'settings.manage',
];
?>
<aside class="sidebar" id="sidebar">
    <a href="<?= e(admin_url('index.php?page=dashboard')); ?>" class="brand">
        <img src="<?= e(url('img/logo.png')); ?>" alt="UniLink Logo" class="brand-logo">
        <div class="brand-text">
            <span>UniLink</span>
            <small>Admin Portal</small>
        </div>
    </a>

    <nav class="sidebar-nav">
        <?php foreach ($menuGroups as $groupKey => $group): ?>
            <?php
            // تحقق إن كانت المجموعة تحتوي على أي عنصر مصرح به
            $hasVisibleItem = false;
            foreach ($group['items'] as $slug => $item) {
                if (user_can($pagePerms[$slug] ?? 'dashboard.view')) {
                    $hasVisibleItem = true;
                    break;
                }
            }
            if (!$hasVisibleItem) continue;
            ?>
            <div class="nav-group">
                <div class="nav-group-label"><?= e($group['label']); ?></div>
                <?php foreach ($group['items'] as $slug => $item): ?>
                    <?php if (!user_can($pagePerms[$slug] ?? 'dashboard.view')): continue; endif; ?>
                    <?php $isActive = $activePage === $slug || ($activePage === 'user_details' && $slug === 'users'); ?>
                    <a href="<?= e(admin_url('index.php?page=' . $slug)); ?>"
                       class="nav-item <?= $isActive ? 'active' : ''; ?>"
                       title="<?= e($item['label_ar']); ?>">
                        <i class="<?= e($item['icon']); ?>"></i>
                        <span><?= e($item['label']); ?></span>
                        <small><?= e($item['label_ar']); ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <!-- زر تسجيل الخروج -->
    <div class="sidebar-footer" style="margin-top:auto;padding:1rem;">
        <form method="post" action="<?= e(admin_url('logout.php')); ?>">
            <?= csrf_input(); ?>
            <button type="submit" class="nav-item" style="width:100%;border:none;cursor:pointer;background:transparent;text-align:inherit;">
                <i class="fa-solid fa-right-from-bracket" style="color:#ef4444;"></i>
                <span style="color:#ef4444;">Logout</span>
                <small>تسجيل الخروج</small>
            </button>
        </form>
    </div>
</aside>
