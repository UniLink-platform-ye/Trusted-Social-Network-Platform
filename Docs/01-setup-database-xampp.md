<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

<div dir="rtl" style="font-family: 'Cairo', Tahoma, Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 1.5rem;">



# دليل إعداد قاعدة بيانات UniLink باستخدام XAMPP

## المحتويات

1. [المتطلبات المسبقة](#المتطلبات-المسبقة)
2. [تثبيت XAMPP وتشغيله](#تثبيت-xampp-وتشغيله)
3. [إنشاء قاعدة البيانات عبر phpMyAdmin](#إنشاء-قاعدة-البيانات-عبر-phpmyadmin)
4. [إنشاء قاعدة البيانات عبر سطر الأوامر](#إنشاء-قاعدة-البيانات-عبر-سطر-الأوامر)
5. [التحقق من صحة التثبيت](#التحقق-من-صحة-التثبيت)
6. [الأخطاء الشائعة وحلولها](#الأخطاء-الشائعة-وحلولها)

---

## المتطلبات المسبقة

قبل البدء، تأكد من توفر ما يلي:

| المتطلب | الإصدار المُوصى به | ملاحظة |
|---|---|---|
| XAMPP | 8.1+ | يتضمن Apache وMySQL وphpMyAdmin |
| نظام التشغيل | Windows 10/11 عو Linux أو macOS | |
| ذاكرة وصول عشوائي | 4 GB أو أكثر | للتطوير المحلي |
| مساحة القرص | 500 MB فأكثر | للتطبيق والبيانات |

---

## تثبيت XAMPP وتشغيله

### الخطوة 1 — تحميل XAMPP

1. اذهب إلى الموقع الرسمي: [apachefriends.org](https://www.apachefriends.org)
2. حمّل النسخة المناسبة لنظام تشغيلك (يُفضّل XAMPP 8.1 مع PHP 8.1)
3. شغّل ملف التثبيت واتبع الخطوات الافتراضية

### الخطوة 2 — تشغيل خدمات XAMPP

1. افتح **XAMPP Control Panel**
2. اضغط **Start** بجانب **Apache** — تحوّل إلى اللون الأخضر
3. اضغط **Start** بجانب **MySQL** — تحوّل إلى اللون الأخضر

> [!NOTE]
> إذا لم تبدأ MySQL بسبب تعارض في المنفذ 3306، تحقق من عدم وجود تثبيت MySQL آخر على جهازك.

---

## إنشاء قاعدة البيانات عبر phpMyAdmin

هذه الطريقة مناسبة للمبتدئين أو من يُفضّلون واجهة رسومية.

### الخطوة 1 — فتح phpMyAdmin

افتح المتصفح وأدخل الرابط التالي:

```
http://localhost/phpmyadmin
```

تسجيل الدخول الافتراضي:
- **المستخدم:** `root`
- **كلمة المرور:** (اتركها فارغة في التثبيت الافتراضي)

---

### الخطوة 2 — تشغيل سكربت إنشاء قاعدة البيانات والجداول

1. من الشريط العلوي اضغط تبويب **Import** (استيراد)
2. في قسم **File to import** اضغط **Choose File**
3. انتقل إلى مجلد المشروع واختر: `scripts/01_schema.sql`
4. تأكد أن **Charset** مضبوط على `utf8mb4`
5. اضغط زر **Go** (تنفيذ) في أسفل الصفحة

✅ ستظهر رسالة نجاح باللون الأخضر وتُنشأ قاعدة البيانات `unilink_db` تلقائياً مع جميع الجداول العشرة.

---

### الخطوة 3 — تشغيل سكربت البيانات التجريبية

1. من القائمة الجانبية اليسرى اضغط على **`unilink_db`** لتحديدها
2. من الشريط العلوي اضغط تبويب **Import** مجدداً
3. اختر الملف: `scripts/02_seed_data.sql`
4. اضغط **Go**

✅ ستُدرج البيانات التجريبية: مستخدمون، مجموعات، منشورات، رسائل، ملفات، وبلاغات.

---

### الخطوة 4 — التحقق من البيانات

1. من القائمة الجانبية اضغط على **`unilink_db`**
2. سترى قائمة بجميع الجداول العشرة
3. اضغط على جدول **`users`** لتفتح نافذة عرض البيانات
4. يجب أن ترى 9 مستخدمين تجريبيين

---

## إنشاء قاعدة البيانات عبر سطر الأوامر

هذه الطريقة أسرع وتُناسب المطورين المتقدمين.

### على Windows

```powershell
# انتقل إلى مجلد MySQL داخل XAMPP
cd C:\xampp\mysql\bin

# تسجيل الدخول إلى MySQL
mysql -u root -p

# بعد الدخول، نفّذ الأوامر التالية داخل MySQL Shell:
```

```sql
-- إظهار قواعد البيانات الموجودة
SHOW DATABASES;
```

```powershell
# تشغيل سكربت الإنشاء مباشرة (خارج MySQL Shell)
mysql -u root -p < "C:\path\to\UniLink-database\scripts\01_schema.sql"

# تشغيل سكربت البيانات التجريبية
mysql -u root -p unilink_db < "C:\path\to\UniLink-database\scripts\02_seed_data.sql"
```

> [!TIP]
> استبدل `C:\path\to\UniLink-database` بالمسار الفعلي لمجلد المشروع على جهازك.

### على Linux / macOS (مع XAMPP)

```bash
# تشغيل سكربت الإنشاء
/opt/lampp/bin/mysql -u root -p < /path/to/scripts/01_schema.sql

# تشغيل سكربت البيانات التجريبية
/opt/lampp/bin/mysql -u root -p unilink_db < /path/to/scripts/02_seed_data.sql
```

---

## التحقق من صحة التثبيت

بعد تشغيل السكربتَين، نفّذ الاستعلامات التالية للتحقق:

```sql
USE unilink_db;

-- التحقق من عدد الجداول (يجب أن يكون 10)
SELECT COUNT(*) AS total_tables
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'unilink_db';

-- التحقق من البيانات التجريبية
SELECT role, COUNT(*) AS count
FROM users
GROUP BY role;
```

النتيجة المتوقعة:

| role | count |
|---|---|
| admin | 1 |
| supervisor | 1 |
| professor | 2 |
| student | 5 |

---

## الأخطاء الشائعة وحلولها

### خطأ: `Access denied for user 'root'@'localhost'`

**السبب:** كلمة مرور MySQL غير صحيحة.

**الحل:**
```bash
# في سطر الأوامر داخل مجلد MySQL
mysql -u root
# إذا طُلبت كلمة مرور اتركها فارغة
```

---

### خطأ: `Can't connect to MySQL server`

**السبب:** خدمة MySQL لم تُشغَّل بعد.

**الحل:** افتح XAMPP Control Panel وتأكد من تشغيل MySQL (اللون الأخضر).

---

### خطأ: `Table 'posts' doesn't exist`

**السبب:** تشغيل `02_seed_data.sql` قبل `01_schema.sql`.

**الحل:** أعد تشغيل الملفات **بالترتيب الصحيح**:
1. `01_schema.sql` أولاً
2. `02_seed_data.sql` ثانياً

---

### خطأ: `Incorrect string value`

**السبب:** قاعدة البيانات لا تدعم utf8mb4.

**الحل:** تأكد أن ترميز قاعدة البيانات هو `utf8mb4` عند إنشائها (مضبوط تلقائياً في السكربت).

---

> [!IMPORTANT]
> دائماً شغّل `01_schema.sql` **قبل** `02_seed_data.sql` وإلا ستفشل العملية بسبب عدم وجود الجداول.

---

*توثيق قاعدة بيانات UniLink — الإصدار 1.0*
