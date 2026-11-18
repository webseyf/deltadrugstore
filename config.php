<?php
declare(strict_types=1);

// Database credentials from Environment Variables or defaults
$dbHost = getenv('DB_HOST') ?: (getenv('MYSQL_HOST') ?: '127.0.0.1');
$dbPort = getenv('DB_PORT') ?: (getenv('MYSQL_PORT') ?: '3306');
$dbName = getenv('DB_NAME') ?: (getenv('MYSQL_DATABASE') ?: 'delta_erp');
$dbUser = getenv('DB_USER') ?: (getenv('MYSQL_USER') ?: 'root');
$dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQL_PASSWORD') !== false ? getenv('MYSQL_PASSWORD') : '');

// Support connection string URL: e.g. mysql://user:password@host:port/dbname
$databaseUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
if (!empty($databaseUrl)) {
    $parts = parse_url($databaseUrl);
    if ($parts) {
        if (!empty($parts['host'])) $dbHost = $parts['host'];
        if (!empty($parts['port'])) $dbPort = (string)$parts['port'];
        if (!empty($parts['user'])) $dbUser = urldecode($parts['user']);
        if (isset($parts['pass'])) $dbPass = urldecode($parts['pass']);
        if (!empty($parts['path'])) $dbName = ltrim($parts['path'], '/');
    }
}

const DB_HOST = 'DATABASE_CONFIGURED'; // Kept for compatibility if checked
define('APP_NAME', getenv('APP_NAME') ?: 'DELTA DRUG STORE ERP');
define('CURRENCY', getenv('CURRENCY') ?: 'ETB');
define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'Africa/Addis_Ababa');

date_default_timezone_set(APP_TIMEZONE);

$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// Handle SSL CA certificate if specified
$sslCa = getenv('MYSQL_ATTR_SSL_CA') ?: getenv('DB_SSL_CA');
if (!empty($sslCa) && file_exists($sslCa)) {
    $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
}

try {
    $dsn = 'mysql:host='.$dbHost.';port='.$dbPort.';dbname='.$dbName.';charset=utf8mb4';
    $pdo = new PDO($dsn, $dbUser, $dbPass, $pdoOptions);
} catch (PDOException $e) {
    http_response_code(500);
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Database Setup Required · <?=htmlspecialchars(APP_NAME)?></title>
      <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 2rem; margin: 0; }
        .card { max-width: 650px; margin: 40px auto; background: #1e293b; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h1 { color: #f43f5e; margin-top: 0; font-size: 1.5rem; }
        p { color: #94a3b8; line-height: 1.6; }
        pre { background: #0f172a; padding: 1rem; border-radius: 8px; overflow-x: auto; color: #38bdf8; font-size: 0.9rem; }
        .badge { background: #334155; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; color: #f1f5f9; display: inline-block; word-break: break-all; }
      </style>
    </head>
    <body>
      <div class="card">
        <h1>Database Connection Failed</h1>
        <p>Delta ERP could not connect to MySQL. In a serverless environment like Vercel, your database must be hosted on an external MySQL service (e.g. Aiven, PlanetScale, Supabase, TiDB Cloud, Railway, or remote cPanel MySQL).</p>
        <p><strong>Error:</strong> <?=htmlspecialchars($e->getMessage())?></p>
        <h3>Configure in Vercel Dashboard &gt; Settings &gt; Environment Variables:</h3>
        <pre>
DB_HOST = your-mysql-host.example.com
DB_PORT = 3306
DB_NAME = your_database_name
DB_USER = your_database_username
DB_PASS = your_database_password
        </pre>
        <p>Or single connection URL: <br><span class="badge">DATABASE_URL = mysql://user:pass@host:port/dbname</span></p>
        <p><em>Make sure you import <strong>schema.sql</strong> into your database first.</em></p>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// Database session handler for serverless multi-instance persistence
if (!class_exists('PdoSessionHandler')) {
    class PdoSessionHandler implements SessionHandlerInterface {
        private PDO $pdo;
        private static bool $tableEnsured = false;

        public function __construct(PDO $pdo) {
            $this->pdo = $pdo;
            if (!self::$tableEnsured) {
                try {
                    $this->pdo->exec("CREATE TABLE IF NOT EXISTS `sessions` (
                        `id` VARCHAR(128) NOT NULL PRIMARY KEY,
                        `data` MEDIUMTEXT NOT NULL,
                        `last_activity` INT UNSIGNED NOT NULL,
                        INDEX `idx_sessions_activity` (`last_activity`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                } catch (Throwable $t) {}
                self::$tableEnsured = true;
            }
        }
        public function open($savePath, $sessionName): bool { return true; }
        public function close(): bool { return true; }
        public function read($id): string {
            try {
                $stmt = $this->pdo->prepare('SELECT `data` FROM `sessions` WHERE `id` = ? AND `last_activity` > ?');
                $stmt->execute([$id, time() - 86400]);
                return (string)($stmt->fetchColumn() ?: '');
            } catch (Throwable $e) {
                return '';
            }
        }
        public function write($id, $data): bool {
            try {
                $stmt = $this->pdo->prepare('REPLACE INTO `sessions` (`id`, `data`, `last_activity`) VALUES (?, ?, ?)');
                return $stmt->execute([$id, $data, time()]);
            } catch (Throwable $e) {
                return false;
            }
        }
        public function destroy($id): bool {
            try {
                $stmt = $this->pdo->prepare('DELETE FROM `sessions` WHERE `id` = ?');
                return $stmt->execute([$id]);
            } catch (Throwable $e) {
                return false;
            }
        }
        public function gc($max_lifetime): int|false {
            try {
                $stmt = $this->pdo->prepare('DELETE FROM `sessions` WHERE `last_activity` < ?');
                $stmt->execute([time() - $max_lifetime]);
                return $stmt->rowCount();
            } catch (Throwable $e) {
                return false;
            }
        }
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('dds_erp_session');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isSecure,
        'samesite' => 'Lax'
    ]);
    if ($pdo instanceof PDO) {
        session_set_save_handler(new PdoSessionHandler($pdo), true);
    }
    session_start();
}

require_once __DIR__.'/includes/functions.php';

