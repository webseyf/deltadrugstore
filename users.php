<?php
require 'includes/bootstrap.php';
if (!is_admin()) {
    http_response_code(403);
    exit('Admin access required.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = post_str('username', 60);
    $name = post_str('full_name', 150);
    $pass = (string)($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'cashier';
    $br = (int)($_POST['branch_id'] ?? $branchId);
    if (!in_array($role, ['admin', 'pharmacist', 'cashier', 'storekeeper'], true)) $role = 'cashier';
    if (strlen($pass) < 12) {
        flash('error', 'Password must contain at least 12 characters.');
        redirect('users.php');
    }
    try {
        $s = $pdo->prepare('INSERT INTO users(username,full_name,password_hash,role,branch_id) VALUES(?,?,?,?,?)');
        $s->execute([$u, $name, password_hash($pass, PASSWORD_DEFAULT), $role, $br]);
        audit($pdo, 'user.created', $u . ' ' . $role);
        flash('success', 'User created.');
    } catch (PDOException $e) {
        flash('error', 'Could not create user. Username may already exist.');
    }
    redirect('users.php');
}
$users = $pdo->query('SELECT u.id,u.username,u.full_name,u.role,u.active,b.name branch FROM users u LEFT JOIN branches b ON b.id=u.branch_id ORDER BY u.id')->fetchAll();
$branches = $pdo->query('SELECT id,name FROM branches WHERE active=1')->fetchAll();
page_header('User management');
?>
<section class="panel">
  <h3>Create staff user</h3>
  <form method="post" style="box-shadow:none;padding:0;background:none;border:0;margin:0">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>">
    <div class="form-grid">
      <div><label>Username *</label><input name="username" required placeholder="e.g. jdoe"></div>
      <div><label>Full name *</label><input name="full_name" required placeholder="Staff member name"></div>
      <div><label>Temporary password (12+ chars) *</label><input type="password" name="password" minlength="12" required></div>
      <div>
        <label>Role</label>
        <select name="role">
          <option value="cashier">Cashier</option>
          <option value="pharmacist">Pharmacist</option>
          <option value="storekeeper">Storekeeper</option>
          <option value="admin">Administrator</option>
        </select>
      </div>
      <div>
        <label>Branch assignment</label>
        <select name="branch_id">
          <?php foreach ($branches as $b): ?>
            <option value="<?=$b['id']?>"><?=e($b['name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><button class="pri" style="width:100%">Create user</button></div>
    </div>
  </form>
</section>

<section class="panel">
  <h3>Existing staff accounts</h3>
  <div class="wrap">
    <table>
      <thead>
        <tr>
          <th>Username</th>
          <th>Name</th>
          <th>Role</th>
          <th>Branch</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><strong><?=e($u['username'])?></strong></td>
          <td><?=e($u['full_name'])?></td>
          <td><span class="tag"><?=e(ucfirst($u['role']))?></span></td>
          <td><?=e($u['branch'] ?? '—')?></td>
          <td><span class="tag <?=$u['active'] ? 'ok' : 'low'?>"><?=$u['active'] ? 'Active' : 'Disabled'?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php page_footer(); ?>
