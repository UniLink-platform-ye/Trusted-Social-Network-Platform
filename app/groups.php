<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_user_login();
$me = current_user(); $uid = (int)$me['user_id'];

/* ── انضمام / مغادرة ───────────────────────────── */
if (is_post() && verify_csrf()) {
    $action  = $_POST['action'] ?? '';
    $groupId = (int)($_POST['group_id'] ?? 0);
    if ($groupId > 0) {
        if ($action === 'join') {
            try {
                $ins = db()->prepare('INSERT IGNORE INTO group_members (group_id,user_id,role,joined_at) VALUES (:g,:u,"member",NOW())');
                $ins->execute([':g'=>$groupId,':u'=>$uid]);
                log_activity('join_group','groups',$groupId,'User joined group');
            } catch(\Throwable $e) {}
        } elseif ($action === 'leave') {
            try {
                $del = db()->prepare('DELETE FROM group_members WHERE group_id=:g AND user_id=:u');
                $del->execute([':g'=>$groupId,':u'=>$uid]);
                log_activity('leave_group','groups',$groupId,'User left group');
            } catch(\Throwable $e) {}
        }
    }
    redirect('app/groups.php');
}

/* ── جلب المجموعات ─────────────────────────────── */
$search = trim($_GET['q'] ?? '');
$filter = in_array($_GET['f']??'', ['all','mine']) ? $_GET['f'] : 'all';

try {
    $sql = "SELECT g.*, u.full_name AS creator_name,
               (SELECT COUNT(*) FROM group_members gm2 WHERE gm2.group_id=g.group_id) AS member_count,
               (SELECT COUNT(*) FROM group_members gm3 WHERE gm3.group_id=g.group_id AND gm3.user_id=:uid) AS is_member
            FROM groups g
            LEFT JOIN users u ON u.user_id = g.created_by
            WHERE g.status = 'active'";
    if ($filter === 'mine') $sql .= " AND g.group_id IN (SELECT group_id FROM group_members WHERE user_id=:uid2)";
    if ($search) $sql .= " AND (g.group_name LIKE :q OR g.description LIKE :q2)";
    $sql .= " ORDER BY member_count DESC, g.created_at DESC";

    $st = db()->prepare($sql);
    $st->bindValue(':uid', $uid, PDO::PARAM_INT);
    if ($filter === 'mine') $st->bindValue(':uid2', $uid, PDO::PARAM_INT);
    if ($search) { $q="%$search%"; $st->bindValue(':q',$q); $st->bindValue(':q2',$q); }
    $st->execute();
    $groups = $st->fetchAll();
} catch(\Throwable $e) { $groups = []; }

$groupTypeLabels = ['course'=>'مقرر','department'=>'قسم','activity'=>'نشاط طلابي','administrative'=>'إداري'];
$groupTypeEmojis = ['course'=>'📚','department'=>'🏛️','activity'=>'🎯','administrative'=>'📋'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>المجموعات | <?= e(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= user_url('assets/css/app.css') ?>">
</head>
<body>
<div class="app-wrapper">
<?php include __DIR__ . '/partials/topnav.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="main-content">
<div class="page-header">
    <h1><i class="fa-solid fa-users" style="color:var(--primary);"></i> المجموعات الأكاديمية</h1>
    <p>استكشف المجموعات وانضم إليها للتواصل مع زملائك</p>
</div>

<!-- شريط البحث والفلاتر -->
<div class="card mb-4" style="margin-bottom:1.25rem;">
    <div class="card-body" style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
        <form method="get" style="flex:1;display:flex;gap:.5rem;min-width:200px;">
            <div style="position:relative;flex:1;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;right:.85rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.8rem;"></i>
                <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="ابحث عن مجموعة..." style="padding-right:2.5rem;">
            </div>
            <input type="hidden" name="f" value="<?= e($filter) ?>">
            <button type="submit" class="btn btn-primary">بحث</button>
        </form>
        <div style="display:flex;gap:.5rem;">
            <a href="?f=all" class="btn <?= $filter==='all'?'btn-primary':'btn-outline' ?> btn-sm">الكل</a>
            <a href="?f=mine" class="btn <?= $filter==='mine'?'btn-primary':'btn-outline' ?> btn-sm">مجموعاتي</a>
        </div>
        <?php if(in_array($me['role'],['admin','supervisor','professor'])): ?>
        <button class="btn btn-success btn-sm" onclick="openModal('createGroupModal')">
            <i class="fa-solid fa-plus"></i> إنشاء مجموعة
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- شبكة المجموعات -->
<?php if (empty($groups)): ?>
<div class="empty-state">
    <div class="empty-icon">🏷️</div>
    <h3>لا توجد مجموعات</h3>
    <p><?= $search ? "لا نتائج لـ \"$search\"" : 'لم تنضم لأي مجموعة بعد.' ?></p>
</div>
<?php else: ?>
<div class="groups-grid">
<?php foreach ($groups as $g):
    $type   = $g['group_type'] ?? 'course';
    $emoji  = $groupTypeEmojis[$type] ?? '📁';
    $label  = $groupTypeLabels[$type] ?? $type;
    $isMem  = (int)$g['is_member'] > 0;
    $covers = ['course'=>'135deg,#1e3a5f,#2563eb','department'=>'135deg,#065f46,#16a34a','activity'=>'135deg,#7c2d12,#f59e0b','administrative'=>'135deg,#4c1d95,#8b5cf6'];
    $covGrad = $covers[$type] ?? '135deg,#1e3a5f,#2563eb';
?>
<div class="group-card">
    <div class="group-cover" style="background:linear-gradient(<?=$covGrad?>);">
        <span style="font-size:2.5rem;"><?= $emoji ?></span>
    </div>
    <div class="group-body">
        <div style="display:flex;align-items:start;justify-content:space-between;margin-bottom:.5rem;">
            <div class="group-name"><?= htmlspecialchars($g['group_name'],ENT_QUOTES) ?></div>
            <span class="badge badge-primary" style="font-size:.65rem;white-space:nowrap;"><?= $label ?></span>
        </div>
        <div class="group-desc"><?= htmlspecialchars($g['description']??'لا يوجد وصف.',ENT_QUOTES) ?></div>
        <div class="group-meta">
            <span><i class="fa-solid fa-users" style="color:var(--primary);"></i> <?= (int)$g['member_count'] ?> عضو</span>
            <span style="font-size:.72rem;"><?= htmlspecialchars($g['creator_name']??'',ENT_QUOTES) ?></span>
        </div>
        <div style="margin-top:.75rem;">
            <?php if ($isMem): ?>
            <form method="post" style="display:inline;">
                <?= csrf_input() ?><input type="hidden" name="action" value="leave"><input type="hidden" name="group_id" value="<?=(int)$g['group_id']?>">
                <button class="btn btn-outline btn-sm btn-block" style="color:var(--danger);border-color:var(--danger);" onclick="return confirm('مغادرة هذه المجموعة؟')"><i class="fa-solid fa-right-from-bracket"></i> مغادرة</button>
            </form>
            <?php else: ?>
            <form method="post" style="display:inline;">
                <?= csrf_input() ?><input type="hidden" name="action" value="join"><input type="hidden" name="group_id" value="<?=(int)$g['group_id']?>">
                <button class="btn btn-primary btn-sm btn-block"><i class="fa-solid fa-user-plus"></i> انضمام</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</main>

<!-- مودال إنشاء مجموعة -->
<div class="modal-backdrop" id="createGroupModal">
<div class="modal-box">
    <div class="modal-header">
        <h3><i class="fa-solid fa-plus" style="color:var(--success);"></i> إنشاء مجموعة جديدة</h3>
        <button class="modal-close" onclick="closeModal('createGroupModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="post" action="<?= user_url('ajax/create_group.php') ?>" id="createGroupForm">
        <div class="modal-body">
            <?= csrf_input() ?>
            <div class="form-group"><label class="form-label">اسم المجموعة *</label><input type="text" name="group_name" class="form-control" required placeholder="مثال: برمجة الشبكات 2024"></div>
            <div class="form-group"><label class="form-label">الوصف</label><textarea name="description" class="form-control" rows="3" placeholder="وصف المجموعة وأهدافها..."></textarea></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                <div class="form-group"><label class="form-label">نوع المجموعة</label>
                    <select name="group_type" class="form-select">
                        <option value="course">📚 مقرر دراسي</option>
                        <option value="department">🏛️ قسم أكاديمي</option>
                        <option value="activity">🎯 نشاط طلابي</option>
                        <option value="administrative">📋 إداري</option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">الخصوصية</label>
                    <select name="privacy" class="form-select">
                        <option value="public">🌍 عامة</option>
                        <option value="private">🔒 خاصة</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('createGroupModal')">إلغاء</button>
            <button type="submit" class="btn btn-success"><i class="fa-solid fa-plus"></i> إنشاء المجموعة</button>
        </div>
    </form>
</div>
</div>

<script>
function openModal(id){document.getElementById(id).classList.add('open');document.body.style.overflow='hidden';}
function closeModal(id){document.getElementById(id).classList.remove('open');document.body.style.overflow='';}
document.querySelectorAll('.modal-backdrop').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)closeModal(this.id);}));

// إرسال نموذج إنشاء مجموعة
document.getElementById('createGroupForm')?.addEventListener('submit',function(e){
    e.preventDefault();
    const form=this, data=new FormData(form);
    const obj={}; data.forEach((v,k)=>obj[k]=v);
    obj.action='create_group';
    fetch(form.action,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body:new URLSearchParams(obj)})
    .then(r=>r.json()).then(res=>{
        if(res.success){closeModal('createGroupModal');location.reload();}
        else alert(res.message||'حدث خطأ.');
    });
});
</script>
</body>
</html>
