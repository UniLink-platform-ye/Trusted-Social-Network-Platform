<?php

declare(strict_types=1);

require_permission('reports.view');

$pageScripts[] = 'reports.js';

// ── فلترة وبحث ───────────────────────────────────────────────────────────────
$filterStatus = query_value('status');
$filterReason = query_value('reason');
$search       = query_value('q');
$page         = max(1, (int) ($_GET['p'] ?? 1));
$perPage      = 15;
$offset       = ($page - 1) * $perPage;

// ── بناء الاستعلام ────────────────────────────────────────────────────────────
$where  = [];
$params = [];

if ($filterStatus !== '' && $filterStatus !== 'all') {
    $where[]             = 'r.status = :status';
    $params[':status']   = $filterStatus;
}
if ($filterReason !== '' && $filterReason !== 'all') {
    $where[]             = 'r.reason = :reason';
    $params[':reason']   = $filterReason;
}
if ($search !== '') {
    $where[]             = '(reporter.full_name LIKE :q OR reported.full_name LIKE :q OR r.details LIKE :q)';
    $params[':q']        = '%' . $search . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare(
    "SELECT COUNT(*) FROM reports r
     LEFT JOIN users reporter ON reporter.user_id = r.reporter_id
     LEFT JOIN users reported ON reported.user_id = r.reported_user_id
     $whereSql"
);
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$pagination = build_pagination($totalRows, $page, $perPage);

$reportsStmt = db()->prepare(
    "SELECT r.report_id, r.reason, r.details, r.status, r.action_taken,
            r.created_at, r.updated_at,
            reporter.user_id   AS reporter_id,
            reporter.full_name AS reporter_name,
            reporter.email     AS reporter_email,
            reported.user_id   AS reported_id,
            reported.full_name AS reported_name,
            reported.role      AS reported_role,
            handler.full_name  AS handler_name,
            p.post_id, p.content AS post_content
     FROM reports r
     LEFT JOIN users reporter ON reporter.user_id = r.reporter_id
     LEFT JOIN users reported ON reported.user_id = r.reported_user_id
     LEFT JOIN users handler  ON handler.user_id  = r.handled_by
     LEFT JOIN posts p        ON p.post_id        = r.post_id
     $whereSql
     ORDER BY
         FIELD(r.status,'pending','under_review','resolved','rejected'),
         r.created_at DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) {
    $reportsStmt->bindValue($k, $v);
}
$reportsStmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$reportsStmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$reportsStmt->execute();
$reports = $reportsStmt->fetchAll();

// إحصائيات سريعة
$summaryStmt = db()->query(
    "SELECT status, COUNT(*) AS total FROM reports GROUP BY status ORDER BY FIELD(status,'pending','under_review','resolved','rejected')"
);
$summary = [];
foreach ($summaryStmt->fetchAll() as $row) {
    $summary[$row['status']] = (int) $row['total'];
}

$reasons = ['spam','harassment','inappropriate_content','misinformation','copyright_violation','other'];
$statuses = ['pending','under_review','resolved','rejected'];
?>

<section class="page-block reveal">
    <div class="section-head">
        <div>
            <h2>Reports Moderation <small style="font-size:.75rem;font-weight:500;color:#64748b">إدارة البلاغات</small></h2>
            <p>مراجعة ومعالجة البلاغات الواردة من المستخدمين.</p>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="grid" style="grid-template-columns: repeat(4, 1fr);gap:.75rem;margin-bottom:1.25rem;">
        <?php
        $statusColors = ['pending'=>'warning','under_review'=>'info','resolved'=>'success','rejected'=>'danger'];
        $statusLabels = ['pending'=>'معلق','under_review'=>'قيد المراجعة','resolved'=>'محلول','rejected'=>'مرفوض'];
        $statusIcons  = ['pending'=>'fa-clock','under_review'=>'fa-spinner','resolved'=>'fa-check-circle','rejected'=>'fa-times-circle'];
        foreach ($statuses as $st):
            $cnt = $summary[$st] ?? 0;
        ?>
        <article class="kpi-card" style="cursor:pointer;" onclick="window.location='?page=reports&status=<?= $st ?>'">
            <div class="kpi-icon <?= e($statusColors[$st] ?? ''); ?>">
                <i class="fa-solid <?= e($statusIcons[$st]); ?>"></i>
            </div>
            <div class="kpi-content">
                <h3><?= e($statusLabels[$st]); ?></h3>
                <div class="value counter" data-target="<?= $cnt; ?>">0</div>
                <p><?= ucfirst(str_replace('_',' ',$st)); ?></p>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- فلاتر -->
    <form method="get" action="" class="filter-bar" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.25rem;">
        <input type="hidden" name="page" value="reports">
        <input type="text" name="q" value="<?= e($search); ?>"
               placeholder="بحث بالاسم أو التفاصيل..."
               class="form-control" style="flex:1;min-width:200px;">
        <select name="status" class="form-control" style="min-width:160px;">
            <option value="all" <?= selected($filterStatus,'all'); ?>>كل الحالات</option>
            <?php foreach ($statuses as $st): ?>
                <option value="<?= e($st); ?>" <?= selected($filterStatus,$st); ?>><?= e($statusLabels[$st]); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="reason" class="form-control" style="min-width:180px;">
            <option value="all" <?= selected($filterReason,'all'); ?>>كل الأسباب</option>
            <?php foreach ($reasons as $r): ?>
                <option value="<?= e($r); ?>" <?= selected($filterReason,$r); ?>><?= e($r); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-info"><i class="fa-solid fa-filter"></i> تصفية</button>
        <?php if ($search !== '' || ($filterStatus !== '' && $filterStatus !== 'all') || ($filterReason !== '' && $filterReason !== 'all')): ?>
            <a href="<?= e(admin_url('index.php?page=reports')); ?>" class="btn btn-muted">
                <i class="fa-solid fa-xmark"></i> مسح
            </a>
        <?php endif; ?>
    </form>

    <!-- جدول البلاغات -->
    <article class="table-wrap reveal">
        <div class="table-scroll">
            <table id="reportsTable">
                <thead>
                <tr>
                    <th>#</th>
                    <th>المُبلِّغ</th>
                    <th>المُبلَّغ عنه</th>
                    <th>السبب</th>
                    <th>الحالة</th>
                    <th>التفاصيل</th>
                    <th>التاريخ</th>
                    <?php if (user_can('reports.review')): ?><th>الإجراءات</th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php if ($reports): ?>
                    <?php foreach ($reports as $rep): ?>
                    <tr id="report-row-<?= (int) $rep['report_id']; ?>">
                        <td><?= (int) $rep['report_id']; ?></td>
                        <td>
                            <?php if ($rep['reporter_name']): ?>
                                <a href="<?= e(admin_url('index.php?page=user_details&uid=' . (int)$rep['reporter_id'])); ?>"
                                   style="color:#2563eb;text-decoration:none;font-size:.85rem;">
                                    <?= e($rep['reporter_name']); ?>
                                </a>
                            <?php else: ?>
                                <span style="color:#94a3b8;">مجهول</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($rep['reported_name']): ?>
                                <div style="display:flex;align-items:center;gap:.4rem;">
                                    <a href="<?= e(admin_url('index.php?page=user_details&uid=' . (int)$rep['reported_id'])); ?>"
                                       style="color:#2563eb;text-decoration:none;font-size:.85rem;">
                                        <?= e($rep['reported_name']); ?>
                                    </a>
                                    <?php if ($rep['reported_role']): ?>
                                        <span class="badge <?= e(role_badge_class($rep['reported_role'])); ?>" style="font-size:.65rem;">
                                            <?= e($rep['reported_role']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($rep['post_content']): ?>
                                <span style="color:#64748b;font-size:.82rem;">منشور: <?= e(mb_substr($rep['post_content'], 0, 40)); ?>…</span>
                            <?php else: ?>
                                <span style="color:#94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-warning" style="font-size:.72rem;white-space:nowrap;">
                                <?= e($rep['reason']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= e(status_badge_class($rep['status'])); ?>">
                                <?= e(status_label($rep['status'])); ?>
                            </span>
                        </td>
                        <td style="max-width:200px;font-size:.82rem;color:#374151;">
                            <?= $rep['details'] ? e(mb_substr($rep['details'], 0, 60)) . (mb_strlen($rep['details']) > 60 ? '…' : '') : '<span style="color:#94a3b8;">—</span>'; ?>
                        </td>
                        <td style="font-size:.8rem;white-space:nowrap;"><?= e(format_datetime($rep['created_at'])); ?></td>
                        <?php if (user_can('reports.review')): ?>
                        <td>
                            <div style="display:flex;gap:.35rem;">
                                <button class="btn btn-sm btn-info btn-view-report"
                                        data-id="<?= (int) $rep['report_id']; ?>"
                                        data-reporter="<?= e($rep['reporter_name'] ?? 'مجهول'); ?>"
                                        data-reported="<?= e($rep['reported_name'] ?? '—'); ?>"
                                        data-reason="<?= e($rep['reason']); ?>"
                                        data-status="<?= e($rep['status']); ?>"
                                        data-details="<?= e($rep['details'] ?? ''); ?>"
                                        data-action="<?= e($rep['action_taken'] ?? ''); ?>"
                                        data-handler="<?= e($rep['handler_name'] ?? ''); ?>"
                                        title="عرض التفاصيل">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <?php if ($rep['status'] === 'pending' || $rep['status'] === 'under_review'): ?>
                                    <?php if (user_can('reports.resolve')): ?>
                                    <button class="btn btn-sm btn-success btn-resolve-report"
                                            data-id="<?= (int) $rep['report_id']; ?>"
                                            title="حل البلاغ">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-reject-report"
                                            data-id="<?= (int) $rep['report_id']; ?>"
                                            title="رفض البلاغ">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:2rem;color:#94a3b8;">
                            <i class="fa-solid fa-flag" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                            لا توجد بلاغات مطابقة.
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
                <a href="?page=reports&p=<?= $pagination['prev']; ?>&status=<?= urlencode($filterStatus); ?>&reason=<?= urlencode($filterReason); ?>&q=<?= urlencode($search); ?>" class="btn btn-sm btn-muted">
                    <i class="fa-solid fa-angle-right"></i>
                </a>
            <?php endif; ?>
            <span style="font-size:.85rem;color:#374151;padding:0 .5rem;">
                صفحة <?= $pagination['current_page']; ?> من <?= $pagination['total_pages']; ?>
                &nbsp;|&nbsp; إجمالي <?= $totalRows; ?> بلاغ
            </span>
            <?php if ($pagination['has_next']): ?>
                <a href="?page=reports&p=<?= $pagination['next']; ?>&status=<?= urlencode($filterStatus); ?>&reason=<?= urlencode($filterReason); ?>&q=<?= urlencode($search); ?>" class="btn btn-sm btn-muted">
                    <i class="fa-solid fa-angle-left"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </article>
</section>

<!-- Modal: تفاصيل البلاغ -->
<div class="modal-overlay" id="viewReportModal">
    <div class="modal-card">
        <div class="modal-head">
            <h3>تفاصيل البلاغ <span id="modal-report-id" style="color:#64748b;font-size:.85rem;"></span></h3>
            <button class="icon-btn" type="button" data-modal-close="viewReportModal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1rem;">
                <div>
                    <label class="form-label" style="color:#64748b;font-size:.75rem;">المُبلِّغ</label>
                    <p id="modal-reporter" style="font-weight:600;margin:0;"></p>
                </div>
                <div>
                    <label class="form-label" style="color:#64748b;font-size:.75rem;">المُبلَّغ عنه</label>
                    <p id="modal-reported" style="font-weight:600;margin:0;"></p>
                </div>
                <div>
                    <label class="form-label" style="color:#64748b;font-size:.75rem;">السبب</label>
                    <p id="modal-reason" style="margin:0;"></p>
                </div>
                <div>
                    <label class="form-label" style="color:#64748b;font-size:.75rem;">الحالة</label>
                    <p id="modal-status" style="margin:0;"></p>
                </div>
            </div>
            <div style="margin-bottom:.75rem;">
                <label class="form-label" style="color:#64748b;font-size:.75rem;">التفاصيل</label>
                <p id="modal-details" style="background:#f8fafc;padding:.75rem;border-radius:.5rem;font-size:.88rem;color:#374151;margin:0;"></p>
            </div>
            <div id="modal-action-row" style="display:none;">
                <label class="form-label" style="color:#64748b;font-size:.75rem;">الإجراء المتخذ</label>
                <p id="modal-action" style="margin:0;font-size:.88rem;"></p>
                <p id="modal-handler" style="margin:.25rem 0 0;font-size:.8rem;color:#64748b;"></p>
            </div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-muted" data-modal-close="viewReportModal">إغلاق</button>
            <?php if (user_can('reports.resolve')): ?>
            <button type="button" class="btn btn-success" id="modalResolveBtn" style="display:none;">
                <i class="fa-solid fa-check"></i> حل البلاغ
            </button>
            <button type="button" class="btn btn-danger" id="modalRejectBtn" style="display:none;">
                <i class="fa-solid fa-xmark"></i> رفض البلاغ
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>
