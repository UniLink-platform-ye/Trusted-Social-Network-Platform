/* users.js — Ajax handlers for Users page */
'use strict';

(function ($) {
    const AJAX_URL = '/Trusted-Social-Network-Platform/admin/ajax/users.php';
    const csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

    function postAjax(data, onSuccess, onError) {
        data.csrf_token = csrfToken;
        $.ajax({
            url: AJAX_URL,
            method: 'POST',
            data: data,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                if (res.success) {
                    showToast('success', res.message);
                    if (onSuccess) onSuccess(res);
                } else {
                    showToast('error', res.message || 'حدث خطأ.');
                    if (onError) onError(res);
                }
            },
            error: function () {
                showToast('error', 'تعذر الاتصال بالخادم.');
            }
        });
    }

    // ── إضافة مستخدم ─────────────────────────────────────────────────────
    $('#createUserForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('[type=submit]').prop('disabled', true);
        postAjax($(this).serialize(), function () {
            closeModal('createUserModal');
            setTimeout(() => location.reload(), 800);
        }, function () { $btn.prop('disabled', false); });
    });

    // ── تعديل مستخدم — فتح المودال وتعبئة البيانات ────────────────────
    $(document).on('click', '.btn-edit-user', function () {
        const btn = $(this);
        $('#edit_user_id').val(btn.data('id'));
        $('#edit_full_name').val(btn.data('name'));
        $('#edit_email').val(btn.data('email'));
        $('#edit_role').val(btn.data('role'));
        $('#edit_department').val(btn.data('department'));
        openModal('editUserModal');
    });

    $('#editUserForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('[type=submit]').prop('disabled', true);
        postAjax($(this).serialize(), function () {
            closeModal('editUserModal');
            setTimeout(() => location.reload(), 800);
        }, function () { $btn.prop('disabled', false); });
    });

    // ── تعليق مستخدم ──────────────────────────────────────────────────
    $(document).on('click', '.btn-suspend-user', function () {
        const id   = $(this).data('id');
        const name = $(this).data('name');
        Swal.fire({
            title: 'تعليق الحساب',
            html: `هل تريد تعليق حساب <strong>${name}</strong>؟`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonText: 'إلغاء',
            confirmButtonText: 'تعليق',
            reverseButtons: true,
        }).then(res => {
            if (res.isConfirmed) {
                postAjax({ action: 'suspend_user', user_id: id }, () => {
                    const row = $('#user-row-' + id);
                    row.find('.btn-suspend-user').replaceWith(
                        `<button class="btn btn-sm btn-success btn-activate-user"
                            data-id="${id}" data-name="${name}" title="تفعيل">
                            <i class="fa-solid fa-user-check"></i></button>`
                    );
                    row.find('.badge').filter(':last').attr('class', 'badge badge-danger').text('موقوف');
                });
            }
        });
    });

    // ── تفعيل مستخدم ──────────────────────────────────────────────────
    $(document).on('click', '.btn-activate-user', function () {
        const id   = $(this).data('id');
        const name = $(this).data('name');
        Swal.fire({
            title: 'تفعيل الحساب',
            html: `تفعيل حساب <strong>${name}</strong>؟`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            cancelButtonText: 'إلغاء',
            confirmButtonText: 'تفعيل',
            reverseButtons: true,
        }).then(res => {
            if (res.isConfirmed) {
                postAjax({ action: 'activate_user', user_id: id }, () => {
                    const row = $('#user-row-' + id);
                    row.find('.btn-activate-user').replaceWith(
                        `<button class="btn btn-sm btn-danger btn-suspend-user"
                            data-id="${id}" data-name="${name}" title="تعليق">
                            <i class="fa-solid fa-user-slash"></i></button>`
                    );
                    row.find('.badge').filter(':last').attr('class', 'badge badge-success').text('نشط');
                });
            }
        });
    });

    // ── عرض تفاصيل مستخدم ──────────────────────────────────────────────
    $(document).on('click', '.btn-view-user', function () {
        const id = $(this).data('id');
        $.ajax({
            url: AJAX_URL,
            method: 'POST',
            data: { action: 'get_user', user_id: id, csrf_token: csrfToken },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (!res.success) { showToast('error', res.message); return; }
                const u = res.data;
                Swal.fire({
                    title: u.full_name,
                    html: `
                        <div style="text-align:right;font-family:'Cairo',sans-serif;line-height:2;">
                            <p><strong>البريد:</strong> ${u.email}</p>
                            <p><strong>المستخدم:</strong> @${u.username}</p>
                            <p><strong>الدور:</strong> ${u.role_label}</p>
                            <p><strong>القسم:</strong> ${u.department || '—'}</p>
                            <p><strong>الرقم الأكاديمي:</strong> ${u.academic_id || '—'}</p>
                            <p><strong>الحالة:</strong> ${u.status}</p>
                            <p><strong>آخر دخول:</strong> ${u.last_login || 'لم يدخل'}</p>
                            <p><strong>تاريخ التسجيل:</strong> ${u.created_at}</p>
                        </div>`,
                    icon: 'info',
                    confirmButtonText: 'إغلاق',
                    confirmButtonColor: '#2563eb',
                });
            }
        });
    });

})(jQuery);
