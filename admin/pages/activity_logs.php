<?php

declare(strict_types=1);

require_permission('logs.view');

// ── فلترة وبحث ────────────────────────────────────────────────────────────────
$filterAction = query_value('action');
$search       = query_value('q');
$dateFrom     = query_value('date_from');
$dateTo       = query_value('date_to');
$page         = max(1, (int) ($_GET['p'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

$where  = [];
$params = [];

$validActions = [
    'login','logout','login_failed','register',
    'post_create','post_delete','post_edit',
    'file_upload','file_delete','report_submit',
    'account_suspend','account_delete',
    'permission_change','password_change',
];

if ($filterAction !== '' && $filterAction !== 'all' && in_array($filterAction, $validActions, true)) {
    $where[]              = 'al.action = :action';
    $params[':action']    = $filterAction;
}
if ($search !== '') {
    $where[]              = '(u.full_name LIKE :q OR u.email LIKE :q OR u.username LIKE :q OR al.description LIKE :q OR al.ip_address LIKE :q)';
    $params[':q']         = '%' . $search . '%';
}
if ($dateFrom !== '') {
    $where[]              = 'al.created_at >= :date_from';
    $params[':date_from'] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
    $where[]              = 'al.created_at <= :date_to';
    $params[':date_to']   = $dateTo . ' 23:59:59';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare(
    "SELECT COUNT(*) FROM audit_logs al
     LEFT JOIN users u ON u.user_id = al.user_id
     $whereSql"
);
$countStmt->execute($params);
$totalRows  = (int) $countStmt->fetchColumn();
$pagination = build_pagination($totalRows, $page, $perPage);

$logsStmt = db()->prepare(
    "SELECT al.log_id, al.action, al.description, al.ip_address, al.user_agent, al.created_at,
            u.user_id, u.full_name, u.email, u.username, u.role
     FROM audit_logs al
     LEFT JOIN users u ON u.user_id = al.user_id
     $whereSql
     ORDER BY al.created_at DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) {
    $logsStmt->bindValue($k, $v);
}
$logsStmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$logsStmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$logsStmt->execute();
$logs = $logsStmt->fetchAll();

// إحصائيات
$totalLogs    = (int) db()->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
$loginCount   = (int) db()->query("SELECT COUNT(*) FROM audit_logs WHERE action = 'login'")->fetchColumn();
$failedLogins = (int) db()->query("SELECT COUNT(*) FROM audit_logs WHERE action = 'login_failed'")->fetchColumn();
$suspensions  = (int) db()->query("SELECT COUNT(*) FROM audit_logs WHERE action = 'account_suspend'")->fetchColumn();

// أيقونات ولن الأحداث
$actionConfig = [
    'login'            => ['icon' => 'fa-right-to-bracket',  'color' => '#16a34a', 'label_ar' => 'تسجيل دخول'],
    'logout'           => ['icon' => 'fa-right-from-bracket', 'color' => '#64748b', 'label_ar' => 'تسجيل خروج'],
    'login_failed'     => ['icon' => 'fa-circle-xmark',      'color' => '#ef4444', 'label_ar' => 'فشل الدخول'],
    'register'         => ['icon' => 'fa-user-plus',          'color' => '#2563eb', 'label_ar' => 'تسجيل حساب'],
    'post_create'      => ['icon' => 'fa-pen-to-square',      'color' => '#0891b2', 'label_ar' => 'إنشاء منشور'],
    'post_delete'      => ['icon' => 'fa-trash',              'color' => '#ef4444', 'label_ar' => 'حذف منشور'],
    'post_edit'        => ['icon' => 'fa-pencil',             'color' => '#d97706', 'label_ar' => 'تعديل منشور'],
    'file_upload'      => ['icon' => 'fa-upload',             'color' => '#16a34a', 'label_ar' => 'رفع ملف'],
    'file_delete'      => ['icon' => 'fa-file-circle-xmark',  'color' => '#ef4444', 'label_ar' => 'حذف ملف'],
    'report_submit'    => ['icon' => 'fa-flag',               'color' => '#f59e0b', 'label_ar' => 'إرسال بلاغ'],
    'account_suspend'  => ['icon' => 'fa-user-slash',         'color' => '#ef4444', 'label_ar' => 'تعليق حساب'],
    'account_delete'   => ['icon' => 'fa-user-minus',         'color' => '#7f1d1d', 'label_ar' => 'حذف حساب'],
    'permission_change'=> ['icon' => 'fa-user-shield',        'color' => '#7c3aed', 'label_ar' => 'تغيير صلاحية'],
    'password_change'  => ['icon' => 'fa-key',                'color' => '#0891b2', 'label_ar' => 'تغيير كلمة مرور'],
];
?>

<section class="page-block reveal">
    <div class="section-head">
        <div>
            <h2>Activity Logs <small style="font-size:.75rem;font-weight:500;color:#64748b">سجلات النشاط</small></h2>
            <p>تتبع ومراجعة جميع الأنشطة والإجراءات داخل المنصة.</p>
        </div>
        <?php if (user_can('export.reports')): ?>
        <div class="quick-actions">
            <a href="<?= e(admin_url('ajax/export_logs.php')); ?>" class="btn btn-secondary">
                <i class="fa-solid fa-file-export"></i> تصدير السجلات
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- إحصائيات -->
    <div class="grid kpi-grid" style="grid-template-columns: repeat(4, 1fr);margin-bottom:1.25rem;">
        <article class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-list-check"></i></div>
            <div class="kpi-content">
                <h3>إجمالي السجلات</h3>
                <div class="value counter" data-target="<?= $totalLogs; ?>">0</div>
                <p>جميع الأحداث المسجلة</p>
            </div>
        </article>
        <article class="kpi-card">
            <div class="kpi-icon success"><i class="fa-solid fa-right-to-bracket"></i></div>
            <div class="kpi-content">
                <h3>تسجيلات الدخول</h3>
                <div class="value counter" data-target="<?= $loginCount; ?>">0</div>
                <p>دخول ناجح</p>
            </div>
        </article>
        <article class="kpi-card">
            <div class="kpi-icon danger"><i class="fa-solid fa-circle-xmark"></i></div>
            <div class="kpi-content">
                <h3>محاولات فاشلة</h3>
                <div class="value counter" data-target="<?= $failedLogins; ?>">0</div>
                <p>دخول فاشل</p>
            </div>
        </article>
        <article class="kpi-card">
            <div class="kpi-icon warning"><i class="fa-solid fa-user-slash"></i></div>
            <div class="kpi-content">
                <h3>تعليقات الحسابات</h3>
                <div class="value counter" data-target="<?= $suspensions; ?>">0</div>
                <p>إجراءات تأديبية</p>
            </div>
        </article>
    </div>

    <!-- فلاتر -->
    <form method="get" action="" class="filter-bar" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.25rem;">
        <input type="hidden" name="page" value="activity_logs">
        <input type="text" name="q" value="<?= e($search); ?>"
               placeholder="بحث بالاسم أو IP أو الوصف..."
               class="form-control" style="flex:1;min-width:200px;">
        <select name="action" class="form-control" style="min-width:180px;">
            <option value="all" <?= selected($filterAction,'all'); ?>>كل الأحداث</option>
            <?php foreach ($validActions as $ac): ?>
                <option value="<?= e($ac); ?>" <?= selected($filterAction,$ac); ?>><?= e($actionConfig[$ac]['label_ar'] ?? $ac); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date_from" value="<?= e($dateFrom); ?>" class="form-control" style="min-width:140px;" title="من تاريخ">
        <input type="date" name="date_to" value="<?= e($dateTo); ?>" class="form-control" style="min-width:140px;" title="إلى تاريخ">
        <button type="submit" class="btn btn-info"><i class="fa-solid fa-filter"></i> تصفية</button>
        <?php if ($search !== '' || ($filterAction !== '' && $filterAction !== 'all') || $dateFrom !== '' || $dateTo !== ''): ?>
            <a href="<?= e(admin_url('index.php?page=activity_logs')); ?>" class="btn btn-muted">
                <i class="fa-solid fa-xmark"></i> مسح
            </a>
        <?php endif; ?>
    </form>

    <!-- جدول السجلات -->
    <article class="table-wrap reveal">
        <div class="table-scroll">
            <table id="logsTable">
                <thead>
                <tr>
                    <th>#</th>
                    <th>الحدث</th>
                    <th>المستخدم</th>
                    <th>الوصف</th>
                    <th>عنوان IP</th>
                    <th>التاريخ والوقت</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($logs): ?>
                    <?php foreach ($logs as $log): ?>
                    <?php $ac = $actionConfig[$log['action']] ?? ['icon'=>'fa-circle-dot','color'=>'#64748b','label_ar'=>$log['action']]; ?>
                    <tr>
                        <td style="color:#94a3b8;font-size:.8rem;"><?= (int) $log['log_id']; ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:.5rem;">
                                <span style="width:28px;height:28px;border-radius:50%;background:<?= e($ac['color']); ?>1a;
                                             display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fa-solid <?= e($ac['icon']); ?>" style="font-size:.75rem;color:<?= e($ac['color']); ?>;"></i>
                                </span>
                                <span style="font-size:.82rem;font-weight:600;color:#374151;"><?= e($ac['label_ar']); ?></span>
                            </div>
                        </td>
                        <td>
                            <?php if ($log['user_id']): ?>
                                <a href="<?= e(admin_url('index.php?page=user_details&uid=' . (int)$log['user_id'])); ?>"
                                   style="color:#2563eb;text-decoration:none;font-size:.85rem;display:block;">
                                    <?= e($log['full_name'] ?? '—'); ?>
                                </a>
                                <small style="color:#94a3b8;">@<?= e($log['username'] ?? ''); ?></small>
                                <?php if ($log['role']): ?>
                                    <span class="badge <?= e(role_badge_class($log['role'])); ?>" style="font-size:.6rem;"><?= e($log['role']); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:#94a3b8;font-size:.82rem;">النظام</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:250px;font-size:.82rem;color:#374151;">
                            <?= $log['description'] ? e(mb_substr($log['description'], 0, 80)) . (mb_strlen($log['description']) > 80 ? '…' : '') : '<span style="color:#94a3b8;">—</span>'; ?>
                        </td>
                        <td>
                            <code style="font-size:.78rem;background:#f1f5f9;padding:.15rem .4rem;border-radius:.3rem;color:#374151;">
                                <?= e($log['ip_address'] ?? '—'); ?>
                            </code>
                        </td>
                        <td style="font-size:.8rem;white-space:nowrap;color:#64748b;">
                            <?= e(format_datetime($log['created_at'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:2rem;color:#94a3b8;">
                            <i class="fa-solid fa-list" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                            لا توجد سجلات مطابقة.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ترقيم الصفحات -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div style="display:flex;justify-content:center;gap:.5rem;padding:1rem;align-items:center;flex-wrap:wrap;">
            <?php if ($pagination['has_prev']): ?>
                <a href="?page=activity_logs&p=<?= $pagination['prev']; ?>&action=<?= urlencode($filterAction); ?>&q=<?= urlencode($search); ?>&date_from=<?= urlencode($dateFrom); ?>&date_to=<?= urlencode($dateTo); ?>" class="btn btn-sm btn-muted">
                    <i class="fa-solid fa-angle-right"></i>
                </a>
            <?php endif; ?>
            <span style="font-size:.85rem;color:#374151;padding:0 .5rem;">
                صفحة <?= $pagination['current_page']; ?> من <?= $pagination['total_pages']; ?>
                &nbsp;|&nbsp; إجمالي <?= number_format($totalRows); ?> سجل
            </span>
            <?php if ($pagination['has_next']): ?>
                <a href="?page=activity_logs&p=<?= $pagination['next']; ?>&action=<?= urlencode($filterAction); ?>&q=<?= urlencode($search); ?>&date_from=<?= urlencode($dateFrom); ?>&date_to=<?= urlencode($dateTo); ?>" class="btn btn-sm btn-muted">
                    <i class="fa-solid fa-angle-left"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </article>
</section>
