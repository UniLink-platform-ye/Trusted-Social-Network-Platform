-- migration_api_schema_fix.sql
-- يوحّد مخطط قاعدة البيانات مع ما يتوقعه الـ API (راجع UniLink_documentions.md).
-- شغّل مرة واحدة على قاعدة trusted_social_network_platform.
-- إذا ظهر خطأ "Duplicate column" فمعناه أن العمود موجود مسبقاً ويمكن تجاهله.

USE `trusted_social_network_platform`;

-- 1) جدول posts: إضافة status للحذف المنطقي وفلترة الخلاصة (الـ API يستخدم p.status='active')
ALTER TABLE `posts`
  ADD COLUMN `status` ENUM('active','deleted') NOT NULL DEFAULT 'active' AFTER `is_flagged`;

-- 2) جدول groups: إضافة status لفلترة المجموعات (الـ API يستخدم g.status='active')
ALTER TABLE `groups`
  ADD COLUMN `status` ENUM('active','hidden','deleted') NOT NULL DEFAULT 'active' AFTER `members_count`;
