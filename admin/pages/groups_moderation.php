<?php

declare(strict_types=1);

require_permission('groups.manage');

$pageScripts[] = 'groups.js';

// ── فلترة وبحث ────────────────────────────────────────────────────────────────
$filterType    = query_value('type');
$filterPrivacy = query_value('privacy');
$search        = query_value('q');
$page          = max(1, (int) ($_GET['p'] ?? 1));
$perPage       = 15;
$offset        = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($filterType !== '' && $filterType !== 'all') {
    $where[]           = 'g.type = :type';
    $params[':type']   = $filterType;
}
if ($filterPrivacy !== '' && $filterPrivacy !== 'all') {
    $where[]               = 'g.privacy = :privacy';
    $params[':privacy']    = $filterPrivacy;
}
if ($search !== '') {
    $where[]           = '(g.group_name LIKE :q OR g.description LIKE :q OR creator.full_name LIKE :q)';
    $params[':q']      = '%' . $search . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare(
    "SELECT COUNT(*) FROM `groups` g
     LEFT JOIN users creator ON creator.user_id = g.created_by
     $whereSql"
);
$countStmt->execute($params);
$totalRows  = (int) $countStmt->fetchColumn();
$pagination = build_pagination($totalRows, $page, $perPage);

$groupsStmt = db()->prepare(
    "SELECT g.group_id, g.group_name, g.description, g.type, g.privacy,
            g.members_count, g.cover_url, g.created_at, g.updated_at,
            creator.user_id AS creator_id,
            creator.full_name AS creator_name,
            creator.role AS creator_role,
            (SELECT COUNT(*) FROM posts p WHERE p.group_id = g.group_id) AS posts_count
     FROM `groups` g
     LEFT JOIN users creator ON creator.user_id = g.created_by
     $whereSql
     ORDER BY g.created_at DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) {
    $groupsStmt->bindValue($k, $v);
}
$groupsStmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$groupsStmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$groupsStmt->execute();
$groups = $groupsStmt->fetchAll();

// إحصائيات
$totalGroups = (int) db()->query("SELECT COUNT(*) FROM `groups`")->fetchColumn();
$byTypeStmt  = db()->query("SELECT type, COUNT(*) AS total FROM `groups` GROUP BY type ORDER BY total DESC");
$byType      = [];
foreach ($byTypeStmt->fetchAll() as $row) {
    $byType[$row['type']] = (int) $row['total'];
}

$groupTypes   = ['course','department','activity','administrative'];
$typeLabels   = ['course'=>'مقرر دراسي','department'=>'قسم','activity'=>'نشاط','administrative'=>'إداري'];
$typeIcons    = ['course'=>'fa-book','department'=>'fa-building','activity'=>'fa-bolt','administrative'=>'fa-shield-halved'];
$privacyTypes = ['public','private','restricted'];
$privacyLabels = ['public'=>'عام','private'=>'خاص','restricted'=>'مقيد'];
$privacyIcons  = ['public'=>'fa-globe','private'=>'fa-lock','restricted'=>'fa-user-lock'];
?>

<section class="page-block reveal">
    <div class="section-head">
        <div>
            <h2>Groups Moderation <small style="font-size:.75rem;font-weight:500;color:#64748b">إشراف على المجموعات</small></h2>
            <p>إدارة ومراقبة مجموعات المنصة الأكاديمية.</p>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="grid" style="grid-template-columns: repeat(4, 1fr);gap:.75rem;margin-bottom:1.25rem;">
        <article class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-people-group"></i></div>
            <div class="kpi-content">
                <h3>إجمالي المجموعات</h3>
                <div class="value counter" data-target="<?= $totalGroups; ?>">0</div>
                <p>جميع المجموعات</p>
            </div>
        </article>
        <?php foreach (array_slice($groupTypes, 0, 3) as $t): ?>
        <article class="kpi-card" style="cursor:pointer;" onclick="window.location='?page=groups_moderation&type=<?= e($t); ?>'">
            <div class="kpi-icon"><i class="fa-solid <?= e($typeIcons[$t]); ?>"></i></div>
            <div class="kpi-content">
                <h3><?= e($typeLabels[$t]); ?></h3>
                <div class="value counter" data-target="<?= $byType[$t] ?? 0; ?>">0</div>
                <p>من نوع <?= e($t); ?></p>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- فلاتر -->
    <form method="get" action="" class="filter-bar" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.25rem;">
        <input type="hidden" name="page" value="groups_moderation">
        <input type="text" name="q" value="<?= e($search); ?>"
               placeholder="بحث باسم المجموعة أو الوصف أو المنشئ..."
               class="form-control" style="flex:1;min-width:220px;">
        <select name="type" class="form-control" style="min-width:160px;">
            <option value="all" <?= selected($filterType,'all'); ?>>كل الأنواع</option>
            <?php foreach ($groupTypes as $t): ?>
                <option value="<?= e($t); ?>" <?= selected($filterType,$t); ?>><?= e($typeLabels[$t]); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="privacy" class="form-control" style="min-width:140px;">
            <option value="all" <?= selected($filterPrivacy,'all'); ?>>كل الخصوصية</option>
            <?php foreach ($privacyTypes as $p): ?>
                <option value="<?= e($p); ?>" <?= selected($filterPrivacy,$p); ?>><?= e($privacyLabels[$p]); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-info"><i class="fa-solid fa-filter"></i> تصفية</button>
        <?php if ($search !== '' || ($filterType !== '' && $filterType !== 'all') || ($filterPrivacy !== '' && $filterPrivacy !== 'all')): ?>
            <a href="<?= e(admin_url('index.php?page=groups_moderation')); ?>" class="btn btn-muted">
                <i class="fa-solid fa-xmark"></i> مسح
            </a>
        <?php endif; ?>
    </form>

    <!-- جدول المجموعات -->
    <article class="table-wrap reveal">
        <div class="table-scroll">
            <table id="groupsTable">
                <thead>
                <tr>
                    <th>#</th>
                    <th>المجموعة</th>
                    <th>النوع</th>
                    <th>الخصوصية</th>
                    <th>المنشئ</th>
                    <th>الأعضاء</th>
                    <th>المنشورات</th>
                    <th>تاريخ الإنشاء</th>
                    <th>الإجراءات</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($groups): ?>
                    <?php foreach ($groups as $g): ?>
                    <tr id="group-row-<?= (int) $g['group_id']; ?>">
                        <td><?= (int) $g['group_id']; ?></td>
                        <td>
                            <strong style="display:block;font-size:.88rem;"><?= e($g['group_name']); ?></strong>
                            <?php if ($g['description']): ?>
                                <small style="color:#64748b;"><?= e(mb_substr($g['description'], 0, 55)); ?><?= mb_strlen($g['description']) > 55 ? '…' : ''; ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-info" style="font-size:.72rem;white-space:nowrap;">
                                <i class="fa-solid <?= e($typeIcons[$g['type']] ?? 'fa-users'); ?>"></i>
                                <?= e($typeLabels[$g['type']] ?? $g['type']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-muted" style="font-size:.72rem;">
                                <i class="fa-solid <?= e($privacyIcons[$g['privacy']] ?? 'fa-globe'); ?>"></i>
                                <?= e($privacyLabels[$g['privacy']] ?? $g['privacy']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($g['creator_id']): ?>
                                <a href="<?= e(admin_url('index.php?page=user_details&uid=' . (int)$g['creator_id'])); ?>"
                                   style="color:#2563eb;text-decoration:none;font-size:.85rem;">
                                    <?= e($g['creator_name'] ?? '—'); ?>
                                </a>
                                <br>
                                <span class="badge <?= e(role_badge_class($g['creator_role'] ?? 'student')); ?>" style="font-size:.65rem;">
                                    <?= e($g['creator_role'] ?? ''); ?>
                                </span>
                            <?php else: ?>
                                <span style="color:#94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;">
                            <strong><?= (int) $g['members_count']; ?></strong>
                        </td>
                        <td style="text-align:center;">
                            <strong><?= (int) $g['posts_count']; ?></strong>
                        </td>
                        <td style="font-size:.8rem;white-space:nowrap;"><?= e(format_datetime($g['created_at'])); ?></td>
                        <td>
                            <div style="display:flex;gap:.35rem;">
                                <button class="btn btn-sm btn-info btn-view-group"
                                        data-id="<?= (int) $g['group_id']; ?>"
                                        data-name="<?= e($g['group_name']); ?>"
                                        data-description="<?= e($g['description'] ?? ''); ?>"
                                        data-type="<?= e($g['type']); ?>"
                                        data-privacy="<?= e($g['privacy']); ?>"
                                        data-creator="<?= e($g['creator_name'] ?? '—'); ?>"
                                        data-members="<?= (int) $g['members_count']; ?>"
                                        data-posts="<?= (int) $g['posts_count']; ?>"
                                        title="عرض التفاصيل">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-delete-group"
                                        data-id="<?= (int) $g['group_id']; ?>"
                                        data-name="<?= e($g['group_name']); ?>"
                                        title="حذف المجموعة">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center;padding:2rem;color:#94a3b8;">
                            <i class="fa-solid fa-people-group" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                            لا توجد مجموعات مطابقة.
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
                <a href="?page=groups_moderation&p=<?= $pagination['prev']; ?>&type=<?= urlencode($filterType); ?>&privacy=<?= urlencode($filterPrivacy); ?>&q=<?= urlencode($search); ?>" class="btn btn-sm btn-muted">
                    <i class="fa-solid fa-angle-right"></i>
                </a>
            <?php endif; ?>
            <span style="font-size:.85rem;color:#374151;padding:0 .5rem;">
                صفحة <?= $pagination['current_page']; ?> من <?= $pagination['total_pages']; ?>
                &nbsp;|&nbsp; إجمالي <?= $totalRows; ?> مجموعة
            </span>
            <?php if ($pagination['has_next']): ?>
                <a href="?page=groups_moderation&p=<?= $pagination['next']; ?>&type=<?= urlencode($filterType); ?>&privacy=<?= urlencode($filterPrivacy); ?>&q=<?= urlencode($search); ?>" class="btn btn-sm btn-muted">
                    <i class="fa-solid fa-angle-left"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </article>
</section>

<!-- Modal: تفاصيل المجموعة -->
<div class="modal-overlay" id="viewGroupModal">
    <div class="modal-card">
        <div class="modal-head">
            <h3>تفاصيل المجموعة</h3>
            <button class="icon-btn" type="button" data-modal-close="viewGroupModal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                <div>
                    <label class="form-label" style="color:#64748b;font-size:.75rem;">اسم المجموعة</label>
                    <p id="modal-group-name" style="font-weight:700;margin:0;font-size:1rem;"></p>
                </div>
                <div>
                    <label class="form-label" style="color:#64748b;font-size:.75rem;">المنشئ</label>
                    <p id="modal-group-creator" style="font-weight:600;margin:0;"></p>
                </div>
                <div>
                    <label class="form-label" style="color:#64748b;font-size:.75rem;">النوع</label>
                    <p id="modal-group-type" style="margin:0;"></p>
                </div>
                <div>
                    <label class="form-label" style="color:#64748b;font-size:.75rem;">الخصوصية</label>
                    <p id="modal-group-privacy" style="margin:0;"></p>
                </div>
                <div>
                    <label class="form-label" style="color:#64748b;font-size:.75rem;">الأعضاء</label>
                    <p id="modal-group-members" style="margin:0;font-weight:600;"></p>
                </div>
                <div>
                    <label class="form-label" style="color:#64748b;font-size:.75rem;">المنشورات</label>
                    <p id="modal-group-posts" style="margin:0;font-weight:600;"></p>
                </div>
            </div>
            <div style="margin-top:.75rem;">
                <label class="form-label" style="color:#64748b;font-size:.75rem;">الوصف</label>
                <p id="modal-group-description" style="background:#f8fafc;padding:.75rem;border-radius:.5rem;font-size:.88rem;color:#374151;margin:0;"></p>
            </div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-muted" data-modal-close="viewGroupModal">إغلاق</button>
            <button type="button" class="btn btn-danger" id="modalDeleteGroupBtn">
                <i class="fa-solid fa-trash"></i> حذف المجموعة
            </button>
        </div>
    </div>
</div>
