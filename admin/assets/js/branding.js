/**
 * branding.js — JavaScript لصفحة إعدادات الهوية البصرية
 * يتعامل مع: المعاينة الحية، القوالب، حفظ الإعدادات، رفع الشعار.
 */

(function () {
    'use strict';

    /* ── AJAX endpoint ───────────────────────────────────── */
    const BRANDING_AJAX = (window.ADMIN_URL || '').replace(/\/+$/, '') + '/ajax/branding.php';

    /* ── حقول DOM الرئيسية ──────────────────────────────── */
    const previewSection = document.getElementById('phonePreviewSection');

    // Login Screen elements
    const mockAppName    = document.getElementById('mockAppName');
    const mockSubtitle   = document.getElementById('mockSubtitle');
    const mockLoginBtn   = document.getElementById('mockLoginBtn');
    const mockLogoImg    = document.getElementById('mockLogoImg');
    const mockLogoBox    = document.getElementById('mockLogoBox');

    // Home Screen elements
    const mockHomeAppName = document.getElementById('mockHomeAppName');

    // Form
    const form            = document.getElementById('brandingForm');
    const saveBrandingBtn = document.getElementById('saveBrandingBtn');
    const saveResult      = document.getElementById('brandingSaveResult');
    const resetBtn        = document.getElementById('resetBrandingBtn');
    const hardResetBtn    = document.getElementById('hardResetBtn');
    const templateBtns    = document.querySelectorAll('.template-btn');
    const activeKeyInput  = document.getElementById('activeTemplateKey');

    // Logo upload
    const logoFileInput    = document.getElementById('logoFileInput');
    const uploadLogoBtn    = document.getElementById('uploadLogoBtn');
    const logoUploadStatus = document.getElementById('logoUploadStatus');
    const logoPreviewImg   = document.getElementById('logoPreviewImg');

    /* ── خريطة الحقول → متغيرات CSS ────────────────────── */
    const CSS_VAR_MAP = {
        primary_color:       '--mock-primary',
        secondary_color:     '--mock-secondary',
        accent_color:        '--mock-accent',
        background_color:    '--mock-bg',
        text_color:          '--mock-text',
        button_primary_color:'--mock-btn-bg',
        button_text_color:   '--mock-btn-text',
        input_bg_color:      '--mock-input-bg',
        input_border_color:  '--mock-input-bdr',
        card_bg_color:       '--mock-card-bg',
    };

    /* ── تطبيق متغير CSS ──────────────────────────────── */
    function setCssVar(name, value) {
        if (previewSection) previewSection.style.setProperty(name, value);
    }

    /* ── تحديث اسم التطبيق في كل الشاشات ──────────────── */
    function updateMockText() {
        const nameEl    = document.getElementById('fPlatformName');
        const taglineEl = document.getElementById('fTagline');
        const name      = nameEl ? (nameEl.value || 'UniLink') : 'UniLink';
        const tagline   = taglineEl ? (taglineEl.value || '') : '';

        if (mockAppName)    mockAppName.textContent    = name;
        if (mockSubtitle)   mockSubtitle.textContent   = tagline;
        if (mockHomeAppName) mockHomeAppName.textContent = name;
    }

    /* ── تحديث الخط ─────────────────────────────────────── */
    function updateMockFont() {
        const fontEl = document.getElementById('fFont');
        if (!fontEl || !previewSection) return;
        const font = fontEl.value;
        previewSection.style.setProperty('--mock-font', `'${font}', 'Cairo', sans-serif`);
    }

    /* ══ تبويبات الشاشات ════════════════════════════════ */
    const tabBtns    = document.querySelectorAll('.preview-tab-btn');
    const mockScreens = document.querySelectorAll('.mock-screen');
    let   activeTab  = 'login';

    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const screen = btn.dataset.screen;
            activeTab = screen;

            // تحديث أنماط التبويبات
            tabBtns.forEach(function (b) {
                b.classList.remove('active');
                b.style.background = 'transparent';
                b.style.color      = '#64748b';
            });
            btn.classList.add('active');
            btn.style.background = '#f5f3ff';
            btn.style.color      = '#7c3aed';

            // إظهار الشاشة المختارة
            mockScreens.forEach(function (s) { s.style.display = 'none'; });
            const target = document.getElementById('screen-' + screen);
            if (target) target.style.display = 'flex';
        });
    });

    /* ══ ربط color pickers بالـ preview ════════════════ */
    document.querySelectorAll('.branding-color-picker').forEach(function (picker) {
        const fieldName = picker.dataset.preview;
        const cssVar    = CSS_VAR_MAP[fieldName];
        const valCode   = document.getElementById(picker.id + '_val');

        picker.addEventListener('input', function () {
            if (cssVar)   setCssVar(cssVar, picker.value);
            if (valCode)  valCode.textContent = picker.value.toUpperCase();
        });
    });

    /* ══ ربط اسم المنصة والـ tagline ════════════════════ */
    const nameInput    = document.getElementById('fPlatformName');
    const taglineInput = document.getElementById('fTagline');
    if (nameInput)    nameInput.addEventListener('input', updateMockText);
    if (taglineInput) taglineInput.addEventListener('input', updateMockText);

    /* ══ ربط الخط ════════════════════════════════════════ */
    const fontSelect = document.getElementById('fFont');
    if (fontSelect) fontSelect.addEventListener('change', updateMockFont);

    /* ══ تطبيق قيم كاملة على النموذج والـ preview ══════ */
    function applyValues(values, templateKey) {
        if (activeKeyInput && templateKey) activeKeyInput.value = templateKey;

        Object.keys(values).forEach(function (field) {
            const input = document.querySelector(`[name="${field}"]`);
            if (!input) return;
            input.value = values[field];

            const cssVar = CSS_VAR_MAP[field];
            if (cssVar) setCssVar(cssVar, values[field]);

            const codeEl = document.getElementById(input.id + '_val');
            if (codeEl) codeEl.textContent = (values[field] || '').toUpperCase();
        });

        // تحديث font
        const fFont = document.getElementById('fFont');
        if (fFont && values['font_family']) {
            fFont.value = values['font_family'];
            updateMockFont();
        }

        updateMockText();
    }

    /* ══ قوالب الهوية الجاهزة ═══════════════════════════ */
    templateBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const values = JSON.parse(btn.dataset.values || '{}');
            const key    = btn.dataset.template;

            applyValues(values, key);

            // تحديث أنماط الأزرار
            templateBtns.forEach(function (b) {
                b.classList.remove('active');
                b.style.borderColor = '#e2e8f0';
                b.style.background  = '#fff';
            });
            btn.classList.add('active');
            btn.style.borderColor = '#7c3aed';
            btn.style.background  = '#f5f3ff';

            showToast('info', `تم تحميل قالب: ${values.name_ar || key}`);
        });
    });

    /* ══ Hard Reset — الإعدادات الأصلية (بدون سيرفر، مع حفظ) ═══ */
    if (hardResetBtn) {
        hardResetBtn.addEventListener('click', function () {
            Swal.fire({
                title: 'استعادة الإعدادات الأصلية؟',
                html: '<p style="font-size:.9rem;color:#475569;">سيتم استعادة الإعدادات التي كان عليها التطبيق <strong>قبل</strong> إضافة نظام التحكم بالهوية.</p>' +
                      '<p style="font-size:.85rem;color:#94a3b8;">سيتم حفظها تلقائياً في قاعدة البيانات.</p>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'نعم، استعد وحفظ',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#004D8C',
            }).then(function (result) {
                if (!result.isConfirmed) return;

                const defaults = window.BRANDING_HARD_DEFAULTS || {};

                // 1) تطبيق القيم على النموذج والمعاينة فوراً
                applyValues(defaults, defaults['active_template_key'] || 'deep_blue');

                // إلغاء تحديد كل القوالب
                templateBtns.forEach(function (b) {
                    b.classList.remove('active');
                    b.style.borderColor = '#e2e8f0';
                    b.style.background  = '#fff';
                });

                // 2) حفظ في قاعدة البيانات عبر AJAX
                hardResetBtn.disabled = true;
                hardResetBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري الحفظ...';

                // بناء FormData من الإعدادات الافتراضية + csrf
                const fd = new FormData(form); // يأخذ csrf_token من النموذج
                fd.set('action', 'save_branding');

                // تأكد من إضافة كل قيم الـ defaults صراحةً
                Object.keys(defaults).forEach(function (key) {
                    fd.set(key, defaults[key] ?? '');
                });

                fetch(BRANDING_AJAX, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token':     window.CSRF_TOKEN || '',
                    },
                    body: fd,
                })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res.success) {
                            showToast('success', 'تم استعادة الإعدادات الأصلية وحفظها بنجاح ✓');
                        } else {
                            showToast('error', res.message || 'فشل الحفظ في قاعدة البيانات.');
                        }
                    })
                    .catch(function () {
                        showToast('error', 'تعذر الاتصال بالخادم. يمكنك الحفظ يدوياً.');
                    })
                    .finally(function () {
                        hardResetBtn.disabled = false;
                        hardResetBtn.innerHTML = '<i class="fa-solid fa-rotate-left" style="color:#94a3b8;"></i> الإعدادات الأصلية';
                    });
            });
        });
    }

    /* ══ إعادة الضبط (أول قالب متاح) ══════════════════ */
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            Swal.fire({
                title: 'إعادة الضبط؟',
                text: 'سيتم تحميل قالب "الأزرق الملكي" (Deep Blue). هل تريد المتابعة؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'نعم، أعد الضبط',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#7c3aed',
            }).then(function (result) {
                if (result.isConfirmed) {
                    const firstBtn = document.querySelector('.template-btn');
                    if (firstBtn) firstBtn.click();
                }
            });
        });
    }

    /* ══ معاينة الشعار قبل الرفع ════════════════════════ */
    if (logoFileInput) {
        logoFileInput.addEventListener('change', function () {
            const file = logoFileInput.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function (e) {
                if (logoPreviewImg) logoPreviewImg.src = e.target.result;
                if (mockLogoImg)    mockLogoImg.src    = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    /* ══ رفع الشعار ══════════════════════════════════════ */
    if (uploadLogoBtn) {
        uploadLogoBtn.addEventListener('click', function () {
            if (!logoFileInput || !logoFileInput.files.length) {
                showToast('warning', 'يرجى اختيار ملف شعار أولاً.');
                return;
            }

            const fd = new FormData();
            fd.append('action', 'upload_logo');
            fd.append('logo', logoFileInput.files[0]);
            fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

            uploadLogoBtn.disabled = true;
            if (logoUploadStatus) logoUploadStatus.textContent = 'جاري الرفع...';

            fetch(BRANDING_AJAX, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token':     window.CSRF_TOKEN || '',
                },
                body: fd,
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) {
                        showToast('success', res.message || 'تم رفع الشعار بنجاح.');
                        if (logoUploadStatus) logoUploadStatus.textContent = '✓ تم';
                        if (res.logo_url) {
                            if (logoPreviewImg) logoPreviewImg.src = res.logo_url;
                            if (mockLogoImg)    mockLogoImg.src    = res.logo_url;
                        }
                    } else {
                        showToast('error', res.message || 'فشل رفع الشعار.');
                        if (logoUploadStatus) logoUploadStatus.textContent = '✗ فشل';
                    }
                })
                .catch(function () {
                    showToast('error', 'تعذر الاتصال بالخادم.');
                    if (logoUploadStatus) logoUploadStatus.textContent = '✗ خطأ';
                })
                .finally(function () {
                    uploadLogoBtn.disabled = false;
                });
        });
    }

    /* ══ حفظ الإعدادات ═══════════════════════════════════ */
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            saveBrandingBtn.disabled = true;
            saveBrandingBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري الحفظ...';

            const fd = new FormData(form);

            fetch(BRANDING_AJAX, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token':     window.CSRF_TOKEN || '',
                },
                body: fd,
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) {
                        showToast('success', res.message || 'تم الحفظ بنجاح.');
                        if (saveResult) {
                            saveResult.style.display = 'block';
                            saveResult.innerHTML = `<div class="inline-note" style="background:#dcfce7;color:#166534;
                                border-right:4px solid #16a34a;padding:.6rem 1rem;border-radius:.5rem;">
                                <i class="fa-solid fa-check-circle"></i> ${res.message}</div>`;
                            setTimeout(function () { saveResult.style.display = 'none'; }, 4000);
                        }
                    } else {
                        showToast('error', res.message || 'فشل الحفظ.');
                    }
                })
                .catch(function () {
                    showToast('error', 'تعذر الاتصال بالخادم.');
                })
                .finally(function () {
                    saveBrandingBtn.disabled = false;
                    saveBrandingBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> حفظ الإعدادات';
                });
        });
    }

    /* ── تحديث أولي للنص والخط ─────────────────────────── */
    updateMockText();
    updateMockFont();

})();
