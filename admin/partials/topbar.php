<?php declare(strict_types=1);
$lang       = $_SESSION['lang'] ?? 'ar';
$isAr       = $lang === 'ar';
$langToggle = $isAr ? 'en' : 'ar';
$langLabel  = $isAr ? 'English' : 'عربي';
$roleLabels = get_all_roles();
$userRole   = $currentUser['role'] ?? 'student';
$roleLabel  = $roleLabels[$userRole] ?? $userRole;
?>
<header class="topbar">
    <div class="topbar-left">
        <button class="icon-btn" id="sidebarToggle" type="button" title="القائمة">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div>
            <div class="breadcrumbs">
                <a href="<?= e(admin_url('index.php?page=dashboard')); ?>">Home</a>
                <span>/</span>
                <strong><?= e($pageTitle); ?></strong>
            </div>
            <h1 style="margin:0;font-size:1.1rem;font-weight:700;">
                <?= e($pageTitleAr); ?>
                <small style="font-size:.7rem;font-weight:500;color:#94a3b8;"><?= e($pageTitle); ?></small>
            </h1>
        </div>
    </div>

    <div class="topbar-right" style="display:flex;align-items:center;gap:.75rem;">
        <!-- بحث -->
        <form class="top-search" method="get" action="<?= e(admin_url('index.php')); ?>">
            <input type="hidden" name="page" value="<?= e($activePage); ?>">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="q"
                   value="<?= e((string) ($_GET['q'] ?? '')); ?>"
                   placeholder="<?= $isAr ? 'بحث في الصفحة...' : 'Search...'; ?>">
        </form>

        <!-- تبديل اللغة -->
        <a href="<?= e(admin_url('index.php?page=' . $activePage . '&lang=' . $langToggle)); ?>"
           class="btn btn-sm btn-muted"
           style="white-space:nowrap;font-weight:700;"
           title="Switch language">
            <i class="fa-solid fa-language"></i>
            <?= e($langLabel); ?>
        </a>

        <!-- معلومات المستخدم + dropdown -->
        <div class="profile-chip" id="profileChip" style="cursor:pointer;position:relative;">
            <div style="
                width:36px;height:36px;border-radius:50%;
                background:linear-gradient(135deg,#2563eb,#0ea5e9);
                display:flex;align-items:center;justify-content:center;
                color:#fff;font-weight:800;font-size:.9rem;flex-shrink:0;">
                <?= e(mb_strtoupper(mb_substr($currentUser['full_name'] ?? 'A', 0, 1))); ?>
            </div>
            <div>
                <strong style="display:block;font-size:.85rem;"><?= e($currentUser['full_name'] ?? 'Admin'); ?></strong>
                <small style="color:#94a3b8;font-size:.72rem;"><?= e($roleLabel); ?></small>
            </div>
            <i class="fa-solid fa-chevron-down" style="font-size:.7rem;color:#94a3b8;margin-right:.2rem;"></i>

            <!-- Dropdown -->
            <div id="profileDropdown" style="
                display:none;position:absolute;top:calc(100% + 8px);left:0;
                background:#fff;border:1px solid #e2e8f0;border-radius:12px;
                box-shadow:0 10px 30px rgba(0,0,0,.12);min-width:200px;z-index:999;overflow:hidden;">
                <div style="padding:.75rem 1rem;border-bottom:1px solid #f1f5f9;">
                    <strong style="font-size:.85rem;display:block;"><?= e($currentUser['full_name'] ?? ''); ?></strong>
                    <small style="color:#64748b;"><?= e($currentUser['email'] ?? ''); ?></small>
                </div>
                <div style="padding:.5rem;">
                    <form method="post" action="<?= e(admin_url('logout.php')); ?>">
                        <?= csrf_input(); ?>
                        <button type="submit" style="
                            width:100%;text-align:right;padding:.55rem .75rem;
                            border:none;background:none;cursor:pointer;
                            border-radius:8px;font-family:inherit;font-size:.85rem;
                            color:#ef4444;display:flex;align-items:center;gap:.5rem;">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            تسجيل الخروج
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
<script>
(function(){
    const chip = document.getElementById('profileChip');
    const drop = document.getElementById('profileDropdown');
    if(!chip||!drop) return;
    chip.addEventListener('click', function(e){
        e.stopPropagation();
        drop.style.display = drop.style.display==='block' ? 'none' : 'block';
    });
    document.addEventListener('click', function(){ drop.style.display='none'; });
})();
</script>
