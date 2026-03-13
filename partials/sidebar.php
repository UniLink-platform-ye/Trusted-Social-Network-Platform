<?php
// partials/sidebar.php — القائمة الجانبية للواجهة الأمامية
$currentUser = current_user();
$userRole    = $currentUser['role'] ?? 'student';
$avatarInit  = mb_substr($currentUser['full_name'] ?? 'U', 0, 1);
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

// عدد الرسائل غير المقروءة
try {
    $stmtM = db()->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id = :id AND is_read = 0');
    $stmtM->execute([':id' => $currentUser['user_id']]);
    $unreadMsgs = (int)$stmtM->fetchColumn();
} catch (\Throwable $e) { $unreadMsgs = 0; }

$nav = [
    ['icon' => 'fa-house',     'label' => 'الرئيسية',       'page' => 'feed.php'],
    ['icon' => 'fa-users',     'label' => 'المجموعات',       'page' => 'groups.php'],
    ['icon' => 'fa-message',   'label' => 'الرسائل',         'page' => 'messages.php', 'badge' => $unreadMsgs],
    ['icon' => 'fa-folder',    'label' => 'الملفات الأكاديمية','page' => 'files.php'],
    ['icon' => 'fa-user',      'label' => 'ملفي الشخصي',    'page' => 'profile.php'],
];
?>
<aside class="sidebar">
    <!-- User Info -->
    <div class="sidebar-user">
        <div class="avatar avatar-md"><?= $avatarInit ?></div>
        <div class="user-info">
            <div class="name"><?= htmlspecialchars($currentUser['full_name'] ?? 'مستخدم', ENT_QUOTES) ?></div>
            <div class="role"><?= htmlspecialchars($currentUser['email'] ?? '', ENT_QUOTES) ?></div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">القائمة الرئيسية</div>
            <?php foreach ($nav as $item): ?>
            <a href="<?= user_url($item['page']) ?>"
               class="nav-link <?= $currentPage === $item['page'] ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fa-solid <?= $item['icon'] ?>"></i></span>
                <?= $item['label'] ?>
                <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                    <span class="nav-badge"><?= $item['badge'] > 99 ? '99+' : $item['badge'] ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if (in_array($userRole, ['admin','supervisor'])): ?>
        <div class="nav-section" style="margin-top:.5rem;">
            <div class="nav-section-title">الإدارة</div>
            <a href="<?= e(url('admin/index.php')) ?>" class="nav-link">
                <span class="nav-icon"><i class="fa-solid fa-gauge"></i></span>
                لوحة التحكم
            </a>
        </div>
        <?php endif; ?>
    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
        <a href="<?= user_url('logout.php') ?>" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            تسجيل الخروج
        </a>
    </div>
</aside>
