<?php

declare(strict_types=1);

require_permission('content.view');

$pageScripts[] = 'content.js';

// ── فلترة وبحث ────────────────────────────────────────────────────────────────
$filterType    = query_value('type');
$filterFlag    = query_value('flagged');
$search        = query_value('q');
$page          = max(1, (int) ($_GET['p'] ?? 1));
$perPage       = 15;
$offset        = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($filterType !== '' && $filterType !== 'all') {
    $where[]           = 'p.type = :type';
    $params[':type']   = $filterType;
}
if ($filterFlag === '1') {
    $where[] = 'p.is_flagged = 1';
}
if ($search !== '') {
    $where[]         = '(p.content LIKE :q OR u.full_name LIKE :q OR u.username LIKE :q)';
    $params[':q']    = '%' . $search . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare(
    "SELECT COUNT(*) FROM posts p
     LEFT JOIN users u ON u.user_id = p.user_id
     $whereSql"
);
$countStmt->execute($params);
$totalRows  = (int) $countStmt->fetchColumn();
$pagination = build_pagination($totalRows, $page, $perPage);

$postsStmt = db()->prepare(
    "SELECT p.post_id, p.content, p.type, p.visibility,
            p.likes_count, p.comments_count, p.is_flagged,
            p.created_at, p.updated_at,
            u.user_id, u.full_name AS author_name,
            u.username, u.role AS author_role,
            g.group_name
     FROM posts p
     LEFT JOIN users u  ON u.user_id  = p.user_id
     LEFT JOIN `groups` g ON g.group_id = p.group_id
     $whereSql
     ORDER BY p.is_flagged DESC, p.created_at DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) {
    $postsStmt->bindValue($k, $v);
}
$postsStmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$postsStmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$postsStmt->execute();
$posts = $postsStmt->fetchAll();

// إحصائيات
$totalPosts   = (int) db()->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$flaggedPosts = (int) db()->query("SELECT COUNT(*) FROM posts WHERE is_flagged = 1")->fetchColumn();
$byTypeStmt   = db()->query("SELECT type, COUNT(*) AS total FROM posts GROUP BY type ORDER BY total DESC");
$byType       = $byTypeStmt->fetchAll();

$postTypes = ['post','announcement','question','lecture'];
$typeLabels = ['post'=>'منشور','announcement'=>'إعلان','question'=>'سؤال','lecture'=>'محاضرة'];
$typeIcons  = ['post'=>'fa-file-lines','announcement'=>'fa-bullhorn','question'=>'fa-circle-question','lecture'=>'fa-chalkboard-user'];
?>

<section class="page-block reveal">
    <div class="section-head">
        <div>
            <h2>Content Moderation <small style="font-size:.75rem;font-weight:500;color:#64748b">إشراف المحتوى</small></h2>
            <p>مراجعة المنشورات وإدارة المحتوى المبلغ عنه أو المخالف للسياسات.</p>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="grid kpi-grid" style="grid-template-columns: repeat(4, 1fr);margin-bottom:1.25rem;">
        <article class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-file-lines"></i></div>
            <div class="kpi-content">
                <h3>إجمالي المنشورات</h3>
                <div class="value counter" data-target="<?= $totalPosts; ?>">0</div>
                <p>كل المحتوى في المنصة</p>
            </div>
        </article>
        <article class="kpi-card">
            <div class="kpi-icon danger"><i class="fa-solid fa-flag"></i></div>
            <div class="kpi-content">
                <h3>مبلغ عنها</h3>
                <div class="value counter" data-target="<?= $flaggedPosts; ?>">0</div>
                <p>تحتاج مراجعة فورية</p>
            </div>
        </article>
        <?php foreach (array_slice($byType, 0, 2) as $bt): ?>
        <article class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid <?= e($typeIcons[$bt['type']] ?? 'fa-file'); ?>"></i></div>
            <div class="kpi-content">
                <h3><?= e($typeLabels[$bt['type']] ?? $bt['type']); ?></h3>
                <div class="value counter" data-target="<?= (int)$bt['total']; ?>">0</div>
                <p>من نوع <?= e($bt['type']); ?></p>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- فلاتر -->
    <form method="get" action="" class="filter-bar" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.25rem;">
        <input type="hidden" name="page" value="content_moderation">
        <input type="text" name="q" value="<?= e($search); ?>"
               placeholder="بحث في المحتوى أو اسم المستخدم..."
               class="form-control" style="flex:1;min-width:220px;">
        <select name="type" class="form-control" style="min-width:160px;">
            <option value="all" <?= selected($filterType,'all'); ?>>كل الأنواع</option>
            <?php foreach ($postTypes as $t): ?>
                <option value="<?= e($t); ?>" <?= selected($filterType,$t); ?>><?= e($typeLabels[$t]); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="flagged" class="form-control" style="min-width:160px;">
            <option value="" <?= selected($filterFlag,''); ?>>كل المنشورات</option>
            <option value="1" <?= selected($filterFlag,'1'); ?>>المبلغ عنها فقط</option>
        </select>
        <button type="submit" class="btn btn-info"><i class="fa-solid fa-filter"></i> تصفية</button>
        <?php if ($search !== '' || ($filterType !== '' && $filterType !== 'all') || $filterFlag !== ''): ?>
            <a href="<?= e(admin_url('index.php?page=content_moderation')); ?>" class="btn btn-muted">
                <i class="fa-solid fa-xmark"></i> مسح
            </a>
        <?php endif; ?>
    </form>

    <!-- جدول المنشورات -->
    <article class="table-wrap reveal">
        <div class="table-scroll">
            <table id="contentTable">
                <thead>
                <tr>
                    <th>#</th>
                    <th>المحتوى</th>
                    <th>الكاتب</th>
                    <th>النوع</th>
                    <th>المجموعة</th>
                    <th>التفاعل</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <?php if (user_can('content.delete')): ?><th>الإجراءات</th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php if ($posts): ?>
                    <?php foreach ($posts as $post): ?>
                    <tr id="content-row-<?= (int) $post['post_id']; ?>" <?= $post['is_flagged'] ? 'style="background:rgba(239,68,68,.04);"' : ''; ?>>
                        <td><?= (int) $post['post_id']; ?></td>
                        <td style="max-width:260px;">
                            <p style="margin:0;font-size:.85rem;color:#374151;line-height:1.5;">
                                <?= e(mb_substr($post['content'], 0, 100)); ?><?= mb_strlen($post['content']) > 100 ? '…' : ''; ?>
                            </p>
                        </td>
                        <td>
                            <?php if ($post['user_id']): ?>
                                <a href="<?= e(admin_url('index.php?page=user_details&uid=' . (int)$post['user_id'])); ?>"
                                   style="color:#2563eb;text-decoration:none;font-size:.85rem;">
                                    <?= e($post['author_name'] ?? '—'); ?>
                                </a>
                                <br><small style="color:#94a3b8;">@<?= e($post['username'] ?? ''); ?></small>
                            <?php else: ?>
                                <span style="color:#94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-muted" style="font-size:.72rem;">
                                <i class="fa-solid <?= e($typeIcons[$post['type']] ?? 'fa-file'); ?>"></i>
                                <?= e($typeLabels[$post['type']] ?? $post['type']); ?>
                            </span>
                        </td>
                        <td style="font-size:.82rem;color:#64748b;">
                            <?= $post['group_name'] ? e($post['group_name']) : '<span style="color:#94a3b8;">عام</span>'; ?>
                        </td>
                        <td style="font-size:.82rem;white-space:nowrap;">
                            <span><i class="fa-solid fa-heart" style="color:#ef4444;"></i> <?= (int)$post['likes_count']; ?></span>
                            &nbsp;
                            <span><i class="fa-solid fa-comment" style="color:#3b82f6;"></i> <?= (int)$post['comments_count']; ?></span>
                        </td>
                        <td>
                            <?php if ($post['is_flagged']): ?>
                                <span class="badge badge-danger" style="font-size:.72rem;">
                                    <i class="fa-solid fa-flag"></i> مُبلَّغ عنه
                                </span>
                            <?php else: ?>
                                <span class="badge badge-success" style="font-size:.72rem;">سليم</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.8rem;white-space:nowrap;"><?= e(format_datetime($post['created_at'])); ?></td>
                        <?php if (user_can('content.delete')): ?>
                        <td>
                            <div style="display:flex;gap:.35rem;">
                                <button class="btn btn-sm btn-info btn-view-content"
                                        data-id="<?= (int) $post['post_id']; ?>"
                                        data-content="<?= e($post['content']); ?>"
                                        data-author="<?= e($post['author_name'] ?? '—'); ?>"
                                        data-type="<?= e($post['type']); ?>"
                                        title="عرض كامل">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-delete-content"
                                        data-id="<?= (int) $post['post_id']; ?>"
                                        title="حذف المنشور">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center;padding:2rem;color:#94a3b8;">
                            <i class="fa-solid fa-file-circle-check" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                            لا يوجد محتوى مطابق.
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
                <a href="?page=content_moderation&p=<?= $pagination['prev']; ?>&type=<?= urlencode($filterType); ?>&flagged=<?= urlencode($filterFlag); ?>&q=<?= urlencode($search); ?>" class="btn btn-sm btn-muted">
                    <i class="fa-solid fa-angle-right"></i>
                </a>
            <?php endif; ?>
            <span style="font-size:.85rem;color:#374151;padding:0 .5rem;">
                صفحة <?= $pagination['current_page']; ?> من <?= $pagination['total_pages']; ?>
                &nbsp;|&nbsp; إجمالي <?= $totalRows; ?> منشور
            </span>
            <?php if ($pagination['has_next']): ?>
                <a href="?page=content_moderation&p=<?= $pagination['next']; ?>&type=<?= urlencode($filterType); ?>&flagged=<?= urlencode($filterFlag); ?>&q=<?= urlencode($search); ?>" class="btn btn-sm btn-muted">
                    <i class="fa-solid fa-angle-left"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </article>
</section>

<!-- Modal: عرض المحتوى الكامل -->
<div class="modal-overlay" id="viewContentModal">
    <div class="modal-card">
        <div class="modal-head">
            <h3>عرض المنشور <span id="modal-content-id" style="color:#64748b;font-size:.85rem;"></span></h3>
            <button class="icon-btn" type="button" data-modal-close="viewContentModal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <div style="margin-bottom:.75rem;">
                <label class="form-label" style="color:#64748b;font-size:.75rem;">الكاتب</label>
                <p id="modal-content-author" style="font-weight:600;margin:0;"></p>
            </div>
            <div style="margin-bottom:.75rem;">
                <label class="form-label" style="color:#64748b;font-size:.75rem;">النوع</label>
                <p id="modal-content-type" style="margin:0;"></p>
            </div>
            <div>
                <label class="form-label" style="color:#64748b;font-size:.75rem;">المحتوى الكامل</label>
                <div id="modal-content-body" style="background:#f8fafc;padding:1rem;border-radius:.5rem;font-size:.9rem;color:#374151;line-height:1.7;white-space:pre-wrap;max-height:300px;overflow-y:auto;"></div>
            </div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-muted" data-modal-close="viewContentModal">إغلاق</button>
            <?php if (user_can('content.delete')): ?>
            <button type="button" class="btn btn-danger" id="modalDeleteContentBtn">
                <i class="fa-solid fa-trash"></i> حذف المنشور
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>
