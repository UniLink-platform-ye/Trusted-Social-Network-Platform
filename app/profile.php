<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_user_login();
$me = current_user(); $uid = (int)$me['user_id'];

$targetId = isset($_GET['id']) ? (int)$_GET['id'] : $uid;
$isOwn    = $targetId === $uid;

/* ── جلب بيانات المستخدم ──────────────────────── */
$user = null;
try {
    $stmt = db()->prepare('SELECT * FROM users WHERE user_id=:id LIMIT 1');
    $stmt->execute([':id'=>$targetId]); $user=$stmt->fetch();
} catch(\Throwable $e) {}
if (!$user) { flash('error','المستخدم غير موجود.'); redirect('app/feed.php'); }

/* ── تحديث الملف الشخصي ───────────────────────── */
$editError = ''; $editSuccess = '';
if ($isOwn && is_post() && isset($_POST['update_profile']) && verify_csrf()) {
    $fullName   = trim($_POST['full_name']   ?? '');
    $department = trim($_POST['department']  ?? '');
    $academicId = trim($_POST['academic_id'] ?? '');
    $bio        = trim($_POST['bio']         ?? '');
    $newPass    = $_POST['new_password']     ?? '';
    $curPass    = $_POST['current_password'] ?? '';

    if (!$fullName) { $editError = 'الاسم الكامل مطلوب.'; }
    else {
        $upd = db()->prepare('UPDATE users SET full_name=:fn,department=:dep,academic_id=:ai,bio=:bio,updated_at=NOW() WHERE user_id=:id');
        $upd->execute([':fn'=>$fullName,':dep'=>$department,':ai'=>$academicId,':bio'=>$bio,':id'=>$uid]);

        if ($newPass) {
            $hash = (string)$user['password_hash'];
            if (str_starts_with($hash,'$2b$')) $hash='$2y$'.substr($hash,4);
            if (!password_verify($curPass, $hash)) { $editError = 'كلمة المرور الحالية غير صحيحة.'; }
            elseif (strlen($newPass)<8) { $editError = 'كلمة المرور الجديدة يجب 8 أحرف على الأقل.'; }
            else {
                $ph = password_hash($newPass, PASSWORD_BCRYPT);
                db()->prepare('UPDATE users SET password_hash=:h WHERE user_id=:id')->execute([':h'=>$ph,':id'=>$uid]);
            }
        }

        if (!$editError) {
            $editSuccess = 'تم تحديث الملف الشخصي بنجاح!';
            // تحديث الجلسة
            $_SESSION['user']['full_name'] = $fullName;
            $user['full_name'] = $fullName;
        }
    }
}

/* ── الإحصائيات ───────────────────────────────── */
try {
    $sP=db()->prepare('SELECT COUNT(*) FROM posts WHERE user_id=:id');$sP->execute([':id'=>$targetId]);$cPosts=(int)$sP->fetchColumn();
    $sG=db()->prepare('SELECT COUNT(*) FROM group_members WHERE user_id=:id');$sG->execute([':id'=>$targetId]);$cGroups=(int)$sG->fetchColumn();
    $sF=db()->prepare('SELECT COUNT(*) FROM files WHERE user_id=:id');$sF->execute([':id'=>$targetId]);$cFiles=(int)$sF->fetchColumn();
} catch(\Throwable $e) { $cPosts=$cGroups=$cFiles=0; }

/* ── منشورات المستخدم ─────────────────────────── */
try {
    $pS=db()->prepare("SELECT p.*,g.group_name FROM posts p LEFT JOIN groups g ON g.group_id=p.group_id WHERE p.user_id=:id AND p.status='active' ORDER BY p.created_at DESC LIMIT 10");
    $pS->execute([':id'=>$targetId]);$userPosts=$pS->fetchAll();
} catch(\Throwable $e) { $userPosts=[]; }

$roleLabels=['admin'=>'مدير النظام','supervisor'=>'مشرف','professor'=>'أستاذ','student'=>'طالب'];
$departments=['Computer Science','Information Systems','IT','Business Administration','Engineering','Medicine','Law','Arts'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($user['full_name']??'الملف الشخصي',ENT_QUOTES) ?> | <?= e(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= user_url('assets/css/app.css') ?>">
</head>
<body>
<div class="app-wrapper">
<?php include __DIR__ . '/partials/topnav.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="main-content">
<div class="profile-layout">

<!-- ─── بطاقة الملف الشخصي ─────────────────────── -->
<div>
<div class="card" style="margin-bottom:1rem;">
    <div style="height:100px;background:linear-gradient(135deg,#1e3a5f,var(--primary));border-radius:13px 13px 0 0;"></div>
    <div class="profile-card-header">
        <div class="avatar avatar-xl" style="margin:-50px auto 0;border:4px solid #fff;box-shadow:var(--shadow-md);">
            <?= mb_substr($user['full_name']??'U',0,1) ?>
        </div>
        <div class="profile-name"><?= htmlspecialchars($user['full_name']??'',ENT_QUOTES) ?></div>
        <div class="profile-role">
            <span class="badge badge-<?= e($user['role']) ?>"><?= $roleLabels[$user['role']??'']??'' ?></span>
        </div>
        <?php if ($user['department']): ?>
        <div style="font-size:.8rem;color:var(--text-muted);margin-top:.25rem;"><i class="fa-solid fa-building-columns" style="color:var(--primary);"></i> <?= htmlspecialchars($user['department'],ENT_QUOTES) ?></div>
        <?php endif; ?>
        <?php if ($user['academic_id']): ?>
        <div style="font-size:.8rem;color:var(--text-muted);"><i class="fa-solid fa-id-card" style="color:var(--primary);"></i> <?= htmlspecialchars($user['academic_id'],ENT_QUOTES) ?></div>
        <?php endif; ?>
        <?php if (!empty($user['bio'])): ?>
        <div style="font-size:.85rem;color:var(--text);margin-top:.75rem;line-height:1.6;"><?= nl2br(htmlspecialchars($user['bio'],ENT_QUOTES)) ?></div>
        <?php endif; ?>

        <div class="profile-stats">
            <div class="profile-stat"><div class="stat-value"><?=$cPosts?></div><div class="stat-label">منشور</div></div>
            <div class="profile-stat"><div class="stat-value"><?=$cGroups?></div><div class="stat-label">مجموعة</div></div>
            <div class="profile-stat"><div class="stat-value"><?=$cFiles?></div><div class="stat-label">ملف</div></div>
        </div>

        <?php if ($isOwn): ?>
        <button class="btn btn-outline btn-sm btn-block mt-3" onclick="openModal('editProfileModal')">
            <i class="fa-solid fa-pen-to-square"></i> تعديل الملف الشخصي
        </button>
        <?php else: ?>
        <a href="<?= user_url('messages.php?with='.$targetId) ?>" class="btn btn-primary btn-sm btn-block mt-3">
            <i class="fa-solid fa-message"></i> إرسال رسالة
        </a>
        <?php endif; ?>
    </div>

    <div style="padding:1rem;border-top:1px solid var(--border);">
        <div style="display:flex;flex-direction:column;gap:.5rem;font-size:.82rem;color:var(--text-muted);">
            <div><i class="fa-regular fa-envelope" style="color:var(--primary);width:16px;"></i> <?= htmlspecialchars($user['email']??'',ENT_QUOTES) ?></div>
            <?php if ($user['last_login']): ?>
            <div><i class="fa-regular fa-clock" style="color:var(--primary);width:16px;"></i> آخر دخول: <?= date('Y/m/d H:i',strtotime((string)$user['last_login'])) ?></div>
            <?php endif; ?>
            <div><i class="fa-solid fa-calendar-plus" style="color:var(--primary);width:16px;"></i> عضو منذ: <?= date('Y/m/d',strtotime((string)($user['created_at']??'now'))) ?></div>
        </div>
    </div>
</div>
</div>

<!-- ─── المنشورات والنشاط ──────────────────────── -->
<div>
<?php if ($editError): ?><div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i><?= e($editError) ?></div><?php endif; ?>
<?php if ($editSuccess): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i><?= e($editSuccess) ?></div><?php endif; ?>

<div class="card mb-4" style="margin-bottom:1.25rem;">
    <div class="card-header"><h3>📝 المنشورات الأخيرة</h3></div>
    <div style="padding:.5rem;">
    <?php if (empty($userPosts)): ?>
    <div style="padding:2rem;text-align:center;color:var(--text-muted);font-size:.875rem;">لا توجد منشورات بعد.</div>
    <?php else: ?>
    <?php foreach ($userPosts as $p): ?>
    <div style="padding:.85rem 1rem;border-bottom:1px solid var(--border);">
        <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:.3rem;">
            <?php if ($p['group_name']): ?><span><i class="fa-solid fa-users" style="color:var(--primary);"></i> <?= htmlspecialchars($p['group_name'],ENT_QUOTES) ?> · </span><?php endif; ?>
            <?= date('Y/m/d H:i',strtotime((string)$p['created_at'])) ?>
        </div>
        <div style="font-size:.875rem;color:var(--text);line-height:1.6;"><?= nl2br(htmlspecialchars(mb_substr((string)$p['content'],0,200),ENT_QUOTES)) ?><?= strlen((string)$p['content'])>200?'...':'' ?></div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>
</div>
</div>
</main>

<!-- مودال تعديل الملف الشخصي -->
<?php if ($isOwn): ?>
<div class="modal-backdrop" id="editProfileModal">
<div class="modal-box" style="max-width:580px;">
    <div class="modal-header">
        <h3><i class="fa-solid fa-pen-to-square" style="color:var(--primary);"></i> تعديل الملف الشخصي</h3>
        <button class="modal-close" onclick="closeModal('editProfileModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="post">
        <div class="modal-body">
            <?= csrf_input() ?><input type="hidden" name="update_profile" value="1">
            <div class="form-group"><label class="form-label">الاسم الكامل *</label><input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']??'',ENT_QUOTES) ?>" required></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                <div class="form-group"><label class="form-label">الرقم الأكاديمي</label><input type="text" name="academic_id" class="form-control" value="<?= htmlspecialchars($user['academic_id']??'',ENT_QUOTES) ?>"></div>
                <div class="form-group"><label class="form-label">القسم</label>
                    <select name="department" class="form-select">
                        <option value="">-- اختر --</option>
                        <?php foreach($departments as $d): ?><option value="<?=e($d)?>" <?= ($user['department']??'')===$d?'selected':''?>><?=e($d)?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group"><label class="form-label">نبذة شخصية</label><textarea name="bio" class="form-control" rows="3" placeholder="اكتب نبذة عن نفسك..."><?= htmlspecialchars($user['bio']??'',ENT_QUOTES) ?></textarea></div>
            <div class="divider">تغيير كلمة المرور (اتركها فارغة إذا لم ترد التغيير)</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                <div class="form-group"><label class="form-label">كلمة المرور الحالية</label><input type="password" name="current_password" class="form-control"></div>
                <div class="form-group"><label class="form-label">كلمة المرور الجديدة</label><input type="password" name="new_password" class="form-control" minlength="8"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('editProfileModal')">إلغاء</button>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ التغييرات</button>
        </div>
    </form>
</div>
</div>
<?php endif; ?>

<script>
function openModal(id){document.getElementById(id).classList.add('open');document.body.style.overflow='hidden';}
function closeModal(id){document.getElementById(id).classList.remove('open');document.body.style.overflow='';}
document.querySelectorAll('.modal-backdrop').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)closeModal(this.id);}));
// فتح مودال التعديل تلقائياً عند وجود خطأ
<?php if ($editError): ?>openModal('editProfileModal');<?php endif; ?>
</script>
</body>
</html>
