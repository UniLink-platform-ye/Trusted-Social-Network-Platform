<?php
// Trusted-Social-Network-Platform/config/database.php
declare(strict_types = 1)
;

require_once __DIR__ . '/app.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $usedPasswordLabel = null;

    try {
        // First attempt with configured password
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $usedPasswordLabel = 'PRIMARY (from config)';

    }
    catch (PDOException $exception) {

        // Local environment fallback logic
        if (in_array(DB_HOST, ['localhost', '127.0.0.1'], true)) {

            // Try alternative password automatically
            $fallbackPass = (DB_PASS === 'root') ? '' : 'root';

            try {
                $pdo = new PDO($dsn, DB_USER, $fallbackPass, $options);
                $usedPasswordLabel = sprintf(
                    'FALLBACK (%s)',
                    $fallbackPass === '' ? 'empty' : 'root'
                );

            }
            catch (PDOException $fallbackException) {
                http_response_code(500);

                echo "<h3>Database connection failed (both attempts)</h3>";
                echo "<p>Host: <strong>" . htmlspecialchars(DB_HOST) . "</strong></p>";
                echo "<p>User: <strong>" . htmlspecialchars(DB_USER) . "</strong></p>";
                echo "<p>Passwords tried: <strong>"
                    . htmlspecialchars(DB_PASS)
                    . "</strong> and <strong>"
                    . ($fallbackPass === '' ? '[empty]' : 'root')
                    . "</strong></p>";

                echo "<h4>Primary error:</h4>";
                echo "<pre>" . htmlspecialchars($exception->getMessage()) . "</pre>";

                echo "<h4>Fallback error:</h4>";
                echo "<pre>" . htmlspecialchars($fallbackException->getMessage()) . "</pre>";

                error_log('DB primary connection error: ' . $exception->getMessage());
                error_log('DB fallback connection error: ' . $fallbackException->getMessage());
                exit;
            }

        }
        else {
            // Production: fail on first attempt
            http_response_code(500);

            echo "<h3>Database connection failed</h3>";
            echo "<p>Host: <strong>" . htmlspecialchars(DB_HOST) . "</strong></p>";
            echo "<p>User: <strong>" . htmlspecialchars(DB_USER) . "</strong></p>";
            echo "<p>Password source: <strong>PRIMARY (from config)</strong></p>";

            echo "<h4>Error message:</h4>";
            echo "<pre>" . htmlspecialchars($exception->getMessage()) . "</pre>";

            error_log('DB connection error: ' . $exception->getMessage());
            exit;
        }
    }

    // Try to align DB timezone with PHP (Asia/Riyadh)
    try {
        $pdo->exec("SET time_zone = '+03:00'");
    }
    catch (PDOException $e) {
        error_log('Failed to set MySQL time_zone: ' . $e->getMessage());
    }

    // Debug output: connection status
    if (PHP_SAPI !== 'cli') {
        echo "<h3>Database connection successful</h3>";
        echo "<p>Host: <strong>" . htmlspecialchars(DB_HOST) . "</strong></p>";
        echo "<p>User: <strong>" . htmlspecialchars(DB_USER) . "</strong></p>";
        echo "<p>Password used: <strong>" . htmlspecialchars($usedPasswordLabel ?? 'UNKNOWN') . "</strong></p>";
    }
    else {
        // CLI-friendly output
        fwrite(STDOUT, "Database connection successful\n");
        fwrite(STDOUT, "Host: " . DB_HOST . "\n");
        fwrite(STDOUT, "User: " . DB_USER . "\n");
        fwrite(STDOUT, "Password used: " . ($usedPasswordLabel ?? 'UNKNOWN') . "\n");
    }

    return $pdo;
}

// ── Call db() when this file is opened directly (for debugging) ────────────
if (!debug_backtrace()) {
    db();
}
