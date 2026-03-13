<?php
// partials/topnav.php — الشريط العلوي للواجهة الأمامية
$currentUser = current_user();
$userName    = htmlspecialchars($currentUser['full_name'] ?? $currentUser['username'] ?? 'مستخدم', ENT_QUOTES);
$userRole    = $currentUser['role'] ?? 'student';
$roleLabels  = ['admin' => 'مدير النظام','supervisor' => 'مشرف','professor' => 'أستاذ','student' => 'طالب'];
$roleLabel   = $roleLabels[$userRole] ?? $userRole;
$avatarInit  = mb_substr($currentUser['full_name'] ?? 'U', 0, 1);

// عدد الإشعارات غير المقروءة
try {
    $stmtN = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :id AND is_read = 0');
    $stmtN->execute([':id' => $currentUser['user_id']]);
    $notifCount = (int)$stmtN->fetchColumn();
} catch (\Throwable $e) { $notifCount = 0; }
?>
<header class="topbar">
    <!-- Brand -->
    <a href="<?= user_url('feed.php') ?>" class="topbar-brand" style="text-decoration:none;">
        <div class="brand-icon">🔗</div>
        <span>UniLink</span>
    </a>

    <!-- Search -->
    <div class="topbar-search">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" id="globalSearch" placeholder="ابحث عن مستخدمين، منشورات، مجموعات..." autocomplete="off">
    </div>

    <!-- Actions -->
    <div class="topbar-end">

        <?php if (in_array($userRole, ['admin','supervisor'])): ?>
        <a href="<?= e(url('admin/index.php')) ?>" class="topbar-icon-btn" title="لوحة التحكم" style="text-decoration:none;">
            <i class="fa-solid fa-gauge"></i>
        </a>
        <?php endif; ?>

        <!-- Notifications -->
        <button class="topbar-icon-btn" title="الإشعارات" onclick="toggleNotifPanel()">
            <i class="fa-regular fa-bell"></i>
            <?php if ($notifCount > 0): ?>
                <span class="notif-badge"><?= $notifCount > 9 ? '9+' : $notifCount ?></span>
            <?php endif; ?>
        </button>

        <!-- User Menu -->
        <div class="topbar-user" onclick="toggleUserMenu()" style="position:relative;">
            <div class="avatar avatar-sm"><?= $avatarInit ?></div>
            <div>
                <div class="user-name"><?= $userName ?></div>
                <div class="user-role"><?= $roleLabel ?></div>
            </div>
            <i class="fa-solid fa-chevron-down" style="font-size:.65rem;color:var(--text-muted);margin-right:.25rem;"></i>

            <!-- Dropdown -->
            <div id="userMenuDropdown" style="display:none;position:absolute;top:calc(100% + 8px);right:0;
                 background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow-lg);
                 min-width:200px;z-index:200;overflow:hidden;">
                <div style="padding:.75rem 1rem;border-bottom:1px solid var(--border);">
                    <div style="font-weight:700;font-size:.875rem;"><?= $userName ?></div>
                    <div style="font-size:.75rem;color:var(--text-muted);"><?= e($currentUser['email'] ?? '') ?></div>
                </div>
                <a href="<?= user_url('profile.php') ?>" style="display:flex;align-items:center;gap:.6rem;padding:.65rem 1rem;font-size:.875rem;color:var(--text);text-decoration:none;">
                    <i class="fa-regular fa-user" style="width:16px;color:var(--primary);"></i> الملف الشخصي
                </a>
                <div style="height:1px;background:var(--border);"></div>
                <a href="<?= user_url('logout.php') ?>" style="display:flex;align-items:center;gap:.6rem;padding:.65rem 1rem;font-size:.875rem;color:var(--danger);text-decoration:none;">
                    <i class="fa-solid fa-right-from-bracket" style="width:16px;"></i> تسجيل الخروج
                </a>
            </div>
        </div>
    </div>
</header>

<script>
function toggleUserMenu() {
    const d = document.getElementById('userMenuDropdown');
    d.style.display = d.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
    const menu = document.getElementById('userMenuDropdown');
    if (menu && !e.target.closest('.topbar-user')) {
        menu.style.display = 'none';
    }
});
function toggleNotifPanel() {
    document.getElementById('notifPanel')?.classList.toggle('open');
}
</script>
