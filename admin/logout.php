<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_login();

if (is_post() && verify_csrf()) {
    $userId = (int) ($_SESSION['user']['user_id'] ?? 0);
    logout_user();
    flash('success', 'تم تسجيل الخروج بنجاح.');
}

redirect('admin/login.php');
