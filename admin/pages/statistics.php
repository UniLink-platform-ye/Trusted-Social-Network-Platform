<?php

declare(strict_types=1);

require_permission('export.reports');

// ── إحصائيات شاملة ────────────────────────────────────────────────────────────

// المستخدمون
$userStats = [
    'total'     => (int) db()->query("SELECT COUNT(*) FROM users WHERE status != 'deleted'")->fetchColumn(),
    'active'    => (int) db()->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
    'suspended' => (int) db()->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn(),
    'verified'  => (int) db()->query("SELECT COUNT(*) FROM users WHERE is_verified = 1")->fetchColumn(),
];

// المنشورات
$postStats = [
    'total'      => (int) db()->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'flagged'    => (int) db()->query("SELECT COUNT(*) FROM posts WHERE is_flagged = 1")->fetchColumn(),
    'total_likes'=> (int) db()->query("SELECT SUM(likes_count) FROM posts")->fetchColumn(),
];

// البلاغات
$reportStats = [
    'total'        => (int) db()->query("SELECT COUNT(*) FROM reports")->fetchColumn(),
    'pending'      => (int) db()->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn(),
    'under_review' => (int) db()->query("SELECT COUNT(*) FROM reports WHERE status = 'under_review'")->fetchColumn(),
    'resolved'     => (int) db()->query("SELECT COUNT(*) FROM reports WHERE status = 'resolved'")->fetchColumn(),
    'rejected'     => (int) db()->query("SELECT COUNT(*) FROM reports WHERE status = 'rejected'")->fetchColumn(),
];

// المجموعات
$groupStats = [
    'total'   => (int) db()->query("SELECT COUNT(*) FROM `groups`")->fetchColumn(),
    'course'  => (int) db()->query("SELECT COUNT(*) FROM `groups` WHERE type='course'")->fetchColumn(),
    'dept'    => (int) db()->query("SELECT COUNT(*) FROM `groups` WHERE type='department'")->fetchColumn(),
    'activity'=> (int) db()->query("SELECT COUNT(*) FROM `groups` WHERE type='activity'")->fetchColumn(),
];

// توزيع المستخدمين حسب الدور
$byRoleStmt = db()->query("SELECT role, COUNT(*) AS total FROM users WHERE status != 'deleted' GROUP BY role ORDER BY FIELD(role,'admin','supervisor','professor','student')");
$byRole = $byRoleStmt->fetchAll();
$maxRoleCount = max(1, ...array_map(fn($r) => (int)$r['total'], $byRole ?: [['total'=>1]]));

// توزيع البلاغات حسب السبب
$byReasonStmt = db()->query("SELECT reason, COUNT(*) AS total FROM reports GROUP BY reason ORDER BY total DESC");
$byReason = $byReasonStmt->fetchAll();
$maxReasonCount = max(1, ...array_map(fn($r) => (int)$r['total'], $byReason ?: [['total'=>1]]));

// توزيع المنشورات حسب النوع
$byTypeStmt = db()->query("SELECT type, COUNT(*) AS total FROM posts GROUP BY type ORDER BY total DESC");
$byType = $byTypeStmt->fetchAll();
$maxTypeCount = max(1, ...array_map(fn($r) => (int)$r['total'], $byType ?: [['total'=>1]]));

// أكثر المستخدمين نشاطاً (منشورات)
$topUsersStmt = db()->query(
    "SELECT u.user_id, u.full_name, u.username, u.role, COUNT(p.post_id) AS posts_count
     FROM users u
     LEFT JOIN posts p ON p.user_id = u.user_id
     WHERE u.status = 'active'
     GROUP BY u.user_id, u.full_name, u.username, u.role
     ORDER BY posts_count DESC
     LIMIT 5"
);
$topUsers = $topUsersStmt->fetchAll();

// آخر المسجلين
$recentUsersStmt = db()->query(
    "SELECT user_id, full_name, username, role, email, created_at
     FROM users
     WHERE status != 'deleted'
     ORDER BY created_at DESC
     LIMIT 5"
);
$recentUsers = $recentUsersStmt->fetchAll();

// سجل النشاط الشهري (آخر 7 أيام من audit_logs)
$activityTrendStmt = db()->query(
    "SELECT DATE(created_at) AS day, COUNT(*) AS total
     FROM audit_logs
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(created_at)
     ORDER BY day ASC"
);
$activityTrend = $activityTrendStmt->fetchAll();

// أكثر الأسباب للبلاغات
$reasonLabels = [
    'spam'                 => 'سبام',
    'harassment'           => 'تحرش',
    'inappropriate_content'=> 'محتوى غير لائق',
    'misinformation'       => 'معلومات مضللة',
    'copyright_violation'  => 'انتهاك حقوق النشر',
    'other'                => 'أخرى',
];

$roleLabels = get_all_roles();
$typeLabels = ['post'=>'منشور','announcement'=>'إعلان','question'=>'سؤال','lecture'=>'محاضرة'];
?>

<section class="page-block reveal">
    <div class="section-head">
        <div>
            <h2>Statistics & Reports <small style="font-size:.75rem;font-weight:500;color:#64748b">إحصائيات وتقارير</small></h2>
            <p>لوحة تحليلية شاملة توضح مؤشرات الأداء والنشاط في المنصة.</p>
        </div>
        <?php if (user_can('export.reports')): ?>
        <div class="quick-actions">
            <a href="<?= e(admin_url('ajax/export_logs.php')); ?>" class="btn btn-secondary">
                <i class="fa-solid fa-file-export"></i> تصدير التقرير
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- KPIs الرئيسية -->
    <div class="grid kpi-grid" style="grid-template-columns: repeat(4, 1fr);margin-bottom:1.5rem;">
        <article class="kpi-card" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;">
            <div class="kpi-icon" style="background:rgba(255,255,255,.2);"><i class="fa-solid fa-users"></i></div>
            <div class="kpi-content">
                <h3 style="color:#fff;">إجمالي المستخدمين</h3>
                <div class="value counter" data-target="<?= $userStats['total']; ?>">0</div>
                <p style="color:rgba(255,255,255,.75);">نشط: <?= $userStats['active']; ?> | موقوف: <?= $userStats['suspended']; ?></p>
            </div>
        </article>
        <article class="kpi-card" style="background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;">
            <div class="kpi-icon" style="background:rgba(255,255,255,.2);"><i class="fa-solid fa-file-lines"></i></div>
            <div class="kpi-content">
                <h3 style="color:#fff;">إجمالي المنشورات</h3>
                <div class="value counter" data-target="<?= $postStats['total']; ?>">0</div>
                <p style="color:rgba(255,255,255,.75);">مبلغ عنها: <?= $postStats['flagged']; ?> | إجمالي الإعجابات: <?= $postStats['total_likes']; ?></p>
            </div>
        </article>
        <article class="kpi-card" style="background:linear-gradient(135deg,#d97706,#b45309);color:#fff;">
            <div class="kpi-icon" style="background:rgba(255,255,255,.2);"><i class="fa-solid fa-flag"></i></div>
            <div class="kpi-content">
                <h3 style="color:#fff;">إجمالي البلاغات</h3>
                <div class="value counter" data-target="<?= $reportStats['total']; ?>">0</div>
                <p style="color:rgba(255,255,255,.75);">معلق: <?= $reportStats['pending']; ?> | محلول: <?= $reportStats['resolved']; ?></p>
            </div>
        </article>
        <article class="kpi-card" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;">
            <div class="kpi-icon" style="background:rgba(255,255,255,.2);"><i class="fa-solid fa-people-group"></i></div>
            <div class="kpi-content">
                <h3 style="color:#fff;">إجمالي المجموعات</h3>
                <div class="value counter" data-target="<?= $groupStats['total']; ?>">0</div>
                <p style="color:rgba(255,255,255,.75);">مقررات: <?= $groupStats['course']; ?> | أنشطة: <?= $groupStats['activity']; ?></p>
            </div>
        </article>
    </div>
</section>

<!-- المخططات التحليلية -->
<section class="page-block grid reveal" style="grid-template-columns: 1fr 1fr 1fr;">
    <!-- توزيع المستخدمين حسب الدور -->
    <article class="soft-card">
        <div class="section-head">
            <div>
                <h2>توزيع المستخدمين حسب الدور</h2>
            </div>
        </div>
        <div class="progress-list">
            <?php foreach ($byRole as $row): ?>
                <?php $width = (int) round(((int)$row['total'] / $maxRoleCount) * 100); ?>
                <div class="progress-item">
                    <strong><?= e($roleLabels[$row['role']] ?? $row['role']); ?></strong>
                    <span><?= (int)$row['total']; ?></span>
                    <div class="progress-track"><div class="progress-bar" data-width="<?= $width; ?>"></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <!-- توزيع البلاغات حسب السبب -->
    <article class="soft-card">
        <div class="section-head">
            <div>
                <h2>البلاغات حسب السبب</h2>
            </div>
        </div>
        <div class="progress-list">
            <?php foreach ($byReason as $row): ?>
                <?php $width = (int) round(((int)$row['total'] / $maxReasonCount) * 100); ?>
                <div class="progress-item">
                    <strong><?= e($reasonLabels[$row['reason']] ?? $row['reason']); ?></strong>
                    <span><?= (int)$row['total']; ?></span>
                    <div class="progress-track"><div class="progress-bar" data-width="<?= $width; ?>"></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <!-- توزيع المنشورات حسب النوع -->
    <article class="soft-card">
        <div class="section-head">
            <div>
                <h2>المنشورات حسب النوع</h2>
            </div>
        </div>
        <div class="progress-list">
            <?php foreach ($byType as $row): ?>
                <?php $width = (int) round(((int)$row['total'] / $maxTypeCount) * 100); ?>
                <div class="progress-item">
                    <strong><?= e($typeLabels[$row['type']] ?? $row['type']); ?></strong>
                    <span><?= (int)$row['total']; ?></span>
                    <div class="progress-track"><div class="progress-bar" data-width="<?= $width; ?>"></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<!-- جداول التحليلات التفصيلية -->
<section class="page-block grid reveal" style="grid-template-columns: 1fr 1fr;">
    <!-- أكثر المستخدمين نشاطاً -->
    <article class="soft-card">
        <div class="section-head">
            <div>
                <h2>أكثر المستخدمين نشاطاً</h2>
                <p>بناءً على عدد المنشورات</p>
            </div>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                <tr><th>#</th><th>المستخدم</th><th>الدور</th><th>منشوراته</th></tr>
                </thead>
                <tbody>
                <?php if ($topUsers): ?>
                    <?php foreach ($topUsers as $i => $u): ?>
                    <tr>
                        <td style="color:#94a3b8;font-weight:700;"><?= $i + 1; ?></td>
                        <td>
                            <a href="<?= e(admin_url('index.php?page=user_details&uid=' . (int)$u['user_id'])); ?>"
                               style="color:#2563eb;text-decoration:none;font-size:.85rem;font-weight:600;">
                                <?= e($u['full_name']); ?>
                            </a>
                            <br><small style="color:#94a3b8;">@<?= e($u['username']); ?></small>
                        </td>
                        <td><span class="badge <?= e(role_badge_class($u['role'])); ?>" style="font-size:.65rem;"><?= e($roleLabels[$u['role']] ?? $u['role']); ?></span></td>
                        <td><strong style="color:#2563eb;"><?= (int)$u['posts_count']; ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:1.5rem;">لا يوجد بيانات</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <!-- آخر المنضمين -->
    <article class="soft-card">
        <div class="section-head">
            <div>
                <h2>آخر المنضمين</h2>
                <p>أحدث المستخدمين المسجلين</p>
            </div>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                <tr><th>المستخدم</th><th>الدور</th><th>التسجيل</th></tr>
                </thead>
                <tbody>
                <?php if ($recentUsers): ?>
                    <?php foreach ($recentUsers as $u): ?>
                    <tr>
                        <td>
                            <a href="<?= e(admin_url('index.php?page=user_details&uid=' . (int)$u['user_id'])); ?>"
                               style="color:#2563eb;text-decoration:none;font-size:.85rem;font-weight:600;display:block;">
                                <?= e($u['full_name']); ?>
                            </a>
                            <small style="color:#94a3b8;"><?= e($u['email']); ?></small>
                        </td>
                        <td><span class="badge <?= e(role_badge_class($u['role'])); ?>" style="font-size:.65rem;"><?= e($roleLabels[$u['role']] ?? $u['role']); ?></span></td>
                        <td style="font-size:.78rem;color:#64748b;white-space:nowrap;"><?= e(format_datetime($u['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:1.5rem;">لا يوجد بيانات</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<!-- نشاط آخر 7 أيام -->
<section class="page-block reveal">
    <article class="soft-card">
        <div class="section-head">
            <div>
                <h2>نشاط المنصة خلال آخر 7 أيام</h2>
                <p>عدد الأحداث المسجلة يومياً</p>
            </div>
        </div>
        <?php if ($activityTrend): ?>
        <div style="display:flex;align-items:flex-end;gap:0.5rem;height:140px;padding:.5rem 0;overflow-x:auto;">
            <?php
            $maxTrend = max(1, ...array_map(fn($r) => (int)$r['total'], $activityTrend));
            foreach ($activityTrend as $day):
                $h = max(8, (int) round(((int)$day['total'] / $maxTrend) * 120));
            ?>
            <div style="flex:1;min-width:40px;display:flex;flex-direction:column;align-items:center;gap:.35rem;">
                <span style="font-size:.7rem;color:#374151;font-weight:600;"><?= e((string)$day['total']); ?></span>
                <div style="width:100%;height:<?= $h; ?>px;background:linear-gradient(180deg,#2563eb,#3b82f6);
                             border-radius:.4rem .4rem 0 0;transition:all .3s;" title="<?= e($day['day']); ?>: <?= e((string)$day['total']); ?> أحداث"></div>
                <span style="font-size:.68rem;color:#94a3b8;white-space:nowrap;"><?= e(substr($day['day'], 5)); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <?php $title = 'لا يوجد نشاط مسجل'; $description = 'لم يتم تسجيل أي أحداث في الأيام الماضية.'; require __DIR__ . '/../partials/empty-state.php'; ?>
        <?php endif; ?>
    </article>
</section>

<!-- ملخص البلاغات -->
<section class="page-block reveal">
    <article class="report-widget">
        <div class="section-head">
            <div>
                <h2>ملخص حالة البلاغات</h2>
                <p>نسب وأرقام توضح حالة البلاغات</p>
            </div>
            <a href="<?= e(admin_url('index.php?page=reports')); ?>" class="btn btn-info btn-sm">
                <i class="fa-solid fa-flag"></i> إدارة البلاغات
            </a>
        </div>
        <div class="report-filter-cards">
            <div class="filter-card">
                <strong><?= $reportStats['pending']; ?></strong>
                <span>معلق</span>
            </div>
            <div class="filter-card">
                <strong><?= $reportStats['under_review']; ?></strong>
                <span>قيد المراجعة</span>
            </div>
            <div class="filter-card">
                <strong><?= $reportStats['resolved']; ?></strong>
                <span>محلول</span>
            </div>
            <div class="filter-card">
                <strong><?= $reportStats['rejected']; ?></strong>
                <span>مرفوض</span>
            </div>
        </div>
    </article>
</section>
