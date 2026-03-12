/**
 * reports.js — إدارة البلاغات
 */
(function () {
    'use strict';

    const endpoint = window.ADMIN_URL + '/ajax/reports.php';

    // ── عرض تفاصيل البلاغ ──────────────────────────────────────────────────────
    document.querySelectorAll('.btn-view-report').forEach(btn => {
        btn.addEventListener('click', () => {
            const id       = btn.dataset.id;
            const reporter = btn.dataset.reporter;
            const reported = btn.dataset.reported;
            const reason   = btn.dataset.reason;
            const status   = btn.dataset.status;
            const details  = btn.dataset.details;
            const action   = btn.dataset.action;
            const handler  = btn.dataset.handler;

            document.getElementById('modal-report-id').textContent = '#' + id;
            document.getElementById('modal-reporter').textContent  = reporter;
            document.getElementById('modal-reported').textContent  = reported;
            document.getElementById('modal-reason').textContent    = reason;
            document.getElementById('modal-status').textContent    = status;
            document.getElementById('modal-details').textContent   = details || '—';

            const actionRow = document.getElementById('modal-action-row');
            if (action) {
                actionRow.style.display = '';
                document.getElementById('modal-action').textContent  = action;
                document.getElementById('modal-handler').textContent = handler ? 'بواسطة: ' + handler : '';
            } else {
                actionRow.style.display = 'none';
            }

            const resolveBtn = document.getElementById('modalResolveBtn');
            const rejectBtn  = document.getElementById('modalRejectBtn');
            if (resolveBtn) {
                const canAct = (status === 'pending' || status === 'under_review');
                resolveBtn.style.display = canAct ? '' : 'none';
                rejectBtn.style.display  = canAct ? '' : 'none';
                resolveBtn.dataset.reportId = id;
                rejectBtn.dataset.reportId  = id;
            }

            document.getElementById('viewReportModal').classList.add('open');
            document.body.classList.add('modal-open');
        });
    });

    // ── حل البلاغ (resolve) ───────────────────────────────────────────────────
    document.querySelectorAll('.btn-resolve-report').forEach(btn => {
        btn.addEventListener('click', () => resolveReport(btn.dataset.id));
    });

    document.getElementById('modalResolveBtn')?.addEventListener('click', function () {
        resolveReport(this.dataset.reportId);
    });

    async function resolveReport(reportId) {
        if (!confirm('هل تريد تغيير حالة هذا البلاغ إلى "محلول"؟')) return;
        await updateReport(reportId, 'resolve');
    }

    // ── رفض البلاغ (reject) ───────────────────────────────────────────────────
    document.querySelectorAll('.btn-reject-report').forEach(btn => {
        btn.addEventListener('click', () => rejectReport(btn.dataset.id));
    });

    document.getElementById('modalRejectBtn')?.addEventListener('click', function () {
        rejectReport(this.dataset.reportId);
    });

    async function rejectReport(reportId) {
        if (!confirm('هل تريد رفض هذا البلاغ؟')) return;
        await updateReport(reportId, 'reject');
    }

    async function updateReport(reportId, action) {
        try {
            const resp = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': window.CSRF_TOKEN || '',
                },
                body: new URLSearchParams({ action, report_id: reportId }),
            });
            const data = await resp.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'حدث خطأ أثناء التحديث.');
            }
        } catch (e) {
            console.error(e);
            alert('خطأ في الاتصال بالخادم.');
        }
    }
})();
