<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$userId = (int) ($_SESSION['user']['user_id'] ?? 0);
if ($userId > 0) logout_user();
flash('success', 'تم تسجيل خروجك بنجاح.');
redirect('login.php');
