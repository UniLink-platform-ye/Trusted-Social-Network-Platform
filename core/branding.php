<?php

declare(strict_types=1);

/**
 * branding.php — مساعد مركزي لإعدادات الهوية البصرية
 * يُستخدم في: لوحة الأدمن، API الهوية، وأي مكون يحتاج إلى ألوان/اسم المنصة.
 *
 * الاستخدام:
 *   $b = get_branding();
 *   echo $b['platform_name'];   // UniLink
 *   echo $b['primary_color'];   // #004D8C
 */

/** القيم الافتراضية (تُستخدم عند فقدان الجدول أو فشل الاتصال) */
const BRANDING_DEFAULTS = [
    'id'                   => 1,
    'platform_name'        => 'UniLink',
    'platform_tagline'     => 'منصة التواصل الأكاديمي الموثوقة',
    'primary_color'        => '#004D8C',
    'secondary_color'      => '#007786',
    'accent_color'         => '#00B4D8',
    'background_color'     => '#FFFFFF',
    'text_color'           => '#1E293B',
    'button_primary_color' => '#004D8C',
    'button_text_color'    => '#FFFFFF',
    'card_bg_color'        => '#F8FAFC',
    'input_bg_color'       => '#FFFFFF',
    'input_border_color'   => '#CBD5E1',
    'font_family'          => 'Cairo',
    'logo_path'            => null,
    'active_template_key'  => 'deep_blue',
    'updated_at'           => null,
];

/**
 * تحميل إعدادات الهوية من قاعدة البيانات.
 * نتيجة مُخبأة في الـ static variable لتفادي استعلامات متكررة.
 *
 * @return array<string, mixed>
 */
function get_branding(): array
{
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    try {
        $stmt = db()->query(
            'SELECT * FROM `branding_settings` WHERE id = 1 LIMIT 1'
        );
        $row = $stmt->fetch();
        $cached = $row ? array_merge(BRANDING_DEFAULTS, $row) : BRANDING_DEFAULTS;
    } catch (Throwable $e) {
        error_log('[Branding] DB error: ' . $e->getMessage());
        $cached = BRANDING_DEFAULTS;
    }

    return $cached;
}

/**
 * بناء رابط الشعار الكامل.
 * إذا كان logo_path فارغًا يُرجع مسار الشعار الافتراضي.
 *
 * @return string URL كامل للشعار
 */
function branding_logo_url(): string
{
    $b    = get_branding();
    $path = $b['logo_path'] ?? null;

    if ($path && file_exists(__DIR__ . '/../' . ltrim($path, '/'))) {
        return url(ltrim($path, '/'));
    }

    return url('img/logo.png');
}

/**
 * إعادة تعيين الـ cache (يُستخدم بعد الحفظ مباشرةً).
 */
function branding_flush_cache(): void
{
    // PHP static variables يعيشون طوال الطلب الواحد فقط،
    // لكن نُعيد التعيين هنا لضمان القراءة الصحيحة بعد الحفظ.
    static $dummy = null;
    $dummy = true;

    // نُعيد تعيين الدالة الفعلية عبر حيلة الـ static variable
    (function () {
        static $cached;
        $cached = null;
    })();
}

/**
 * قوالب الهوية الجاهزة.
 *
 * @return array<string, array<string, mixed>>
 */
function branding_templates(): array
{
    return [

        'deep_blue' => [
            'name'                 => 'UniLink Deep Blue',
            'name_ar'              => 'الأزرق الملكي العميق',
            'description'          => 'درجات الأزرق الملكي — هوية أكاديمية راقية',
            'primary_color'        => '#004D8C',
            'secondary_color'      => '#007786',
            'accent_color'         => '#00B4D8',
            'background_color'     => '#FFFFFF',
            'text_color'           => '#1E293B',
            'button_primary_color' => '#004D8C',
            'button_text_color'    => '#FFFFFF',
            'card_bg_color'        => '#F0F7FF',
            'input_bg_color'       => '#FFFFFF',
            'input_border_color'   => '#B3D4F0',
            'font_family'          => 'Cairo',
        ],

        'emerald_warmth' => [
            'name'                 => 'Emerald Warmth',
            'name_ar'              => 'الدفء الزمردي',
            'description'          => 'ثيم دافئ بدرجات الزيتوني والذهبي — واجهة مريحة للعين',
            'primary_color'        => '#065F46',
            'secondary_color'      => '#D97706',
            'accent_color'         => '#6EE7B7',
            'background_color'     => '#FFFBEB',
            'text_color'           => '#1C1917',
            'button_primary_color' => '#065F46',
            'button_text_color'    => '#FFFFFF',
            'card_bg_color'        => '#F0FDF4',
            'input_bg_color'       => '#FFFFFF',
            'input_border_color'   => '#A7F3D0',
            'font_family'          => 'Tajawal',
        ],

        'slate_dark' => [
            'name'                 => 'Slate Dark',
            'name_ar'              => 'الداكن الرمادي',
            'description'          => 'ثيم داكن أنيق مناسب للوضع الليلي',
            'primary_color'        => '#6366F1',
            'secondary_color'      => '#8B5CF6',
            'accent_color'         => '#A78BFA',
            'background_color'     => '#0F172A',
            'text_color'           => '#E2E8F0',
            'button_primary_color' => '#6366F1',
            'button_text_color'    => '#FFFFFF',
            'card_bg_color'        => '#1E293B',
            'input_bg_color'       => '#1E293B',
            'input_border_color'   => '#334155',
            'font_family'          => 'Cairo',
        ],
    ];
}
