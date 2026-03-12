<?php

declare(strict_types=1);

require_permission('settings.manage');

$pageScripts[] = 'settings.js';

$success = flash('settings_success');
$error   = flash('settings_error');

// إعدادات النظام المخزنة في session أو ملف config (محاكاة لأغراض العرض)
// في نظام حقيقي يتم تخزين هذه القيم في جدول settings في قاعدة البيانات
$currentUser = current_user();

// ── إحصائيات لعرضها في صفحة الإعدادات ─────────────────────────────────────
$dbInfo = [
    'platform_name'    => 'UniLink Platform',
    'platform_version' => '1.0.0',
    'db_engine'        => 'MySQL / InnoDB',
    'charset'          => 'utf8mb4',
    'php_version'      => PHP_VERSION,
    'total_users'      => (int) db()->query("SELECT COUNT(*) FROM users WHERE status != 'deleted'")->fetchColumn(),
    'total_groups'     => (int) db()->query("SELECT COUNT(*) FROM `groups`")->fetchColumn(),
    'total_posts'      => (int) db()->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'total_logs'       => (int) db()->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn(),
];

$dbSize = '—';
try {
    $sizeStmt = db()->query(
        "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
         FROM information_schema.tables
         WHERE table_schema = 'trusted_social_network_platform'"
    );
    $row = $sizeStmt->fetch();
    if ($row && $row['size_mb']) {
        $dbSize = $row['size_mb'] . ' MB';
    }
} catch (Throwable) {}
?>

<section class="page-block reveal">
    <div class="section-head">
        <div>
            <h2>Settings <small style="font-size:.75rem;font-weight:500;color:#64748b">إعدادات النظام</small></h2>
            <p>إدارة إعدادات المنصة وعرض معلومات النظام.</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="inline-note" style="background:#dcfce7;color:#166534;border-right:4px solid #16a34a;padding:.75rem 1rem;border-radius:.5rem;margin-bottom:1rem;">
            <i class="fa-solid fa-check-circle"></i> <?= e($success); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="inline-note" style="background:#fee2e2;color:#991b1b;border-right:4px solid #ef4444;padding:.75rem 1rem;border-radius:.5rem;margin-bottom:1rem;">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= e($error); ?>
        </div>
    <?php endif; ?>
</section>

<!-- قسمان: معلومات النظام والإعدادات -->
<section class="page-block grid reveal" style="grid-template-columns: 1fr 1.4fr;">

    <!-- معلومات النظام -->
    <div style="display:flex;flex-direction:column;gap:1rem;">

        <article class="soft-card">
            <div class="section-head" style="margin-bottom:.75rem;">
                <div>
                    <h2>معلومات النظام</h2>
                    <p>بيانات تقنية عن بيئة الخادم</p>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:.6rem;">
                <?php $infoRows = [
                    ['icon'=>'fa-server',       'label'=>'اسم المنصة',          'value'=>$dbInfo['platform_name']],
                    ['icon'=>'fa-tag',           'label'=>'الإصدار',            'value'=>$dbInfo['platform_version']],
                    ['icon'=>'fa-code',          'label'=>'إصدار PHP',           'value'=>$dbInfo['php_version']],
                    ['icon'=>'fa-database',      'label'=>'محرك قاعدة البيانات','value'=>$dbInfo['db_engine']],
                    ['icon'=>'fa-font',          'label'=>'Charset',             'value'=>$dbInfo['charset']],
                    ['icon'=>'fa-hard-drive',    'label'=>'حجم قاعدة البيانات', 'value'=>$dbSize],
                ]; ?>
                <?php foreach ($infoRows as $row): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid #f1f5f9;">
                    <span style="display:flex;align-items:center;gap:.5rem;color:#64748b;font-size:.85rem;">
                        <i class="fa-solid <?= e($row['icon']); ?>" style="width:16px;color:#94a3b8;"></i>
                        <?= e($row['label']); ?>
                    </span>
                    <code style="font-size:.82rem;background:#f8fafc;padding:.15rem .5rem;border-radius:.3rem;color:#374151;"><?= e($row['value']); ?></code>
                </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="soft-card">
            <div class="section-head" style="margin-bottom:.75rem;">
                <div>
                    <h2>ملخص قاعدة البيانات</h2>
                    <p>عدد السجلات في الجداول الرئيسية</p>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                <?php $dbRows = [
                    ['icon'=>'fa-users',       'label'=>'المستخدمون', 'value'=>$dbInfo['total_users'],  'color'=>'#2563eb'],
                    ['icon'=>'fa-people-group','label'=>'المجموعات',  'value'=>$dbInfo['total_groups'], 'color'=>'#7c3aed'],
                    ['icon'=>'fa-file-lines',  'label'=>'المنشورات',  'value'=>$dbInfo['total_posts'],  'color'=>'#16a34a'],
                    ['icon'=>'fa-list-check',  'label'=>'سجلات النشاط','value'=>$dbInfo['total_logs'], 'color'=>'#d97706'],
                ]; ?>
                <?php foreach ($dbRows as $r): ?>
                <div style="background:#f8fafc;border-radius:.6rem;padding:.75rem;display:flex;align-items:center;gap:.6rem;">
                    <div style="width:36px;height:36px;border-radius:50%;background:<?= e($r['color']); ?>1a;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fa-solid <?= e($r['icon']); ?>" style="color:<?= e($r['color']); ?>;font-size:.85rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:1.1rem;font-weight:700;color:#1e293b;"><?= number_format($r['value']); ?></div>
                        <div style="font-size:.75rem;color:#64748b;"><?= e($r['label']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </article>

        <!-- معلومات المدير الحالي -->
        <article class="soft-card">
            <div class="section-head" style="margin-bottom:.75rem;">
                <div><h2>المدير الحالي</h2></div>
            </div>
            <div style="display:flex;align-items:center;gap:.75rem;">
                <div style="width:48px;height:48px;border-radius:50%;background:#2563eb;
                            display:flex;align-items:center;justify-content:center;
                            color:#fff;font-weight:700;font-size:1.1rem;flex-shrink:0;">
                    <?= e(mb_strtoupper(mb_substr($currentUser['full_name'] ?? 'A', 0, 1))); ?>
                </div>
                <div>
                    <strong style="display:block;font-size:.95rem;"><?= e($currentUser['full_name'] ?? '—'); ?></strong>
                    <span style="color:#64748b;font-size:.82rem;"><?= e($currentUser['email'] ?? '—'); ?></span>
                    <br>
                    <span class="badge badge-primary" style="font-size:.65rem;margin-top:.2rem;"><?= e($currentUser['role'] ?? '—'); ?></span>
                </div>
            </div>
        </article>
    </div>

    <!-- نماذج الإعدادات -->
    <div style="display:flex;flex-direction:column;gap:1rem;">

        <!-- تغيير كلمة المرور -->
        <article class="soft-card" id="changePasswordSection">
            <div class="section-head" style="margin-bottom:.75rem;">
                <div>
                    <h2><i class="fa-solid fa-key" style="color:#d97706;"></i> تغيير كلمة المرور</h2>
                    <p>تحديث كلمة مرور حساب المدير</p>
                </div>
            </div>
            <form id="changePasswordForm">
                <?= csrf_input(); ?>
                <input type="hidden" name="action" value="change_password">
                <div style="display:flex;flex-direction:column;gap:.75rem;">
                    <div>
                        <label class="form-label">كلمة المرور الحالية *</label>
                        <input type="password" name="current_password" id="current_password"
                               class="form-control" required placeholder="••••••••">
                    </div>
                    <div>
                        <label class="form-label">كلمة المرور الجديدة *</label>
                        <input type="password" name="new_password" id="new_password"
                               class="form-control" required placeholder="8 أحرف على الأقل"
                               minlength="8">
                    </div>
                    <div>
                        <label class="form-label">تأكيد كلمة المرور الجديدة *</label>
                        <input type="password" name="confirm_password" id="confirm_password"
                               class="form-control" required placeholder="••••••••">
                    </div>
                    <div style="text-align:left;">
                        <button type="submit" class="btn btn-primary" id="changePasswordBtn">
                            <i class="fa-solid fa-key"></i> تغيير كلمة المرور
                        </button>
                    </div>
                    <div id="passwordChangeResult" style="display:none;"></div>
                </div>
            </form>
        </article>

        <!-- إعدادات الأمان -->
        <article class="soft-card">
            <div class="section-head" style="margin-bottom:.75rem;">
                <div>
                    <h2><i class="fa-solid fa-shield-halved" style="color:#2563eb;"></i> إعدادات الأمان</h2>
                    <p>سياسات الحماية والأمان المطبقة في النظام</p>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:.85rem;">
                <?php $securitySettings = [
                    ['icon'=>'fa-lock',         'label'=>'تشفير كلمات المرور',     'value'=>'Bcrypt + Salting',     'status'=>true],
                    ['icon'=>'fa-shield',       'label'=>'حماية CSRF',              'value'=>'مفعّلة - Random Token', 'status'=>true],
                    ['icon'=>'fa-code',         'label'=>'حماية XSS',               'value'=>'مفعّلة - htmlspecialchars', 'status'=>true],
                    ['icon'=>'fa-database',     'label'=>'حماية SQL Injection',     'value'=>'مفعّلة - PDO Prepared',  'status'=>true],
                    ['icon'=>'fa-id-badge',     'label'=>'نظام الصلاحيات RBAC',    'value'=>'مفعّل',                 'status'=>true],
                    ['icon'=>'fa-list-check',   'label'=>'تسجيل النشاط Audit Log',  'value'=>'مفعّل',                 'status'=>true],
                    ['icon'=>'fa-envelope',     'label'=>'التحقق بـ OTP',            'value'=>'مدعوم في النظام',       'status'=>true],
                ]; ?>
                <?php foreach ($securitySettings as $s): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid #f1f5f9;">
                    <span style="display:flex;align-items:center;gap:.6rem;font-size:.85rem;color:#374151;">
                        <i class="fa-solid <?= e($s['icon']); ?>" style="width:16px;color:#64748b;"></i>
                        <?= e($s['label']); ?>
                    </span>
                    <div style="display:flex;align-items:center;gap:.5rem;">
                        <span style="font-size:.78rem;color:#64748b;"><?= e($s['value']); ?></span>
                        <span style="width:8px;height:8px;border-radius:50%;background:<?= $s['status'] ? '#16a34a' : '#ef4444'; ?>;display:inline-block;"></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </article>

        <!-- معلومات الاتصال بقاعدة البيانات -->
        <article class="soft-card">
            <div class="section-head" style="margin-bottom:.75rem;">
                <div>
                    <h2><i class="fa-solid fa-database" style="color:#7c3aed;"></i> إعدادات قاعدة البيانات</h2>
                    <p>إعدادات الاتصال المستخدمة حالياً</p>
                </div>
            </div>
            <?php
            // قراءة إعدادات DB من ملف config إن وجد
            $configFile = __DIR__ . '/../../config/database.php';
            $dbHost = 'localhost';
            $dbName = 'trusted_social_network_platform';
            if (file_exists($configFile)) {
                $configContent = file_get_contents($configFile);
                if (preg_match("/DB_HOST.*?['\"]([^'\"]+)['\"]/",$configContent,$m)) $dbHost=$m[1]??'localhost';
                if (preg_match("/DB_NAME.*?['\"]([^'\"]+)['\"]/",$configContent,$m)) $dbName=$m[1]??$dbName;
            }
            ?>
            <div style="display:flex;flex-direction:column;gap:.5rem;">
                <?php $dbSettingsRows = [
                    ['label'=>'Host',     'value'=>$dbHost],
                    ['label'=>'Database', 'value'=>$dbName],
                    ['label'=>'Charset',  'value'=>'utf8mb4'],
                    ['label'=>'Collation','value'=>'utf8mb4_unicode_ci'],
                ]; ?>
                <?php foreach ($dbSettingsRows as $r): ?>
                <div style="display:flex;gap:.5rem;align-items:center;">
                    <span style="min-width:80px;font-size:.8rem;color:#64748b;"><?= e($r['label']); ?></span>
                    <code style="font-size:.8rem;background:#f8fafc;padding:.15rem .5rem;border-radius:.3rem;color:#374151;"><?= e($r['value']); ?></code>
                </div>
                <?php endforeach; ?>
            </div>
        </article>

        <!-- الاختصارات السريعة -->
        <article class="soft-card">
            <div class="section-head" style="margin-bottom:.75rem;">
                <div>
                    <h2>إجراءات الإدارة السريعة</h2>
                </div>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:.6rem;">
                <a href="<?= e(admin_url('index.php?page=users')); ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-users"></i> إدارة المستخدمين
                </a>
                <a href="<?= e(admin_url('index.php?page=permissions')); ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-user-shield"></i> إدارة الصلاحيات
                </a>
                <a href="<?= e(admin_url('index.php?page=reports')); ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-flag"></i> البلاغات
                </a>
                <a href="<?= e(admin_url('index.php?page=activity_logs')); ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-list-check"></i> سجلات النشاط
                </a>
                <a href="<?= e(admin_url('index.php?page=statistics')); ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-chart-bar"></i> الإحصائيات
                </a>
            </div>
        </article>

    </div>
</section>
