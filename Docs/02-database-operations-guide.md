<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

<div dir="rtl" style="font-family: 'Cairo', Tahoma, Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 1.5rem;">

# دليل عمليات قاعدة بيانات UniLink

## المحتويات

1. [نظرة عامة](#نظرة-عامة)
2. [القسم A — استعلامات SELECT](#قسم-a--استعلامات-select)
3. [القسم B — عمليات INSERT](#قسم-b--عمليات-insert)
4. [القسم C — عمليات UPDATE](#قسم-c--عمليات-update)
5. [القسم D — عمليات DELETE](#قسم-d--عمليات-delete)
6. [القسم E — معالجات Transactions](#قسم-e--معالجات-transactions)
7. [القسم F — الفهارس Indexes](#قسم-f--الفهارس)
8. [أفضل الممارسات العامة](#أفضل-الممارسات)

---

## نظرة عامة

يوفر هذا الدليل مجموعة شاملة من الاستعلامات والعمليات النموذجية التي تُمثّل ما سيُنفّذه الخادم على قاعدة بيانات UniLink خلال الاستخدام الفعلي.

> [!NOTE]
> جميع الاستعلامات في هذا الدليل هي نماذج مرجعية للمطورين. لا تحتوي على كود PHP أو أي لغة تطبيق، بل هي SQL خالص يمكن تشغيله مباشرة.

---

## قسم A — استعلامات SELECT

### A-1: تغذية الأخبار (News Feed)

**الغرض:** جلب المنشورات التي يراها المستخدم الحالي مرتبةً بالأحدث مع ترقيم الصفحات.

**ما تجلبه:** تفاصيل المنشور + بيانات صاحبه + اسم المجموعة إن وُجدت.

**منطق الفلترة:**
- المنشورات العامة (`visibility = 'public'`) تُجلب لجميع المستخدمين.
- منشورات المجموعات (`visibility = 'group'`) تُجلب فقط للأعضاء المنتسبين.
- المنشورات الموقوفة (`is_flagged = 1`) تُستثنى تلقائياً.

**تعديل الترقيم:** غيّر `LIMIT 10 OFFSET 0` لتغيير حجم الصفحة وموقعها.

```sql
SELECT
    p.post_id,
    p.content,
    p.type,
    p.visibility,
    p.likes_count,
    p.comments_count,
    p.created_at,
    u.user_id    AS author_id,
    u.full_name  AS author_name,
    u.avatar_url AS author_avatar,
    u.role       AS author_role,
    g.group_id,
    g.group_name
FROM `posts` p
INNER JOIN `users`  u ON p.user_id   = u.user_id
LEFT  JOIN `groups` g ON p.group_id  = g.group_id
WHERE p.is_flagged = 0
  AND (
      p.visibility = 'public'
      OR (
          p.visibility = 'group'
          AND p.group_id IN (
              SELECT group_id
              FROM `group_members`
              WHERE user_id = 5  -- ← ضع هنا معرّف المستخدم الحالي
          )
      )
  )
ORDER BY p.created_at DESC
LIMIT 10 OFFSET 0;
```

---

### A-2: محادثة بين مستخدمَين

**الغرض:** جلب تاريخ الرسائل المتبادلة بين مستخدمَين محددَين.

**ما تجلبه:** محتوى الرسالة + اسم المُرسِل + اسم المُستقبِل + معلومات الملف المرفق إن وُجد.

**التحكم بالمحادثة:** غيّر قيم `sender_id` و`receiver_id` عند استدعاء الاستعلام.

```sql
SELECT
    m.msg_id,
    m.content,
    m.type,
    m.is_read,
    m.created_at,
    s.user_id   AS sender_id,
    s.full_name AS sender_name,
    r.user_id   AS receiver_id,
    r.full_name AS receiver_name,
    f.original_name AS file_name,
    f.storage_path  AS file_path
FROM `messages` m
INNER JOIN `users` s ON m.sender_id   = s.user_id
INNER JOIN `users` r ON m.receiver_id = r.user_id
LEFT  JOIN `files` f ON m.file_id     = f.file_id
WHERE
    (m.sender_id = 5 AND m.receiver_id = 3)
    OR
    (m.sender_id = 3 AND m.receiver_id = 5)
ORDER BY m.created_at ASC
LIMIT 20 OFFSET 0;
```

---

### A-3 و A-4: الإشعارات

- **A-3** يجلب قائمة الإشعارات غير المقروءة للمستخدم.
- **A-4** يجلب عدداً صغيراً (عدد الإشعارات) لعرض الشارة Badge في واجهة التطبيق.

**الاستخدام المُوصى:** استدعاء A-4 بشكل دوري (كل 30 ثانية) لتحديث الشارة، وA-3 عند فتح نافذة الإشعارات.

```sql
-- A-3: قائمة الإشعارات غير المقروءة
SELECT
    notification_id,
    type,
    content,
    reference,
    is_read,
    created_at
FROM `notifications`
WHERE user_id = 5      -- ← معرّف المستخدم الحالي
  AND is_read = 0
ORDER BY created_at DESC
LIMIT 20;

-- A-4: عدد الإشعارات غير المقروءة (للشارة Badge)
SELECT COUNT(*) AS unread_count
FROM `notifications`
WHERE user_id = 5 AND is_read = 0;
```

---

### A-5: إحصائيات لوحة التحكم

**الغرض:** جلب المؤشرات الرئيسية دفعة واحدة للوحة التحكم الإدارية.

**ما يُعيده:**

| المؤشر | الوصف |
|---|---|
| `active_users_count` | إجمالي الحسابات النشطة |
| `students_count` | عدد الطلاب النشطين |
| `professors_count` | عدد الأساتذة النشطين |
| `total_posts` | إجمالي المنشورات غير المُعلَّمة |
| `total_groups` | إجمالي المجموعات |
| `total_messages` | إجمالي الرسائل المُرسَلة |
| `total_storage_bytes` | إجمالي حجم الملفات بالبايت |
| `pending_reports` | البلاغات التي تنتظر المراجعة |

```sql
SELECT
    (SELECT COUNT(*) FROM `users`
     WHERE status = 'active')                              AS active_users_count,

    (SELECT COUNT(*) FROM `users`
     WHERE role = 'student' AND status = 'active')        AS students_count,

    (SELECT COUNT(*) FROM `users`
     WHERE role = 'professor' AND status = 'active')      AS professors_count,

    (SELECT COUNT(*) FROM `posts`
     WHERE is_flagged = 0)                                AS total_posts,

    (SELECT COUNT(*) FROM `groups`)                       AS total_groups,

    (SELECT COUNT(*) FROM `messages`)                     AS total_messages,

    (SELECT COUNT(*) FROM `files`)                        AS total_files,

    (SELECT COALESCE(SUM(file_size), 0)
     FROM `files`)                                        AS total_storage_bytes,

    (SELECT COUNT(*) FROM `reports`
     WHERE status = 'pending')                            AS pending_reports;
```

---

### A-6: الملف الشخصي

**الغرض:** جلب بيانات مستخدم كاملة مع إحصائياته (عدد منشوراته، ملفاته، رسائله).

```sql
SELECT
    u.user_id,
    u.username,
    u.full_name,
    u.email,
    u.role,
    u.academic_id,
    u.department,
    u.avatar_url,
    u.is_verified,
    u.status,
    u.last_login,
    u.created_at,
    (SELECT COUNT(*) FROM `posts`    WHERE user_id   = u.user_id) AS posts_count,
    (SELECT COUNT(*) FROM `files`    WHERE user_id   = u.user_id) AS files_count,
    (SELECT COUNT(*) FROM `messages` WHERE sender_id = u.user_id) AS sent_messages
FROM `users` u
WHERE u.user_id = 3;  -- ← معرّف المستخدم المطلوب
```

---

### A-7: مجموعات المستخدم

**الغرض:** عرض قائمة بجميع المجموعات التي ينتمي إليها المستخدم مع دوره في كل مجموعة.

```sql
SELECT
    g.group_id,
    g.group_name,
    g.type,
    g.privacy,
    g.members_count,
    gm.member_role,
    gm.joined_at,
    u.full_name AS creator_name
FROM `groups` g
INNER JOIN `group_members` gm ON g.group_id   = gm.group_id
INNER JOIN `users`         u  ON g.created_by = u.user_id
WHERE gm.user_id = 5  -- ← معرّف المستخدم
ORDER BY gm.joined_at DESC;
```

---

### A-8: منشورات مجموعة

**الغرض:** جلب منشورات مجموعة معينة مع ملفاتها المرفقة.

**ملاحظة:** يستخدم `LEFT JOIN` مع جدول files للحصول على بيانات الملف عند وجوده دون إخفاء المنشورات التي ليس لها مرفق.

```sql
SELECT
    p.post_id,
    p.content,
    p.type,
    p.likes_count,
    p.comments_count,
    p.created_at,
    u.full_name AS author_name,
    u.role      AS author_role,
    f.file_id,
    f.original_name,
    f.file_type
FROM `posts` p
INNER JOIN `users` u ON p.user_id = u.user_id
LEFT  JOIN `files` f ON f.post_id = p.post_id
WHERE p.group_id   = 1   -- ← معرّف المجموعة
  AND p.is_flagged = 0
ORDER BY p.created_at DESC
LIMIT 10 OFFSET 0;
```

---

### A-9: البلاغات المعلّقة

**الغرض:** يعرض قائمة البلاغات التي تنتظر مراجعة المشرف أو المدير.

**من يستخدمه:** المشرف (`supervisor`) والمدير (`admin`) فقط.

```sql
SELECT
    r.report_id,
    r.reason,
    r.details,
    r.status,
    r.created_at,
    reporter.full_name AS reporter_name,
    reporter.email     AS reporter_email,
    reported.full_name AS reported_user_name,
    p.content          AS reported_post_content,
    p.created_at       AS post_date
FROM `reports` r
INNER JOIN `users` reporter ON r.reporter_id      = reporter.user_id
LEFT  JOIN `users` reported ON r.reported_user_id = reported.user_id
LEFT  JOIN `posts` p        ON r.post_id          = p.post_id
WHERE r.status IN ('pending', 'under_review')
ORDER BY r.created_at ASC;
```

---

### A-10: أكثر الملفات تحميلاً

**الغرض:** عرض أكثر 10 ملفات تحميلاً، مفيد لتحليل المحتوى الأكثر طلباً.

```sql
SELECT
    f.file_id,
    f.original_name,
    f.file_type,
    f.file_size,
    f.download_count,
    f.created_at,
    u.full_name AS uploader_name,
    p.post_id,
    p.type      AS post_type
FROM `files` f
INNER JOIN `users` u ON f.user_id = u.user_id
LEFT  JOIN `posts` p ON f.post_id = p.post_id
ORDER BY f.download_count DESC
LIMIT 10;
```

---

### A-11: سجل التدقيق

**الغرض:** عرض العمليات الحساسة خلال فترة زمنية محددة.

**تصفية النتائج:** عدّل شرط `action` لتصفية نوع عملية معينة كـ `'login_failed'`.

```sql
SELECT
    al.log_id,
    al.action,
    al.description,
    al.ip_address,
    al.created_at,
    u.username,
    u.full_name,
    u.role
FROM `audit_logs` al
LEFT JOIN `users` u ON al.user_id = u.user_id
WHERE al.created_at BETWEEN '2026-03-01' AND '2026-03-31'
ORDER BY al.created_at DESC
LIMIT 50;
```

---

## قسم B — عمليات INSERT

### B-1: تسجيل مستخدم جديد

يُدرج حساباً جديداً مع hash bcrypt لكلمة المرور. تجلب طبقة التطبيق hash الكلمة قبل الإرسال إلى قاعدة البيانات.

```sql
INSERT INTO `users`
  (`username`, `email`, `password_hash`, `role`,
   `full_name`, `academic_id`, `department`)
VALUES
  ('student_new',
   'new.student@unilink.edu',
   '$2y$10$PlaceholderHashForNewStudentX123456789012345',
   'student',
   'محمد عبدالله النمر', 'STU-3001', 'قسم علوم الحاسوب');
```

---

### B-2: تسجيل نشاط في Audit Log

يجب استدعاؤه بعد **كل عملية حساسة** (دخول، رفع ملف، تغيير صلاحيات).

```sql
INSERT INTO `audit_logs` (`user_id`, `action`, `description`, `ip_address`, `user_agent`)
VALUES
  (5, 'login',
   'تسجيل دخول ناجح — تم التحقق من OTP',
   '10.0.0.20',
   'Mozilla/5.0 (Android; Mobile) UniLink-App/1.0');
```

---

### B-3: نشر منشور جديد

يُدرج المنشور مع تحديد نوعه ومستوى رؤيته والمجموعة المستهدفة.

```sql
INSERT INTO `posts`
  (`user_id`, `group_id`, `content`, `type`, `visibility`)
VALUES
  (5, 1,
   'هل يمكن توضيح متى نستخدم LEFT JOIN بدلاً من INNER JOIN في الاستعلامات؟',
   'question', 'group');
```

---

### B-4: إرسال رسالة خاصة

رسالة نصية بسيطة بين مستخدمَين. لإرسال ملف، استخدم نمط Transaction في القسم E.

```sql
INSERT INTO `messages` (`sender_id`, `receiver_id`, `content`, `type`)
VALUES (5, 3, 'دكتور، أرسلت التقرير النهائي للمراجعة.', 'text');
```

---

### B-5: رفع ملف

يُدرج بيانات الملف. تُنفّذ عملية التخزين الفعلية على قرص الخادم في طبقة التطبيق قبل استدعاء هذا الاستعلام.

```sql
INSERT INTO `files`
  (`user_id`, `post_id`, `original_name`, `stored_name`,
   `file_type`, `file_size`, `storage_path`, `is_encrypted`)
VALUES
  (3, NULL,
   'DB_Week02_SQL_Basics.pdf',
   'g9h8i7j6-db-week02-sql.pdf',
   'pdf', 3145728,
   '/uploads/2026/03/g9h8i7j6-db-week02-sql.pdf',
   1);
```

---

### B-6: تقديم بلاغ

```sql
INSERT INTO `reports`
  (`reporter_id`, `post_id`, `reported_user_id`, `reason`, `details`)
VALUES
  (7, 5, NULL,
   'misinformation',
   'المعلومات المذكورة في هذا المنشور غير دقيقة وقد تضلل الطلاب.');
```

---

### B-7: إنشاء إشعار

```sql
INSERT INTO `notifications` (`user_id`, `type`, `content`, `reference`)
VALUES (5, 'post_like', 'أُعجب د. أحمد بمنشورك الأخير.', 'post:3');
```

---

### B-8: إضافة عضو لمجموعة

```sql
INSERT INTO `group_members` (`group_id`, `user_id`, `member_role`)
VALUES (1, 8, 'member');

UPDATE `groups`
SET `members_count` = `members_count` + 1
WHERE `group_id` = 1;
```

---

## قسم C — عمليات UPDATE

### C-1: تحديث الملف الشخصي

**مهم:** يجب أن يتضمن الاستعلام دائماً `WHERE user_id = :current_user_id` لضمان تعديل المستخدم الحالي فقط.

```sql
UPDATE `users`
SET
    `full_name`  = 'رانيا فهد الزهراني',
    `department` = 'قسم علوم الحاسوب — المستوى الثالث',
    `avatar_url` = '/avatars/user_5_rania.jpg',
    `updated_at` = CURRENT_TIMESTAMP
WHERE `user_id` = 5;
```

---

### C-2: تغيير كلمة المرور

يتضمن تسجيلاً في Audit Log. **لا تُخزَّن كلمة المرور كنص صريح** — دائماً hash bcrypt.

```sql
UPDATE `users`
SET
    `password_hash` = '$2y$10$NewBcryptHashAfterPasswordChangeXXXXXXXXXXXX',
    `updated_at`    = CURRENT_TIMESTAMP
WHERE `user_id` = 5;

INSERT INTO `audit_logs` (`user_id`, `action`, `description`, `ip_address`)
VALUES (5, 'password_change', 'تغيير كلمة المرور بنجاح', '10.0.0.20');
```

---

### C-3: تفعيل الحساب بعد OTP

يُنفَّذ بعد التحقق الناجح من OTP. يُصفّر رمز OTP ووقت انتهائه لمنع إعادة استخدامه.

```sql
UPDATE `users`
SET
    `is_verified`    = 1,
    `otp_code`       = NULL,
    `otp_expires_at` = NULL,
    `last_login`     = CURRENT_TIMESTAMP
WHERE `user_id` = 8;
```

---

### C-4: إيقاف حساب مخالف

```sql
UPDATE `users`
SET
    `status`     = 'suspended',
    `updated_at` = CURRENT_TIMESTAMP
WHERE `user_id` = 9;

INSERT INTO `audit_logs` (`user_id`, `action`, `description`)
VALUES (2, 'account_suspend', 'تعليق حساب المستخدم user_id=9 بسبب بلاغات متعددة');
```

---

### C-5: تحديث حالة بلاغ

```sql
UPDATE `reports`
SET
    `status`     = 'under_review',
    `handled_by` = 2,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `report_id` = 1;
```

---

### C-6: تعليم الرسائل مقروءة

```sql
UPDATE `messages`
SET `is_read` = 1
WHERE `receiver_id` = 5
  AND `is_read`     = 0;
```

---

### C-7: تعليم الإشعارات مقروءة

يُحدِّث جميع الإشعارات غير المقروءة دفعةً واحدة، مما يُحسّن الأداء مقارنةً بتحديث كل إشعار على حدة.

```sql
UPDATE `notifications`
SET `is_read` = 1
WHERE `user_id` = 5
  AND `is_read` = 0;
```

---

### C-8: تعديل منشور

```sql
UPDATE `posts`
SET
    `content`    = 'السلام عليكم، هل من أحد لديه ملخص IS الفصل السابق؟ (تم تحديث السؤال)',
    `updated_at` = CURRENT_TIMESTAMP
WHERE `post_id` = 5
  AND `user_id` = 6;  -- التحقق من الملكية
```

---

### C-9: إضافة إعجاب مع تحديث العداد

يتكوّن من استعلامَين: INSERT لتسجيل الإعجاب، وUPDATE لتحديث عداد `likes_count`. يُنصح بتنفيذها في Transaction.

```sql
INSERT INTO `post_likes` (`post_id`, `user_id`) VALUES (7, 8);

UPDATE `posts`
SET `likes_count` = `likes_count` + 1
WHERE `post_id` = 7;
```

---

### C-10: زيادة عداد تحميل ملف

```sql
UPDATE `files`
SET `download_count` = `download_count` + 1
WHERE `file_id` = 1;
```

---

## قسم D — عمليات DELETE

> [!WARNING]
> عمليات الحذف لا رجعة فيها. تأكد دائماً من التحقق من هوية المستخدم وصلاحياته قبل تنفيذ أي حذف.

### D-1: حذف منشور

يتضمن تسجيلاً في Audit Log **قبل** الحذف الفعلي. جداول `post_likes` وإشعارات المنشور تُحذف تلقائياً بفضل `ON DELETE CASCADE`.

```sql
INSERT INTO `audit_logs` (`user_id`, `action`, `description`)
VALUES (2, 'post_delete', 'حذف المنشور post_id=5 لمخالفة شروط الاستخدام');

DELETE FROM `posts`
WHERE `post_id` = 5;
```

---

### D-2: حذف رسالة

يتحقق من ملكية المستخدم لضمان عدم حذف محتوى لا يملكه.

```sql
DELETE FROM `messages`
WHERE `msg_id`    = 8
  AND `sender_id` = 6;
```

---

### D-3: حذف ملف

```sql
INSERT INTO `audit_logs` (`user_id`, `action`, `description`)
VALUES (3, 'file_delete', 'حذف ملف file_id=4 من قِبل صاحبه');

DELETE FROM `files`
WHERE `file_id` = 4
  AND `user_id` = 7;
```

---

### D-4: إلغاء الإعجاب

```sql
DELETE FROM `post_likes`
WHERE `post_id` = 7 AND `user_id` = 8;

UPDATE `posts`
SET `likes_count` = GREATEST(`likes_count` - 1, 0)
WHERE `post_id` = 7;
```

---

### D-5: إزالة عضو من مجموعة

```sql
DELETE FROM `group_members`
WHERE `group_id` = 1 AND `user_id` = 6;

UPDATE `groups`
SET `members_count` = GREATEST(`members_count` - 1, 1)
WHERE `group_id` = 1;
```

---

## قسم E — معالجات Transactions

### E-1: إرسال رسالة مع ملف

تضمن المعالجة أن **الثلاثة استعلامات** (إدراج الملف، إدراج الرسالة، إنشاء الإشعار) تُنفَّذ كوحدة واحدة. إذا فشل أي استعلام، تُلغى العملية بالكامل.

```sql
START TRANSACTION;

  INSERT INTO `files`
    (`user_id`, `post_id`, `original_name`, `stored_name`,
     `file_type`, `file_size`, `storage_path`, `is_encrypted`)
  VALUES
    (5, NULL, 'Assignment_Q3.pdf', 'uuid-assignment-q3.pdf',
     'pdf', 2097152, '/uploads/2026/03/uuid-assignment-q3.pdf', 1);

  INSERT INTO `messages`
    (`sender_id`, `receiver_id`, `content`, `type`, `file_id`)
  VALUES
    (5, 3, 'دكتور، هذا تسليم الواجب رقم 3.', 'file', LAST_INSERT_ID());

  INSERT INTO `notifications` (`user_id`, `type`, `content`, `reference`)
  VALUES (3, 'new_message', 'رانيا أرسلت لك ملفاً جديداً.', CONCAT('msg:', LAST_INSERT_ID()));

COMMIT;
```

---

### E-2: معالجة بلاغ وتوقيف حساب

تضمن تزامن: تحديث حالة البلاغ + توقيف الحساب + تسجيل الحدث في Audit Log.

```sql
START TRANSACTION;

  UPDATE `reports`
  SET status       = 'resolved',
      handled_by   = 2,
      action_taken = 'تم توقيف الحساب بعد المراجعة.'
  WHERE report_id  = 2;

  UPDATE `users`
  SET status = 'suspended'
  WHERE user_id = 9;

  INSERT INTO `audit_logs` (`user_id`, `action`, `description`)
  VALUES (2, 'account_suspend', 'تعليق حساب user_id=9 بعد مراجعة بلاغ report_id=2');

COMMIT;
```

**متى تستخدم Transactions:**
- عند تعديل أكثر من جدول في عملية واحدة.
- عند وجود تبعيات بين الاستعلامات (مثل استخدام `LAST_INSERT_ID()`).
- في العمليات الحساسة التي تتطلب التراجع عند الخطأ.

---

## قسم F — الفهارس

### فهرس البحث النصي الكامل (Full-Text)

يُتيح البحث الكلمي داخل محتوى المنشورات.

```sql
ALTER TABLE `posts`
    ADD FULLTEXT INDEX `ft_posts_content` (`content`);

SELECT post_id, content, created_at
FROM   `posts`
WHERE  MATCH(content) AGAINST ('قواعد البيانات' IN BOOLEAN MODE)
ORDER BY created_at DESC
LIMIT 10;
```

**البحث البولياني (Boolean Mode)** يدعم:
- `+` للكلمات الإلزامية: `+'قواعد البيانات'`
- `-` لاستبعاد كلمة: `+'قواعد' -'بيانات'`
- `*` للبحث الجزئي: `'قواعد*'`

---

### فهرس المحادثات

```sql
CREATE INDEX IF NOT EXISTS `idx_msg_conversation`
    ON `messages` (`sender_id`, `receiver_id`, `created_at`);
```

---

### فهرس الإشعارات غير المقروءة

```sql
CREATE INDEX IF NOT EXISTS `idx_notif_unread`
    ON `notifications` (`user_id`, `is_read`, `created_at`);
```

---

## أفضل الممارسات

### 1. استخدام القيم المُعلَّمة (Parameterized Queries)

لا تُدمج قيم المستخدم مباشرة في SQL. استبدل القيم المضمّنة بمعاملات `:param` أو `?` حسب لغتك.

```sql
-- ❌ خطأ (عُرضة لحقن SQL)
WHERE user_id = $_GET['id']

-- ✅ صحيح (معامل مُعلَّم)
WHERE user_id = :user_id
```

---

### 2. ترقيم الصفحات (Pagination)

استخدم دائماً `LIMIT` و`OFFSET` لتجنب جلب آلاف السجلات دفعة واحدة:

```sql
LIMIT 10 OFFSET 0   -- الصفحة الأولى
LIMIT 10 OFFSET 10  -- الصفحة الثانية
LIMIT 10 OFFSET ((n - 1) * 10)  -- الصفحة n
```

---

### 3. التحقق من الملكية عند التعديل والحذف

دائماً أضف شرط ملكية المستخدم في استعلامات UPDATE وDELETE:

```sql
DELETE FROM posts
WHERE post_id = :post_id
  AND user_id = :current_user_id;
```

---

### 4. استخدام Transactions للعمليات المُركَّبة

أي عملية تُعدّل أكثر من جدول يجب تغليفها في:

```sql
START TRANSACTION;
  -- الاستعلامات المتتالية هنا
COMMIT;
```

---

### 5. تسجيل العمليات الحساسة في audit_logs

```sql
INSERT INTO audit_logs (user_id, action, description, ip_address)
VALUES (:uid, 'post_delete', 'حذف المنشور X', :ip);
```

العمليات الواجب تسجيلها:
- تسجيل الدخول (نجاح وفشل)
- تغيير كلمة المرور أو الصلاحيات
- حذف المحتوى أو تعليق الحسابات
- رفع الملفات وحذفها

---

### 6. تجنب SELECT *

حدد الأعمدة المطلوبة دائماً لتفادي جلب بيانات حساسة مثل `password_hash`:

```sql
-- ❌ خطأ
SELECT * FROM users WHERE user_id = 5;

-- ✅ صحيح
SELECT user_id, username, full_name, role, avatar_url
FROM users WHERE user_id = 5;
```

---

### 7. الفهارس وتحسين الأداء

- فهارس `INDEX` مُعرَّفة في `01_schema.sql` على الأعمدة الأكثر استخداماً في `WHERE` و`JOIN`.
- إذا لاحظت بطئاً في استعلام، استخدم `EXPLAIN` لفهم خطة التنفيذ.
- لا تُضف فهارس على أعمدة تُكتب كثيراً وتُقرأ نادراً (مثل `audit_logs.description`).

```sql
EXPLAIN SELECT * FROM posts WHERE group_id = 1 ORDER BY created_at DESC;
```

---

*توثيق قاعدة بيانات UniLink — دليل العمليات v2.0*
