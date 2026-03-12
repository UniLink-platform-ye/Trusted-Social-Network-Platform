/**
 * content.js — إشراف المحتوى
 */
(function () {
    'use strict';

    const endpoint = window.ADMIN_URL + '/ajax/content.php';
    let currentContentId = null;

    // ── عرض المنشور الكامل ─────────────────────────────────────────────────────
    document.querySelectorAll('.btn-view-content').forEach(btn => {
        btn.addEventListener('click', () => {
            const id      = btn.dataset.id;
            const content = btn.dataset.content;
            const author  = btn.dataset.author;
            const type    = btn.dataset.type;

            currentContentId = id;
            document.getElementById('modal-content-id').textContent   = '#' + id;
            document.getElementById('modal-content-author').textContent = author;
            document.getElementById('modal-content-type').textContent  = type;
            document.getElementById('modal-content-body').textContent  = content;

            const deleteBtn = document.getElementById('modalDeleteContentBtn');
            if (deleteBtn) deleteBtn.dataset.id = id;

            document.getElementById('viewContentModal').classList.add('open');
            document.body.classList.add('modal-open');
        });
    });

    // ── حذف المنشور مباشرة ────────────────────────────────────────────────────
    document.querySelectorAll('.btn-delete-content').forEach(btn => {
        btn.addEventListener('click', () => deleteContent(btn.dataset.id));
    });

    document.getElementById('modalDeleteContentBtn')?.addEventListener('click', function () {
        deleteContent(this.dataset.id || currentContentId);
    });

    async function deleteContent(postId) {
        if (!confirm('هل تريد حذف هذا المنشور نهائياً؟ لا يمكن التراجع عن هذا الإجراء.')) return;
        try {
            const resp = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': window.CSRF_TOKEN || '',
                },
                body: new URLSearchParams({ action: 'delete_post', post_id: postId }),
            });
            const data = await resp.json();
            if (data.success) {
                const row = document.getElementById('content-row-' + postId);
                if (row) {
                    row.style.opacity = '0';
                    row.style.transition = 'opacity 0.3s';
                    setTimeout(() => row.remove(), 300);
                }
                document.getElementById('viewContentModal')?.classList.remove('open');
                document.body.classList.remove('modal-open');
            } else {
                alert(data.message || 'حدث خطأ أثناء الحذف.');
            }
        } catch (e) {
            console.error(e);
            alert('خطأ في الاتصال بالخادم.');
        }
    }
})();
