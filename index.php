<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

// نقطة الدخول الرئيسية — توجيه حسب حالة المستخدم
if (is_logged_in()) {
    $role = current_user()['role'] ?? '';
    redirect(in_array($role, ['admin','supervisor']) ? 'admin/index.php' : 'feed.php');
} else {
    redirect('login.php');
}
