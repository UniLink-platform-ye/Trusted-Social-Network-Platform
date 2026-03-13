<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_user_login();
$me = current_user(); $uid = (int)$me['user_id'];

/* ── رفع ملف ───────────────────────────────────── */
$uploadError = ''; $uploadSuccess = '';
if (is_post() && verify_csrf() && isset($_FILES['file'])) {
    $file     = $_FILES['file'];
    $filename = basename($file['name'] ?? '');
    $size     = (int)($file['size'] ?? 0);
    $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed  = ['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','zip','jpg','png','mp4'];
    $maxSize  = 50 * 1024 * 1024; // 50MB

    if ($file['error'] !== UPLOAD_ERR_OK) { $uploadError = 'فشل رفع الملف.'; }
    elseif (!in_array($ext, $allowed)) { $uploadError = "امتداد .$ext غير مسموح."; }
    elseif ($size > $maxSize) { $uploadError = 'الملف أكبر من 50MB.'; }
    else {
        $uploadDir = __DIR__ . '/uploads/files/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
        $newName  = uniqid('f_', true) . '.' . $ext;
        $destPath = $uploadDir . $newName;
        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $desc    = trim($_POST['description'] ?? '');
            $groupId = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;
            $ins = db()->prepare('INSERT INTO files (user_id,group_id,file_name,original_name,file_type,file_size,description,created_at) VALUES (:u,:g,:fn,:on,:ft,:fs,:d,NOW())');
            $ins->execute([':u'=>$uid,':g'=>$groupId,':fn'=>$newName,':on'=>$filename,':ft'=>$ext,':fs'=>$size,':d'=>$desc]);
            log_activity('upload_file','files',(int)db()->lastInsertId(),"File: $filename");
            $uploadSuccess = "تم رفع الملف \"$filename\" بنجاح!";
        } else { $uploadError = 'فشل حفظ الملف.'; }
    }
}

/* ── جلب الملفات ───────────────────────────────── */
$filter = $_GET['type'] ?? '';
$search = trim($_GET['q'] ?? '');
$allowed_types = ['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','zip','jpg','png','mp4'];

try {
    $sql = "SELECT f.*, u.full_name, u.username, g.group_name
            FROM files f
            JOIN users u ON u.user_id=f.user_id
            LEFT JOIN groups g ON g.group_id=f.group_id
            WHERE 1=1";
    if ($filter && in_array($filter,$allowed_types)) $sql .= " AND f.file_type=:type";
    if ($search) $sql .= " AND (f.original_name LIKE :q OR f.description LIKE :q2)";
    $sql .= " ORDER BY f.created_at DESC LIMIT 60";
    $st = db()->prepare($sql);
    if ($filter && in_array($filter,$allowed_types)) $st->bindValue(':type',$filter);
    if ($search) { $q="%$search%"; $st->bindValue(':q',$q); $st->bindValue(':q2',$q); }
    $st->execute();
    $files = $st->fetchAll();
} catch(\Throwable $e) { $files = []; }

/* ── مجموعاتي ─────────────────────────────────── */
try {
    $gS = db()->prepare('SELECT g.group_id,g.group_name FROM groups g JOIN group_members gm ON gm.group_id=g.group_id WHERE gm.user_id=:uid ORDER BY g.group_name');
    $gS->execute([':uid'=>$uid]); $myGroups=$gS->fetchAll();
} catch(\Throwable $e) { $myGroups=[]; }

$fileIcons = ['pdf'=>'📄','doc'=>'📝','docx'=>'📝','ppt'=>'📊','pptx'=>'📊','xls'=>'📈','xlsx'=>'📈','txt'=>'📃','zip'=>'🗜️','jpg'=>'🖼️','png'=>'🖼️','mp4'=>'🎥'];
function formatSize(int $b): string {
    if($b<1024) return $b.'B'; if($b<1048576) return round($b/1024,1).'KB';
    return round($b/1048576,1).'MB';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>الملفات الأكاديمية | <?= e(APP_NAME) ?></title>
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
    <h1><i class="fa-solid fa-folder-open" style="color:var(--primary);"></i> الملفات الأكاديمية</h1>
    <p>شارك وحمّل المحاضرات والواجبات والمراجع</p>
</div>

<?php if ($uploadError): ?><div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i><?= e($uploadError) ?></div><?php endif; ?>
<?php if ($uploadSuccess): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i><?= e($uploadSuccess) ?></div><?php endif; ?>

<!-- شريط الأدوات -->
<div class="card mb-4" style="margin-bottom:1.25rem;">
<div class="card-body" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;">
    <form method="get" style="display:flex;gap:.5rem;flex:1;min-width:180px;">
        <div style="position:relative;flex:1;">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute;right:.85rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.8rem;"></i>
            <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="ابحث عن ملف..." style="padding-right:2.5rem;">
        </div>
        <input type="hidden" name="type" value="<?= e($filter) ?>">
        <button type="submit" class="btn btn-primary">بحث</button>
    </form>
    <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
        <a href="?" class="btn btn-sm <?= !$filter?'btn-primary':'btn-outline' ?>">الكل</a>
        <?php foreach(['pdf','docx','pptx','xlsx','zip','jpg','mp4'] as $t): ?>
        <a href="?type=<?=$t?>" class="btn btn-sm <?= $filter===$t?'btn-primary':'btn-outline' ?>"><?= $fileIcons[$t]??'📁' ?> <?= strtoupper($t) ?></a>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-success btn-sm" onclick="openModal('uploadModal')">
        <i class="fa-solid fa-cloud-arrow-up"></i> رفع ملف
    </button>
</div>
</div>

<!-- عرض الملفات -->
<?php if (empty($files)): ?>
<div class="empty-state">
    <div class="empty-icon">📂</div>
    <h3>لا توجد ملفات بعد</h3>
    <p>كن أول من يشارك ملفاً أكاديمياً!</p>
    <button class="btn btn-primary mt-3" onclick="openModal('uploadModal')"><i class="fa-solid fa-cloud-arrow-up"></i> رفع ملف</button>
</div>
<?php else: ?>
<div class="files-grid">
<?php foreach ($files as $f):
    $ext  = strtolower($f['file_type']??'');
    $icon = $fileIcons[$ext] ?? '📄';
    $name = $f['original_name'] ?? 'ملف';
    $filePath = user_url('uploads/files/' . ($f['file_name']??''));
?>
<div class="file-card">
    <div class="file-icon"><?= $icon ?></div>
    <div class="file-name" title="<?= htmlspecialchars($name,ENT_QUOTES) ?>"><?= htmlspecialchars(mb_substr($name,0,30),ENT_QUOTES) ?><?= strlen($name)>30?'...':'' ?></div>
    <?php if ($f['description']): ?><div class="file-meta" style="margin-bottom:.5rem;"><?= htmlspecialchars(mb_substr($f['description'],0,50),ENT_QUOTES) ?></div><?php endif; ?>
    <div class="file-meta">
        <span><?= formatSize((int)($f['file_size']??0)) ?></span> ·
        <span><?= htmlspecialchars($f['full_name']??'',ENT_QUOTES) ?></span>
    </div>
    <?php if ($f['group_name']): ?><div class="file-meta"><i class="fa-solid fa-users" style="color:var(--primary);"></i> <?= htmlspecialchars($f['group_name'],ENT_QUOTES) ?></div><?php endif; ?>
    <div style="margin-top:.75rem;display:flex;gap:.5rem;">
        <a href="<?= e($filePath) ?>" download class="btn btn-primary btn-sm" style="flex:1;"><i class="fa-solid fa-download"></i> تحميل</a>
        <?php if ((int)$f['user_id']===$uid || in_array($me['role'],['admin','supervisor'])): ?>
        <a href="<?= user_url('ajax/delete_file.php?id='.(int)$f['file_id'].'&csrf='.csrf_token()) ?>" class="btn btn-sm btn-ghost" style="color:var(--danger);" onclick="return confirm('حذف الملف؟')"><i class="fa-solid fa-trash"></i></a>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</main>

<!-- مودال رفع ملف -->
<div class="modal-backdrop" id="uploadModal">
<div class="modal-box">
    <div class="modal-header">
        <h3><i class="fa-solid fa-cloud-arrow-up" style="color:var(--success);"></i> رفع ملف جديد</h3>
        <button class="modal-close" onclick="closeModal('uploadModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="post" enctype="multipart/form-data">
        <div class="modal-body">
            <?= csrf_input() ?>
            <div class="form-group">
                <label class="form-label">الملف * <span style="color:var(--text-muted);font-weight:400">(PDF, Word, PowerPoint, Excel, Zip, صور, فيديو — حد 50MB)</span></label>
                <input type="file" name="file" class="form-control" required accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.jpg,.png,.mp4">
            </div>
            <div class="form-group">
                <label class="form-label">وصف الملف (اختياري)</label>
                <textarea name="description" class="form-control" rows="2" placeholder="محاضرة الأسبوع الثالث — مادة البرمجة..."></textarea>
            </div>
            <?php if (!empty($myGroups)): ?>
            <div class="form-group">
                <label class="form-label">ربط بمجموعة (اختياري)</label>
                <select name="group_id" class="form-select">
                    <option value="">-- بدون مجموعة (عام) --</option>
                    <?php foreach ($myGroups as $g): ?>
                    <option value="<?= (int)$g['group_id'] ?>"><?= htmlspecialchars($g['group_name'],ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div id="uploadProgress" style="display:none;">
                <div style="background:var(--border);border-radius:10px;height:8px;overflow:hidden;margin-top:.5rem;">
                    <div id="progressBar" style="background:var(--primary);height:100%;width:0%;transition:.3s;"></div>
                </div>
                <div style="font-size:.78rem;color:var(--text-muted);margin-top:.25rem;">جارٍ الرفع...</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('uploadModal')">إلغاء</button>
            <button type="submit" class="btn btn-success" id="uploadBtn"><i class="fa-solid fa-cloud-arrow-up"></i> رفع الملف</button>
        </div>
    </form>
</div>
</div>

<script>
function openModal(id){document.getElementById(id).classList.add('open');document.body.style.overflow='hidden';}
function closeModal(id){document.getElementById(id).classList.remove('open');document.body.style.overflow='';}
document.querySelectorAll('.modal-backdrop').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)closeModal(this.id);}));
function csrf_token(){return document.querySelector('input[name=csrf_token]')?.value||'';}
</script>
</body>
</html>
