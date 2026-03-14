<?php declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$me=api_require_auth(); $uid=(int)$me['user_id'];
$method=$_SERVER['REQUEST_METHOD'];

if ($method==='GET') {
    $withId=(int)($_GET['with']??0);
    if ($withId) {
        // رسائل محادثة محددة
        db()->prepare('UPDATE messages SET is_read=1 WHERE sender_id=:s AND receiver_id=:r')->execute([':s'=>$withId,':r'=>$uid]);
        $st=db()->prepare('SELECT m.*,sender.full_name AS sender_name,receiver.full_name AS receiver_name FROM messages m JOIN users sender ON sender.user_id=m.sender_id JOIN users receiver ON receiver.user_id=m.receiver_id WHERE (m.sender_id=:u AND m.receiver_id=:r) OR (m.sender_id=:r2 AND m.receiver_id=:u2) ORDER BY m.created_at ASC LIMIT 100');
        $st->bindValue(':u',$uid);$st->bindValue(':r',$withId);$st->bindValue(':r2',$withId);$st->bindValue(':u2',$uid);
        $st->execute();
        api_ok(['messages'=>$st->fetchAll()]);
    } else {
        // قائمة المحادثات
        $st=db()->prepare("SELECT u.user_id,u.full_name,u.email,u.avatar_url,m.content AS last_msg,m.created_at AS last_time,(SELECT COUNT(*) FROM messages mm WHERE mm.sender_id=u.user_id AND mm.receiver_id=:uid2 AND mm.is_read=0) AS unread FROM (SELECT CASE WHEN sender_id=:uid THEN receiver_id ELSE sender_id END AS partner_id,MAX(created_at) AS last_at FROM messages WHERE sender_id=:uid3 OR receiver_id=:uid4 GROUP BY partner_id) AS conv JOIN users u ON u.user_id=conv.partner_id JOIN messages m ON m.created_at=conv.last_at AND (m.sender_id=:uid5 OR m.receiver_id=:uid6) ORDER BY conv.last_at DESC LIMIT 30");
        for ($i=1;$i<=6;$i++) $st->bindValue(':uid'.($i===1?'':$i),$uid);
        $st->execute();
        api_ok(['conversations'=>$st->fetchAll()]);
    }
} elseif ($method==='POST') {
    $b=json_body(); $toId=(int)($b['to_id']??0); $msg=trim($b['message']??'');
    if (!$toId||!$msg) api_error('to_id و message مطلوبان');
    $ins=db()->prepare('INSERT INTO messages (sender_id,receiver_id,content,is_read,created_at) VALUES (:s,:r,:c,0,NOW())');
    $ins->execute([':s'=>$uid,':r'=>$toId,':c'=>$msg]);
    notify_new_message($toId,$me['full_name']??'مستخدم',$uid);
    api_ok(['message_id'=>(int)db()->lastInsertId()],'تم إرسال الرسالة',201);
} else api_error('Method Not Allowed',405);
