<?php
declare(strict_types=1);

function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function money(float|int|string|null $n): string { return number_format((float)$n, 2).' '.CURRENCY; }
function redirect(string $url): never { header('Location: '.$url); exit; }
function flash(string $type, string $message): void { $_SESSION['flash'] = [$type, $message]; }
function take_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf'] ?? '';
        if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
            http_response_code(419); exit('Session expired or invalid request. Go back and try again.');
        }
    }
}
function require_login(): void { if (empty($_SESSION['user']['id'])) redirect('login.php'); }
function is_admin(): bool { return ($_SESSION['user']['role'] ?? '') === 'admin'; }
function current_branch_id(): int {
    if (is_admin()) {
        if (isset($_GET['branch']) && ctype_digit((string)$_GET['branch'])) $_SESSION['branch_id'] = (int)$_GET['branch'];
        if (empty($_SESSION['branch_id'])) $_SESSION['branch_id'] = (int)($_SESSION['user']['branch_id'] ?? 1);
        return (int)$_SESSION['branch_id'];
    }
    return (int)($_SESSION['user']['branch_id'] ?? 0);
}
function audit(PDO $pdo, string $action, string $details=''): void {
    $s = $pdo->prepare('INSERT INTO audit_logs(user_id, branch_id, action, details, ip_address) VALUES(?,?,?,?,?)');
    $s->execute([$_SESSION['user']['id'] ?? null, current_branch_id() ?: null, $action, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
}
function post_str(string $key, int $max=255): string {
    $v = trim((string)($_POST[$key] ?? ''));
    return mb_substr($v, 0, $max);
}
function post_num(string $key): float { return max(0, (float)($_POST[$key] ?? 0)); }
function branch_guard_sql(string $alias=''): string { return is_admin() ? '1=1' : $alias.'branch_id='.current_branch_id(); }
function page_header(string $title): void {
    global $pdo;
    $branches = $pdo->query('SELECT id,name FROM branches WHERE active=1 ORDER BY name')->fetchAll();
    $f = take_flash();
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=e($title)?> · <?=e(APP_NAME)?></title><link rel="stylesheet" href="assets/style.css"></head><body>
    <header class="topbar"><a class="brand" href="index.php">DDS <span>ERP</span></a>
    <nav><a href="index.php">Dashboard</a><a href="products.php">Inventory</a><a href="sales.php">Sales / POS</a><a href="purchases.php">Purchases</a><a href="contacts.php">Contacts</a><a href="expenses.php">Expenses</a><a href="reports.php">Reports</a><?php if(is_admin()):?><a href="users.php">Users</a><?php endif?></nav>
    <div class="top-actions"><?php if(is_admin() && count($branches)>1):?><form method="get"><select name="branch" onchange="this.form.submit()"><?php foreach($branches as $br):?><option value="<?=$br['id']?>" <?=$br['id']===current_branch_id()?'selected':''?>><?=e($br['name'])?></option><?php endforeach?></select></form><?php endif?><span><?=e($_SESSION['user']['username']??'')?></span><a href="logout.php">Logout</a></div></header>
    <main class="container"><div class="page-heading"><div><div class="eyebrow">DELTA DRUG STORE</div><h1><?=e($title)?></h1></div></div>
    <?php if($f):?><div class="alert <?=e($f[0])?>"><?=e($f[1])?></div><?php endif;
}
function page_footer(): void { ?></main><footer>DELTA DRUG STORE ERP · Independent PHP + MySQL Edition</footer></body></html><?php }
function selected_branch_id(PDO $pdo): int {
    $id = current_branch_id();
    $s=$pdo->prepare('SELECT id FROM branches WHERE id=? AND active=1'); $s->execute([$id]);
    if(!$s->fetchColumn()) { $id=(int)$pdo->query('SELECT id FROM branches WHERE active=1 ORDER BY id LIMIT 1')->fetchColumn(); $_SESSION['branch_id']=$id; }
    return $id;
}
