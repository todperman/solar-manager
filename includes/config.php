<?php
/**
 * Application bootstrap.
 *
 * Settings are read from config/app.php (created by the installer, not committed),
 * then environment variables (used by docker-compose), then XAMPP-friendly defaults.
 */

$__cfgFile = __DIR__ . '/../config/app.php';
$__cfg = is_file($__cfgFile) ? (require $__cfgFile) : [];

function cfg_value(array $cfg, string $section, string $key, string $env, $default) {
    if (isset($cfg[$section][$key]) && $cfg[$section][$key] !== '') {
        return $cfg[$section][$key];
    }
    $fromEnv = getenv($env);
    return $fromEnv !== false ? $fromEnv : $default;
}

// Database Configuration
define('DB_HOST', cfg_value($__cfg, 'db', 'host', 'DB_HOST', '127.0.0.1'));
define('DB_PORT', (string) cfg_value($__cfg, 'db', 'port', 'DB_PORT', '3306'));
define('DB_NAME', cfg_value($__cfg, 'db', 'name', 'DB_NAME', 'solar_manager'));
define('DB_USER', cfg_value($__cfg, 'db', 'user', 'DB_USER', 'root'));
define('DB_PASS', $__cfg['db']['pass'] ?? (getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''));

// App Configuration
define('APP_NAME', 'G2K');
define('APP_VERSION', '2.0.0');
define('APP_DEBUG', filter_var(cfg_value($__cfg, 'app', 'debug', 'APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN));
define('APP_TIMEZONE', cfg_value($__cfg, 'app', 'timezone', 'APP_TIMEZONE', 'Asia/Bangkok'));
define('APP_BASE_URL', cfg_value($__cfg, 'app', 'base_url', 'APP_BASE_URL', 'auto'));
define('UPLOAD_DIR', __DIR__ . '/../public/assets/img/uploads/');
unset($__cfgFile, $__cfg);

date_default_timezone_set(APP_TIMEZONE);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
error_reporting(APP_DEBUG ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// Last-resort handler: log the real error, show a friendly page instead of a blank 500.
set_exception_handler(function (Throwable $e) {
    error_log('Uncaught ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    while (ob_get_level() > 0) ob_end_clean();
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    $detail = APP_DEBUG ? '<pre style="white-space:pre-wrap">' . htmlspecialchars((string) $e) . '</pre>' : '';
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><title>เกิดข้อผิดพลาด</title></head><body>'
        . '<div style="font-family:sans-serif;max-width:640px;margin:80px auto;padding:24px;border:1px solid #e5e7eb;border-radius:12px">'
        . '<h2 style="margin-top:0">เกิดข้อผิดพลาดในระบบ</h2><p>กรุณาลองใหม่อีกครั้ง หากยังพบปัญหาโปรดแจ้งผู้ดูแลระบบ</p>'
        . '<p><a href="javascript:history.back()">&larr; ย้อนกลับ</a></p>' . $detail . '</div></body></html>';
});

// Pages print the admin layout before handling POST + redirect, so buffer output
// to keep header() working regardless of the server's output_buffering setting.
if (PHP_SAPI !== 'cli' && ob_get_level() === 0) {
    ob_start();
}

/**
 * URL path the public/ folder is served from: "" when public/ is the document root,
 * "/solar-manager" when installed at htdocs/solar-manager, etc.
 */
function base_path(): string {
    static $base = null;
    if ($base !== null) return $base;

    if (APP_BASE_URL !== 'auto' && APP_BASE_URL !== null) {
        return $base = rtrim((string) APP_BASE_URL, '/');
    }
    $base = '';
    if (PHP_SAPI === 'cli') return $base;

    $norm = fn($p) => str_replace('\\', '/', (string) $p);
    $publicDir = $norm(realpath(__DIR__ . '/../public'));
    $script = $norm(realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $scriptName = $norm($_SERVER['SCRIPT_NAME'] ?? '');

    if ($publicDir && $script && stripos($script, $publicDir . '/') === 0) {
        $rel = substr($script, strlen($publicDir)); // e.g. /admin/index.php
        if (strlen($scriptName) >= strlen($rel) && strcasecmp(substr($scriptName, -strlen($rel)), $rel) === 0) {
            $base = substr($scriptName, 0, -strlen($rel));
        }
    }

    // Requests routed through the project-root .htaccess keep "/public" out of the URL.
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    if (substr($base, -7) === '/public' && stripos($uri, $base . '/') !== 0 && strcasecmp($uri, $base) !== 0) {
        $base = substr($base, 0, -7);
    }
    return $base;
}

/** Build an app URL from an app-absolute path, e.g. url('/admin/orders.php'). */
function url(string $path = '/'): string {
    return base_path() . '/' . ltrim($path, '/');
}

// Database Connection
function db() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            $detail = APP_DEBUG ? '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>' : '';
            die('<div style="font-family:sans-serif;max-width:560px;margin:80px auto;padding:24px;border:1px solid #e5e7eb;border-radius:12px">'
                . '<h2 style="margin-top:0">ไม่สามารถเชื่อมต่อฐานข้อมูลได้</h2>'
                . '<p>ตรวจสอบว่า MySQL ใน XAMPP Control Panel กำลังทำงาน และค่าในไฟล์ <code>config/app.php</code> ถูกต้อง</p>'
                . $detail . '</div>');
        }
    }
    return $pdo;
}

// Start Session
if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    session_name('G2KSESSID');
    session_set_cookie_params(['path' => base_path() . '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
