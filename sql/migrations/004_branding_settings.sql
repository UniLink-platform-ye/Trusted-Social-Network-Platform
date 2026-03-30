-- ============================================================
-- Migration 004 — Branding / Theme Settings
-- Created: 2026-03-30
-- Description: إضافة جدول إعدادات الهوية البصرية للمنصة
-- ============================================================



CREATE TABLE IF NOT EXISTS `branding_settings` (
  `id`                    INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `platform_name`         VARCHAR(120)     NOT NULL DEFAULT 'Trusted Social Network Platform',
  `platform_tagline`      VARCHAR(255)     NOT NULL DEFAULT 'منصة التواصل الأكاديمي الموثوقة',

  -- ── الألوان ────────────────────────────────────────────────
  `primary_color`         VARCHAR(9)       NOT NULL DEFAULT '#004D8C',
  `secondary_color`       VARCHAR(9)       NOT NULL DEFAULT '#007786',
  `accent_color`          VARCHAR(9)       NOT NULL DEFAULT '#00B4D8',
  `background_color`      VARCHAR(9)       NOT NULL DEFAULT '#FFFFFF',
  `text_color`            VARCHAR(9)       NOT NULL DEFAULT '#1E293B',
  `button_primary_color`  VARCHAR(9)       NOT NULL DEFAULT '#004D8C',
  `button_text_color`     VARCHAR(9)       NOT NULL DEFAULT '#FFFFFF',
  `card_bg_color`         VARCHAR(9)       NOT NULL DEFAULT '#F8FAFC',
  `input_bg_color`        VARCHAR(9)       NOT NULL DEFAULT '#FFFFFF',
  `input_border_color`    VARCHAR(9)       NOT NULL DEFAULT '#CBD5E1',

  -- ── الخط ───────────────────────────────────────────────────
  `font_family`           VARCHAR(80)      NOT NULL DEFAULT 'Cairo',

  -- ── الشعار ─────────────────────────────────────────────────
  `logo_path`             VARCHAR(512)              DEFAULT NULL COMMENT 'مسار الشعار نسبة إلى جذر المشروع',

  -- ── القالب النشط ───────────────────────────────────────────
  `active_template_key`   VARCHAR(60)      NOT NULL DEFAULT 'deep_blue',

  -- ── معلومات ───────────────────────────────────────────────
  `updated_by`            INT UNSIGNED              DEFAULT NULL COMMENT 'user_id للمدير الذي آخر تعديل',
  `updated_at`            DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`)
) ENGINE=InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  COMMENT = 'إعدادات الهوية البصرية للمنصة – صف واحد دائماً (id=1)';

-- ── الصف الافتراضي (القالب: Deep Blue) ──────────────────────
INSERT IGNORE INTO `branding_settings`
  (`id`, `platform_name`, `platform_tagline`,
   `primary_color`, `secondary_color`, `accent_color`,
   `background_color`, `text_color`,
   `button_primary_color`, `button_text_color`,
   `card_bg_color`, `input_bg_color`, `input_border_color`,
   `font_family`, `logo_path`, `active_template_key`)
VALUES
  (1, 'Trusted Social Network Platform', 'منصة التواصل الأكاديمي الموثوقة',
   '#004D8C', '#007786', '#00B4D8',
   '#FFFFFF', '#1E293B',
   '#004D8C', '#FFFFFF',
   '#F8FAFC', '#FFFFFF', '#CBD5E1',
   'Cairo', NULL, 'deep_blue');


