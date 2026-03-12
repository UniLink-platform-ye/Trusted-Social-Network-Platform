<?php

declare(strict_types=1);

require_permission('users.view');

$userId = (int) ($_GET['uid'] ?? 0);
if ($userId <= 0) {
    redirect(admin_url('index.php?page=users'));
}

// جلب بيانات المستخدم
$userStmt = db()->prepare(
    "SELECT u.user_id, u.username, u.full_name, u.email, u.role,
            u.department, u.academic_id, u.avatar_url,
            u.is_verified, u.status, u.last_login, u.created_at, u.updated_at
     FROM users u
     WHERE u.user_id = :id AND u.status != 'deleted'
     LIMIT 1"
);
$userStmt->execute([':id' => $userId]);
$userDetail = $userStmt->fetch();

if (!$userDetail) {
    redirect(admin_url('index.php?page=users'));
}

$roleLabels = get_all_roles();

// إحصائيات المستخدم
$postsCount   = (int) db()->prepare("SELECT COUNT(*) FROM posts WHERE user_id = :id")->execute([':id'=>$userId]) ?
    (function() use ($userId) {
        $s = db()->prepare("SELECT COUNT(*) FROM posts WHERE user_id = :id");
        $s->execute([':id'=>$userId]);
        return (int)$s->fetchColumn();
    })() : 0;

$postsStmt = db()->prepare("SELECT COUNT(*) FROM posts WHERE user_id = :id");
$postsStmt->execute([':id' => $userId]);
$postsCount = (int) $postsStmt->fetchColumn();

$reportsGivenStmt = db()->prepare("SELECT COUNT(*) FROM reports WHERE reporter_id = :id");
$reportsGivenStmt->execute([':id' => $userId]);
$reportsGiven = (int) $reportsGivenStmt->fetchColumn();

$reportsReceivedStmt = db()->prepare("SELECT COUNT(*) FROM reports WHERE reported_user_id = :id");
$reportsReceivedStmt->execute([':id' => $userId]);
$reportsReceived = (int) $reportsReceivedStmt->fetchColumn();

$groupsStmt = db()->prepare("SELECT COUNT(*) FROM group_members WHERE user_id = :id");
$groupsStmt->execute([':id' => $userId]);
$groupsCount = (int) $groupsStmt->fetchColumn();

// آخر منشورات المستخدم
$recentPostsStmt = db()->prepare(
    "SELECT p.post_id, p.content, p.type, p.visibility,
            p.likes_count, p.comments_count, p.is_flagged, p.created_at,
            g.group_name
     FROM posts p
     LEFT JOIN `groups` g ON g.group_id = p.group_id
     WHERE p.user_id = :id
     ORDER BY p.created_at DESC
     LIMIT 5"
);
$recentPostsStmt->execute([':id' => $userId]);
$recentPosts = $recentPostsStmt->fetchAll();

// سجل نشاط المستخدم
$activityStmt = db()->prepare(
    "SELECT log_id, action, description, ip_address, created_at
     FROM audit_logs
     WHERE user_id = :id
     ORDER BY created_at DESC
     LIMIT 10"
);
$activityStmt->execute([':id' => $userId]);
$userActivity = $activityStmt->fetchAll();

// البلاغات المرتبطة بالمستخدم
$reportsStmt = db()->prepare(
    "SELECT r.report_id, r.reason, r.status, r.details, r.created_at,
            rep.full_name AS reporter_name
     FROM reports r
     LEFT JOIN users rep ON rep.user_id = r.reporter_id
     WHERE r.reported_user_id = :id
     ORDER BY r.created_at DESC
     LIMIT 5"
);
$reportsStmt->execute([':id' => $userId]);
$userReports = $reportsStmt->fetchAll();

// المجموعات التي ينتمي إليها
$userGroupsStmt = db()->prepare(
    "SELECT g.group_id, g.group_name, g.type, g.privacy,
            gm.member_role, gm.joined_at
     FROM group_members gm
     JOIN `groups` g ON g.group_id = gm.group_id
     WHERE gm.user_id = :id
     ORDER BY gm.joined_at DESC
     LIMIT 5"
);
$userGroupsStmt->execute([':id' => $userId]);
$userGroups = $userGroupsStmt->fetchAll();

$initials = mb_strtoupper(mb_substr($userDetail['full_name'], 0, 2));
$avatarColor = role_color($userDetail['role']);
?>

<!-- رابط العودة -->
<section class="page-block reveal">
    <div style="margin-bottom: 1rem;">
        <a href="<?= e(admin_url('index.php?page=users')); ?>" class="btn btn-muted" style="font-size:.85rem;">
            <i class="fa-solid fa-arrow-right"></i> العودة إلى إدارة المستخدمين
        </a>
    </div>

    <!-- بطاقة ملف المستخدم -->
    <div class="soft-card" style="display:flex;align-items:flex-start;gap:1.5rem;flex-wrap:wrap;">
        <div style="width:80px;height:80px;border-radius:50%;background:<?= e($avatarColor); ?>;
                    display:flex;align-items:center;justify-content:center;
                    color:#fff;font-weight:700;font-size:1.6rem;flex-shrink:0;">
            <?= e($initials); ?>
        </div>
        <div style="flex:1;min-width:200px;">
            <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:.35rem;">
                <h2 style="margin:0;font-size:1.3rem;"><?= e($userDetail['full_name']); ?></h2>
                <span class="badge <?= e(role_badge_class($userDetail['role'])); ?>">
                    <?= e($roleLabels[$userDetail['role']] ?? $userDetail['role']); ?>
                </span>
                <span class="badge <?= e(status_badge_class($userDetail['status'])); ?>">
                    <?= e(status_label($userDetail['status'])); ?>
                </span>
                <?php if ($userDetail['is_verified']): ?>
                    <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> موثق</span>
                <?php endif; ?>
            </div>
            <p style="color:#64748b;margin:0 0 .5rem;">@<?= e($userDetail['username']); ?> — <?= e($userDetail['email']); ?></p>
            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;font-size:.85rem;color:#475569;">
                <?php if ($userDetail['department']): ?>
                    <span><i class="fa-solid fa-building"></i> <?= e($userDetail['department']); ?></span>
                <?php endif; ?>
                <?php if ($userDetail['academic_id']): ?>
                    <span><i class="fa-solid fa-id-card"></i> <?= e($userDetail['academic_id']); ?></span>
                <?php endif; ?>
                <span><i class="fa-solid fa-calendar-plus"></i> انضم: <?= e(format_datetime($userDetail['created_at'])); ?></span>
                <span><i class="fa-solid fa-right-to-bracket"></i> آخر دخول: <?= e($userDetail['last_login'] ? format_datetime($userDetail['last_login']) : 'لم يدخل بعد'); ?></span>
            </div>
        </div>
        <!-- أزرار الإجراءات -->
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-self:flex-start;">
            <?php if (user_can('users.edit')): ?>
                <button class="btn btn-secondary btn-sm btn-edit-user"
                        data-id="<?= (int) $userDetail['user_id']; ?>"
                        data-name="<?= e($userDetail['full_name']); ?>"
                        data-email="<?= e($userDetail['email']); ?>"
                        data-role="<?= e($userDetail['role']); ?>"
                        data-department="<?= e($userDetail['department'] ?? ''); ?>"
                        data-status="<?= e($userDetail['status']); ?>">
                    <i class="fa-regular fa-pen-to-square"></i> تعديل
                </button>
            <?php endif; ?>
            <?php if (user_can('users.suspend') && $userDetail['status'] === 'active'): ?>
                <button class="btn btn-danger btn-sm btn-suspend-user"
                        data-id="<?= (int) $userDetail['user_id']; ?>"
                        data-name="<?= e($userDetail['full_name']); ?>">
                    <i class="fa-solid fa-user-slash"></i> تعليق
                </button>
            <?php elseif (user_can('users.suspend') && $userDetail['status'] === 'suspended'): ?>
                <button class="btn btn-success btn-sm btn-activate-user"
                        data-id="<?= (int) $userDetail['user_id']; ?>"
                        data-name="<?= e($userDetail['full_name']); ?>">
                    <i class="fa-solid fa-user-check"></i> تفعيل
                </button>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- إحصائيات سريعة -->
<section class="page-block reveal">
    <div class="grid kpi-grid" style="grid-template-columns: repeat(4, 1fr);">
        <article class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-file-lines"></i></div>
            <div class="kpi-content">
                <h3>المنشورات</h3>
                <div class="value counter" data-target="<?= e((string) $postsCount); ?>">0</div>
                <p>إجمالي المنشورات</p>
            </div>
        </article>
        <article class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-people-group"></i></div>
            <div class="kpi-content">
                <h3>المجموعات</h3>
                <div class="value counter" data-target="<?= e((string) $groupsCount); ?>">0</div>
                <p>المجموعات المنضم إليها</p>
            </div>
        </article>
        <article class="kpi-card">
            <div class="kpi-icon warning"><i class="fa-solid fa-flag"></i></div>
            <div class="kpi-content">
                <h3>بلاغات مرسلة</h3>
                <div class="value counter" data-target="<?= e((string) $reportsGiven); ?>">0</div>
                <p>بلاغات قدّمها المستخدم</p>
            </div>
        </article>
        <article class="kpi-card">
            <div class="kpi-icon danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="kpi-content">
                <h3>بلاغات واردة</h3>
                <div class="value counter" data-target="<?= e((string) $reportsReceived); ?>">0</div>
                <p>بلاغات ضد هذا المستخدم</p>
            </div>
        </article>
    </div>
</section>

<!-- المنشورات الأخيرة، البلاغات، المجموعات -->
<section class="page-block grid reveal" style="grid-template-columns: 1.3fr 1fr;">
    <!-- المنشورات الأخيرة -->
    <article class="soft-card">
        <div class="section-head">
            <div>
                <h2>آخر المنشورات</h2>
                <p>أحدث 5 منشورات للمستخدم</p>
            </div>
        </div>
        <?php if ($recentPosts): ?>
            <div class="timeline-list">
                <?php foreach ($recentPosts as $post): ?>
                    <div class="timeline-item">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;">
                            <div style="flex:1;">
                                <span class="badge badge-muted" style="font-size:.7rem;margin-bottom:.25rem;">
                                    <?= e($post['type']); ?>
                                    <?php if ($post['group_name']): ?>
                                        — <?= e($post['group_name']); ?>
                                    <?php endif; ?>
                                </span>
                                <p style="margin:.25rem 0;font-size:.88rem;color:#374151;">
                                    <?= e(mb_substr($post['content'], 0, 100)); ?><?= mb_strlen($post['content']) > 100 ? '…' : ''; ?>
                                </p>
                                <small style="color:#94a3b8;">
                                    <?= e(format_datetime($post['created_at'])); ?>
                                    &nbsp;·&nbsp; <i class="fa-solid fa-heart"></i> <?= (int)$post['likes_count']; ?>
                                    &nbsp;·&nbsp; <i class="fa-solid fa-comment"></i> <?= (int)$post['comments_count']; ?>
                                    <?php if ($post['is_flagged']): ?>
                                        &nbsp;·&nbsp; <span style="color:#ef4444;"><i class="fa-solid fa-flag"></i> مُبلَّغ عنه</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?php $title = 'لا توجد منشورات بعد'; $description = 'لم يقم هذا المستخدم بأي نشر.'; require __DIR__ . '/../partials/empty-state.php'; ?>
        <?php endif; ?>
    </article>

    <!-- البلاغات الواردة -->
    <article class="soft-card">
        <div class="section-head">
            <div>
                <h2>البلاغات الواردة</h2>
                <p>بلاغات مقدمة ضد هذا المستخدم</p>
            </div>
        </div>
        <?php if ($userReports): ?>
            <div class="timeline-list">
                <?php foreach ($userReports as $rep): ?>
                    <div class="timeline-item">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.25rem;">
                            <strong style="font-size:.85rem;"><?= e($rep['reason']); ?></strong>
                            <span class="badge <?= e(status_badge_class($rep['status'])); ?>" style="font-size:.7rem;">
                                <?= e(status_label($rep['status'])); ?>
                            </span>
                        </div>
                        <?php if ($rep['details']): ?>
                            <p style="font-size:.82rem;color:#374151;margin:.2rem 0;"><?= e(mb_substr($rep['details'], 0, 80)); ?></p>
                        <?php endif; ?>
                        <small style="color:#94a3b8;">
                            من: <?= e($rep['reporter_name'] ?? 'مجهول'); ?> | <?= e(format_datetime($rep['created_at'])); ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?php $title = 'لا توجد بلاغات'; $description = 'لا يوجد أي بلاغ ضد هذا المستخدم.'; require __DIR__ . '/../partials/empty-state.php'; ?>
        <?php endif; ?>
    </article>
</section>

<!-- المجموعات وسجل النشاط -->
<section class="page-block grid reveal" style="grid-template-columns: 1fr 1.2fr;">
    <!-- المجموعات -->
    <article class="soft-card">
        <div class="section-head">
            <div>
                <h2>المجموعات</h2>
                <p>المجموعات التي ينتمي إليها</p>
            </div>
        </div>
        <?php if ($userGroups): ?>
            <div class="progress-list">
                <?php foreach ($userGroups as $g): ?>
                    <div class="progress-item" style="flex-direction:column;align-items:flex-start;gap:.2rem;">
                        <div style="display:flex;justify-content:space-between;width:100%;align-items:center;">
                            <strong style="font-size:.85rem;"><?= e($g['group_name']); ?></strong>
                            <span class="badge badge-muted" style="font-size:.7rem;"><?= e($g['member_role']); ?></span>
                        </div>
                        <small style="color:#94a3b8;"><?= e($g['type']); ?> · <?= e($g['privacy']); ?> · <?= e(format_datetime($g['joined_at'])); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?php $title = 'لا توجد مجموعات'; $description = 'لم ينضم هذا المستخدم لأي مجموعة.'; require __DIR__ . '/../partials/empty-state.php'; ?>
        <?php endif; ?>
    </article>

    <!-- سجل النشاط -->
    <article class="timeline reveal">
        <div class="section-head">
            <div>
                <h2>سجل النشاط</h2>
                <p>آخر 10 إجراءات للمستخدم</p>
            </div>
        </div>
        <?php if ($userActivity): ?>
            <div class="timeline-list">
                <?php foreach ($userActivity as $log): ?>
                    <div class="timeline-item">
                        <h4><?= e($log['action']); ?></h4>
                        <?php if ($log['description']): ?>
                            <p><?= e($log['description']); ?></p>
                        <?php endif; ?>
                        <p>
                            <i class="fa-solid fa-location-dot" style="color:#94a3b8;"></i> <?= e($log['ip_address'] ?? 'N/A'); ?>
                            &nbsp;|&nbsp; <?= e(format_datetime($log['created_at'])); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?php $title = 'لا يوجد نشاط مسجل'; $description = 'لم يتم تسجيل أي نشاط لهذا المستخدم.'; require __DIR__ . '/../partials/empty-state.php'; ?>
        <?php endif; ?>
    </article>
</section>

<!-- Modal: تعديل مستخدم (مشترك مع users.php) -->
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
                <input type="hidden" name="user_id" id="edit_user_id" value="<?= (int) $userDetail['user_id']; ?>">
                <div>
                    <label class="form-label">الاسم الكامل *</label>
                    <input type="text" name="full_name" id="edit_full_name" class="form-control" required value="<?= e($userDetail['full_name']); ?>">
                </div>
                <div>
                    <label class="form-label">البريد الإلكتروني *</label>
                    <input type="email" name="email" id="edit_email" class="form-control" required value="<?= e($userDetail['email']); ?>">
                </div>
                <div>
                    <label class="form-label">الدور *</label>
                    <select name="role" id="edit_role" class="form-control" required>
                        <?php foreach (get_all_roles() as $k => $v): ?>
                            <option value="<?= e($k); ?>" <?= $userDetail['role'] === $k ? 'selected' : ''; ?>><?= e($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">القسم</label>
                    <input type="text" name="department" id="edit_department" class="form-control" value="<?= e($userDetail['department'] ?? ''); ?>">
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
<?php
$pageScripts[] = 'users.js';
?>
