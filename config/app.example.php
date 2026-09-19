<?php
/**
 * Copy to config/app.php (the installer does this for you) and edit.
 * config/app.php is ignored by git — never commit real credentials.
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'solar_manager',
        'user' => 'root',
        'pass' => '',            // XAMPP default: empty
    ],
    'app' => [
        // 'auto' detects the install folder (e.g. http://localhost/solar-manager).
        // Set explicitly, e.g. '/solar-manager' or '', if auto-detection is wrong.
        'base_url' => 'auto',
        'timezone' => 'Asia/Bangkok',
        'debug'    => false,     // true shows PHP errors on screen; keep false in production
    ],
];
