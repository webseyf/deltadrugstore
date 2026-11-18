<?php
declare(strict_types=1);
/*
 * Copy this file to config.php and enter your cPanel MySQL credentials.
 * Never upload real credentials to a public repository.
 */
const DB_HOST = 'localhost';
const DB_NAME = 'cpaneluser_delta_erp';
const DB_USER = 'cpaneluser_delta_user';
const DB_PASS = 'CHANGE_THIS_PASSWORD';
const APP_NAME = 'DELTA DRUG STORE ERP';
const CURRENCY = 'ETB';
const APP_TIMEZONE = 'Africa/Addis_Ababa';

date_default_timezone_set(APP_TIMEZONE);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('dds_erp_session');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);
    session_start();
}
try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Check config.php and your cPanel database settings.');
}
require_once __DIR__.'/includes/functions.php';
