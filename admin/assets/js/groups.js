/**
 * groups.js — إشراف المجموعات
 */
(function () {
    'use strict';

    const endpoint = window.ADMIN_URL + '/ajax/groups.php';
    let currentGroupId = null;

    // ── عرض تفاصيل المجموعة ───────────────────────────────────────────────────
    document.querySelectorAll('.btn-view-group').forEach(btn => {
        btn.addEventListener('click', () => {
            const id          = btn.dataset.id;
            const name        = btn.dataset.name;
            const description = btn.dataset.description;
            const type        = btn.dataset.type;
            const privacy     = btn.dataset.privacy;
            const creator     = btn.dataset.creator;
            const members     = btn.dataset.members;
            const posts       = btn.dataset.posts;

            currentGroupId = id;
            document.getElementById('modal-group-name').textContent        = name;
            document.getElementById('modal-group-creator').textContent     = creator;
            document.getElementById('modal-group-type').textContent        = type;
            document.getElementById('modal-group-privacy').textContent     = privacy;
            document.getElementById('modal-group-members').textContent     = members + ' عضو';
            document.getElementById('modal-group-posts').textContent       = posts + ' منشور';
            document.getElementById('modal-group-description').textContent = description || 'لا يوجد وصف.';

            const deleteBtn = document.getElementById('modalDeleteGroupBtn');
            if (deleteBtn) deleteBtn.dataset.id = id;

            document.getElementById('viewGroupModal').classList.add('open');
            document.body.classList.add('modal-open');
        });
    });

    // ── حذف المجموعة مباشرة ──────────────────────────────────────────────────
    document.querySelectorAll('.btn-delete-group').forEach(btn => {
        btn.addEventListener('click', () => deleteGroup(btn.dataset.id, btn.dataset.name));
    });

    document.getElementById('modalDeleteGroupBtn')?.addEventListener('click', function () {
        deleteGroup(this.dataset.id || currentGroupId);
    });

    async function deleteGroup(groupId, groupName) {
        const name = groupName || ('المجموعة #' + groupId);
        if (!confirm(`هل تريد حذف مجموعة "${name}" نهائياً؟ سيتم حذف جميع أعضائها ومنشوراتها.`)) return;
        try {
            const resp = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': window.CSRF_TOKEN || '',
                },
                body: new URLSearchParams({ action: 'delete_group', group_id: groupId }),
            });
            const data = await resp.json();
            if (data.success) {
                const row = document.getElementById('group-row-' + groupId);
                if (row) {
                    row.style.opacity = '0';
                    row.style.transition = 'opacity 0.3s';
                    setTimeout(() => row.remove(), 300);
                }
                document.getElementById('viewGroupModal')?.classList.remove('open');
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
