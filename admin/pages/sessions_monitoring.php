<?php

declare(strict_types=1);

require_permission('logs.view');

// ── الجلسات النشطة (من جدول users عبر remember_token وlast_login) ────────────
$page    = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;
$search  = query_value('q');
$filterRole   = query_value('role');
$filterStatus = query_value('status');

$where  = ["u.status != 'deleted'"];
$params = [];

if ($search !== '') {
    $where[]       = '(u.full_name LIKE :q OR u.email LIKE :q OR u.username LIKE :q)';
    $params[':q']  = '%' . $search . '%';
}
if ($filterRole !== '' && $filterRole !== 'all') {
    $where[]           = 'u.role = :role';
    $params[':role']   = $filterRole;
}

// تعريف "الجلسات النشطة": المستخدم الذي دخل في آخر 30 دقيقة
$activeThreshold = date('Y-m-d H:i:s', strtotime('-30 minutes'));

if ($filterStatus === 'active') {
    $where[] = "u.last_login >= :threshold";
    $params[':threshold'] = $activeThreshold;
} elseif ($filterStatus === 'inactive') {
    $where[] = "(u.last_login IS NULL OR u.last_login < :threshold)";
    $params[':threshold'] = $activeThreshold;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM users u $whereSql");
$countStmt->execute($params);
$totalRows  = (int) $countStmt->fetchColumn();
$pagination = build_pagination($totalRows, $page, $perPage);

$usersStmt = db()->prepare(
    "SELECT u.user_id, u.username, u.full_name, u.email, u.role,
            u.department, u.status, u.last_login, u.avatar_url,
            u.remember_token_expires_at,
            CASE WHEN u.last_login >= :threshold THEN 1 ELSE 0 END AS is_online
     FROM users u
     $whereSql
     ORDER BY u.last_login DESC NULLS LAST
     LIMIT :limit OFFSET :offset"
);
// MySQL doesn't support NULLS LAST, rewrite:
$usersStmt = db()->prepare(
    "SELECT u.user_id, u.username, u.full_name, u.email, u.role,
            u.department, u.status, u.last_login, u.avatar_url,
            u.remember_token_expires_at,
            CASE WHEN (u.last_login IS NOT NULL AND u.last_login >= :threshold) THEN 1 ELSE 0 END AS is_online
     FROM users u
     $whereSql
     ORDER BY is_online DESC, u.last_login DESC
     LIMIT :limit OFFSET :offset"
);
$usersStmt->bindValue(':threshold', $activeThreshold);
foreach ($params as $k => $v) {
    $usersStmt->bindValue($k, $v);
}
$usersStmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$usersStmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$usersStmt->execute();
$sessions = $usersStmt->fetchAll();

// إحصائيات
$onlineCount     = (int) db()->prepare("SELECT COUNT(*) FROM users WHERE last_login >= :t AND status = 'active'")->execute([':t' => $activeThreshold]) ?
    (function() use ($activeThreshold) {
        $s = db()->prepare("SELECT COUNT(*) FROM users WHERE last_login >= :t AND status = 'active'");
        $s->execute([':t' => $activeThreshold]);
        return (int) $s->fetchColumn();
    })() : 0;

$onlineStmt = db()->prepare("SELECT COUNT(*) FROM users WHERE last_login >= :t AND status = 'active'");
$onlineStmt->execute([':t' => $activeThreshold]);
$onlineCount = (int) $onlineStmt->fetchColumn();

$totalActive  = (int) db()->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$totalSuspended = (int) db()->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn();

// الدخول الأخير من audit_logs
$recentLoginsStmt = db()->query(
    "SELECT al.created_at, al.ip_address, al.action,
            u.full_name, u.email, u.role, u.user_id
     FROM audit_logs al
     LEFT JOIN users u ON u.user_id = al.user_id
     WHERE al.action IN ('login','login_failed','logout')
     ORDER BY al.created_at DESC
     LIMIT 8"
);
$recentLogins = $recentLoginsStmt->fetchAll();

$roleLabels = get_all_roles();
?>

<section class="page-block reveal">
    <div class="section-head">
        <div>
            <h2>Sessions Monitoring <small style="font-size:.75rem;font-weight:500;color:#64748b">مراقبة الجلسات</small></h2>
            <p>متابعة المستخدمين النشطين وتتبع جلسات الدخول في الوقت الفعلي.</p>
        </div>
    </div>

    <!-- إحصائيات -->
    <div class="grid kpi-grid" style="grid-template-columns: repeat(4, 1fr);margin-bottom:1.25rem;">
        <article class="kpi-card">
            <div class="kpi-icon success"><i class="fa-solid fa-circle"></i></div>
            <div class="kpi-content">
                <h3>متصلون الآن</h3>
                <div class="value counter" data-target="<?= $onlineCount; ?>">0</div>
                <p>آخر 30 دقيقة</p>
            </div>
        </article>
        <article class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-users"></i></div>
            <div class="kpi-content">
                <h3>حسابات نشطة</h3>
                <div class="value counter" data-target="<?= $totalActive; ?>">0</div>
                <p>إجمالي المستخدمين النشطين</p>
            </div>
        </article>
        <article class="kpi-card">
            <div class="kpi-icon danger"><i class="fa-solid fa-user-slash"></i></div>
            <div class="kpi-content">
                <h3>موقوفون</h3>
                <div class="value counter" data-target="<?= $totalSuspended; ?>">0</div>
                <p>حسابات معلقة</p>
            </div>
        </article>
        <article class="kpi-card">
            <div class="kpi-icon warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="kpi-content">
                <h3>محاولات فاشلة</h3>
                <div class="value counter" data-target="<?= (int) db()->query("SELECT COUNT(*) FROM audit_logs WHERE action='login_failed'")->fetchColumn(); ?>">0</div>
                <p>إجمالي فشل الدخول</p>
            </div>
        </article>
    </div>
</section>

<!-- قسم الجلسات وآخر الدخول -->
<section class="page-block grid reveal" style="grid-template-columns: 1.6fr 1fr;">
    <!-- جدول الجلسات -->
    <article class="soft-card">
        <div class="section-head">
            <div>
                <h2>المستخدمون وجلساتهم</h2>
                <p>حالة الدخول لكل مستخدم</p>
            </div>
        </div>

        <!-- فلاتر -->
        <form method="get" action="" class="filter-bar" style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1rem;">
            <input type="hidden" name="page" value="sessions_monitoring">
            <input type="text" name="q" value="<?= e($search); ?>"
                   placeholder="بحث بالاسم أو البريد..."
                   class="form-control" style="flex:1;min-width:180px;font-size:.83rem;">
            <select name="role" class="form-control" style="min-width:130px;font-size:.83rem;">
                <option value="all" <?= selected($filterRole,'all'); ?>>كل الأدوار</option>
                <?php foreach ($roleLabels as $k => $v): ?>
                    <option value="<?= e($k); ?>" <?= selected($filterRole,$k); ?>><?= e($v); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-control" style="min-width:130px;font-size:.83rem;">
                <option value="" <?= selected($filterStatus,''); ?>>كل الحالات</option>
                <option value="active" <?= selected($filterStatus,'active'); ?>>متصل الآن</option>
                <option value="inactive" <?= selected($filterStatus,'inactive'); ?>>غير متصل</option>
            </select>
            <button type="submit" class="btn btn-info btn-sm"><i class="fa-solid fa-filter"></i></button>
            <?php if ($search !== '' || ($filterRole !== '' && $filterRole !== 'all') || $filterStatus !== ''): ?>
                <a href="<?= e(admin_url('index.php?page=sessions_monitoring')); ?>" class="btn btn-muted btn-sm">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </form>

        <div class="table-scroll">
            <table>
                <thead>
                <tr>
                    <th>المستخدم</th>
                    <th>الدور</th>
                    <th>الحالة</th>
                    <th>آخر دخول</th>
                    <th>الإجراءات</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($sessions): ?>
                    <?php foreach ($sessions as $s): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:.5rem;">
                                <div style="position:relative;">
                                    <div style="width:32px;height:32px;border-radius:50%;background:<?= e(role_color($s['role'])); ?>;
                                                display:flex;align-items:center;justify-content:center;
                                                color:#fff;font-weight:700;font-size:.8rem;flex-shrink:0;">
                                        <?= e(mb_strtoupper(mb_substr($s['full_name'], 0, 1))); ?>
                                    </div>
                                    <?php if ($s['is_online']): ?>
                                    <span style="position:absolute;bottom:0;right:0;width:9px;height:9px;
                                                 border-radius:50%;background:#16a34a;border:2px solid #fff;"></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <a href="<?= e(admin_url('index.php?page=user_details&uid=' . (int)$s['user_id'])); ?>"
                                       style="color:#2563eb;text-decoration:none;font-size:.85rem;font-weight:600;display:block;">
                                        <?= e($s['full_name']); ?>
                                    </a>
                                    <small style="color:#94a3b8;">@<?= e($s['username']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= e(role_badge_class($s['role'])); ?>" style="font-size:.65rem;">
                                <?= e($roleLabels[$s['role']] ?? $s['role']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($s['is_online']): ?>
                                <span style="display:flex;align-items:center;gap:.3rem;font-size:.8rem;color:#16a34a;font-weight:600;">
                                    <span style="width:7px;height:7px;border-radius:50%;background:#16a34a;display:inline-block;"></span>
                                    متصل
                                </span>
                            <?php else: ?>
                                <span style="color:#94a3b8;font-size:.8rem;">غير متصل</span>
                            <?php endif; ?>
                            &nbsp;
                            <span class="badge <?= e(status_badge_class($s['status'])); ?>" style="font-size:.62rem;">
                                <?= e(status_label($s['status'])); ?>
                            </span>
                        </td>
                        <td style="font-size:.78rem;color:#64748b;white-space:nowrap;">
                            <?= e($s['last_login'] ? format_datetime($s['last_login']) : 'لم يدخل'); ?>
                        </td>
                        <td>
                            <?php if (user_can('users.suspend') && $s['status'] === 'active'): ?>
                            <button class="btn btn-sm btn-danger btn-suspend-user"
                                    data-id="<?= (int) $s['user_id']; ?>"
                                    data-name="<?= e($s['full_name']); ?>"
                                    title="تعليق الجلسة">
                                <i class="fa-solid fa-ban"></i>
                            </button>
                            <?php elseif (user_can('users.suspend') && $s['status'] === 'suspended'): ?>
                            <button class="btn btn-sm btn-success btn-activate-user"
                                    data-id="<?= (int) $s['user_id']; ?>"
                                    data-name="<?= e($s['full_name']); ?>"
                                    title="تفعيل الجلسة">
                                <i class="fa-solid fa-user-check"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:2rem;color:#94a3b8;">
                            <i class="fa-solid fa-users-slash" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                            لا توجد جلسات مطابقة.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ترقيم -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div style="display:flex;justify-content:center;gap:.5rem;padding:.75rem;align-items:center;">
            <?php if ($pagination['has_prev']): ?>
                <a href="?page=sessions_monitoring&p=<?= $pagination['prev']; ?>&q=<?= urlencode($search); ?>&role=<?= urlencode($filterRole); ?>&status=<?= urlencode($filterStatus); ?>" class="btn btn-sm btn-muted">
                    <i class="fa-solid fa-angle-right"></i>
                </a>
            <?php endif; ?>
            <span style="font-size:.82rem;color:#374151;">
                <?= $pagination['current_page']; ?> / <?= $pagination['total_pages']; ?>
            </span>
            <?php if ($pagination['has_next']): ?>
                <a href="?page=sessions_monitoring&p=<?= $pagination['next']; ?>&q=<?= urlencode($search); ?>&role=<?= urlencode($filterRole); ?>&status=<?= urlencode($filterStatus); ?>" class="btn btn-sm btn-muted">
                    <i class="fa-solid fa-angle-left"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </article>

    <!-- آخر أحداث الدخول -->
    <article class="timeline soft-card">
        <div class="section-head">
            <div>
                <h2>آخر أحداث الدخول</h2>
                <p>تسجيل الدخول والخروج الأخير</p>
            </div>
        </div>
        <div class="timeline-list">
            <?php if ($recentLogins): ?>
                <?php foreach ($recentLogins as $ev): ?>
                    <?php
                    $evIcon  = match($ev['action']) {
                        'login'        => ['icon' => 'fa-right-to-bracket', 'color' => '#16a34a'],
                        'logout'       => ['icon' => 'fa-right-from-bracket','color' => '#64748b'],
                        'login_failed' => ['icon' => 'fa-circle-xmark',     'color' => '#ef4444'],
                        default        => ['icon' => 'fa-circle-dot',        'color' => '#64748b'],
                    };
                    ?>
                    <div class="timeline-item" style="display:flex;gap:.6rem;align-items:flex-start;">
                        <span style="width:28px;height:28px;border-radius:50%;background:<?= e($evIcon['color']); ?>1a;
                                     display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:.1rem;">
                            <i class="fa-solid <?= e($evIcon['icon']); ?>" style="font-size:.7rem;color:<?= e($evIcon['color']); ?>;"></i>
                        </span>
                        <div style="flex:1;">
                            <strong style="font-size:.85rem;">
                                <?= e($ev['full_name'] ?? 'مجهول'); ?>
                            </strong>
                            <?php if ($ev['role']): ?>
                                <span class="badge <?= e(role_badge_class($ev['role'])); ?>" style="font-size:.6rem;"><?= e($ev['role']); ?></span>
                            <?php endif; ?>
                            <p style="margin:.15rem 0;font-size:.78rem;color:#64748b;">
                                <?= match($ev['action']) { 'login'=>'تسجيل دخول', 'logout'=>'تسجيل خروج', 'login_failed'=>'محاولة دخول فاشلة', default=>$ev['action'] }; ?>
                                &nbsp;·&nbsp; <code style="font-size:.72rem;background:#f1f5f9;padding:.1rem .3rem;border-radius:.2rem;"><?= e($ev['ip_address'] ?? '—'); ?></code>
                            </p>
                            <small style="color:#94a3b8;"><?= e(format_datetime($ev['created_at'])); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php $title = 'لا توجد أحداث دخول'; $description = 'لم يسجل أي أحداث بعد.'; require __DIR__ . '/../partials/empty-state.php'; ?>
            <?php endif; ?>
        </div>
    </article>
</section>
<?php $pageScripts[] = 'users.js'; ?>
