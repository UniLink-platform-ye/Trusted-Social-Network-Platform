<?php declare(strict_types=1);

$menu = [
    'dashboard'   => ['label' => 'Dashboard',    'label_ar' => 'لوحة المعلومات',     'icon' => 'fa-solid fa-chart-pie',    'perm' => 'dashboard.view'],
    'users'       => ['label' => 'Users',         'label_ar' => 'إدارة المستخدمين',   'icon' => 'fa-solid fa-users',        'perm' => 'users.view'],
    'permissions' => ['label' => 'Permissions',  'label_ar' => 'إدارة الصلاحيات',    'icon' => 'fa-solid fa-user-shield',  'perm' => 'roles.view'],
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
        <?php foreach ($menu as $slug => $item): ?>
            <?php if (!user_can($item['perm'])): continue; endif; ?>
            <?php $isActive = $activePage === $slug; ?>
            <a href="<?= e(admin_url('index.php?page=' . $slug)); ?>"
               class="nav-item <?= $isActive ? 'active' : ''; ?>"
               title="<?= e($item['label_ar']); ?>">
                <i class="<?= e($item['icon']); ?>"></i>
                <span><?= e($item['label']); ?></span>
                <small><?= e($item['label_ar']); ?></small>
            </a>
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
