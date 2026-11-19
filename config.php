<?php
declare(strict_types=1);

/*
 * DELTA DRUG STORE ERP - Configuration
 *
 * For InfinityFree / Shared Hosting:
 * - DB_HOST: Found in your InfinityFree Client Area / Control Panel (e.g. sql123.infinityfree.com or sql200.byetcluster.com)
 *   IMPORTANT: 'localhost' does NOT work on InfinityFree!
 * - DB_NAME: Full database name with account prefix (e.g. epiz_12345678_delta_erp)
 * - DB_USER: Full database username with account prefix (e.g. epiz_12345678)
 * - DB_PASS: Your InfinityFree vPanel / hosting account password
 */

const DB_HOST = 'localhost'; // Change for InfinityFree (e.g. sqlXXX.infinityfree.com)
const DB_NAME = 'delta_erp'; // Change to your full database name
const DB_USER = 'root';      // Change to your database user
const DB_PASS = '';          // Change to your database password
const APP_NAME = 'DELTA DRUG STORE ERP';
const CURRENCY = 'ETB';
const APP_TIMEZONE = 'Africa/Addis_Ababa';

date_default_timezone_set(APP_TIMEZONE);

// Standard PHP native sessions
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('dds_erp_session');
    session_set_cookie_params([
        'httponly' => true,
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                      || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'),
        'samesite' => 'Lax'
    ]);
    session_start();
}

try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Database Connection Error · <?=htmlspecialchars(APP_NAME)?></title>
      <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 2rem; margin: 0; }
        .card { max-width: 600px; margin: 40px auto; background: #1e293b; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h1 { color: #f43f5e; margin-top: 0; font-size: 1.4rem; }
        p { color: #94a3b8; line-height: 1.6; }
        .error-msg { background: #0f172a; padding: 0.8rem; border-radius: 6px; color: #fca5a5; font-family: monospace; font-size: 0.85rem; word-break: break-all; margin: 1rem 0; }
        .notice { background: #0f172a; padding: 1rem; border-radius: 8px; border-left: 4px solid #38bdf8; font-size: 0.9rem; color: #cbd5e1; }
        .notice ul { margin: 0.5rem 0 0; padding-left: 1.2rem; }
        .notice li { margin-bottom: 0.3rem; }
        code { background: #334155; padding: 2px 6px; border-radius: 4px; color: #38bdf8; }
      </style>
    </head>
    <body>
      <div class="card">
        <h1>Database Connection Failed</h1>
        <p>Delta ERP could not connect to MySQL. Please verify your credentials in <strong>config.php</strong>.</p>
        <div class="error-msg"><?=htmlspecialchars($e->getMessage())?></div>
        <div class="notice">
          <strong>Troubleshooting for InfinityFree:</strong>
          <ul>
            <li><strong>Host:</strong> Do NOT use <code>localhost</code>. Use the MySQL Hostname from your InfinityFree Client Area (e.g. <code>sqlXXX.infinityfree.com</code>).</li>
            <li><strong>Database & Username:</strong> Must include your account prefix (e.g. <code>epiz_12345678_...</code>).</li>
            <li><strong>Schema:</strong> Ensure you imported <code>schema.sql</code> into your database via phpMyAdmin in the InfinityFree Control Panel.</li>
          </ul>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}

require_once __DIR__.'/includes/functions.php';
