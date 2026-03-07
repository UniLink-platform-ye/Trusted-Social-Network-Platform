<?php

declare(strict_types=1);

require_permission('roles.view');

$pageScripts[] = 'permissions.js';

// الأدوار من ENUM — محددة مسبقاً في 01_schema.sql
$roles           = get_all_roles();            // ['admin'=>'مدير النظام', ...]
$permissionsMap  = get_all_permissions_map();  // من rbac.php

// بناء مصفوفة الصلاحيات من الثوابت
$matrix = [];
foreach (ROLE_PERMISSIONS as $roleKey => $permKeys) {
    foreach ($permKeys as $pKey) {
        $matrix[$pKey][$roleKey] = true;
    }
}

// ملخص عدد الصلاحيات لكل دور
$roleSummaries = [];
foreach ($roles as $roleKey => $roleLabel) {
    $roleSummaries[] = [
        'role_name'         => $roleKey,
        'display_name'      => $roleLabel,
        'total_permissions' => count(ROLE_PERMISSIONS[$roleKey] ?? []),
    ];
}

// آخر تغييرات الصلاحيات من audit_logs
$recentChanges = db()->query(
    "SELECT al.log_id, al.action AS action_type, al.description, al.created_at, u.full_name
     FROM audit_logs al
     LEFT JOIN users u ON u.user_id = al.user_id
     WHERE al.action = 'permission_change'
     ORDER BY al.created_at DESC
     LIMIT 10"
)->fetchAll();

$initialRoleKey         = array_key_first($roles);
$initialRolePermissions = [];
foreach ((ROLE_PERMISSIONS[$initialRoleKey] ?? []) as $pKey) {
    if (isset($permissionsMap[$pKey])) {
        $initialRolePermissions[] = ['permission_key' => $pKey] + $permissionsMap[$pKey];
    }
}

?>
<section class="page-block reveal" id="permissionsPage" data-endpoint="<?= e(admin_url('ajax/permissions.php')); ?>">
    <div class="section-head">
        <div>
            <h2>Role-Based Access Control (RBAC)</h2>
            <p>Manage permissions matrix and role capabilities from one centralized screen.</p>
        </div>
        <div class="quick-actions">
            <button class="btn btn-primary" data-modal-open="createRoleModal"><i class="fa-solid fa-plus"></i> Create Role</button>
            <button class="btn btn-secondary" id="openEditRole"><i class="fa-regular fa-pen-to-square"></i> Edit Selected Role</button>
        </div>
    </div>

    <article class="soft-card" style="margin-bottom: 14px;">
        <p class="inline-note">
            Governance note: every role and permission change is written to activity logs for traceability.
        </p>
    </article>

    <div class="role-cards reveal" id="roleCards">
        <?php foreach ($roleSummaries as $summary): ?>
            <article class="role-card <?= $initialRole && (int) $initialRole['role_id'] === (int) $summary['role_id'] ? 'active' : ''; ?>"
                     data-role-id="<?= e((string) $summary['role_id']); ?>"
                     data-role-name="<?= e($summary['role_name']); ?>"
                     data-role-display="<?= e($summary['display_name']); ?>">
                <h3><?= e($summary['display_name']); ?> <span class="badge <?= e(role_badge_class($summary['role_name'])); ?>"><?= e($summary['role_name']); ?></span></h3>
                <p>Assigned permissions: <strong><?= e((string) $summary['total_permissions']); ?></strong></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="page-block grid" style="grid-template-columns: 1.8fr 1fr;">
    <article class="table-wrap reveal">
        <div class="table-scroll">
            <table>
                <thead>
                <tr>
                    <th>Permission Key</th>
                    <th>English Label</th>
                    <th>Arabic Label</th>
                    <?php foreach ($roles as $role): ?>
                        <th><?= e($role['display_name']); ?><br><small><?= e($role['role_name']); ?></small></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($permissions as $permission): ?>
                    <tr>
                        <td><strong><?= e($permission['permission_key']); ?></strong></td>
                        <td><?= e($permission['label_en']); ?></td>
                        <td><?= e($permission['label_ar']); ?></td>
                        <?php foreach ($roles as $role): ?>
                            <?php $isChecked = !empty($matrix[(int) $permission['permission_id']][(int) $role['role_id']]); ?>
                            <td>
                                <input
                                    type="checkbox"
                                    class="toggle-switch permission-toggle"
                                    data-role-id="<?= e((string) $role['role_id']); ?>"
                                    data-permission-id="<?= e((string) $permission['permission_id']); ?>"
                                    <?= $isChecked ? 'checked' : ''; ?>
                                >
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <aside class="soft-card reveal" id="roleDetailsPanel">
        <div class="section-head">
            <div>
                <h2>Role Details</h2>
                <p id="roleDetailsTitle"><?= e($initialRole['display_name'] ?? 'Select a role'); ?></p>
            </div>
        </div>
        <div class="permission-tag-list" id="rolePermissionList">
            <?php foreach ($initialRolePermissions as $permission): ?>
                <span class="permission-tag" title="<?= e($permission['label_ar']); ?>">
                    <?= e($permission['permission_key']); ?>
                </span>
            <?php endforeach; ?>
        </div>
        <?php if (!$initialRolePermissions): ?>
            <p class="inline-note" id="rolePermissionEmpty">No permissions assigned yet.</p>
        <?php else: ?>
            <p class="inline-note" id="rolePermissionEmpty" style="display:none;">No permissions assigned yet.</p>
        <?php endif; ?>

        <hr style="border:none;border-top:1px solid #e1ebf6;margin:14px 0;">
        <div class="section-head">
            <div>
                <h2>Recent Permission Changes</h2>
            </div>
        </div>
        <div class="timeline-list">
            <?php if ($recentChanges): ?>
                <?php foreach ($recentChanges as $log): ?>
                    <div class="timeline-item">
                        <h4><?= e($log['action_type']); ?></h4>
                        <p><?= e($log['description']); ?></p>
                        <p><?= e($log['full_name'] ?? 'System'); ?> | <?= e(format_datetime($log['created_at'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="inline-note">No permission changes logged yet.</p>
            <?php endif; ?>
        </div>
    </aside>
</section>

<div class="modal-overlay" id="createRoleModal">
    <div class="modal-card sm">
        <div class="modal-head">
            <h3>Create Custom Role</h3>
            <button class="icon-btn" type="button" data-modal-close="createRoleModal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="createRoleForm">
            <div class="modal-body">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="create_role">
                <div>
                    <label class="form-label">Role key (english slug)</label>
                    <input type="text" name="role_name" class="form-control" required placeholder="moderator_assistant">
                </div>
                <div>
                    <label class="form-label">Display name</label>
                    <input type="text" name="display_name" class="form-control" required placeholder="Moderator Assistant">
                </div>
                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Short role description"></textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-muted" data-modal-close="createRoleModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Role</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editRoleModal">
    <div class="modal-card sm">
        <div class="modal-head">
            <h3>Edit Role</h3>
            <button class="icon-btn" type="button" data-modal-close="editRoleModal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="editRoleForm">
            <div class="modal-body">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="role_id" id="edit_role_id">
                <div>
                    <label class="form-label">Role key (slug)</label>
                    <input type="text" name="role_name" id="edit_role_name" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Display name</label>
                    <input type="text" name="display_name" id="edit_role_display" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_role_description" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-muted" data-modal-close="editRoleModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Role</button>
            </div>
        </form>
    </div>
</div>
