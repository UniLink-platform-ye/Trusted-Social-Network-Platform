<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_user_login();

$me = current_user();
$uid = (int)$me['user_id'];

/* ── إنشاء منشور ─────────────────────────────────────── */
$postError = ''; $postSuccess = '';
if (is_post() && isset($_POST['action']) && $_POST['action'] === 'create_post') {
    if (!verify_csrf()) { $postError = 'طلب غير صالح.'; }
    else {
        $content  = trim($_POST['content'] ?? '');
        $type     = in_array($_POST['post_type']??'', ['post','announcement','question','lecture']) ? $_POST['post_type'] : 'post';
        $groupId  = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;
        $vis      = $groupId ? 'group' : 'public';
        if (!$content) { $postError = 'لا يمكن نشر منشور فارغ.'; }
        else {
            $ins = db()->prepare('INSERT INTO posts (user_id,group_id,content,post_type,visibility,status,created_at) VALUES (:u,:g,:c,:t,:v,"active",NOW())');
            $ins->execute([':u'=>$uid,':g'=>$groupId,':c'=>$content,':t'=>$type,':v'=>$vis]);
            log_activity('create_post','posts',(int)db()->lastInsertId(),'Post created');
            $postSuccess = 'تم نشر المنشور بنجاح!';
        }
    }
}

/* ── جلب المنشورات ───────────────────────────────────── */
$page  = max(1,(int)($_GET['page']??1));
$limit = 10; $offset = ($page-1)*$limit;

try {
    $stmt = db()->prepare("
        SELECT p.*, u.full_name, u.username, u.role, g.group_name
        FROM posts p
        JOIN users u ON u.user_id = p.user_id
        LEFT JOIN groups g ON g.group_id = p.group_id
        WHERE p.status = 'active'
          AND (p.visibility = 'public'
               OR (p.visibility = 'group' AND p.group_id IN
                   (SELECT gm.group_id FROM group_members gm WHERE gm.user_id = :uid)))
        ORDER BY p.created_at DESC
        LIMIT :lim OFFSET :off
    ");
    $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll();
} catch (\Throwable $e) { $posts = []; }

/* ── المجموعات للكومبو ────────────────────────────────── */
try {
    $gStmt = db()->prepare("SELECT g.group_id, g.group_name FROM groups g JOIN group_members gm ON gm.group_id=g.group_id WHERE gm.user_id=:uid ORDER BY g.group_name");
    $gStmt->execute([':uid'=>$uid]);
    $myGroups = $gStmt->fetchAll();
} catch(\Throwable $e) { $myGroups = []; }

/* ── إحصائيات سريعة ──────────────────────────────────── */
try {
    $sPost = db()->prepare('SELECT COUNT(*) FROM posts WHERE user_id=:id'); $sPost->execute([':id'=>$uid]);
    $statPosts = (int)$sPost->fetchColumn();
    $sGroup = db()->prepare('SELECT COUNT(*) FROM group_members WHERE user_id=:id'); $sGroup->execute([':id'=>$uid]);
    $statGroups = (int)$sGroup->fetchColumn();
} catch(\Throwable $e) { $statPosts=0; $statGroups=0; }

$typeLabels = ['post'=>'منشور','announcement'=>'إعلان','question'=>'سؤال','lecture'=>'محاضرة'];
$typeColors = ['post'=>'type-post','announcement'=>'type-announcement','question'=>'type-question','lecture'=>'type-lecture'];
$typeIcons  = ['post'=>'📝','announcement'=>'📢','question'=>'❓','lecture'=>'📖'];

$roleLabels = ['admin'=>'مدير','supervisor'=>'مشرف','professor'=>'أستاذ','student'=>'طالب'];

function relativeTime(string $dt): string {
    $diff = time() - strtotime($dt);
    if ($diff < 60) return 'الآن';
    if ($diff < 3600) return (int)($diff/60) . ' دقيقة';
    if ($diff < 86400) return (int)($diff/3600) . ' ساعة';
    if ($diff < 604800) return (int)($diff/86400) . ' يوم';
    return date('d/m/Y', strtotime($dt));
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>الرئيسية | <?= e(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= user_url('assets/css/app.css') ?>">
</head>
<body>
<div class="app-wrapper">
<?php include __DIR__ . '/partials/topnav.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="main-content">
<div class="feed-layout">

<!-- ─── خلاصة المنشورات ─────────────────────────────── -->
<div>
<?php if ($postError): ?><div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i><?= e($postError) ?></div><?php endif; ?>
<?php if ($postSuccess): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i><?= e($postSuccess) ?></div><?php endif; ?>

<!-- صندوق إنشاء منشور -->
<div class="create-post-card">
    <div class="create-post-row">
        <div class="avatar avatar-md"><?= mb_substr($me['full_name']??'U',0,1) ?></div>
        <button class="create-post-input text-start" onclick="openModal('createPostModal')" style="text-align:right;">
            ماذا تريد أن تشارك، <?= htmlspecialchars(explode(' ', $me['full_name']??'')[0], ENT_QUOTES) ?>؟
        </button>
    </div>
    <div class="create-post-actions">
        <button class="create-post-action" onclick="openModal('createPostModal')"><i class="fa-solid fa-pen-to-square" style="color:var(--primary);"></i> منشور</button>
        <button class="create-post-action" onclick="openModal('createPostModal')"><i class="fa-solid fa-bullhorn" style="color:var(--warning);"></i> إعلان</button>
        <button class="create-post-action" onclick="openModal('createPostModal')"><i class="fa-solid fa-circle-question" style="color:var(--info);"></i> سؤال</button>
    </div>
</div>

<!-- المنشورات -->
<?php if (empty($posts)): ?>
<div class="empty-state">
    <div class="empty-icon">📭</div>
    <h3>لا توجد منشورات بعد</h3>
    <p>انضم إلى بعض المجموعات أو كن أول من ينشر!</p>
    <a href="<?= user_url('groups.php') ?>" class="btn btn-primary mt-3">استكشف المجموعات</a>
</div>
<?php else: ?>
<?php foreach ($posts as $p): ?>
<article class="post-card">
    <div class="post-header">
        <div class="avatar avatar-md"><?= mb_substr($p['full_name']??'',0,1) ?></div>
        <div class="post-meta">
            <div class="post-author">
                <?= htmlspecialchars($p['full_name']??$p['username'], ENT_QUOTES) ?>
                <span class="badge badge-<?= e($p['role']??'student') ?>" style="font-size:.65rem;margin-right:.3rem;"><?= $roleLabels[$p['role']??''] ?? '' ?></span>
            </div>
            <div class="post-info">
                <?php if ($p['group_name']): ?>
                    <span class="post-group-tag"><i class="fa-solid fa-users"></i><?= htmlspecialchars($p['group_name'],ENT_QUOTES) ?></span> ·
                <?php endif; ?>
                <span><?= relativeTime((string)$p['created_at']) ?></span>
                · <span class="post-type-badge <?= $typeColors[$p['post_type']??'post'] ?? 'type-post' ?>"><?= ($typeIcons[$p['post_type']??'']??'') . ' ' . ($typeLabels[$p['post_type']??'']??'منشور') ?></span>
            </div>
        </div>
    </div>
    <div class="post-content"><?= nl2br(htmlspecialchars((string)$p['content'], ENT_QUOTES)) ?></div>
    <div class="post-actions">
        <button class="post-action-btn"><i class="fa-regular fa-heart"></i> إعجاب</button>
        <button class="post-action-btn"><i class="fa-regular fa-comment"></i> تعليق</button>
        <button class="post-action-btn"><i class="fa-solid fa-share-nodes"></i> مشاركة</button>
    </div>
</article>
<?php endforeach; ?>

<!-- Pagination -->
<?php if ($page > 1 || count($posts) === $limit): ?>
<div style="display:flex;justify-content:center;gap:.5rem;margin-top:1rem;">
    <?php if ($page > 1): ?><a href="?page=<?=$page-1?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-chevron-right"></i> السابق</a><?php endif; ?>
    <?php if (count($posts)===$limit): ?><a href="?page=<?=$page+1?>" class="btn btn-outline btn-sm">التالي <i class="fa-solid fa-chevron-left"></i></a><?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>
</div>

<!-- ─── الشريط الجانبي الأيسر ─────────────────────────── -->
<aside class="right-sidebar">
    <!-- بطاقة الملف الشخصي -->
    <div class="widget">
        <div style="background:linear-gradient(135deg,#1e3a5f,var(--primary));height:60px;border-radius:13px 13px 0 0;"></div>
        <div style="padding:0 1rem 1rem;text-align:center;margin-top:-30px;">
            <div class="avatar avatar-lg" style="margin:0 auto .5rem;border:3px solid #fff;"><?= mb_substr($me['full_name']??'',0,1) ?></div>
            <div style="font-weight:800;font-size:.95rem;"><?= htmlspecialchars($me['full_name']??'', ENT_QUOTES) ?></div>
            <div style="font-size:.75rem;color:var(--text-muted);"><?= htmlspecialchars($me['email']??'', ENT_QUOTES) ?></div>
            <div style="display:flex;justify-content:center;gap:1.5rem;margin-top:.75rem;padding-top:.75rem;border-top:1px solid var(--border);">
                <div style="text-align:center;"><div style="font-weight:800;color:var(--primary);"><?= $statPosts ?></div><div style="font-size:.7rem;color:var(--text-muted);">منشور</div></div>
                <div style="text-align:center;"><div style="font-weight:800;color:var(--primary);"><?= $statGroups ?></div><div style="font-size:.7rem;color:var(--text-muted);">مجموعة</div></div>
            </div>
        </div>
    </div>

    <!-- روابط سريعة -->
    <div class="widget">
        <div class="widget-header">🔗 روابط سريعة</div>
        <div class="widget-body" style="padding:.5rem 0;">
            <?php $links = [['href'=>'groups.php','icon'=>'fa-users','label'=>'مجموعاتي'],['href'=>'files.php','icon'=>'fa-folder','label'=>'الملفات'],['href'=>'messages.php','icon'=>'fa-message','label'=>'الرسائل'],['href'=>'profile.php','icon'=>'fa-user','label'=>'ملفي الشخصي']];
            foreach ($links as $l): ?>
            <a href="<?= user_url($l['href']) ?>" style="display:flex;align-items:center;gap:.6rem;padding:.5rem 1rem;color:var(--text);font-size:.875rem;font-weight:600;text-decoration:none;border-radius:8px;transition:.2s;" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background=''">
                <i class="fa-solid <?= $l['icon'] ?>" style="color:var(--primary);width:16px;"></i><?= $l['label'] ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</aside>
</div>
</main>

<!-- ─── مودال إنشاء منشور ─────────────────────────────── -->
<div class="modal-backdrop" id="createPostModal">
<div class="modal-box">
    <div class="modal-header">
        <h3><i class="fa-solid fa-pen-to-square" style="color:var(--primary);"></i> إنشاء منشور جديد</h3>
        <button class="modal-close" onclick="closeModal('createPostModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="post" id="createPostForm">
        <div class="modal-body">
            <?= csrf_input() ?><input type="hidden" name="action" value="create_post">
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
                <div class="avatar avatar-md"><?= mb_substr($me['full_name']??'',0,1) ?></div>
                <div>
                    <div style="font-weight:700;"><?= htmlspecialchars($me['full_name']??'', ENT_QUOTES) ?></div>
                    <select name="post_type" class="form-select" style="font-size:.75rem;padding:.25rem .5rem;width:auto;display:inline-block;">
                        <option value="post">📝 منشور عادي</option>
                        <option value="announcement">📢 إعلان</option>
                        <option value="question">❓ سؤال</option>
                        <option value="lecture">📖 محاضرة</option>
                    </select>
                </div>
            </div>
            <textarea name="content" class="form-control" placeholder="شارك أفكارك أو سؤالاً أو محتوى أكاديمياً..." rows="5" required autofocus></textarea>
            <?php if (!empty($myGroups)): ?>
            <div class="form-group mt-3">
                <label class="form-label"><i class="fa-solid fa-users" style="color:var(--primary);"></i> نشر في مجموعة (اختياري)</label>
                <select name="group_id" class="form-select">
                    <option value="">-- عام (يراه الجميع) --</option>
                    <?php foreach ($myGroups as $g): ?>
                    <option value="<?= (int)$g['group_id'] ?>"><?= htmlspecialchars($g['group_name'], ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('createPostModal')">إلغاء</button>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> نشر</button>
        </div>
    </form>
</div>
</div>

<script>
function openModal(id){document.getElementById(id).classList.add('open');document.body.style.overflow='hidden';}
function closeModal(id){document.getElementById(id).classList.remove('open');document.body.style.overflow='';}
document.querySelectorAll('.modal-backdrop').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)closeModal(this.id);}));
</script>
</body>
</html>
