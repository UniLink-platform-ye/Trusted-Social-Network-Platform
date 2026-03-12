/**
 * settings.js — إعدادات النظام
 */
(function () {
    'use strict';

    const endpoint = window.ADMIN_URL + '/ajax/settings.php';

    // ── تغيير كلمة المرور ─────────────────────────────────────────────────────
    const changePasswordForm = document.getElementById('changePasswordForm');
    const changePasswordBtn  = document.getElementById('changePasswordBtn');
    const resultDiv          = document.getElementById('passwordChangeResult');

    changePasswordForm?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const newPassword     = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (newPassword !== confirmPassword) {
            showResult('error', 'كلمتا المرور غير متطابقتين.');
            return;
        }

        if (newPassword.length < 8) {
            showResult('error', 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.');
            return;
        }

        changePasswordBtn.disabled = true;
        changePasswordBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري التغيير...';

        try {
            const resp = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': window.CSRF_TOKEN || '',
                },
                body: new URLSearchParams(new FormData(changePasswordForm)),
            });

            const data = await resp.json();

            if (data.success) {
                showResult('success', data.message || 'تم تغيير كلمة المرور بنجاح.');
                changePasswordForm.reset();
            } else {
                showResult('error', data.message || 'حدث خطأ أثناء تغيير كلمة المرور.');
            }
        } catch (err) {
            console.error(err);
            showResult('error', 'خطأ في الاتصال بالخادم.');
        } finally {
            changePasswordBtn.disabled = false;
            changePasswordBtn.innerHTML = '<i class="fa-solid fa-key"></i> تغيير كلمة المرور';
        }
    });

    function showResult(type, message) {
        if (!resultDiv) return;
        resultDiv.style.display = '';
        const isSuccess = type === 'success';
        resultDiv.style.background    = isSuccess ? '#dcfce7' : '#fee2e2';
        resultDiv.style.color         = isSuccess ? '#166534' : '#991b1b';
        resultDiv.style.borderRight   = `4px solid ${isSuccess ? '#16a34a' : '#ef4444'}`;
        resultDiv.style.padding       = '.65rem 1rem';
        resultDiv.style.borderRadius  = '.5rem';
        resultDiv.style.fontSize      = '.85rem';
        resultDiv.innerHTML = `<i class="fa-solid ${isSuccess ? 'fa-check-circle' : 'fa-triangle-exclamation'}"></i> ${message}`;
        setTimeout(() => {
            resultDiv.style.display = 'none';
        }, 5000);
    }
})();
