# دليل إعدادات هوية المنصة (App Branding)

## نظرة عامة
يتيح نظام **App Branding** للأدمن التحكم الكامل في هوية المنصة البصرية (الألوان، الاسم، الشعار، الخط) من لوحة التحكم، وتطبيقها تلقائياً على تطبيق Flutter للموبايل.

---

## 1. تشغيل Migration قاعدة البيانات

```sql
-- تنفيذ الملف:
-- sql/migrations/004_branding_settings.sql
```

أو عبر phpMyAdmin / MySQL CLI:
```bash
mysql -u root -p trusted_social_network_platform < sql/migrations/004_branding_settings.sql
```

---

## 2. الوصول إلى صفحة إعدادات الهوية

1. سجّل الدخول إلى لوحة الأدمن.
2. في القائمة الجانبية تحت **النظام** → **App Branding** (أيقونة اللوحة).
3. URL: `admin/index.php?page=branding`

> **الصلاحية المطلوبة:** `settings.manage`

---

## 3. ميزات صفحة إعدادات الهوية

### 3.1 معلومات المنصة
| الحقل | الوصف |
|-------|-------|
| اسم المنصة | يُستخدم في عنوان التطبيق وواجهاته |
| الـ Tagline | نص وصفي قصير يظهر أسفل الاسم |
| نوع الخط | Cairo / Tajawal / Roboto / Inter |

### 3.2 الألوان
يمكن ضبط 10 ألوان منفصلة تشمل:
- اللون الأساسي (Primary) والثانوي والـ Accent
- لون الخلفية ولون النص
- ألوان الأزرار وحقول الإدخال والبطاقات

### 3.3 القوالب الجاهزة
| المفتاح | الاسم | الوصف |
|---------|-------|-------|
| `deep_blue` | الأزرق الملكي العميق | درجات `#004D8C` → `#00B4D8` — هوية أكاديمية |
| `emerald_warmth` | الدفء الزمردي | درجات زيتون وذهبي دافئة |
| `slate_dark` | الداكن الرمادي | ثيم ليلي بنفسجي |

**عند الضغط على قالب:** تُملأ حقول الألوان تلقائياً ويتحدث إطار المعاينة فوراً.

### 3.4 المعاينة الحية (Phone Mockup)
- إطار هاتف HTML/CSS على يمين الصفحة.
- يتحدث فورياً عند تغيير أي لون أو خط أو اسم المنصة.
- لا يحتاج إلى إعادة تحميل الصفحة.

### 3.5 رفع الشعار
- صيغ مدعومة: PNG، JPG، SVG، WebP
- الحد الأقصى: 2 MB
- يُحفظ في: `uploads/branding/`

---

## 4. حفظ الإعدادات

اضغط **حفظ الإعدادات** — تُرسَل البيانات عبر AJAX إلى:
```
admin/ajax/branding.php (action=save_branding)
```
وتُخزَّن في جدول `branding_settings` (id=1).

---

## 5. Branding API

### Endpoint
```
GET /api/v1/branding.php
```

### مثال الاستجابة
```json
{
  "success": true,
  "data": {
    "platform_name": "UniLink",
    "platform_tagline": "منصة التواصل الأكاديمي الموثوقة",
    "primary_color": "#004D8C",
    "secondary_color": "#007786",
    "accent_color": "#00B4D8",
    "background_color": "#FFFFFF",
    "text_color": "#1E293B",
    "button_primary_color": "#004D8C",
    "button_text_color": "#FFFFFF",
    "card_bg_color": "#F0F7FF",
    "input_bg_color": "#FFFFFF",
    "input_border_color": "#B3D4F0",
    "font_family": "Cairo",
    "logo_url": "http://localhost/Trusted-Social-Network-Platform/uploads/branding/logo_123.png",
    "active_template_key": "deep_blue"
  }
}
```

> الـ API **عام** (لا يتطلب مصادقة) مع Cache-Control لمدة 5 دقائق.

---

## 6. بنية قاعدة البيانات

```sql
-- جدول: branding_settings (صف واحد دائماً id=1)
CREATE TABLE branding_settings (
  id                    INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  platform_name         VARCHAR(120) DEFAULT 'UniLink',
  platform_tagline      VARCHAR(255),
  primary_color         VARCHAR(9),
  secondary_color       VARCHAR(9),
  accent_color          VARCHAR(9),
  background_color      VARCHAR(9),
  text_color            VARCHAR(9),
  button_primary_color  VARCHAR(9),
  button_text_color     VARCHAR(9),
  card_bg_color         VARCHAR(9),
  input_bg_color        VARCHAR(9),
  input_border_color    VARCHAR(9),
  font_family           VARCHAR(80),
  logo_path             VARCHAR(512),
  active_template_key   VARCHAR(60),
  updated_by            INT UNSIGNED,
  updated_at            DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 7. مسار التدفق الكامل

```
الأدمن يعدّل الإعدادات في لوحة التحكم
         ↓ (حفظ)
  branding_settings  في MySQL
         ↓
    GET /api/v1/branding.php
         ↓
  تطبيق Flutter يجلب البيانات عند بدء التشغيل
         ↓
  ThemeProvider يبني ThemeData من الألوان
         ↓
  جميع شاشات التطبيق تعكس الهوية الجديدة
```

---

## 8. الملفات ذات الصلة

| الملف | الدور |
|-------|-------|
| `sql/migrations/004_branding_settings.sql` | إنشاء جدول الإعدادات |
| `core/branding.php` | Helper مركزي لتحميل الإعدادات |
| `admin/pages/branding.php` | صفحة إعدادات الهوية في لوحة الأدمن |
| `admin/ajax/branding.php` | معالج حفظ الإعدادات ورفع الشعار |
| `admin/assets/js/branding.js` | JavaScript للمعاينة الحية والقوالب |
| `api/v1/branding.php` | Endpoint عام لإرجاع الإعدادات |
