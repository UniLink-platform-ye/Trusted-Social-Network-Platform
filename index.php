<?php
declare(strict_types=1);

// إذا كان المستخدم مسجّلاً دخوله، وجّهه مباشرة
session_start();
if (!empty($_SESSION['user']['user_id'])) {
    $role = $_SESSION['user']['role'] ?? '';
    $dest = in_array($role, ['admin','supervisor'])
        ? '/Trusted-Social-Network-Platform/admin/index.php'
        : '/Trusted-Social-Network-Platform/app/feed.php';
    header("Location: $dest"); exit;
}

$base = '/Trusted-Social-Network-Platform';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>UniLink — منصة التواصل الأكاديمي الموثوقة</title>
<meta name="description" content="منصة UniLink للتواصل الأكاديمي — تجمع الطلاب والأساتذة في بيئة رقمية آمنة وموثوقة.">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
/* ══════════════════════════════════════════════════════
   UniLink Landing Page — Premium Design
══════════════════════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
  --primary:#2563eb;--primary-light:#60a5fa;--primary-dark:#1d4ed8;
  --accent:#f59e0b;--success:#10b981;--danger:#ef4444;
  --bg-dark:#060b18;--bg-card:rgba(255,255,255,.04);
  --border:rgba(255,255,255,.08);--text:#e2e8f0;--text-muted:#94a3b8;
  --radius:16px;--radius-sm:10px;
}
html{scroll-behavior:smooth;}
body{
  font-family:'Cairo',Tahoma,sans-serif;
  background:var(--bg-dark);
  color:var(--text);
  min-height:100vh;
  overflow-x:hidden;
}
a{text-decoration:none;color:inherit;}
/* ── Stars Background ─────────────────────────────── */
.stars-bg{
  position:fixed;inset:0;z-index:0;
  background:
    radial-gradient(ellipse at 20% 50%, rgba(37,99,235,.15) 0%, transparent 50%),
    radial-gradient(ellipse at 80% 20%, rgba(99,102,241,.12) 0%, transparent 50%),
    radial-gradient(ellipse at 60% 80%, rgba(16,185,129,.08) 0%, transparent 40%),
    #060b18;
}
.stars-bg::before{
  content:'';position:absolute;inset:0;
  background-image:
    radial-gradient(1px 1px at 10% 15%, rgba(255,255,255,.6) 0%, transparent 100%),
    radial-gradient(1px 1px at 30% 40%, rgba(255,255,255,.4) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 50% 10%, rgba(255,255,255,.5) 0%, transparent 100%),
    radial-gradient(1px 1px at 70% 60%, rgba(255,255,255,.3) 0%, transparent 100%),
    radial-gradient(1px 1px at 85% 30%, rgba(255,255,255,.5) 0%, transparent 100%),
    radial-gradient(1px 1px at 15% 80%, rgba(255,255,255,.4) 0%, transparent 100%),
    radial-gradient(1px 1px at 55% 70%, rgba(255,255,255,.3) 0%, transparent 100%),
    radial-gradient(1px 1px at 90% 85%, rgba(255,255,255,.4) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 25% 55%, rgba(255,255,255,.3) 0%, transparent 100%),
    radial-gradient(1px 1px at 75% 15%, rgba(255,255,255,.5) 0%, transparent 100%);
}
/* ── Layout ───────────────────────────────────────── */
.container{max-width:1100px;margin:0 auto;padding:0 1.5rem;position:relative;z-index:1;}
/* ── Navbar ───────────────────────────────────────── */
.navbar{
  position:fixed;top:0;right:0;left:0;z-index:100;
  padding:.9rem 2rem;
  display:flex;align-items:center;justify-content:space-between;
  background:rgba(6,11,24,.8);backdrop-filter:blur(16px);
  border-bottom:1px solid var(--border);
  transition:.3s;
}
.nav-brand{display:flex;align-items:center;gap:.6rem;font-size:1.1rem;font-weight:800;}
.nav-logo{
  width:38px;height:38px;border-radius:10px;
  background:linear-gradient(135deg,#1e3a5f,var(--primary));
  display:flex;align-items:center;justify-content:center;font-size:1.1rem;
}
.nav-links{display:flex;gap:.75rem;align-items:center;}
.btn-nav{
  padding:.45rem 1rem;border-radius:8px;font-family:inherit;font-size:.82rem;
  font-weight:700;cursor:pointer;border:none;transition:.2s;
}
.btn-nav-ghost{background:rgba(255,255,255,.06);color:var(--text-muted);}
.btn-nav-ghost:hover{background:rgba(255,255,255,.1);color:#fff;}
.btn-nav-primary{background:var(--primary);color:#fff;}
.btn-nav-primary:hover{background:var(--primary-dark);}
/* ── Hero ─────────────────────────────────────────── */
.hero{
  min-height:100vh;display:flex;align-items:center;
  padding:7rem 0 4rem;text-align:center;flex-direction:column;justify-content:center;
}
.hero-badge{
  display:inline-flex;align-items:center;gap:.5rem;
  background:rgba(37,99,235,.15);border:1px solid rgba(37,99,235,.3);
  color:var(--primary-light);font-size:.78rem;font-weight:700;
  padding:.4rem 1rem;border-radius:20px;margin-bottom:1.5rem;
  animation:fadeDown .6s ease;
}
.hero h1{
  font-size:clamp(2.2rem,5vw,3.8rem);font-weight:900;
  line-height:1.15;margin-bottom:1.25rem;
  animation:fadeDown .7s ease .1s both;
}
.hero h1 .gradient-text{
  background:linear-gradient(135deg,var(--primary-light),#818cf8,var(--primary-light));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
  background-clip:text;background-size:200%;
  animation:shimmer 3s linear infinite;
}
.hero p{
  font-size:1.1rem;color:var(--text-muted);max-width:580px;margin:0 auto 2.5rem;
  line-height:1.8;animation:fadeDown .7s ease .2s both;
}
.hero-btns{
  display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;
  animation:fadeDown .7s ease .3s both;
}
.btn{
  display:inline-flex;align-items:center;gap:.5rem;
  padding:.75rem 1.75rem;border-radius:var(--radius-sm);font-family:inherit;
  font-size:.95rem;font-weight:700;cursor:pointer;border:none;transition:.25s;
}
.btn-primary{
  background:linear-gradient(135deg,var(--primary),#4f46e5);color:#fff;
  box-shadow:0 4px 20px rgba(37,99,235,.4);
}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(37,99,235,.5);}
.btn-outline{
  background:rgba(255,255,255,.05);color:#fff;
  border:1.5px solid rgba(255,255,255,.15);
}
.btn-outline:hover{background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.3);}
.btn-admin{
  background:linear-gradient(135deg,rgba(99,102,241,.3),rgba(139,92,246,.3));
  color:#c4b5fd;border:1.5px solid rgba(139,92,246,.3);
}
.btn-admin:hover{background:rgba(139,92,246,.2);}
/* ── Stats Bar ────────────────────────────────────── */
.stats-bar{
  display:flex;justify-content:center;flex-wrap:wrap;gap:2rem;
  padding:2.5rem 0;border-top:1px solid var(--border);
  border-bottom:1px solid var(--border);margin:0 0 5rem;
  animation:fadeUp .7s ease .5s both;
}
.stat-item{text-align:center;}
.stat-value{font-size:1.6rem;font-weight:900;color:var(--primary-light);}
.stat-label{font-size:.75rem;color:var(--text-muted);margin-top:.15rem;}
/* ── Section ──────────────────────────────────────── */
.section{padding:5rem 0;position:relative;z-index:1;}
.section-badge{
  display:inline-flex;align-items:center;gap:.4rem;
  background:rgba(37,99,235,.1);border:1px solid rgba(37,99,235,.2);
  color:var(--primary-light);font-size:.72rem;font-weight:700;
  padding:.3rem .85rem;border-radius:20px;margin-bottom:1rem;text-transform:uppercase;letter-spacing:1px;
}
.section-title{font-size:1.9rem;font-weight:900;margin-bottom:.5rem;}
.section-sub{color:var(--text-muted);font-size:.95rem;margin-bottom:3rem;max-width:500px;}
/* ── Features ─────────────────────────────────────── */
.features-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(300px,1fr));
  gap:1.25rem;
}
.feature-card{
  background:var(--bg-card);
  border:1px solid var(--border);
  border-radius:var(--radius);
  padding:1.75rem;
  transition:.3s;position:relative;overflow:hidden;
}
.feature-card::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(37,99,235,.05),transparent);
  opacity:0;transition:.3s;
}
.feature-card:hover{border-color:rgba(37,99,235,.3);transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,.3);}
.feature-card:hover::before{opacity:1;}
.feature-icon{
  width:48px;height:48px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;
  font-size:1.2rem;margin-bottom:1.1rem;
}
.feature-card h3{font-size:1rem;font-weight:800;margin-bottom:.5rem;}
.feature-card p{font-size:.82rem;color:var(--text-muted);line-height:1.7;}
/* ── Routes ───────────────────────────────────────── */
.routes-layout{
  display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;
}
@media(max-width:700px){.routes-layout{grid-template-columns:1fr;}}
.route-group{
  background:var(--bg-card);border:1px solid var(--border);
  border-radius:var(--radius);overflow:hidden;
}
.route-group-header{
  padding:1rem 1.25rem;
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:.6rem;
  font-weight:800;font-size:.9rem;
}
.route-item{
  display:flex;align-items:center;gap:.75rem;
  padding:.65rem 1.25rem;border-bottom:1px solid rgba(255,255,255,.03);
  transition:.2s;
}
.route-item:hover{background:rgba(255,255,255,.04);}
.route-item:last-child{border-bottom:none;}
.route-method{
  font-size:.65rem;font-weight:800;padding:2px 8px;border-radius:4px;
  min-width:36px;text-align:center;
}
.method-get{background:rgba(16,185,129,.15);color:#10b981;}
.method-post{background:rgba(245,158,11,.15);color:#f59e0b;}
.route-path{
  font-family:'Courier New',monospace;font-size:.78rem;
  color:#93c5fd;flex:1;
}
.route-desc{font-size:.72rem;color:var(--text-muted);}
.route-badge{
  font-size:.6rem;font-weight:700;padding:2px 7px;border-radius:8px;
  white-space:nowrap;
}
.badge-user{background:rgba(16,185,129,.15);color:#10b981;}
.badge-admin{background:rgba(239,68,68,.15);color:#f87171;}
.badge-shared{background:rgba(37,99,235,.15);color:#60a5fa;}
/* ── Team / Tech ──────────────────────────────────── */
.tech-stack{
  display:flex;flex-wrap:wrap;justify-content:center;gap:1rem;margin-top:2rem;
}
.tech-badge{
  display:flex;align-items:center;gap:.5rem;
  background:var(--bg-card);border:1px solid var(--border);
  border-radius:30px;padding:.5rem 1.1rem;
  font-size:.8rem;font-weight:700;color:var(--text-muted);
  transition:.2s;
}
.tech-badge:hover{border-color:rgba(37,99,235,.4);color:#fff;background:rgba(37,99,235,.1);}
.tech-badge i{color:var(--primary-light);}
/* ── CTA ──────────────────────────────────────────── */
.cta-section{
  text-align:center;
  background:linear-gradient(135deg,rgba(37,99,235,.12),rgba(99,102,241,.08));
  border:1px solid rgba(37,99,235,.2);
  border-radius:24px;padding:4rem 2rem;
  margin:2rem 0;
}
.cta-section h2{font-size:2rem;font-weight:900;margin-bottom:.75rem;}
.cta-section p{color:var(--text-muted);margin-bottom:2rem;font-size:.95rem;}
/* ── Footer ───────────────────────────────────────── */
footer{
  border-top:1px solid var(--border);
  padding:2rem;text-align:center;
  color:var(--text-muted);font-size:.8rem;position:relative;z-index:1;
}
footer strong{color:var(--primary-light);}
/* ── Animations ───────────────────────────────────── */
@keyframes fadeDown{from{opacity:0;transform:translateY(-20px);}to{opacity:1;transform:translateY(0);}}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
@keyframes shimmer{0%{background-position:0% 50%;}100%{background-position:200% 50%;}}
/* ── Divider ──────────────────────────────────────── */
.gradient-line{
  height:1px;
  background:linear-gradient(90deg,transparent,rgba(37,99,235,.5),rgba(99,102,241,.5),transparent);
  margin:5rem 0;
}
/* ── Scroll reveal ────────────────────────────────── */
.reveal{opacity:0;transform:translateY(24px);transition:.6s ease;}
.reveal.visible{opacity:1;transform:translateY(0);}
</style>
</head>
<body>
<div class="stars-bg"></div>

<!-- ══ Navbar ══════════════════════════════════════════════ -->
<nav class="navbar">
    <div class="nav-brand">
        <div class="nav-logo">🔗</div>
        <span>UniLink</span>
    </div>
    <div class="nav-links">
        <a href="#features"  class="btn-nav btn-nav-ghost">المميزات</a>
        <a href="#routes"    class="btn-nav btn-nav-ghost">المسارات</a>
        <a href="#tech"      class="btn-nav btn-nav-ghost">التقنيات</a>
        <a href="<?= $base ?>/app/login.php" class="btn-nav btn-nav-primary">
            <i class="fa-solid fa-right-to-bracket"></i> تسجيل الدخول
        </a>
    </div>
</nav>

<!-- ══ Hero ════════════════════════════════════════════════ -->
<section class="hero">
<div class="container">
    <div class="hero-badge">
        <i class="fa-solid fa-shield-halved"></i>
        منصة أكاديمية موثوقة • تقنية OTP • Amazon RDS
    </div>
    <h1>
        مرحباً بك في<br>
        <span class="gradient-text">منصة UniLink</span>
    </h1>
    <p>
        بيئة جامعية رقمية آمنة تجمع الطلاب والأساتذة والإدارة في منصة واحدة منظّمة.
        تسجيل دخول بالتحقق الثنائي OTP، مجموعات أكاديمية، رسائل، ملفات، وإدارة متكاملة.
    </p>
    <div class="hero-btns">
        <a href="<?= $base ?>/app/login.php" class="btn btn-primary">
            <i class="fa-solid fa-right-to-bracket"></i> تسجيل الدخول
        </a>
        <a href="<?= $base ?>/app/register.php" class="btn btn-outline">
            <i class="fa-solid fa-user-plus"></i> إنشاء حساب جديد
        </a>
        <a href="<?= $base ?>/admin/login.php" class="btn btn-admin">
            <i class="fa-solid fa-gauge"></i> لوحة تحكم الإدارة
        </a>
    </div>

    <!-- Stats -->
    <div class="stats-bar" style="margin-top:4rem;">
        <div class="stat-item"><div class="stat-value">OTP</div><div class="stat-label">تحقق ثنائي آمن</div></div>
        <div class="stat-item"><div class="stat-value">11</div><div class="stat-label">صفحة إدارية</div></div>
        <div class="stat-item"><div class="stat-value">4</div><div class="stat-label">أدوار مستخدمين</div></div>
        <div class="stat-item"><div class="stat-value">RDS</div><div class="stat-label">Amazon Cloud DB</div></div>
        <div class="stat-item"><div class="stat-value">SMTP</div><div class="stat-label">Gmail مُشفّر SSL</div></div>
    </div>
</div>
</section>

<!-- ══ Features ════════════════════════════════════════════ -->
<section class="section" id="features">
<div class="container">
    <div class="reveal">
        <div class="section-badge"><i class="fa-solid fa-star"></i> المميزات</div>
        <h2 class="section-title">كل ما تحتاجه في مكان واحد</h2>
        <p class="section-sub">منصة شاملة لإدارة التواصل الأكاديمي بأمان وكفاءة</p>
    </div>

    <div class="features-grid reveal">
        <?php
        $features = [
            ['icon'=>'🔐','color'=>'rgba(239,68,68,.15)','title'=>'تسجيل دخول ثنائي (OTP)','desc'=>'كل جلسة دخول تتطلب رمز OTP مُرسَل للبريد عبر Gmail SMTP مشفّر بـ SSL على المنفذ 465.'],
            ['icon'=>'📊','color'=>'rgba(37,99,235,.15)','title'=>'لوحة تحكم شاملة','desc'=>'11 صفحة إدارية: المستخدمون، الأذونات، الإشراف على المحتوى، التقارير، سجل النشاط، الإحصائيات.'],
            ['icon'=>'👥','color'=>'rgba(16,185,129,.15)','title'=>'مجموعات أكاديمية','desc'=>'مجموعات المقررات والأقسام والأنشطة الطلابية مع إدارة العضوية والنشر المنظّم.'],
            ['icon'=>'💬','color'=>'rgba(245,158,11,.15)','title'=>'رسائل خاصة','desc'=>'نظام رسائل مباشرة بين المستخدمين مع تتبع القراءة وتخزين المحادثات في قاعدة البيانات.'],
            ['icon'=>'📁','color'=>'rgba(99,102,241,.15)','title'=>'مستودع الملفات','desc'=>'رفع وتحميل الملفات الأكاديمية (PDF, Word, PowerPoint...) مع ربطها بالمجموعات.'],
            ['icon'=>'🛡️','color'=>'rgba(236,72,153,.15)','title'=>'نظام الصلاحيات (RBAC)','desc'=>'4 أدوار: Admin, Supervisor, Professor, Student — مع تحكم دقيق في الأذونات.'],
            ['icon'=>'☁️','color'=>'rgba(6,182,212,.15)','title'=>'Amazon RDS','desc'=>'قاعدة بيانات MySQL على السحابة بنسخ احتياطية تلقائية وأداء عالٍ.'],
            ['icon'=>'📱','color'=>'rgba(132,204,22,.15)','title'=>'تصميم متجاوب','desc'=>'واجهة مستخدم حديثة تعمل على الجوال والتابلت والحاسوب بسلاسة.'],
            ['icon'=>'🔍','color'=>'rgba(251,146,60,.15)','title'=>'إشراف ذكي','desc'=>'مراجعة المحتوى، تعليق الحسابات، تتبع الجلسات، وتقارير الجهات عبر لوحة الإدارة.'],
        ];
        foreach ($features as $f): ?>
        <div class="feature-card">
            <div class="feature-icon" style="background:<?= $f['color'] ?>;">
                <span style="font-size:1.4rem;"><?= $f['icon'] ?></span>
            </div>
            <h3><?= $f['title'] ?></h3>
            <p><?= $f['desc'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</section>

<div class="gradient-line"></div>

<!-- ══ Routes ══════════════════════════════════════════════ -->
<section class="section" id="routes">
<div class="container">
    <div class="reveal">
        <div class="section-badge"><i class="fa-solid fa-map"></i> المسارات</div>
        <h2 class="section-title">خريطة المسارات الكاملة</h2>
        <p class="section-sub">جميع نقاط الوصول في التطبيق مقسّمة حسب القسم</p>
    </div>

    <div class="routes-layout reveal">

        <!-- App (Frontend) Routes -->
        <div class="route-group">
            <div class="route-group-header" style="border-right:3px solid #10b981;">
                <span style="font-size:1.1rem;">📱</span>
                <span>واجهة المستخدم <code style="font-size:.7rem;background:rgba(16,185,129,.15);color:#10b981;padding:2px 7px;border-radius:4px;">/app/</code></span>
            </div>
            <?php
            $appRoutes = [
                ['GET','login.php','تسجيل الدخول (بريد + كلمة مرور)','user'],
                ['POST','login.php','إرسال OTP بعد التحقق','user'],
                ['GET','otp_verify.php','إدخال رمز OTP','user'],
                ['POST','otp_verify.php','التحقق وإكمال الدخول','user'],
                ['GET','register.php','نموذج تسجيل جديد','user'],
                ['POST','register.php','إنشاء حساب + OTP تفعيل','user'],
                ['GET','verify_email.php','تفعيل البريد الإلكتروني','user'],
                ['GET','feed.php','الخلاصة الاجتماعية','user'],
                ['POST','feed.php','نشر منشور جديد','user'],
                ['GET','groups.php','استكشاف المجموعات','user'],
                ['POST','groups.php','انضمام / مغادرة مجموعة','user'],
                ['GET','messages.php','الرسائل الخاصة','user'],
                ['POST','messages.php','إرسال رسالة','user'],
                ['GET','files.php','الملفات الأكاديمية','user'],
                ['POST','files.php','رفع ملف جديد','user'],
                ['GET','profile.php','الملف الشخصي','user'],
                ['POST','profile.php','تعديل البيانات الشخصية','user'],
                ['GET','logout.php','تسجيل الخروج','user'],
                ['POST','ajax/create_group.php','إنشاء مجموعة (Ajax)','user'],
            ];
            foreach ($appRoutes as [$m,$p,$d,$b]): ?>
            <div class="route-item">
                <span class="route-method method-<?= strtolower($m) ?>"><?= $m ?></span>
                <span class="route-path">/app/<?= $p ?></span>
                <span class="route-desc"><?= $d ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Admin Routes -->
        <div class="route-group">
            <div class="route-group-header" style="border-right:3px solid #ef4444;">
                <span style="font-size:1.1rem;">🛡️</span>
                <span>لوحة الإدارة <code style="font-size:.7rem;background:rgba(239,68,68,.15);color:#f87171;padding:2px 7px;border-radius:4px;">/admin/</code></span>
            </div>
            <?php
            $adminRoutes = [
                ['GET','login.php','دخول لوحة التحكم','admin'],
                ['POST','login.php','التحقق + إرسال OTP للأدمن','admin'],
                ['GET','otp_verify.php','OTP الأدمن','admin'],
                ['GET','index.php','لوحة التحكم الرئيسية','admin'],
                ['GET','pages/users.php','إدارة المستخدمين','admin'],
                ['GET','pages/permissions.php','الأدوار والصلاحيات','admin'],
                ['GET','pages/content_moderation.php','الإشراف على المحتوى','admin'],
                ['GET','pages/groups_moderation.php','إدارة المجموعات','admin'],
                ['GET','pages/reports.php','التقارير','admin'],
                ['GET','pages/sessions_monitoring.php','مراقبة الجلسات','admin'],
                ['GET','pages/activity_logs.php','سجل النشاط','admin'],
                ['GET','pages/statistics.php','الإحصائيات والتحليلات','admin'],
                ['GET','pages/settings.php','إعدادات النظام','admin'],
                ['GET','pages/user_details.php','تفاصيل المستخدم','admin'],
                ['POST','ajax/users.php','CRUD المستخدمين (Ajax)','admin'],
                ['POST','ajax/permissions.php','تعديل الصلاحيات (Ajax)','admin'],
                ['POST','ajax/content.php','إجراءات المحتوى (Ajax)','admin'],
                ['POST','ajax/reports.php','معالجة البلاغات (Ajax)','admin'],
                ['POST','ajax/settings.php','حفظ الإعدادات (Ajax)','admin'],
                ['POST','ajax/groups.php','إدارة المجموعات (Ajax)','admin'],
            ];
            foreach ($adminRoutes as [$m,$p,$d,$b]): ?>
            <div class="route-item">
                <span class="route-method method-<?= strtolower($m) ?>"><?= $m ?></span>
                <span class="route-path">/admin/<?= $p ?></span>
                <span class="route-desc"><?= $d ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Core / Config -->
        <div class="route-group">
            <div class="route-group-header" style="border-right:3px solid #60a5fa;">
                <span style="font-size:1.1rem;">⚙️</span>
                <span>Core & Config <code style="font-size:.7rem;background:rgba(37,99,235,.15);color:#60a5fa;padding:2px 7px;border-radius:4px;">مشترك</code></span>
            </div>
            <?php
            $coreFiles = [
                ['—','config/app.php','APP_NAME, APP_BASE_PATH, SMTP constants','shared'],
                ['—','config/database.php','PDO connection — Amazon RDS MySQL','shared'],
                ['—','core/auth.php','login, OTP, session, remember_me, logout','shared'],
                ['—','core/helpers.php','e(), redirect(), flash(), csrf, url()','shared'],
                ['—','core/mailer.php','SMTP direct (ssl://465) — send_otp_email()','shared'],
                ['—','core/rbac.php','ROLE_PERMISSIONS, user_can()','shared'],
                ['—','app/includes/bootstrap.php','Bootstrap الواجهة + user_url + app_redirect','shared'],
                ['—','admin/includes/bootstrap.php','Bootstrap لوحة التحكم','shared'],
            ];
            foreach ($coreFiles as [$m,$p,$d,$b]): ?>
            <div class="route-item">
                <span class="route-method" style="background:rgba(37,99,235,.15);color:#60a5fa;min-width:36px;text-align:center;font-size:.6rem;padding:2px 6px;border-radius:4px;">PHP</span>
                <span class="route-path">/<?= $p ?></span>
                <span class="route-desc"><?= $d ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Project Structure -->
        <div class="route-group">
            <div class="route-group-header" style="border-right:3px solid #f59e0b;">
                <span style="font-size:1.1rem;">📁</span>
                <span>هيكل المشروع</span>
            </div>
            <div style="padding:1.1rem 1.25rem;font-family:'Courier New',monospace;font-size:.75rem;line-height:2;color:var(--text-muted);">
                <span style="color:#f59e0b;">📁</span> <span style="color:#60a5fa;">Trusted-Social-Network-Platform/</span><br>
                <span style="color:#475569;">├── </span><span style="color:#f59e0b;">📁</span> <span style="color:#86efac;">config/</span> <span style="color:#475569;">← SMTP, DB, constants</span><br>
                <span style="color:#475569;">├── </span><span style="color:#f59e0b;">📁</span> <span style="color:#86efac;">core/</span> <span style="color:#475569;">← مكتبة مشتركة</span><br>
                <span style="color:#475569;">├── </span><span style="color:#f59e0b;">📁</span> <span style="color:#f87171;">admin/</span> <span style="color:#475569;">← لوحة التحكم</span><br>
                <span style="color:#475569;">│&nbsp;&nbsp; ├── </span><span style="color:#f59e0b;">📁</span> <span>includes/</span> <span style="color:#475569;">(bootstrap)</span><br>
                <span style="color:#475569;">│&nbsp;&nbsp; ├── </span><span style="color:#f59e0b;">📁</span> <span>pages/</span> <span style="color:#475569;">(11 صفحة)</span><br>
                <span style="color:#475569;">│&nbsp;&nbsp; ├── </span><span style="color:#f59e0b;">📁</span> <span>ajax/</span> <span style="color:#475569;">(6 handlers)</span><br>
                <span style="color:#475569;">│&nbsp;&nbsp; └── </span><span style="color:#f59e0b;">📁</span> <span>assets/</span> <span style="color:#475569;">(css, js)</span><br>
                <span style="color:#475569;">├── </span><span style="color:#f59e0b;">📁</span> <span style="color:#86efac;">app/</span> <span style="color:#475569;">← واجهة المستخدمين</span><br>
                <span style="color:#475569;">│&nbsp;&nbsp; ├── </span><span style="color:#f59e0b;">📁</span> <span>includes/</span> <span style="color:#475569;">(bootstrap)</span><br>
                <span style="color:#475569;">│&nbsp;&nbsp; ├── </span><span style="color:#f59e0b;">📁</span> <span>partials/</span> <span style="color:#475569;">(nav, sidebar)</span><br>
                <span style="color:#475569;">│&nbsp;&nbsp; ├── </span><span style="color:#f59e0b;">📁</span> <span>ajax/</span><br>
                <span style="color:#475569;">│&nbsp;&nbsp; └── </span><span style="color:#f59e0b;">📁</span> <span>assets/css/</span><br>
                <span style="color:#475569;">├── </span><span style="color:#f59e0b;">📁</span> <span style="color:#86efac;">uploads/files/</span> <span style="color:#475569;">← رفع المستخدمين</span><br>
                <span style="color:#475569;">├── </span><span style="color:#f59e0b;">📁</span> <span>sql/</span> <span style="color:#475569;">← سكريبتات قاعدة البيانات</span><br>
                <span style="color:#475569;">└── </span><span style="color:#fbbf24;">index.php</span> <span style="color:#475569;">← هذه الصفحة 🎯</span>
            </div>
        </div>
    </div>
</div>
</section>

<div class="gradient-line"></div>

<!-- ══ Tech Stack ═══════════════════════════════════════════ -->
<section class="section" id="tech">
<div class="container" style="text-align:center;">
    <div class="reveal">
        <div class="section-badge"><i class="fa-solid fa-microchip"></i> التقنيات</div>
        <h2 class="section-title">المكدّس التقني المستخدم</h2>
        <p class="section-sub">تقنيات موثوقة وحديثة لضمان الأمان والأداء</p>
    </div>
    <div class="tech-stack reveal">
        <?php
        $techs = [
            ['fa-brands fa-php','PHP 8.3','Backend Language'],
            ['fa-solid fa-database','MySQL 8','Database Engine'],
            ['fa-brands fa-aws','Amazon RDS','Cloud Database'],
            ['fa-solid fa-envelope','Gmail SMTP','Email via SSL/465'],
            ['fa-brands fa-html5','HTML 5','Markup'],
            ['fa-brands fa-css3-alt','Vanilla CSS','Styling'],
            ['fa-brands fa-js','JavaScript','Frontend Logic'],
            ['fa-solid fa-shield-halved','CSRF + OTP','Security Layer'],
            ['fa-solid fa-server','XAMPP','Local Server'],
            ['fa-brands fa-git-alt','Git','Version Control'],
        ];
        foreach ($techs as [$icon,$name,$desc]): ?>
        <div class="tech-badge">
            <i class="<?= $icon ?>"></i>
            <span><strong><?= $name ?></strong> <small style="font-weight:400"><?= $desc ?></small></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</section>

<!-- ══ CTA ══════════════════════════════════════════════════ -->
<section class="section">
<div class="container reveal">
    <div class="cta-section">
        <h2>🚀 جاهز للبدء؟</h2>
        <p>سجّل دخولك الآن للوصول لجميع مميزات المنصة الأكاديمية</p>
        <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
            <a href="<?= $base ?>/app/login.php" class="btn btn-primary" style="font-size:1rem;">
                <i class="fa-solid fa-right-to-bracket"></i> تسجيل الدخول
            </a>
            <a href="<?= $base ?>/app/register.php" class="btn btn-outline" style="font-size:1rem;">
                <i class="fa-solid fa-user-plus"></i> إنشاء حساب جديد
            </a>
            <a href="<?= $base ?>/admin/login.php" class="btn btn-admin" style="font-size:1rem;">
                <i class="fa-solid fa-gauge"></i> لوحة الإدارة
            </a>
        </div>
    </div>
</div>
</section>

<!-- ══ Footer ═══════════════════════════════════════════════ -->
<footer>
    <p>
        <strong>UniLink</strong> — منصة التواصل الأكاديمي الموثوقة
        &nbsp;|&nbsp; Version 1.0
        &nbsp;|&nbsp; <?= date('Y') ?>
    </p>
    <p style="margin-top:.4rem;font-size:.72rem;color:#334155;">
        PHP 8.3 + MySQL (Amazon RDS) + Gmail SMTP SSL + CSRF + OTP
    </p>
</footer>

<script>
// Scroll reveal
const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
        if (e.isIntersecting) { e.target.classList.add('visible'); }
    });
}, { threshold: 0.1 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// Navbar scroll effect
window.addEventListener('scroll', () => {
    const n = document.querySelector('.navbar');
    n.style.background = window.scrollY > 60
        ? 'rgba(6,11,24,.95)'
        : 'rgba(6,11,24,.8)';
});
</script>
</body>
</html>
