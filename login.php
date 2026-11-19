<?php
require 'config.php';
if (!empty($_SESSION['user']['id'])) redirect('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $s = $pdo->prepare('SELECT * FROM users WHERE username=? AND active=1 LIMIT 1');
    $s->execute([trim((string)($_POST['username'] ?? ''))]);
    $u = $s->fetch();
    if ($u && password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = $u;
        $_SESSION['branch_id'] = $u['branch_id'] ?: 1;
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        redirect('index.php');
    }
    $error = 'Invalid username or password.';
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Sign in · <?=e(APP_NAME)?></title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-wrap">
  <form class="login-card" method="post">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;font-size:18px;font-weight:800;color:var(--tx)">
      <span style="font-size:26px">💊</span> <?=e(APP_NAME)?>
    </div>
    <h1>Sign in</h1>
    <p class="note">Sign in to your independent pharmacy system.</p>
    <?php if ($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
    <div class="field">
      <label>Username</label>
      <input name="username" required autocomplete="username" autofocus>
    </div>
    <div class="field">
      <label>Password</label>
      <input name="password" type="password" required autocomplete="current-password">
    </div>
    <button class="pri" style="width:100%;margin-top:12px">Sign in</button>
  </form>
</body>
</html>
