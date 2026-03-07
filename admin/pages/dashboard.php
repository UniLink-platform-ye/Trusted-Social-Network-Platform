<?php

declare(strict_types=1);

$stats = [
    'total_users'      => 0,
    'active_users'     => 0,
    'suspended_users'  => 0,
    'total_reports'    => 0,
    'pending_reports'  => 0,
    'resolved_reports' => 0,
    'total_posts'      => 0,
    'total_groups'     => 0,
];

$stats['total_users']      = (int) db()->query("SELECT COUNT(*) FROM users WHERE status <> 'deleted'")->fetchColumn();
$stats['active_users']     = (int) db()->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$stats['suspended_users']  = (int) db()->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn();
$stats['total_reports']    = (int) db()->query('SELECT COUNT(*) FROM reports')->fetchColumn();
$stats['pending_reports']  = (int) db()->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
$stats['resolved_reports'] = (int) db()->query("SELECT COUNT(*) FROM reports WHERE status = 'resolved'")->fetchColumn();
$stats['total_posts']      = (int) db()->query('SELECT COUNT(*) FROM posts')->fetchColumn();
$stats['total_groups']     = (int) db()->query('SELECT COUNT(*) FROM `groups`')->fetchColumn();

// توزيع المستخدمين — يعتمد على ENUM role في 01_schema.sql
$usersByRoleStmt = db()->query(
    "SELECT role, COUNT(*) AS total
     FROM users
     WHERE status <> 'deleted'
     GROUP BY role
     ORDER BY FIELD(role,'admin','supervisor','professor','student')"
);
$usersByRole = $usersByRoleStmt->fetchAll();
$roleLabels  = ['admin' => 'مدير النظام', 'supervisor' => 'مشرف', 'professor' => 'أستاذ', 'student' => 'طالب'];
foreach ($usersByRole as &$row) {
    $row['display_name'] = $roleLabels[$row['role']] ?? $row['role'];
    $row['role_name']    = $row['role'];
}
unset($row);

$reportsByStatusStmt = db()->query(
    "SELECT status, COUNT(*) AS total FROM reports
     GROUP BY status
     ORDER BY FIELD(status,'pending','under_review','resolved','rejected')"
);
$reportsByStatus = $reportsByStatusStmt->fetchAll();

$accountsByStatusStmt = db()->query(
    "SELECT status, COUNT(*) AS total FROM users
     GROUP BY status
     ORDER BY FIELD(status,'active','suspended','deleted')"
);
$accountsByStatus = $accountsByStatusStmt->fetchAll();

// audit_logs هو اسم الجدول المعتمد في 01_schema.sql
$recentActivityStmt = db()->query(
    'SELECT al.log_id AS id, al.action AS action_type,
            al.description, al.created_at,
            u.full_name, u.email
     FROM audit_logs al
     LEFT JOIN users u ON u.user_id = al.user_id
     ORDER BY al.created_at DESC
     LIMIT 10'
);
$recentActivities = $recentActivityStmt->fetchAll();

$maxRoleCount = max(1, ...array_map(static fn($item) => (int) $item['total'], $usersByRole ?: [['total' => 1]]));
$maxReportsCount = max(1, ...array_map(static fn($item) => (int) $item['total'], $reportsByStatus ?: [['total' => 1]]));
$maxStatusCount = max(1, ...array_map(static fn($item) => (int) $item['total'], $accountsByStatus ?: [['total' => 1]]));
?>
<section class="page-block reveal">
    <div class="section-head">
        <div>
            <h2>Executive KPI Snapshot</h2>
            <p>Real-time operational indicators for administration and moderation performance.</p>
        </div>
    </div>

    <div class="grid kpi-grid">
        <article class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-users"></i></div>
            <div class="kpi-content">
                <h3>Total Users</h3>
                <div class="value counter" data-target="<?= e((string) $stats['total_users']); ?>">0</div>
                <p>Accounts in the network</p>
            </div>
        </article>

        <article class="kpi-card">
            <div class="kpi-icon success"><i class="fa-solid fa-user-check"></i></div>
            <div class="kpi-content">
                <h3>Active Users</h3>
                <div class="value counter" data-target="<?= e((string) $stats['active_users']); ?>">0</div>
                <p>Verified and active</p>
            </div>
        </article>

        <article class="kpi-card">
            <div class="kpi-icon danger"><i class="fa-solid fa-user-slash"></i></div>
            <div class="kpi-content">
                <h3>Suspended Users</h3>
                <div class="value counter" data-target="<?= e((string) $stats['suspended_users']); ?>">0</div>
                <p>Requires moderation review</p>
            </div>
        </article>

        <article class="kpi-card">
            <div class="kpi-icon warning"><i class="fa-solid fa-flag"></i></div>
            <div class="kpi-content">
                <h3>Total Reports</h3>
                <div class="value counter" data-target="<?= e((string) $stats['total_reports']); ?>">0</div>
                <p>Complaints and misuse reports</p>
            </div>
        </article>

        <article class="kpi-card">
            <div class="kpi-icon warning"><i class="fa-regular fa-clock"></i></div>
            <div class="kpi-content">
                <h3>Pending Reports</h3>
                <div class="value counter" data-target="<?= e((string) $stats['pending_reports']); ?>">0</div>
                <p>Waiting moderation action</p>
            </div>
        </article>

        <article class="kpi-card">
            <div class="kpi-icon success"><i class="fa-solid fa-circle-check"></i></div>
            <div class="kpi-content">
                <h3>Resolved Reports</h3>
                <div class="value counter" data-target="<?= e((string) $stats['resolved_reports']); ?>">0</div>
                <p>Resolved and closed</p>
            </div>
        </article>

        <article class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-file-lines"></i></div>
            <div class="kpi-content">
                <h3>Total Posts</h3>
                <div class="value counter" data-target="<?= e((string) $stats['total_posts']); ?>">0</div>
                <p>Placeholder until content tables</p>
            </div>
        </article>

        <article class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-people-group"></i></div>
            <div class="kpi-content">
                <h3>Total Groups</h3>
                <div class="value counter" data-target="<?= e((string) $stats['total_groups']); ?>">0</div>
                <p>Placeholder until groups module</p>
            </div>
        </article>
    </div>
</section>

<section class="page-block grid" style="grid-template-columns: 1.2fr 1fr;">
    <article class="soft-card reveal">
        <div class="section-head">
            <div>
                <h2>Users by Role</h2>
                <p>Distribution across student, professor, supervisor, and admin accounts.</p>
            </div>
        </div>
        <div class="progress-list">
            <?php foreach ($usersByRole as $row): ?>
                <?php $width = (int) round(((int) $row['total'] / $maxRoleCount) * 100); ?>
                <div class="progress-item">
                    <strong><?= e($row['display_name']); ?> (<?= e($row['role_name']); ?>)</strong>
                    <span><?= e((string) $row['total']); ?></span>
                    <div class="progress-track"><div class="progress-bar" data-width="<?= e((string) $width); ?>"></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="soft-card reveal">
        <div class="section-head">
            <div>
                <h2>Reports by Status</h2>
                <p>Complaint lifecycle visibility</p>
            </div>
        </div>
        <div class="progress-list">
            <?php foreach ($reportsByStatus as $row): ?>
                <?php $width = (int) round(((int) $row['total'] / $maxReportsCount) * 100); ?>
                <div class="progress-item">
                    <strong><?= e(ucfirst($row['status'])); ?></strong>
                    <span><?= e((string) $row['total']); ?></span>
                    <div class="progress-track"><div class="progress-bar" data-width="<?= e((string) $width); ?>"></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="page-block grid" style="grid-template-columns: 1fr 1fr;">
    <article class="report-widget reveal">
        <div class="section-head">
            <div>
                <h2>Reports Overview</h2>
                <p>Quick status snapshot</p>
            </div>
        </div>
        <div class="report-filter-cards">
            <div class="filter-card">
                <strong><?= e((string) $stats['pending_reports']); ?></strong>
                <span>Pending</span>
            </div>
            <div class="filter-card">
                <strong><?= e((string) ($stats['total_reports'] - $stats['pending_reports'] - $stats['resolved_reports'])); ?></strong>
                <span>Reviewed</span>
            </div>
            <div class="filter-card">
                <strong><?= e((string) $stats['resolved_reports']); ?></strong>
                <span>Resolved</span>
            </div>
        </div>
    </article>

    <article class="soft-card reveal">
        <div class="section-head">
            <div>
                <h2>Account Status Distribution</h2>
                <p>Security and compliance snapshot</p>
            </div>
        </div>
        <div class="progress-list">
            <?php foreach ($accountsByStatus as $row): ?>
                <?php $width = (int) round(((int) $row['total'] / $maxStatusCount) * 100); ?>
                <div class="progress-item">
                    <strong><?= e(ucfirst($row['status'])); ?></strong>
                    <span><?= e((string) $row['total']); ?></span>
                    <div class="progress-track"><div class="progress-bar" data-width="<?= e((string) $width); ?>"></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="page-block grid" style="grid-template-columns: 1fr 1fr;">
    <article class="soft-card reveal">
        <div class="section-head">
            <div>
                <h2>Quick Actions</h2>
                <p>Administrative shortcuts</p>
            </div>
        </div>
        <div class="quick-actions">
            <a href="<?= e(admin_url('index.php?page=permissions')); ?>" class="btn btn-info"><i class="fa-solid fa-user-shield"></i> Open Permissions</a>
        </div>
    </article>

    <article class="timeline reveal">
        <div class="section-head">
            <div>
                <h2>Recent Activity</h2>
                <p>Latest actions in the platform</p>
            </div>
        </div>

        <?php if ($recentActivities): ?>
            <div class="timeline-list">
                <?php foreach ($recentActivities as $activity): ?>
                    <div class="timeline-item">
                        <h4><?= e($activity['action_type']); ?></h4>
                        <p><?= e($activity['description']); ?></p>
                        <p>
                            <strong><?= e($activity['full_name'] ?? 'System'); ?></strong>
                            | <?= e(format_datetime($activity['created_at'])); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?php
            $title = 'No activity entries yet';
            $description = 'Activity logs will appear after system actions.';
            require __DIR__ . '/../partials/empty-state.php';
            ?>
        <?php endif; ?>
    </article>
</section>
