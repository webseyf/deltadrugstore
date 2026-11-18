<?php
require 'config.php';
$message='';$error='';
$count=(int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if($_SERVER['REQUEST_METHOD']==='POST' && $count===0){
  $username=trim((string)($_POST['username']??'admin'));$name=trim((string)($_POST['full_name']??'DDS Administrator'));$pass=(string)($_POST['password']??'');
  if(strlen($pass)<12){$error='Use an administrator password with at least 12 characters.';}
  elseif($username===''||$name===''){$error='Complete all required fields.';}
  else{$branch=(int)$pdo->query('SELECT id FROM branches ORDER BY id LIMIT 1')->fetchColumn();
    $s=$pdo->prepare('INSERT INTO users(username,full_name,password_hash,role,branch_id) VALUES(?,?,?,"admin",?)');
    $s->execute([$username,$name,password_hash($pass,PASSWORD_DEFAULT),$branch]);$message='Admin account created. Delete install.php now, then sign in.';}
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Install DDS ERP</title><link rel="stylesheet" href="assets/style.css"></head><body class="login-wrap"><form class="login-card" method="post"><div class="eyebrow">ONE-TIME SETUP</div><h1>ERP Administrator</h1><?php if($message):?><div class="alert"><?=e($message)?></div><?php elseif($count>0):?><div class="alert error">An administrator already exists. Delete install.php and go to login.php.</div><?php else:?><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif?><div class="field"><label>Full name</label><input name="full_name" required value="DDS Administrator"></div><div class="field"><label>Username</label><input name="username" required value="admin"></div><div class="field"><label>Password (minimum 12 characters)</label><input type="password" name="password" required minlength="12"></div><button style="width:100%">Create administrator</button><?php endif?><p><a href="login.php">Go to login</a></p></form></body></html>
