<?php

declare(strict_types=1);

require_once __DIR__ . '/app.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $exception) {
        // إذا فشل الاتصال في البيئة المحلية، نجرب كلمة المرور البديلة آلياً
        if (in_array(DB_HOST, ['localhost', '127.0.0.1'])) {
            $fallbackPass = (DB_PASS === 'root') ? '' : 'root';
            try {
                $pdo = new PDO($dsn, DB_USER, $fallbackPass, $options);
            } catch (PDOException $fallbackException) {
                // فشل بالكلمتين!
                http_response_code(500);
                echo 'Database connection failed with both passwords. Verify XAMPP settings.';
                error_log('DB connection error: ' . $fallbackException->getMessage());
                exit;
            }
        } else {
            // بيئة الإنتاج: تفشل من المحاولة الأولى إذا كانت غير صحيحة
            http_response_code(500);
            echo 'Database connection failed. Please verify config/database.php settings.';
            error_log('DB connection error: ' . $exception->getMessage());
            exit;
        }
    }

    // توحيد المنطقة الزمنية لقاعدة البيانات مع إعداد PHP (Asia/Riyadh)
    try {
        $pdo->exec("SET time_zone = '+03:00'");
    } catch (PDOException $e) {
        error_log('Failed to set MySQL time_zone: ' . $e->getMessage());
    }

    return $pdo;
}
