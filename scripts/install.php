<?php
/**
 * G2K Solar Manager — installer
 *
 *   Windows (XAMPP):  install.bat
 *   macOS / Linux:    ./install.sh
 *   Direct:           php scripts/install.php [options]
 *
 * Options (all optional; anything missing is asked interactively):
 *   --db-host=127.0.0.1 --db-port=3306 --db-name=solar_manager --db-user=root --db-pass=
 *   --admin-name="Administrator" --admin-email=admin@example.com --admin-password=secret
 *   --base-url=auto        Install folder in the URL ('auto', '' or e.g. '/solar-manager')
 *   --yes                  Accept defaults for every unanswered question (non-interactive)
 *   --force-config         Overwrite an existing config/app.php
 *
 * Safe to re-run: an existing database is never dropped or re-seeded.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this installer from the command line.\n");
}

const ROOT = __DIR__ . '/..';
date_default_timezone_set('Asia/Bangkok');
$opts = getopt('', ['db-host::', 'db-port::', 'db-name::', 'db-user::', 'db-pass::', 'admin-name::', 'admin-email::',
    'admin-password::', 'base-url::', 'yes', 'force-config', 'help']);
$assumeYes = isset($opts['yes']);

if (isset($opts['help'])) {
    echo preg_replace('/^ \* ?/m', '', explode('*/', explode('/**', file_get_contents(__FILE__))[1])[0]);
    exit(0);
}

function out(string $msg = ''): void { echo $msg . PHP_EOL; }
function ok(string $msg): void { out("  [OK]   $msg"); }
function warn(string $msg): void { out("  [WARN] $msg"); }
function fail(string $msg): void { out("  [FAIL] $msg"); exit(1); }

function ask(string $label, string $default, ?string $given, bool $assumeYes): string {
    if ($given !== null) return $given;
    if ($assumeYes) return $default;
    $shown = $default === '' ? '' : " [$default]";
    echo "  $label$shown: ";
    $line = fgets(STDIN);
    $line = $line === false ? '' : trim($line);
    return $line === '' ? $default : $line;
}

function confirm(string $label, bool $default, bool $assumeYes): bool {
    if ($assumeYes) return $default;
    echo "  $label " . ($default ? '[Y/n]' : '[y/N]') . ': ';
    $line = strtolower(trim((string) fgets(STDIN)));
    return $line === '' ? $default : in_array($line, ['y', 'yes'], true);
}

out('==============================================');
out('  G2K Solar Manager — Installer');
out('==============================================');

// 1. Requirements -----------------------------------------------------------
out();
out('1) Checking requirements');
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    fail('PHP 8.0 or newer is required (found ' . PHP_VERSION . '). Use XAMPP 8.x.');
}
ok('PHP ' . PHP_VERSION);
foreach (['pdo_mysql', 'mbstring', 'json', 'fileinfo'] as $ext) {
    if (extension_loaded($ext)) {
        ok("extension $ext");
    } elseif ($ext === 'fileinfo') {
        warn("extension fileinfo not loaded (optional)");
    } else {
        fail("PHP extension '$ext' is missing. Enable it in php.ini (remove ';' before extension=$ext) and restart Apache.");
    }
}

// 2. Database ----------------------------------------------------------------
out();
out('2) Database connection (XAMPP default: user root, empty password)');
$existing = is_file(ROOT . '/config/app.php') ? (require ROOT . '/config/app.php') : [];
$db = $existing['db'] ?? [];

while (true) {
    $host = ask('MySQL host', (string) ($db['host'] ?? '127.0.0.1'), $opts['db-host'] ?? null, $assumeYes);
    $port = ask('MySQL port', (string) ($db['port'] ?? '3306'), $opts['db-port'] ?? null, $assumeYes);
    $user = ask('MySQL user', (string) ($db['user'] ?? 'root'), $opts['db-user'] ?? null, $assumeYes);
    $pass = ask('MySQL password', (string) ($db['pass'] ?? ''), $opts['db-pass'] ?? null, $assumeYes);
    $name = ask('Database name', (string) ($db['name'] ?? 'solar_manager'), $opts['db-name'] ?? null, $assumeYes);
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        warn('Database name may only contain letters, digits and _');
        if ($assumeYes || isset($opts['db-name'])) exit(1);
        continue;
    }
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        ok('Connected to ' . $pdo->query('SELECT VERSION()')->fetchColumn());
        break;
    } catch (PDOException $e) {
        warn('Cannot connect: ' . $e->getMessage());
        out('         Is MySQL started in the XAMPP Control Panel?');
        if ($assumeYes || isset($opts['db-host']) || !confirm('Try again?', true, false)) exit(1);
    }
}

$pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$name`");
ok("Database `$name` ready");

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$freshInstall = count($tables) === 0;
if ($freshInstall) {
    $sql = file_get_contents(ROOT . '/sql/schema.sql');
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', preg_split('/;\s*(\r?\n|$)/', $sql)));
    try {
        foreach ($statements as $stmt) {
            $pdo->exec($stmt);
        }
    } catch (PDOException $e) {
        fail("Schema import failed: " . $e->getMessage() . "\n         Statement: " . substr($stmt, 0, 120));
    }
    ok('Schema and sample data imported (' . count($statements) . ' statements)');
} else {
    ok('Existing data kept (' . count($tables) . ' tables found) — nothing imported');
    if (!in_array('users', $tables, true)) {
        fail("Database `$name` has tables but no `users` table. Use an empty database or the correct one.");
    }
}

// 3. Admin account ------------------------------------------------------------
out();
out('3) Administrator account');
$setAdmin = $freshInstall || isset($opts['admin-email']) || isset($opts['admin-password'])
    || confirm('Reset the administrator login?', false, $assumeYes);
if ($setAdmin) {
    $adminName = ask('Admin name', 'Administrator', $opts['admin-name'] ?? null, $assumeYes);
    $adminEmail = ask('Admin email', 'admin@g2k.co.th', $opts['admin-email'] ?? null, $assumeYes);
    $generated = bin2hex(random_bytes(5));
    $adminPass = ask('Admin password (min 8 chars, blank = random)', '', $opts['admin-password'] ?? null, $assumeYes);
    if ($adminPass === '') {
        $adminPass = $generated;
    }
    if (strlen($adminPass) < 8) fail('Password must be at least 8 characters.');
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) fail('Invalid email address.');

    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
    $adminId = $pdo->query("SELECT id FROM users WHERE role='admin' ORDER BY id LIMIT 1")->fetchColumn();
    if ($adminId) {
        $pdo->prepare("UPDATE users SET name=?, email=?, password=?, status='active' WHERE id=?")->execute([$adminName, $adminEmail, $hash, $adminId]);
    } else {
        $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?, 'admin')")->execute([$adminName, $adminEmail, $hash]);
    }
    ok("Admin login: $adminEmail / $adminPass");
} else {
    ok('Admin account unchanged');
}

// 4. Config file -----------------------------------------------------------------
out();
out('4) Writing config/app.php');
$configPath = ROOT . '/config/app.php';
$writeConfig = !is_file($configPath) || isset($opts['force-config'])
    || confirm('config/app.php exists. Overwrite with the settings above?', true, $assumeYes);
if ($writeConfig) {
    $baseUrl = ask('Base URL path (auto / "" / e.g. /solar-manager)', (string) ($existing['app']['base_url'] ?? 'auto'), $opts['base-url'] ?? null, $assumeYes);
    $config = [
        'db' => ['host' => $host, 'port' => (int) $port, 'name' => $name, 'user' => $user, 'pass' => $pass],
        'app' => [
            'base_url' => $baseUrl,
            'timezone' => $existing['app']['timezone'] ?? 'Asia/Bangkok',
            'debug' => (bool) ($existing['app']['debug'] ?? false),
        ],
    ];
    $php = "<?php\n// Generated by scripts/install.php on " . date('Y-m-d H:i') . ". Not tracked by git.\n"
        . "// See config/app.example.php for the meaning of each option.\nreturn " . var_export($config, true) . ";\n";
    if (file_put_contents($configPath, $php) === false) fail("Cannot write $configPath");
    ok('config/app.php written');
} else {
    ok('config/app.php kept');
}

// 5. Folders -------------------------------------------------------------------------
out();
out('5) File permissions');
$uploads = ROOT . '/public/assets/img/uploads';
if (!is_dir($uploads)) mkdir($uploads, 0775, true);
@chmod($uploads, 0775);
is_writable($uploads) ? ok('public/assets/img/uploads is writable') : warn('public/assets/img/uploads is NOT writable by this user — make it writable for Apache');

out();
out('==============================================');
out('  Installation complete');
out('==============================================');
$folder = basename(realpath(ROOT));
out("  Store : http://localhost/$folder/");
out("  Admin : http://localhost/$folder/login.php");
out('  (or your VirtualHost URL — see docs/DEPLOY-XAMPP.md)');
out();
