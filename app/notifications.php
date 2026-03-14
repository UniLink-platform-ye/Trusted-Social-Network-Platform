<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_user_login();
$me  = current_user(); $uid = (int)$me['user_id'];

// تحديد جميع إشعارات المستخدم كمقروءة عند فتح الصفحة
try {
    db()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=:id AND is_read=0')
        ->execute([':id' => $uid]);
} catch(\Throwable $e) {}

// جلب الإشعارات
try {
    $stmt = db()->prepare(
        'SELECT * FROM notifications WHERE user_id=:id ORDER BY created_at DESC LIMIT 100'
    );
    $stmt->execute([':id' => $uid]);
    $notifications = $stmt->fetchAll();
} catch(\Throwable $e) { $notifications = []; }

$typeConfig = [
    'new_post'     => ['icon' => '📝', 'color' => '#2563eb', 'bg' => '#eff6ff'],
    'new_message'  => ['icon' => '💬', 'color' => '#16a34a', 'bg' => '#f0fdf4'],
    'group_join'   => ['icon' => '👥', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
    'group_invite' => ['icon' => '🔗', 'color' => '#0891b2', 'bg' => '#ecfeff'],
    'file_upload'  => ['icon' => '📁', 'color' => '#f59e0b', 'bg' => '#fffbeb'],
    'report'       => ['icon' => '🚩', 'color' => '#dc2626', 'bg' => '#fef2f2'],
    'system'       => ['icon' => '🔔', 'color' => '#64748b', 'bg' => '#f8fafc'],
];

function relT(string $dt): string {
    $d = time() - strtotime($dt);
    if ($d < 60) return 'الآن';
    if ($d < 3600) return (int)($d/60) . ' دقيقة';
    if ($d < 86400) return (int)($d/3600) . ' ساعة';
    if ($d < 604800) return (int)($d/86400) . ' يوم';
    return date('d/m/Y', strtotime($dt));
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>الإشعارات | <?= e(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= user_url('assets/css/app.css') ?>">
</head>
<body>
<div class="app-wrapper">
<?php include __DIR__ . '/partials/topnav.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="main-content" style="max-width:720px;">
<div class="page-header">
    <h1><i class="fa-regular fa-bell" style="color:var(--primary);"></i> الإشعارات</h1>
    <p>جميع تنبيهاتك في مكان واحد</p>
</div>

<?php if (empty($notifications)): ?>
<div class="empty-state">
    <div class="empty-icon">🔔</div>
    <h3>لا توجد إشعارات</h3>
    <p>ستظهر هنا إشعارات المنشورات والرسائل والمجموعات</p>
</div>
<?php else: ?>

<!-- تجميع حسب اليوم -->
<?php
$grouped = [];
foreach ($notifications as $n) {
    $day = date('Y-m-d', strtotime($n['created_at']));
    $grouped[$day][] = $n;
}
$today     = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
?>

<?php foreach ($grouped as $day => $items): ?>
<div style="margin-bottom:1.75rem;">
    <div style="font-size:.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem;">
        <span style="height:1px;flex:1;background:var(--border);"></span>
        <?= $day === $today ? 'اليوم' : ($day === $yesterday ? 'أمس' : date('d/m/Y', strtotime($day))) ?>
        <span style="height:1px;flex:1;background:var(--border);"></span>
    </div>

    <?php foreach ($items as $notif):
        $type = $notif['type'] ?? 'system';
        $cfg  = $typeConfig[$type] ?? $typeConfig['system'];
    ?>
    <div style="display:flex;align-items:flex-start;gap:.875rem;padding:1rem 1.25rem;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:.5rem;transition:.2s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.1)'" onmouseout="this.style.boxShadow=''">
        <!-- أيقونة النوع -->
        <div style="width:44px;height:44px;border-radius:12px;background:<?= $cfg['bg'] ?>;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
            <?= $cfg['icon'] ?>
        </div>
        <!-- المحتوى -->
        <div style="flex:1;min-width:0;">
            <div style="font-weight:700;font-size:.9rem;margin-bottom:.2rem;color:var(--text);">
                <?= htmlspecialchars($notif['title'] ?? '', ENT_QUOTES) ?>
            </div>
            <?php if ($notif['message']): ?>
            <div style="font-size:.8rem;color:var(--text-muted);line-height:1.5;">
                <?= htmlspecialchars($notif['message'], ENT_QUOTES) ?>
            </div>
            <?php endif; ?>
            <div style="font-size:.72rem;color:var(--text-muted);margin-top:.3rem;">
                <?= relT((string)$notif['created_at']) ?>
            </div>
        </div>
        <!-- رابط إذا وُجد -->
        <?php if (!empty($notif['link'])): ?>
        <a href="<?= htmlspecialchars($notif['link'], ENT_QUOTES) ?>" style="flex-shrink:0;width:32px;height:32px;border-radius:50%;background:var(--bg);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:.75rem;transition:.2s;" onmouseover="this.style.background='<?= $cfg['bg'] ?>';this.style.color='<?= $cfg['color'] ?>'" onmouseout="this.style.background='var(--bg)';this.style.color='var(--text-muted)'">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
</main>
</div>
</body>
</html>
