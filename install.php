<?php
require 'config.php';
$message = '';
$error = '';
$count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $count === 0) {
    $username = trim((string)($_POST['username'] ?? 'admin'));
    $name = trim((string)($_POST['full_name'] ?? 'DDS Administrator'));
    $pass = (string)($_POST['password'] ?? '');
    if (strlen($pass) < 12) {
        $error = 'Use an administrator password with at least 12 characters.';
    } elseif ($username === '' || $name === '') {
        $error = 'Complete all required fields.';
    } else {
        $branch = (int)$pdo->query('SELECT id FROM branches ORDER BY id LIMIT 1')->fetchColumn();
        $s = $pdo->prepare('INSERT INTO users(username,full_name,password_hash,role,branch_id) VALUES(?,?,?,"admin",?)');
        $s->execute([$username, $name, password_hash($pass, PASSWORD_DEFAULT), $branch]);
        $message = 'Admin account created. Delete install.php now, then sign in.';
    }
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Setup · <?=e(APP_NAME)?></title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-wrap">
  <form class="login-card" method="post">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;font-size:18px;font-weight:800;color:var(--tx)">
      <span style="font-size:26px">💊</span> <?=e(APP_NAME)?>
    </div>
    <h1>Administrator Setup</h1>
    <p class="note">Create your initial administrator account.</p>
    <?php if ($message): ?>
      <div class="alert"><?=e($message)?></div>
      <p><a href="login.php" class="btn pri" style="width:100%">Go to Sign in</a></p>
    <?php elseif ($count > 0): ?>
      <div class="alert error">An administrator already exists. Delete install.php and sign in.</div>
      <p><a href="login.php" class="btn pri" style="width:100%">Go to Sign in</a></p>
    <?php else: ?>
      <?php if ($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
      <div class="field">
        <label>Full name</label>
        <input name="full_name" required value="DDS Administrator">
      </div>
      <div class="field">
        <label>Username</label>
        <input name="username" required value="admin">
      </div>
      <div class="field">
        <label>Password (min 12 characters)</label>
        <input type="password" name="password" required minlength="12">
      </div>
      <button class="pri" style="width:100%;margin-top:12px">Create administrator</button>
      <p style="text-align:center;margin-top:14px"><a href="login.php" style="color:var(--ac);text-decoration:none;font-size:13px;font-weight:700">Already set up? Sign in</a></p>
    <?php endif; ?>
  </form>
</body>
</html>
