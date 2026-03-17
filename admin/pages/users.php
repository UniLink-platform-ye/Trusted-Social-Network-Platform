<?php

declare(strict_types=1);

require_permission('users.view');

$pageScripts[] = 'users.js';

// ── فلترة وبحث ───────────────────────────────────────────────────────────
$search      = query_value('q');
$filterRole  = query_value('role');
$filterStatus= query_value('status');
$page        = max(1, (int) ($_GET['p'] ?? 1));
$perPage     = 15;
$offset      = ($page - 1) * $perPage;

// ── بناء الاستعلام ────────────────────────────────────────────────────────
$where  = ['u.status != \'deleted\''];
$params = [];

if ($search !== '') {
    $where[]             = '(u.full_name LIKE :q OR u.email LIKE :q OR u.username LIKE :q OR u.academic_id LIKE :q)';
    $params[':q']        = '%' . $search . '%';
}
if ($filterRole !== '') {
    $where[]             = 'u.role = :role';
    $params[':role']     = $filterRole;
}
if ($filterStatus !== '' && $filterStatus !== 'all') {
    $where[]             = 'u.status = :status';
    $params[':status']   = $filterStatus;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$totalRows = (int) db()->prepare("SELECT COUNT(*) FROM users u $whereSql")
    ->execute($params) ? db()->prepare("SELECT COUNT(*) FROM users u $whereSql") : 0;

$countStmt = db()->prepare("SELECT COUNT(*) FROM users u $whereSql");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();

$pagination = build_pagination($totalRows, $page, $perPage);

$usersStmt = db()->prepare(
    "SELECT u.user_id, u.username, u.full_name, u.email,
            u.role, u.department, u.academic_id,
            u.is_verified, u.status, u.avatar_url,
            u.last_login, u.created_at
     FROM users u
     $whereSql
     ORDER BY u.created_at DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) {
    $usersStmt->bindValue($k, $v);
}
$usersStmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$usersStmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$usersStmt->execute();
$users = $usersStmt->fetchAll();

$roleLabels  = get_all_roles();
$roleOptions = [''=>'كل الأدوار'] + $roleLabels;
?>

<section class="page-block reveal">
    <div class="section-head">
        <div>
            <h2>User Management <small style="font-size:.75rem;font-weight:500;color:#64748b">إدارة المستخدمين</small></h2>
            <p>عرض وإدارة حسابات المستخدمين — الطلاب، الأساتذة، والمشرفين.</p>
        </div>
        <?php if (user_can('users.create')): ?>
        <div class="quick-actions">
            <button class="btn btn-primary" data-modal-open="createUserModal">
                <i class="fa-solid fa-user-plus"></i> إضافة مستخدم
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- فلاتر البحث -->
    <form method="get" action="" id="filterForm" class="filter-bar" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.25rem;">
        <input type="hidden" name="page" value="users">
        <input type="text" name="q" value="<?= e($search); ?>"
               placeholder="بحث بالاسم أو البريد أو الرقم الأكاديمي..."
               class="form-control" style="flex:1;min-width:220px;">
        <select name="role" class="form-control" style="min-width:150px;">
            <?php foreach ($roleOptions as $k => $v): ?>
                <option value="<?= e($k); ?>" <?= selected($filterRole, $k); ?>><?= e($v); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="form-control" style="min-width:140px;">
            <option value="all" <?= selected($filterStatus,'all'); ?>>كل الحالات</option>
            <option value="active"    <?= selected($filterStatus,'active'); ?>>نشط</option>
            <option value="suspended" <?= selected($filterStatus,'suspended'); ?>>موقوف</option>
        </select>
        <button type="submit" class="btn btn-info"><i class="fa-solid fa-filter"></i> تصفية</button>
        <?php if ($search !== '' || $filterRole !== '' || $filterStatus !== ''): ?>
            <a href="<?= e(admin_url('index.php?page=users')); ?>" class="btn btn-muted">
                <i class="fa-solid fa-xmark"></i> مسح
            </a>
        <?php endif; ?>
    </form>

    <!-- جدول المستخدمين -->
    <article class="table-wrap reveal">
        <div class="table-scroll">
            <table id="usersTable">
                <thead>
                <tr>
                    <th>#</th>
                    <th>المستخدم</th>
                    <th>البريد الإلكتروني</th>
                    <th>الدور</th>
                    <th>القسم</th>
                    <th>الحالة</th>
                    <th>آخر دخول</th>
                    <th>الإجراءات</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($users): ?>
                    <?php foreach ($users as $u): ?>
                    <tr id="user-row-<?= (int) $u['user_id']; ?>">
                        <td><?= (int) $u['user_id']; ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:.6rem;">
                                <div class="user-avatar-sm" style="
                                    width:36px;height:36px;border-radius:50%;
                                    background:<?= e(role_color($u['role'])); ?>;
                                    display:flex;align-items:center;justify-content:center;
                                    color:#fff;font-weight:700;font-size:.85rem;flex-shrink:0;">
                                    <?= e(mb_strtoupper(mb_substr($u['full_name'], 0, 1))); ?>
                                </div>
                                <div>
                                    <strong style="display:block;font-size:.88rem;"><?= e($u['full_name']); ?></strong>
                                    <small style="color:#64748b;">@<?= e($u['username']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= e($u['email']); ?></td>
                        <td>
                            <span class="badge <?= e(role_badge_class($u['role'])); ?>">
                                <?= e($roleLabels[$u['role']] ?? $u['role']); ?>
                            </span>
                        </td>
                        <td><?= e($u['department'] ?? '-'); ?></td>
                        <td>
                            <span class="badge <?= e(status_badge_class($u['status'])); ?>">
                                <?= e(status_label($u['status'])); ?>
                            </span>
                        </td>
                        <td><?= e($u['last_login'] ? format_datetime($u['last_login']) : 'لم يدخل'); ?></td>
                        <td>
                            <div style="display:flex;gap:.4rem;">
                                <button class="btn btn-sm btn-info btn-view-user"
                                        data-id="<?= (int) $u['user_id']; ?>"
                                        data-name="<?= e($u['full_name']); ?>"
                                        title="عرض التفاصيل">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <?php if (user_can('users.edit')): ?>
                                <button class="btn btn-sm btn-secondary btn-edit-user"
                                        data-id="<?= (int) $u['user_id']; ?>"
                                        data-name="<?= e($u['full_name']); ?>"
                                        data-email="<?= e($u['email']); ?>"
                                        data-role="<?= e($u['role']); ?>"
                                        data-department="<?= e($u['department'] ?? ''); ?>"
                                        data-status="<?= e($u['status']); ?>"
                                        title="تعديل">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (user_can('users.suspend') && $u['status'] === 'active'): ?>
                                <button class="btn btn-sm btn-danger btn-suspend-user"
                                        data-id="<?= (int) $u['user_id']; ?>"
                                        data-name="<?= e($u['full_name']); ?>"
                                        title="تعليق الحساب">
                                    <i class="fa-solid fa-user-slash"></i>
                                </button>
                                <?php elseif (user_can('users.suspend') && $u['status'] === 'suspended'): ?>
                                <button class="btn btn-sm btn-success btn-activate-user"
                                        data-id="<?= (int) $u['user_id']; ?>"
                                        data-name="<?= e($u['full_name']); ?>"
                                        title="تفعيل الحساب">
                                    <i class="fa-solid fa-user-check"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (current_user()['role'] === 'admin' && $u['user_id'] != current_user()['user_id']): ?>
                                <button class="btn btn-sm btn-danger btn-delete-user"
                                        data-id="<?= (int) $u['user_id']; ?>"
                                        data-name="<?= e($u['full_name']); ?>"
                                        title="حذف الحساب نهائياً">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:2rem;color:#94a3b8;">
                            <i class="fa-solid fa-users" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                            لا توجد نتائج مطابقة للبحث.
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
                <a href="?page=users&p=<?= $pagination['prev']; ?>&q=<?= urlencode($search); ?>&role=<?= urlencode($filterRole); ?>&status=<?= urlencode($filterStatus); ?>" class="btn btn-sm btn-muted">
                    <i class="fa-solid fa-angle-right"></i>
                </a>
            <?php endif; ?>
            <span style="font-size:.85rem;color:#374151;padding:0 .5rem;">
                صفحة <?= $pagination['current_page']; ?> من <?= $pagination['total_pages']; ?>
                &nbsp;|&nbsp; إجمالي <?= $totalRows; ?> مستخدم
            </span>
            <?php if ($pagination['has_next']): ?>
                <a href="?page=users&p=<?= $pagination['next']; ?>&q=<?= urlencode($search); ?>&role=<?= urlencode($filterRole); ?>&status=<?= urlencode($filterStatus); ?>" class="btn btn-sm btn-muted">
                    <i class="fa-solid fa-angle-left"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </article>
</section>

<!-- Modal: إضافة مستخدم -->
<?php if (user_can('users.create')): ?>
<div class="modal-overlay" id="createUserModal">
    <div class="modal-card sm">
        <div class="modal-head">
            <h3>إضافة مستخدم جديد</h3>
            <button class="icon-btn" type="button" data-modal-close="createUserModal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="createUserForm">
            <div class="modal-body" style="display:flex;flex-direction:column;gap:.9rem;">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="create_user">
                <div>
                    <label class="form-label">الاسم الكامل *</label>
                    <input type="text" name="full_name" class="form-control" required placeholder="أحمد محمد الأحمد">
                </div>
                <div>
                    <label class="form-label">اسم المستخدم *</label>
                    <input type="text" name="username" class="form-control" required placeholder="ahmed.ahmad">
                </div>
                <div>
                    <label class="form-label">البريد الإلكتروني *</label>
                    <input type="email" name="email" class="form-control" required placeholder="ahmed@unilink.edu">
                </div>
                <div>
                    <label class="form-label">كلمة المرور *</label>
                    <input type="password" name="password" class="form-control" required placeholder="8 أحرف على الأقل">
                </div>
                <div>
                    <label class="form-label">الدور *</label>
                    <select name="role" class="form-control" required>
                        <?php foreach ($roleLabels as $k => $v): ?>
                            <option value="<?= e($k); ?>"><?= e($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">القسم</label>
                    <input type="text" name="department" class="form-control" placeholder="Computer Science">
                </div>
                <div>
                    <label class="form-label">الرقم الأكاديمي / الوظيفي</label>
                    <input type="text" name="academic_id" class="form-control" placeholder="STU-2024-001">
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-muted" data-modal-close="createUserModal">إلغاء</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-user-plus"></i> إضافة
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal: تعديل مستخدم -->
<?php if (user_can('users.edit')): ?>
<div class="modal-overlay" id="editUserModal">
    <div class="modal-card sm">
        <div class="modal-head">
            <h3>تعديل بيانات المستخدم</h3>
            <button class="icon-btn" type="button" data-modal-close="editUserModal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="editUserForm">
            <div class="modal-body" style="display:flex;flex-direction:column;gap:.9rem;">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div>
                    <label class="form-label">الاسم الكامل *</label>
                    <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">البريد الإلكتروني *</label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">الدور *</label>
                    <select name="role" id="edit_role" class="form-control" required>
                        <?php foreach ($roleLabels as $k => $v): ?>
                            <option value="<?= e($k); ?>"><?= e($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">القسم</label>
                    <input type="text" name="department" id="edit_department" class="form-control">
                </div>
                <div>
                    <label class="form-label">كلمة مرور جديدة <small>(اتركها فارغة لعدم التغيير)</small></label>
                    <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-muted" data-modal-close="editUserModal">إلغاء</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-regular fa-floppy-disk"></i> حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
