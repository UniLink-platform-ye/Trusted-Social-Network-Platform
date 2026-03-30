<?php
declare(strict_types=1);

// ── core/ (مكتبة مشتركة) ─────────────────────────────────
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/rbac.php';
require_once __DIR__ . '/../../core/mailer.php';
require_once __DIR__ . '/../../core/branding.php';

restore_remember_session();
