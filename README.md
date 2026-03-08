# 🎓 UniLink – Trusted Social Network Platform
### لوحة التحكم الإدارية | Admin Control Panel

> منصة شبكة اجتماعية موثوقة مخصصة للبيئات الجامعية — تشمل إدارة المستخدمين، الصلاحيات، البلاغات، والمحتوى.

---

## 📋 نظرة عامة | Overview

هذا المستودع يحتوي على **لوحة التحكم الإدارية** لمنصة UniLink. تم بناؤها بـ PHP خالص وتتصل بـ **Amazon RDS** كمصدر بيانات افتراضي.

| التفاصيل | القيمة |
|---|---|
| اللغة | PHP 8.0+ |
| قاعدة البيانات | MySQL 8.0 (Amazon RDS — مُستضافة خارجياً) |
| خادم التطوير | XAMPP / Apache |
| نظام الاتصال | PDO |
| المصادقة | Sessions + bcrypt + Remember Me |

---

## ⚡ البداية السريعة | Quick Start

> **قاعدة البيانات مُستضافة مسبقاً على Amazon RDS** — لا تحتاج لإعداد قاعدة بيانات محلية للتشغيل الفوري.

### 1. النسخ والإعداد

```bash
git clone https://github.com/UniLink-platform-ye/Trusted-Social-Network-Platform.git
```

### 2. الربط بـ XAMPP

افتح **Command Prompt كـ Administrator** ونفّذ:

```cmd
mklink /J "C:\xampp\htdocs\Trusted-Social-Network-Platform" "C:\Users\M\Downloads\UniLink-platform-ye\Trusted-Social-Network-Platform"
```

> غيّر `D:\UniLink-platform-ye\Trusted-Social-Network-Platform` إلى المسار الذي استنسخت فيه المشروع.

### 3. تشغيل Apache في XAMPP

افتح XAMPP Control Panel وشغّل **Apache** فقط (لا تحتاج MySQL لأن قاعدة البيانات خارجية).

### 4. الدخول للوحة التحكم

```
http://localhost/Trusted-Social-Network-Platform/admin/login.php
```

| البريد الإلكتروني | كلمة المرور | الدور |
|---|---|---|
| `admin@unilink.local` | `Admin@1234` | مدير النظام |
| `meera.admin@unilink.edu` | `Test@123` | مدير النظام (بديل) |
| `salma.supervisor@unilink.edu` | `Test@123` | مشرف |
| `ahmed.prof@unilink.edu` | `Test@123` | أستاذ |
| `rania.student@unilink.edu` | `Test@123` | طالب |

---

## 🗄️ قاعدة البيانات | Database

### الوضع الافتراضي — Amazon RDS (مُستضافة خارجياً)

قاعدة البيانات مُعدَّة مسبقاً على Amazon RDS وجاهزة للاستخدام. الإعدادات موجودة في:

```
config/app.php
```

```php
const DB_HOST = 'unilink-platform.c6pgq44asn04.us-east-1.rds.amazonaws.com';
const DB_PORT = '3306';
const DB_NAME = 'unilink_db';
const DB_USER = 'admin';
const DB_PASS = '...'; // راجع ملف config/app.php
```

**لا تحتاج لأي إعداد إضافي** — فقط شغّل Apache وستعمل اللوحة مباشرة.

---

### 🏠 إذا أردت قاعدة بيانات محلية | Local Database Setup

إذا أردت تشغيل قاعدة البيانات محلياً (مثلاً بـ XAMPP MySQL):

#### الخطوة 1 — إنشاء قاعدة البيانات

افتح [phpMyAdmin](http://localhost/phpmyadmin) أو MySQL CLI ونفّذ السكريبتات بهذا الترتيب:

```bash
# من مجلد UniLink-database/scripts/
1. 01_schema.sql          # البنية الأساسية للجداول
2. 02_admin_panel_additions.sql   # البيانات التجريبية + حقول remember token
3. 03_remember_token.sql  # أعمدة Remember Me (إذا لم تُنفَّذ في الخطوة 2)
```

#### الخطوة 2 — تعديل إعدادات الاتصال

افحص ملف `config/app.php` وعدّل القسم المخصص للاتصال:

```php
// ── قاعدة بيانات محلية (XAMPP) ───────────────────────────────────────────
const DB_HOST = '127.0.0.1';   // أو 'localhost'
const DB_PORT = '3306';
const DB_NAME = 'unilink_db';
const DB_USER = 'root';         // المستخدم الافتراضي في XAMPP
const DB_PASS = '';             // فارغة في XAMPP الافتراضي
// ─────────────────────────────────────────────────────────────────────────
```

#### الخطوة 3 — تعديل إعداد الـ Cookie

في نفس الملف، غيّر `secure` إلى `false` للتطوير المحلي (HTTP):

```php
session_set_cookie_params([
    'secure'   => false,   // false للتطوير المحلي (HTTP)
    ...
]);
```

#### الخطوة 4 — تشغيل MySQL في XAMPP

افتح XAMPP Control Panel وشغّل **Apache + MySQL**.

---

## 📁 هيكل المشروع | Project Structure

```
Trusted-Social-Network-Platform/
├── admin/
│   ├── index.php           # نقطة الدخول الرئيسية
│   ├── login.php           # صفحة تسجيل الدخول
│   ├── logout.php          # تسجيل الخروج
│   ├── ajax/
│   │   ├── users.php       # معالج Ajax للمستخدمين
│   │   └── permissions.php # معالج Ajax للصلاحيات
│   ├── assets/
│   │   ├── css/style.css   # الأنماط الرئيسية
│   │   └── js/
│   │       ├── app.js      # السلوك العام
│   │       └── users.js    # منطق صفحة المستخدمين
│   ├── includes/
│   │   ├── bootstrap.php   # التهيئة العامة
│   │   ├── auth.php        # المصادقة والجلسات
│   │   ├── rbac.php        # التحكم في الصلاحيات
│   │   └── helpers.php     # دوال مساعدة
│   ├── pages/
│   │   ├── dashboard.php   # لوحة القيادة (KPIs)
│   │   ├── users.php       # إدارة المستخدمين
│   │   └── permissions.php # إدارة الصلاحيات
│   └── partials/
│       ├── header.php      # رأس الصفحة (HTML head)
│       ├── sidebar.php     # القائمة الجانبية
│       ├── topbar.php      # الشريط العلوي
│       └── footer.php      # ذيل الصفحة + JS
├── config/
│   └── app.php             # إعدادات التطبيق والاتصال بقاعدة البيانات
└── sql/
    └── trusted_social_admin_modules.sql   # سكريبت مرجعي للهيكل
```

---

## 🔐 الأدوار والصلاحيات | Roles & Permissions

| الدور | الصلاحيات |
|---|---|
| **admin** | كامل الصلاحيات — إدارة كل شيء |
| **supervisor** | عرض/تعديل مستخدمين، مراجعة البلاغات، عرض السجلات |
| **professor** | عرض لوحة التحكم والمحتوى |
| **student** | عرض لوحة التحكم فقط |

---

## 🛠️ متطلبات النظام | Requirements

| المتطلب | الإصدار |
|---|---|
| PHP | 8.0 أو أحدث |
| Apache | أي إصدار حديث |
| MySQL (محلي) | 8.0+ (فقط إذا أردت قاعدة محلية) |
| امتداد PHP | `pdo`, `pdo_mysql`, `mbstring`, `openssl` |

---

## 🌐 الروابط المهمة

| الصفحة | الرابط |
|---|---|
| لوحة الدخول | `http://localhost/Trusted-Social-Network-Platform/admin/login.php` |
| لوحة التحكم | `http://localhost/Trusted-Social-Network-Platform/admin/index.php` |
| إدارة المستخدمين | `http://localhost/Trusted-Social-Network-Platform/admin/index.php?page=users` |
| الصلاحيات | `http://localhost/Trusted-Social-Network-Platform/admin/index.php?page=permissions` |

---

## 📝 ملاحظات للمطورين | Developer Notes

- **CSRF Protection**: جميع النماذج محمية بـ CSRF token تلقائياً.
- **bcrypt Compatibility**: الكود يتعامل مع هاشات `$2b$` (Node.js) و`$2y$` (PHP) تلقائياً.
- **Remember Me**: تتطلب أعمدة `remember_token_hash` و`remember_token_expires_at` — راجع `03_remember_token.sql`.
- **Language Toggle**: زر تبديل اللغة (AR/EN) موجود في الشريط العلوي.

---

## 🎓 معلومات المشروع

- **المشروع**: UniLink – Trusted Social Network Platform
- **النوع**: مشروع تخرج جامعي
- **المنصة**: Admin Panel (web)
- **قاعدة البيانات**: Amazon RDS MySQL

---

*للاستفسارات، تواصل مع فريق التطوير.*
