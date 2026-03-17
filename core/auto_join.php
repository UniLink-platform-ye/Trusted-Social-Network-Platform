<?php
declare(strict_types=1);

/**
 * Auto-Join — ينشئ عضويات تلقائية حسب قواعد group_auto_join_rules.
 *
 * ملاحظة التوافق:
 * - إذا لم تُطبَّق migrations بعد (غياب جدول/أعمدة) نتجاهل بصمت حتى لا نكسر السلوك الحالي.
 */

function auto_join_apply(int $userId): void
{
    if ($userId <= 0) return;

    try {
        // قد تفشل إذا لم تُضاف الأعمدة بعد — سيتم التقاطها في catch.
        $uStmt = db()->prepare('SELECT user_id, department, academic_id, year_level, batch_year FROM users WHERE user_id = :id LIMIT 1');
        $uStmt->execute([':id' => $userId]);
        $u = $uStmt->fetch();
        if (!$u) return;

        $department = trim((string) ($u['department'] ?? ''));
        $academicId = trim((string) ($u['academic_id'] ?? ''));
        $yearLevel  = isset($u['year_level']) ? (int) $u['year_level'] : null;
        $batchYear  = isset($u['batch_year']) ? (int) $u['batch_year'] : null;

        // جلب القواعد النشطة التي قد تنطبق على المستخدم
        $rulesStmt = db()->prepare(
            'SELECT rule_id, group_id, department, academic_id_prefix, year_level, batch_year
             FROM group_auto_join_rules
             WHERE is_active = 1'
        );
        $rulesStmt->execute();
        $rules = $rulesStmt->fetchAll();
        if (!$rules) return;

        $ins = db()->prepare(
            'INSERT IGNORE INTO group_members (group_id, user_id, member_role, joined_at)
             VALUES (:g, :u, "member", NOW())'
        );

        foreach ($rules as $r) {
            // كل شرط NULL يعني "لا يقيّد"
            if (!empty($r['department']) && $department !== (string) $r['department']) continue;
            if (!empty($r['academic_id_prefix']) && ($academicId === '' || !str_starts_with($academicId, (string) $r['academic_id_prefix']))) continue;
            if ($r['year_level'] !== null && $yearLevel !== (int) $r['year_level']) continue;
            if ($r['batch_year'] !== null && $batchYear !== (int) $r['batch_year']) continue;

            $ins->execute([':g' => (int) $r['group_id'], ':u' => $userId]);
        }
    } catch (\Throwable $e) {
        // لا نكسر التسجيل/التحديث إذا كانت الجداول/الأعمدة غير موجودة بعد.
        error_log('Auto-Join skipped: ' . $e->getMessage());
    }
}

