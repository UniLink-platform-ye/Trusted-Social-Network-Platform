<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="<?= e(APP_LOCALE); ?>" dir="<?= e(APP_DIR); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()); ?>">
    <title><?= e($pageTitle . ' | ' . APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= e(asset_url('css/style.css')); ?>">
</head>
<body class="admin-body">
<div id="appLoader" class="app-loader">
    <div class="loader-ring"></div>
</div>
<div class="app-shell">
