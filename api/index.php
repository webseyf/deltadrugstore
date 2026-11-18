<?php
declare(strict_types=1);

// Ensure working directory is always the project root
chdir(dirname(__DIR__));

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
$path = trim($path, '/');

// Root request defaults to index.php
if ($path === '' || $path === 'index' || $path === 'index.php') {
    require 'index.php';
    exit;
}

// Serve static assets if routed through serverless handler
if (str_starts_with($path, 'assets/')) {
    $filePath = __DIR__ . '/../' . $path;
    if (file_exists($filePath) && is_file($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimes = [
            'css'   => 'text/css; charset=UTF-8',
            'js'    => 'application/javascript; charset=UTF-8',
            'json'  => 'application/json',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
        ];
        if (isset($mimes[$ext])) {
            header('Content-Type: ' . $mimes[$ext]);
        }
        header('Cache-Control: public, max-age=86400');
        readfile($filePath);
        exit;
    }
}

// Support extensionless URLs (e.g. /login -> login.php)
if (!str_ends_with($path, '.php') && file_exists($path . '.php')) {
    $path .= '.php';
}

// Allowed application scripts to prevent unauthorized file execution
$allowedScripts = [
    'index.php',
    'login.php',
    'logout.php',
    'products.php',
    'sales.php',
    'purchases.php',
    'contacts.php',
    'expenses.php',
    'reports.php',
    'users.php',
    'install.php',
    'receipt.php',
];

$base = basename($path);
if (in_array($base, $allowedScripts, true) && file_exists($base)) {
    require $base;
    exit;
}

http_response_code(404);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>404 Not Found · <?=defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'Delta ERP'?></title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    body { display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
    .box { text-align: center; max-width: 480px; padding: 2rem; }
  </style>
</head>
<body>
  <div class="box">
    <h1>404</h1>
    <p>The page you requested could not be found.</p>
    <a href="index.php" class="btn">Return to Dashboard</a>
  </div>
</body>
</html>

