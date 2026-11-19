<?php
require 'includes/bootstrap.php';
$type = ($_GET['type'] ?? 'suppliers') === 'customers' ? 'customers' : 'suppliers';
$title = $type === 'customers' ? 'Customers' : 'Suppliers';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = post_str('name', 150);
    if ($name !== '') {
        $s = $pdo->prepare("INSERT INTO $type(name,phone,email,address) VALUES(?,?,?,?)");
        $s->execute([$name, post_str('phone', 40), post_str('email', 150), post_str('address', 255)]);
        audit($pdo, $type . '.created', $name);
        flash('success', $title . ' record added.');
    }
    redirect('contacts.php?type=' . $type);
}
$rows = $pdo->query("SELECT * FROM $type ORDER BY name")->fetchAll();
page_header($title);
?>
<section class="panel">
  <div class="toolbar">
    <h3><?=e($title)?></h3>
    <div class="actions">
      <a class="btn <?=$type==='suppliers'?'pri':'secondary'?>" href="?type=suppliers">Suppliers</a>
      <a class="btn <?=$type==='customers'?'pri':'secondary'?>" href="?type=customers">Customers</a>
    </div>
  </div>
  <form method="post" style="box-shadow:none;padding:0;background:none;border:0;margin:0">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>">
    <div class="form-grid">
      <div><label>Name *</label><input name="name" required placeholder="Full name / Business"></div>
      <div><label>Phone</label><input name="phone" placeholder="+251..."></div>
      <div><label>Email</label><input type="email" name="email" placeholder="contact@example.com"></div>
      <div><label>Address</label><input name="address" placeholder="City, Location"></div>
      <div><button class="pri" style="width:100%">Add <?=e($title==='Suppliers'?'supplier':'customer')?></button></div>
    </div>
  </form>
</section>

<section class="panel">
  <h3>Registered <?=strtolower(e($title))?></h3>
  <div class="wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Address</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><strong><?=e($r['name'])?></strong></td>
          <td><?=e($r['phone'] ?: '—')?></td>
          <td><?=e($r['email'] ?: '—')?></td>
          <td><?=e($r['address'] ?: '—')?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
        <tr><td colspan="4" class="empty">No <?=strtolower(e($title))?> registered yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php page_footer(); ?>
