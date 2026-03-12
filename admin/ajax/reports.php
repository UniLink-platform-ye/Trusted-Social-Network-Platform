<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_ajax_request()) {
    http_response_code(403);
    exit;
}

require_permission('reports.review');
verify_csrf_or_abort();

$action   = trim((string) ($_POST['action'] ?? ''));
$reportId = (int) ($_POST['report_id'] ?? 0);

if ($reportId <= 0) {
    json_response(['success' => false, 'message' => 'معرف البلاغ غير صحيح.'], 422);
}

// التحقق من وجود البلاغ
$reportStmt = db()->prepare("SELECT report_id, status FROM reports WHERE report_id = :id LIMIT 1");
$reportStmt->execute([':id' => $reportId]);
$report = $reportStmt->fetch();

if (!$report) {
    json_response(['success' => false, 'message' => 'البلاغ غير موجود.'], 404);
}

$currentUser = current_user();

switch ($action) {
    case 'resolve':
        if ($report['status'] === 'resolved') {
            json_response(['success' => false, 'message' => 'البلاغ محلول بالفعل.']);
        }
        require_permission('reports.resolve');
        $stmt = db()->prepare(
            "UPDATE reports SET status = 'resolved', handled_by = :uid, updated_at = NOW() WHERE report_id = :rid"
        );
        $stmt->execute([':uid' => $currentUser['user_id'], ':rid' => $reportId]);
        log_activity('report_submit', 'reports', $reportId, "تم حل البلاغ #{$reportId}");
        json_response(['success' => true, 'message' => 'تم حل البلاغ بنجاح.']);
        break;

    case 'reject':
        if ($report['status'] === 'rejected') {
            json_response(['success' => false, 'message' => 'البلاغ مرفوض بالفعل.']);
        }
        require_permission('reports.resolve');
        $stmt = db()->prepare(
            "UPDATE reports SET status = 'rejected', handled_by = :uid, updated_at = NOW() WHERE report_id = :rid"
        );
        $stmt->execute([':uid' => $currentUser['user_id'], ':rid' => $reportId]);
        log_activity('report_submit', 'reports', $reportId, "تم رفض البلاغ #{$reportId}");
        json_response(['success' => true, 'message' => 'تم رفض البلاغ.']);
        break;

    case 'review':
        $stmt = db()->prepare(
            "UPDATE reports SET status = 'under_review', handled_by = :uid, updated_at = NOW() WHERE report_id = :rid"
        );
        $stmt->execute([':uid' => $currentUser['user_id'], ':rid' => $reportId]);
        json_response(['success' => true, 'message' => 'تم وضع البلاغ قيد المراجعة.']);
        break;

    default:
        json_response(['success' => false, 'message' => 'إجراء غير معروف.'], 400);
}
