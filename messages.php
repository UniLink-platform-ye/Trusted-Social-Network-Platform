<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_user_login();
$me = current_user(); $uid = (int)$me['user_id'];

$activeId = (int)($_GET['with'] ?? 0); // user_id للمحادثة المفتوحة

/* ── إرسال رسالة ─────────────────────────────── */
if (is_post() && verify_csrf() && isset($_POST['send_msg'])) {
    $toId   = (int)($_POST['to_id'] ?? 0);
    $msg    = trim($_POST['message'] ?? '');
    if ($toId > 0 && $msg) {
        try {
            $ins = db()->prepare('INSERT INTO messages (sender_id,receiver_id,content,is_read,created_at) VALUES (:s,:r,:c,0,NOW())');
            $ins->execute([':s'=>$uid,':r'=>$toId,':c'=>$msg]);
        } catch(\Throwable $e) {}
    }
    redirect('messages.php?with=' . $toId);
}

/* ── قائمة المحادثات ─────────────────────────── */
try {
    $stmt = db()->prepare("
        SELECT u.user_id, u.full_name, u.username, u.role,
               m.content AS last_msg, m.created_at AS last_time,
               (SELECT COUNT(*) FROM messages mm WHERE mm.sender_id=u.user_id AND mm.receiver_id=:uid2 AND mm.is_read=0) AS unread
        FROM (
            SELECT CASE WHEN sender_id=:uid THEN receiver_id ELSE sender_id END AS partner_id,
                   MAX(created_at) AS last_at
            FROM messages WHERE sender_id=:uid3 OR receiver_id=:uid4
            GROUP BY partner_id
        ) AS conv
        JOIN users u ON u.user_id = conv.partner_id
        JOIN messages m ON m.created_at = conv.last_at
                       AND (m.sender_id=:uid5 OR m.receiver_id=:uid6)
        ORDER BY conv.last_at DESC
        LIMIT 30
    ");
    $stmt->bindValue(':uid', $uid); $stmt->bindValue(':uid2', $uid);
    $stmt->bindValue(':uid3', $uid); $stmt->bindValue(':uid4', $uid);
    $stmt->bindValue(':uid5', $uid); $stmt->bindValue(':uid6', $uid);
    $stmt->execute();
    $conversations = $stmt->fetchAll();
} catch(\Throwable $e) { $conversations = []; }

/* ── رسائل المحادثة المفتوحة ─────────────────── */
$chatUser = null; $chatMessages = [];
if ($activeId > 0) {
    try {
        $cu = db()->prepare('SELECT * FROM users WHERE user_id=:id LIMIT 1');
        $cu->execute([':id'=>$activeId]); $chatUser = $cu->fetch();
        // تحديث is_read
        db()->prepare('UPDATE messages SET is_read=1 WHERE sender_id=:s AND receiver_id=:r')->execute([':s'=>$activeId,':r'=>$uid]);
        // جلب الرسائل
        $ms = db()->prepare("SELECT * FROM messages WHERE (sender_id=:u AND receiver_id=:r) OR (sender_id=:r2 AND receiver_id=:u2) ORDER BY created_at ASC LIMIT 100");
        $ms->bindValue(':u',$uid); $ms->bindValue(':r',$activeId); $ms->bindValue(':r2',$activeId); $ms->bindValue(':u2',$uid);
        $ms->execute(); $chatMessages = $ms->fetchAll();
    } catch(\Throwable $e) {}
}

function relT(string $dt): string {
    $d=time()-strtotime($dt);
    if($d<60) return 'الآن';
    if($d<3600) return (int)($d/60).'د';
    if($d<86400) return (int)($d/3600).'س';
    return date('d/m',strtotime($dt));
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>الرسائل | <?= e(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= user_url('assets/css/app.css') ?>">
</head>
<body>
<div class="app-wrapper">
<?php include __DIR__ . '/partials/topnav.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="main-content" style="padding-bottom:0;">
<div class="page-header"><h1><i class="fa-solid fa-message" style="color:var(--primary);"></i> الرسائل الخاصة</h1></div>

<div class="messages-layout" style="height:calc(100vh - var(--topbar-h) - 100px);">

<!-- قائمة المحادثات -->
<div class="conversations-list">
    <div style="padding:.75rem 1rem;border-bottom:1px solid var(--border);font-weight:700;font-size:.875rem;background:#fafafa;">المحادثات</div>
    <?php if (empty($conversations)): ?>
    <div style="padding:2rem;text-align:center;color:var(--text-muted);font-size:.85rem;">
        <i class="fa-regular fa-message" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
        لا توجد محادثات بعد
    </div>
    <?php else: ?>
    <?php foreach ($conversations as $c): ?>
    <a href="?with=<?=(int)$c['user_id']?>" class="conv-item <?= $activeId===(int)$c['user_id']?'active':'' ?>" style="text-decoration:none;color:inherit;">
        <div class="avatar avatar-sm"><?= mb_substr($c['full_name']??'',0,1) ?></div>
        <div class="conv-info">
            <div class="conv-name"><?= htmlspecialchars($c['full_name']??$c['username'],ENT_QUOTES) ?></div>
            <div class="conv-preview"><?= htmlspecialchars(mb_substr($c['last_msg']??'',0,40),ENT_QUOTES) ?></div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.25rem;">
            <span class="conv-time"><?= relT((string)($c['last_time']??date('Y-m-d'))) ?></span>
            <?php if ((int)$c['unread']>0): ?><span class="unread-dot"></span><?php endif; ?>
        </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- نافذة الدردشة -->
<div class="chat-window">
<?php if ($chatUser): ?>
    <div class="chat-header">
        <div class="avatar avatar-sm"><?= mb_substr($chatUser['full_name']??'',0,1) ?></div>
        <div>
            <div style="font-weight:700;"><?= htmlspecialchars($chatUser['full_name']??'',ENT_QUOTES) ?></div>
            <div style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($chatUser['email']??'',ENT_QUOTES) ?></div>
        </div>
    </div>

    <div class="chat-messages" id="chatMessages">
        <?php if (empty($chatMessages)): ?>
        <div style="text-align:center;color:var(--text-muted);margin:auto;font-size:.875rem;">ابدأ المحادثة الآن!</div>
        <?php else: foreach ($chatMessages as $m): $mine=(int)$m['sender_id']===$uid; ?>
        <div class="chat-msg <?= $mine?'mine':'theirs' ?>">
            <?php if (!$mine): ?><div class="avatar avatar-sm"><?= mb_substr($chatUser['full_name']??'',0,1) ?></div><?php endif; ?>
            <div>
                <div class="chat-bubble"><?= nl2br(htmlspecialchars((string)$m['content'],ENT_QUOTES)) ?></div>
                <div class="chat-time"><?= date('H:i',strtotime((string)$m['created_at'])) ?></div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <form method="post" class="chat-input-area" id="chatForm">
        <?= csrf_input() ?>
        <input type="hidden" name="to_id" value="<?= $activeId ?>">
        <input type="hidden" name="send_msg" value="1">
        <input type="text" name="message" id="msgInput" class="chat-input" placeholder="اكتب رسالتك..." autocomplete="off" required>
        <button type="submit" class="btn btn-primary btn-icon"><i class="fa-solid fa-paper-plane"></i></button>
    </form>
<?php else: ?>
    <div class="empty-state" style="margin:auto;">
        <div class="empty-icon">💬</div>
        <h3>اختر محادثة</h3>
        <p>اختر من القائمة لفتح محادثة أو ابدأ محادثة جديدة</p>
    </div>
<?php endif; ?>
</div>
</div>
</main>
<script>
// التمرير لآخر رسالة
const msgs=document.getElementById('chatMessages');
if(msgs) msgs.scrollTop=msgs.scrollHeight;
</script>
</body>
</html>
