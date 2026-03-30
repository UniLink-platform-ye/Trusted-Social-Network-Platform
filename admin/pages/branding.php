<?php

declare(strict_types=1);

require_permission('settings.manage');

$pageScripts[] = 'branding.js';

// تحميل الإعدادات الحالية
$b         = get_branding();
$templates = branding_templates();

$success = flash('branding_success');
$error   = flash('branding_error');

// الإعدادات الافتراضية الأصلية (تُمرر لـ JS للـ Hard Reset)
$hardDefaults = [
    'platform_name'        => 'UniLink',
    'platform_tagline'     => 'منصة التواصل الأكاديمي الموثوقة',
    'primary_color'        => '#004D8C',
    'secondary_color'      => '#007786',
    'accent_color'         => '#00B4D8',
    'background_color'     => '#FFFFFF',
    'text_color'           => '#1E293B',
    'button_primary_color' => '#004D8C',
    'button_text_color'    => '#FFFFFF',
    'card_bg_color'        => '#F8FAFC',
    'input_bg_color'       => '#FFFFFF',
    'input_border_color'   => '#CBD5E1',
    'font_family'          => 'Cairo',
    'active_template_key'  => 'deep_blue',
];
?>

<!-- ══ رأس الصفحة ══════════════════════════════════════════════════════════ -->
<section class="page-block reveal">
    <div class="section-head">
        <div>
            <h2>App Branding
                <small style="font-size:.75rem;font-weight:500;color:#64748b">هوية المنصة</small>
            </h2>
            <p>تحكم في اسم المنصة وألوانها وشعارها — تُطبَّق تلقائياً على تطبيق الموبايل.</p>
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

<!-- ══ قوالب جاهزة ═══════════════════════════════════════════════════════ -->
<section class="page-block reveal">
    <div class="section-head" style="margin-bottom:1rem;">
        <div>
            <h2 style="font-size:1rem;"><i class="fa-solid fa-palette" style="color:#7c3aed;"></i> قوالب هوية جاهزة</h2>
        </div>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:.75rem;" id="templateBtns">
        <?php foreach ($templates as $key => $tmpl): ?>
            <button type="button"
                    class="template-btn <?= $b['active_template_key'] === $key ? 'active' : ''; ?>"
                    data-template="<?= e($key); ?>"
                    data-values='<?= json_encode($tmpl, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS); ?>'
                    title="<?= e($tmpl['description']); ?>"
                    style="display:flex;align-items:center;gap:.6rem;padding:.6rem 1rem;border-radius:.75rem;
                           border:2px solid <?= $b['active_template_key'] === $key ? '#7c3aed' : '#e2e8f0'; ?>;
                           background:<?= $b['active_template_key'] === $key ? '#f5f3ff' : '#fff'; ?>;
                           cursor:pointer;font-size:.85rem;font-weight:600;transition:all .2s;">
                <span style="width:18px;height:18px;border-radius:50%;display:inline-block;
                             background:<?= e($tmpl['primary_color']); ?>;
                             box-shadow:2px 2px 0 <?= e($tmpl['accent_color']); ?>;"></span>
                <?= e($tmpl['name_ar']); ?>
            </button>
        <?php endforeach; ?>

        <!-- زر القيم الافتراضية الأصلية -->
        <button type="button" id="hardResetBtn"
                style="display:flex;align-items:center;gap:.6rem;padding:.6rem 1rem;border-radius:.75rem;
                       border:2px dashed #e2e8f0;background:#fff;
                       cursor:pointer;font-size:.85rem;font-weight:600;color:#64748b;transition:all .2s;"
                title="استعادة الإعدادات الأصلية للتطبيق قبل إضافة نظام الهوية">
            <i class="fa-solid fa-rotate-left" style="color:#94a3b8;"></i>
            الإعدادات الأصلية
        </button>
    </div>
</section>

<!-- ══ صفوف الإعدادات + المعاينة الحية ══════════════════════════════════ -->
<div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;" class="reveal">

<!-- ── لوحة الإعدادات ──────────────────────────────────────────────────── -->
<form id="brandingForm" enctype="multipart/form-data">
    <?= csrf_input(); ?>
    <input type="hidden" name="action" value="save_branding">
    <input type="hidden" name="active_template_key" id="activeTemplateKey" value="<?= e($b['active_template_key']); ?>">

    <!-- معلومات المنصة -->
    <article class="soft-card" style="margin-bottom:1rem;">
        <div class="section-head" style="margin-bottom:.75rem;">
            <div>
                <h2 style="font-size:.95rem;"><i class="fa-solid fa-circle-info" style="color:#2563eb;"></i> معلومات المنصة</h2>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div>
                <label class="form-label">اسم المنصة *</label>
                <input type="text" name="platform_name" id="fPlatformName"
                       class="form-control" required maxlength="120"
                       value="<?= e($b['platform_name']); ?>"
                       placeholder="UniLink">
            </div>
            <div>
                <label class="form-label">العنوان الفرعي / Tagline</label>
                <input type="text" name="platform_tagline" id="fTagline"
                       class="form-control" maxlength="255"
                       value="<?= e($b['platform_tagline']); ?>"
                       placeholder="منصة التواصل الأكاديمي الموثوقة">
            </div>
            <div>
                <label class="form-label">نوع الخط</label>
                <select name="font_family" id="fFont" class="form-control">
                    <?php foreach (['Cairo', 'Tajawal', 'Roboto', 'Inter'] as $font): ?>
                        <option value="<?= e($font); ?>" <?= $b['font_family'] === $font ? 'selected' : ''; ?>>
                            <?= e($font); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </article>

    <!-- الألوان -->
    <article class="soft-card" style="margin-bottom:1rem;">
        <div class="section-head" style="margin-bottom:.75rem;">
            <div>
                <h2 style="font-size:.95rem;"><i class="fa-solid fa-droplet" style="color:#7c3aed;"></i> الألوان</h2>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;">

            <?php
            $colorFields = [
                ['name'=>'primary_color',        'label'=>'اللون الأساسي',          'id'=>'fPrimary'],
                ['name'=>'secondary_color',       'label'=>'اللون الثانوي',          'id'=>'fSecondary'],
                ['name'=>'accent_color',          'label'=>'لون Accent',             'id'=>'fAccent'],
                ['name'=>'background_color',      'label'=>'لون الخلفية',            'id'=>'fBg'],
                ['name'=>'text_color',            'label'=>'لون النص الرئيسي',       'id'=>'fText'],
                ['name'=>'button_primary_color',  'label'=>'لون الأزرار',            'id'=>'fBtnBg'],
                ['name'=>'button_text_color',     'label'=>'لون نص الأزرار',         'id'=>'fBtnText'],
                ['name'=>'card_bg_color',         'label'=>'خلفية البطاقات',         'id'=>'fCard'],
                ['name'=>'input_bg_color',        'label'=>'خلفية حقول الإدخال',    'id'=>'fInputBg'],
                ['name'=>'input_border_color',    'label'=>'حدود حقول الإدخال',     'id'=>'fInputBorder'],
            ];
            foreach ($colorFields as $cf): ?>
                <div class="color-field" style="display:flex;align-items:center;gap:.6rem;">
                    <input type="color"
                           name="<?= e($cf['name']); ?>"
                           id="<?= e($cf['id']); ?>"
                           class="branding-color-picker"
                           data-preview="<?= e($cf['name']); ?>"
                           value="<?= e($b[$cf['name']]); ?>"
                           title="<?= e($cf['label']); ?>"
                           style="width:48px;height:40px;border:none;border-radius:.5rem;cursor:pointer;padding:2px;">
                    <div>
                        <div style="font-size:.8rem;font-weight:600;color:#374151;"><?= e($cf['label']); ?></div>
                        <code id="<?= e($cf['id']); ?>_val" style="font-size:.72rem;color:#64748b;"><?= e($b[$cf['name']]); ?></code>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <!-- الشعار -->
    <article class="soft-card" style="margin-bottom:1rem;">
        <div class="section-head" style="margin-bottom:.75rem;">
            <div>
                <h2 style="font-size:.95rem;"><i class="fa-solid fa-image" style="color:#d97706;"></i> الشعار / Logo</h2>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:1.5rem;">
            <div id="logoPreviewWrapper" style="width:80px;height:80px;border-radius:16px;overflow:hidden;background:#f1f5f9;
                         border:2px dashed #cbd5e1;display:flex;align-items:center;justify-content:center;">
                <?php if (!empty($b['logo_path'])): ?>
                    <img id="logoPreviewImg" src="<?= e(url($b['logo_path'])); ?>"
                         style="width:100%;height:100%;object-fit:contain;" alt="Logo">
                <?php else: ?>
                    <img id="logoPreviewImg" src="<?= e(url('img/logo.png')); ?>"
                         style="width:100%;height:100%;object-fit:contain;" alt="Logo">
                <?php endif; ?>
            </div>
            <div>
                <label class="form-label">رفع شعار جديد</label>
                <input type="file" name="logo" id="logoFileInput"
                       accept="image/png,image/jpeg,image/svg+xml,image/webp"
                       style="display:block;margin-bottom:.5rem;">
                <small style="color:#94a3b8;display:block;">PNG / JPG / SVG / WebP — حد أقصى 2 MB</small>
                <button type="button" id="uploadLogoBtn" class="btn btn-secondary btn-sm" style="margin-top:.5rem;">
                    <i class="fa-solid fa-cloud-arrow-up"></i> رفع الشعار الآن
                </button>
                <span id="logoUploadStatus" style="font-size:.8rem;margin-right:.5rem;"></span>
            </div>
        </div>
    </article>

    <!-- أزرار الحفظ -->
    <div style="display:flex;gap:.75rem;justify-content:flex-end;">
        <button type="button" id="resetBrandingBtn" class="btn btn-secondary">
            <i class="fa-solid fa-rotate-left"></i> إعادة الضبط
        </button>
        <button type="submit" id="saveBrandingBtn" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> حفظ الإعدادات
        </button>
    </div>
    <div id="brandingSaveResult" style="display:none;margin-top:.75rem;"></div>
</form>


<!-- ── معاينة الهاتف (Phone Mockup) ──────────────────────────────────── -->
<div id="phonePreviewSection">
    <article class="soft-card" style="position:sticky;top:80px;">
        <div class="section-head" style="margin-bottom:.5rem;">
            <div>
                <h2 style="font-size:.9rem;"><i class="fa-solid fa-mobile-screen-button" style="color:#16a34a;"></i> معاينة التطبيق</h2>
                <p style="font-size:.78rem;">تتحدث فورياً عند تغيير الإعدادات</p>
            </div>
        </div>

        <!-- تبويبات الشاشات -->
        <div id="previewTabs" style="display:flex;gap:4px;margin-bottom:.75rem;flex-wrap:wrap;border-bottom:1px solid #e2e8f0;padding-bottom:.5rem;">
            <?php
            $tabs = [
                ['key'=>'login',  'icon'=>'fa-right-to-bracket', 'label'=>'دخول'],
                ['key'=>'home',   'icon'=>'fa-house',             'label'=>'الرئيسية'],
                ['key'=>'groups', 'icon'=>'fa-users',             'label'=>'مجموعات'],
                ['key'=>'notif',  'icon'=>'fa-bell',              'label'=>'إشعارات'],
                ['key'=>'profile','icon'=>'fa-user-circle',       'label'=>'ملف'],
            ];
            foreach ($tabs as $i => $tab): ?>
                <button class="preview-tab-btn <?= $i === 0 ? 'active' : ''; ?>"
                        data-screen="<?= $tab['key']; ?>"
                        type="button"
                        style="display:flex;align-items:center;gap:4px;padding:4px 10px;border-radius:6px;border:none;
                               font-size:.72rem;font-weight:600;cursor:pointer;transition:all .15s;
                               background:<?= $i === 0 ? '#f5f3ff' : 'transparent'; ?>;
                               color:<?= $i === 0 ? '#7c3aed' : '#64748b'; ?>;">
                    <i class="fa-solid <?= $tab['icon']; ?>" style="font-size:.7rem;"></i>
                    <?= $tab['label']; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- إطار الهاتف -->
        <div style="display:flex;justify-content:center;padding:.25rem 0;">
            <div class="phone-case">
                <!-- الشاشة -->
                <div class="phone-screen" id="mockupScreen">

                    <!-- شريط الحالة -->
                    <div class="mock-status-bar">
                        <span>9:41</span>
                        <span>📶 🔋</span>
                    </div>

                    <!-- ══ شاشة 1: تسجيل الدخول ══ -->
                    <div class="mock-screen" id="screen-login">
                        <div class="mock-logo-area">
                            <div class="mock-logo-box" id="mockLogoBox">
                                <img id="mockLogoImg"
                                     src="<?= e(url(!empty($b['logo_path']) ? $b['logo_path'] : 'img/logo.png')); ?>"
                                     style="width:44px;height:44px;object-fit:contain;" alt="">
                            </div>
                            <div class="mock-app-name" id="mockAppName"><?= e($b['platform_name']); ?></div>
                            <div class="mock-subtitle" id="mockSubtitle"><?= e($b['platform_tagline']); ?></div>
                        </div>
                        <div class="mock-input-group">
                            <span>✉️</span><span class="mock-placeholder">البريد الإلكتروني</span>
                        </div>
                        <div class="mock-input-group">
                            <span>🔒</span><span class="mock-placeholder">كلمة المرور</span>
                        </div>
                        <button class="mock-login-btn" id="mockLoginBtn">تسجيل الدخول</button>
                        <div class="mock-footer-text" style="margin-top:8px;">
                            نسيت كلمة المرور؟ <span class="mock-link" id="mockFooterLink">استعادة</span>
                        </div>
                        <div class="mock-footer-text">
                            ليس لديك حساب؟ <span class="mock-link">سجّل الآن</span>
                        </div>
                    </div>

                    <!-- ══ شاشة 2: الرئيسية ══ -->
                    <div class="mock-screen" id="screen-home" style="display:none;">
                        <!-- AppBar -->
                        <div class="mock-appbar">
                            <span class="mock-appbar-name" id="mockHomeAppName"><?= e($b['platform_name']); ?></span>
                            <div style="display:flex;gap:5px;align-items:center;">
                                <div class="mock-avatar-sm">A</div>
                            </div>
                        </div>
                        <!-- منشور 1 -->
                        <div class="mock-post-card">
                            <div class="mock-post-header">
                                <div class="mock-avatar">م</div>
                                <div>
                                    <div class="mock-post-user">محمد أحمد</div>
                                    <div class="mock-post-time" style="opacity:.55;font-size:8px;">منذ ساعة</div>
                                </div>
                            </div>
                            <div class="mock-post-text">تمت مراجعة المادة اليوم بشكل رائع 🎓</div>
                            <div class="mock-post-actions">
                                <span class="mock-action"><i class="fa-regular fa-heart"></i> 12</span>
                                <span class="mock-action"><i class="fa-regular fa-comment"></i> 4</span>
                                <span class="mock-action"><i class="fa-solid fa-share-nodes"></i></span>
                            </div>
                        </div>
                        <!-- منشور 2 -->
                        <div class="mock-post-card">
                            <div class="mock-post-header">
                                <div class="mock-avatar" style="background:var(--mock-secondary);">س</div>
                                <div>
                                    <div class="mock-post-user">سارة الزهراني</div>
                                    <div class="mock-post-time" style="opacity:.55;font-size:8px;">منذ 3 ساعات</div>
                                </div>
                            </div>
                            <div class="mock-post-text">موعد الاختبار غداً الساعة 10 صباحاً 📌</div>
                            <div class="mock-post-actions">
                                <span class="mock-action"><i class="fa-regular fa-heart"></i> 7</span>
                                <span class="mock-action"><i class="fa-regular fa-comment"></i> 2</span>
                            </div>
                        </div>
                        <!-- Bottom Nav -->
                        <div class="mock-bottom-nav">
                            <span class="mock-nav-item active"><i class="fa-solid fa-house"></i></span>
                            <span class="mock-nav-item"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <span class="mock-nav-fab"><i class="fa-solid fa-plus"></i></span>
                            <span class="mock-nav-item"><i class="fa-solid fa-users"></i></span>
                            <span class="mock-nav-item"><i class="fa-solid fa-user"></i></span>
                        </div>
                    </div>

                    <!-- ══ شاشة 3: المجموعات ══ -->
                    <div class="mock-screen" id="screen-groups" style="display:none;">
                        <div class="mock-appbar">
                            <span class="mock-appbar-name">المجموعات</span>
                            <i class="fa-solid fa-plus" style="font-size:10px;opacity:.8;"></i>
                        </div>
                        <?php
                        $groups = [
                            ['icon'=>'💻','name'=>'Computer Science','count'=>'128 عضو'],
                            ['icon'=>'📐','name'=>'Engineering','count'=>'87 عضو'],
                            ['icon'=>'📊','name'=>'Business Admin','count'=>'64 عضو'],
                        ];
                        foreach ($groups as $g): ?>
                            <div class="mock-group-card">
                                <div class="mock-group-icon"><?= $g['icon']; ?></div>
                                <div style="flex:1;">
                                    <div class="mock-group-name"><?= $g['name']; ?></div>
                                    <div class="mock-group-count"><?= $g['count']; ?></div>
                                </div>
                                <button class="mock-join-btn">انضمام</button>
                            </div>
                        <?php endforeach; ?>
                        <div class="mock-bottom-nav">
                            <span class="mock-nav-item"><i class="fa-solid fa-house"></i></span>
                            <span class="mock-nav-item"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <span class="mock-nav-fab"><i class="fa-solid fa-plus"></i></span>
                            <span class="mock-nav-item active"><i class="fa-solid fa-users"></i></span>
                            <span class="mock-nav-item"><i class="fa-solid fa-user"></i></span>
                        </div>
                    </div>

                    <!-- ══ شاشة 4: الإشعارات ══ -->
                    <div class="mock-screen" id="screen-notif" style="display:none;">
                        <div class="mock-appbar">
                            <span class="mock-appbar-name">الإشعارات</span>
                            <i class="fa-solid fa-check-double" style="font-size:10px;opacity:.8;"></i>
                        </div>
                        <?php
                        $notifs = [
                            ['icon'=>'❤️','text'=>'أعجب محمد بمنشورك','time'=>'الآن','unread'=>true],
                            ['icon'=>'💬','text'=>'علّقت سارة على منشورك','time'=>'منذ 5 د','unread'=>true],
                            ['icon'=>'👥','text'=>'تمت إضافتك لمجموعة CS','time'=>'منذ ساعة','unread'=>false],
                            ['icon'=>'📢','text'=>'إعلان جديد من الكلية','time'=>'أمس','unread'=>false],
                        ];
                        foreach ($notifs as $n): ?>
                            <div class="mock-notif-item <?= $n['unread'] ? 'unread' : ''; ?>">
                                <div class="mock-notif-icon"><?= $n['icon']; ?></div>
                                <div style="flex:1;">
                                    <div class="mock-notif-text"><?= $n['text']; ?></div>
                                    <div class="mock-notif-time"><?= $n['time']; ?></div>
                                </div>
                                <?php if ($n['unread']): ?>
                                    <div class="mock-unread-dot"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <div class="mock-bottom-nav">
                            <span class="mock-nav-item"><i class="fa-solid fa-house"></i></span>
                            <span class="mock-nav-item"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <span class="mock-nav-fab"><i class="fa-solid fa-plus"></i></span>
                            <span class="mock-nav-item"><i class="fa-solid fa-users"></i></span>
                            <span class="mock-nav-item"><i class="fa-solid fa-user"></i></span>
                        </div>
                    </div>

                    <!-- ══ شاشة 5: الملف الشخصي ══ -->
                    <div class="mock-screen" id="screen-profile" style="display:none;">
                        <!-- Header gradient -->
                        <div class="mock-profile-header">
                            <div class="mock-profile-avatar">م</div>
                            <div class="mock-profile-name">محمد العمري</div>
                            <div class="mock-profile-badge">طالب</div>
                        </div>
                        <!-- Info cards -->
                        <div class="mock-info-card">
                            <i class="fa-solid fa-envelope" style="font-size:8px;opacity:.6;"></i>
                            <span>student@example.com</span>
                        </div>
                        <div class="mock-info-card">
                            <i class="fa-solid fa-school" style="font-size:8px;opacity:.6;"></i>
                            <span>Computer Science</span>
                        </div>
                        <!-- Theme toggle -->
                        <div class="mock-setting-row">
                            <i class="fa-solid fa-moon" style="font-size:8px;color:var(--mock-primary);"></i>
                            <span style="flex:1;">وضع الثيم</span>
                            <div class="mock-toggle"></div>
                        </div>
                        <!-- Logout -->
                        <button class="mock-logout-btn">
                            <i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج
                        </button>
                        <div class="mock-bottom-nav">
                            <span class="mock-nav-item"><i class="fa-solid fa-house"></i></span>
                            <span class="mock-nav-item"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <span class="mock-nav-fab"><i class="fa-solid fa-plus"></i></span>
                            <span class="mock-nav-item"><i class="fa-solid fa-users"></i></span>
                            <span class="mock-nav-item active"><i class="fa-solid fa-user"></i></span>
                        </div>
                    </div>

                </div><!-- /phone-screen -->
            </div><!-- /phone-case -->
        </div>
    </article>
</div>

</div><!-- /grid -->


<!-- ══ بيانات Hard Reset تُمرر لـ JS ══════════════════════════════════ -->
<script>
window.BRANDING_HARD_DEFAULTS = <?= json_encode($hardDefaults, JSON_UNESCAPED_UNICODE); ?>;
</script>

<!-- ══ CSS المحاكاة ═══════════════════════════════════════════════════════ -->
<style>
/* ── Fonts ─────────────────────────────────────────────── */
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&family=Tajawal:wght@400;700&family=Roboto:wght@400;700&family=Inter:wght@400;700&display=swap');

/* ── CSS Variables (معاينة الهاتف) ────────────────────── */
#phonePreviewSection {
    --mock-primary:   <?= e($b['primary_color']); ?>;
    --mock-secondary: <?= e($b['secondary_color']); ?>;
    --mock-accent:    <?= e($b['accent_color']); ?>;
    --mock-bg:        <?= e($b['background_color']); ?>;
    --mock-text:      <?= e($b['text_color']); ?>;
    --mock-btn-bg:    <?= e($b['button_primary_color']); ?>;
    --mock-btn-text:  <?= e($b['button_text_color']); ?>;
    --mock-input-bg:  <?= e($b['input_bg_color']); ?>;
    --mock-input-bdr: <?= e($b['input_border_color']); ?>;
    --mock-card-bg:   <?= e($b['card_bg_color']); ?>;
    --mock-font:      '<?= e($b['font_family']); ?>', 'Cairo', sans-serif;
}

/* ── Phone Case ─────────────────────────────────────────── */
.phone-case {
    width: 220px; height: 460px;
    background: #111827;
    border-radius: 36px;
    border: 7px solid #374151;
    position: relative;
    box-shadow: 0 25px 50px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.08);
    overflow: hidden;
}
.phone-case::before {
    content: '';
    position: absolute;
    top: 10px; left: 50%; transform: translateX(-50%);
    width: 60px; height: 6px;
    background: #374151; border-radius: 3px; z-index: 10;
}

/* ── Phone Screen ───────────────────────────────────────── */
.phone-screen {
    width: 100%; height: 100%;
    background-color: var(--mock-bg);
    font-family: var(--mock-font);
    display: flex; flex-direction: column;
    box-sizing: border-box;
    overflow: hidden;
    transition: background-color .3s, font-family .2s;
}

/* ── Mock Screen Wrapper ─────────────────────────────────── */
.mock-screen {
    display: flex; flex-direction: column;
    flex: 1; overflow: hidden;
    padding: 6px 10px 0;
}

/* ── Status Bar ─────────────────────────────────────────── */
.mock-status-bar {
    width: 100%; display: flex; justify-content: space-between;
    font-size: 9px; color: var(--mock-text);
    padding: 22px 10px 4px;
    opacity: .7;
    flex-shrink: 0;
}

/* ── Logo Area (Login) ──────────────────────────────────── */
.mock-logo-area { text-align: center; margin-bottom: 8px; margin-top: 4px; }
.mock-logo-box {
    width: 50px; height: 50px; border-radius: 13px;
    background: linear-gradient(135deg, var(--mock-primary), var(--mock-accent));
    margin: 0 auto 6px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 6px 14px rgba(0,0,0,.2);
    transition: background .3s;
    overflow: hidden;
}
.mock-app-name {
    font-size: 12px; font-weight: 900; color: var(--mock-primary);
    letter-spacing: .5px; text-transform: uppercase; transition: color .3s;
}
.mock-subtitle {
    font-size: 8px; color: var(--mock-text); opacity: .6;
    margin-top: 2px; transition: color .3s;
}

/* ── Input Groups ───────────────────────────────────────── */
.mock-input-group {
    width: 100%; display: flex; align-items: center; gap: 5px;
    background: var(--mock-input-bg);
    border: 1px solid var(--mock-input-bdr);
    border-radius: 7px; padding: 6px 8px;
    margin-bottom: 6px; font-size: 9px;
    color: var(--mock-text); transition: background .3s, border-color .3s;
}
.mock-placeholder { opacity: .55; }

/* ── Login Button ───────────────────────────────────────── */
.mock-login-btn {
    width: 100%; padding: 8px;
    background-color: var(--mock-btn-bg);
    color: var(--mock-btn-text);
    border: none; border-radius: 7px;
    font-size: 10px; font-weight: 700; cursor: default;
    transition: background-color .3s, color .3s;
    font-family: inherit; margin-bottom: 4px;
}

/* ── Footer ─────────────────────────────────────────────── */
.mock-footer-text {
    font-size: 8px; color: var(--mock-text); opacity: .7;
    transition: color .3s; margin-top: 3px; text-align: center;
}
.mock-link { color: var(--mock-primary); font-weight: 700; transition: color .3s; }

/* ── AppBar (Home, Groups, ...) ─────────────────────────── */
.mock-appbar {
    width: 100%; display: flex; justify-content: space-between; align-items: center;
    background: var(--mock-primary);
    color: var(--mock-btn-text);
    padding: 8px 10px; border-radius: 0;
    flex-shrink: 0;
    transition: background .3s;
    font-size: 10px; font-weight: 700;
}
.mock-appbar-name { font-size: 11px; font-weight: 900; }
.mock-avatar-sm {
    width: 20px; height: 20px; border-radius: 50%;
    background: rgba(255,255,255,.3);
    display: flex; align-items: center; justify-content: center;
    font-size: 9px; font-weight: 700;
}

/* ── Post Card ──────────────────────────────────────────── */
.mock-post-card {
    background: var(--mock-card-bg);
    border-radius: 8px; padding: 8px;
    margin-bottom: 6px;
    transition: background .3s;
    border: 1px solid var(--mock-input-bdr);
}
.mock-post-header { display: flex; align-items: center; gap: 6px; margin-bottom: 5px; }
.mock-avatar {
    width: 24px; height: 24px; border-radius: 50%;
    background: var(--mock-primary);
    color: var(--mock-btn-text);
    display: flex; align-items: center; justify-content: center;
    font-size: 10px; font-weight: 700; flex-shrink: 0;
    transition: background .3s;
}
.mock-post-user { font-size: 9px; font-weight: 700; color: var(--mock-text); }
.mock-post-time { font-size: 7px; color: var(--mock-text); opacity: .5; }
.mock-post-text { font-size: 9px; color: var(--mock-text); margin-bottom: 6px; line-height: 1.4; }
.mock-post-actions { display: flex; gap: 10px; }
.mock-action { font-size: 8px; color: var(--mock-text); opacity: .6; display: flex; align-items: center; gap: 3px; }

/* ── Bottom Nav ─────────────────────────────────────────── */
.mock-bottom-nav {
    display: flex; justify-content: space-around; align-items: center;
    background: var(--mock-card-bg);
    border-top: 1px solid var(--mock-input-bdr);
    padding: 6px 0 4px;
    margin-top: auto; flex-shrink: 0;
    transition: background .3s;
}
.mock-nav-item { font-size: 12px; color: var(--mock-text); opacity: .45; transition: color .3s; }
.mock-nav-item.active { color: var(--mock-primary); opacity: 1; }
.mock-nav-fab {
    width: 30px; height: 30px; border-radius: 50%;
    background: var(--mock-primary);
    color: var(--mock-btn-text);
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700;
    box-shadow: 0 4px 10px rgba(0,0,0,.2);
    transition: background .3s;
}

/* ── Groups ─────────────────────────────────────────────── */
.mock-group-card {
    display: flex; align-items: center; gap: 7px;
    background: var(--mock-card-bg);
    border: 1px solid var(--mock-input-bdr);
    border-radius: 8px; padding: 7px 8px; margin-bottom: 5px;
    transition: background .3s;
}
.mock-group-icon { font-size: 14px; }
.mock-group-name { font-size: 9px; font-weight: 700; color: var(--mock-text); }
.mock-group-count { font-size: 7px; color: var(--mock-text); opacity: .5; }
.mock-join-btn {
    background: var(--mock-primary); color: var(--mock-btn-text);
    border: none; border-radius: 5px; padding: 3px 7px;
    font-size: 8px; font-weight: 700; cursor: default;
    transition: background .3s;
}

/* ── Notifications ──────────────────────────────────────── */
.mock-notif-item {
    display: flex; align-items: center; gap: 7px;
    padding: 6px 8px; border-radius: 7px; margin-bottom: 4px;
    background: var(--mock-card-bg);
    border: 1px solid var(--mock-input-bdr);
    transition: background .3s;
}
.mock-notif-item.unread { background: color-mix(in srgb, var(--mock-primary) 8%, var(--mock-bg)); }
.mock-notif-icon { font-size: 12px; flex-shrink: 0; }
.mock-notif-text { font-size: 8px; color: var(--mock-text); font-weight: 600; line-height: 1.3; }
.mock-notif-time { font-size: 7px; color: var(--mock-text); opacity: .5; }
.mock-unread-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: var(--mock-primary);
    flex-shrink: 0; transition: background .3s;
}

/* ── Profile ─────────────────────────────────────────────── */
.mock-profile-header {
    background: linear-gradient(135deg, var(--mock-primary), var(--mock-secondary));
    padding: 14px 8px 10px;
    display: flex; flex-direction: column; align-items: center;
    flex-shrink: 0; transition: background .3s;
}
.mock-profile-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: rgba(255,255,255,.25);
    color: #fff; font-size: 16px; font-weight: 900;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 5px;
}
.mock-profile-name { font-size: 10px; font-weight: 800; color: #fff; }
.mock-profile-badge {
    font-size: 7px; background: rgba(255,255,255,.2);
    color: #fff; padding: 2px 8px; border-radius: 10px; margin-top: 3px;
}
.mock-info-card {
    display: flex; align-items: center; gap: 6px;
    background: var(--mock-card-bg);
    border: 1px solid var(--mock-input-bdr);
    border-radius: 7px; padding: 5px 8px; margin: 3px 0;
    font-size: 8px; color: var(--mock-text);
    transition: background .3s;
}
.mock-setting-row {
    display: flex; align-items: center; gap: 6px;
    background: var(--mock-card-bg);
    border: 1px solid var(--mock-input-bdr);
    border-radius: 7px; padding: 5px 8px; margin: 3px 0;
    font-size: 8px; color: var(--mock-text);
    transition: background .3s;
}
.mock-toggle {
    width: 22px; height: 12px; border-radius: 6px;
    background: var(--mock-primary); opacity: .7;
    position: relative; transition: background .3s;
}
.mock-logout-btn {
    width: 100%; background: #ef4444; color: #fff;
    border: none; border-radius: 7px; padding: 6px;
    font-size: 9px; font-weight: 700; cursor: default;
    margin-top: 5px; font-family: inherit;
}

/* ── Preview Tabs ───────────────────────────────────────── */
.preview-tab-btn:hover { background: #f8fafc !important; color: #374151 !important; }
.preview-tab-btn.active { background: #f5f3ff !important; color: #7c3aed !important; }

/* ── Template Buttons ───────────────────────────────────── */
.template-btn:hover { border-color: #7c3aed !important; background: #faf5ff !important; }
.template-btn.active { border-color: #7c3aed !important; background: #f5f3ff !important; }
#hardResetBtn:hover { border-color: #94a3b8 !important; background: #f8fafc !important; }
</style>
