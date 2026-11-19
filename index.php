<?php
require 'includes/bootstrap.php';
$b = $branchId;
function scalar(PDO $pdo, string $sql, array $p = []): mixed {
    $s = $pdo->prepare($sql);
    $s->execute($p);
    return $s->fetchColumn();
}

$today = scalar($pdo, "SELECT COALESCE(SUM(total),0) FROM sales WHERE branch_id=? AND DATE(created_at)=CURDATE()", [$b]);
$month = scalar($pdo, "SELECT COALESCE(SUM(total),0) FROM sales WHERE branch_id=? AND YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())", [$b]);
$profit = scalar($pdo, "SELECT COALESCE(SUM(total-cost_total),0) FROM sales WHERE branch_id=? AND DATE(created_at)=CURDATE()", [$b]);
$stock = (int)scalar($pdo, "SELECT COUNT(*) FROM products WHERE branch_id=? AND active=1 AND qty<=reorder_level", [$b]);
$expiry = (int)scalar($pdo, "SELECT COUNT(*) FROM products WHERE branch_id=? AND active=1 AND expiry_date IS NOT NULL AND expiry_date<=DATE_ADD(CURDATE(),INTERVAL 60 DAY)", [$b]);
$totalValue = (float)scalar($pdo, 'SELECT COALESCE(SUM(qty*cost_price),0) FROM products WHERE branch_id=? AND active=1', [$b]);

$s = $pdo->prepare("SELECT * FROM products WHERE branch_id=? AND active=1 AND (qty<=reorder_level OR (expiry_date IS NOT NULL AND expiry_date<=DATE_ADD(CURDATE(),INTERVAL 60 DAY))) ORDER BY expiry_date IS NULL,expiry_date LIMIT 10");
$s->execute([$b]);
$alerts = $s->fetchAll();

page_header('Dashboard');
?>
<div class="cards">
  <div class="card">
    <span>Sales today</span>
    <b><?=money($today)?></b>
    <small class="<?=((float)$today>0?'up':'')?>">Live today</small>
  </div>
  <div class="card">
    <span>Sales this month</span>
    <b><?=money($month)?></b>
    <small>Current month</small>
  </div>
  <div class="card">
    <span>Gross profit today*</span>
    <b><?=money($profit)?></b>
    <small class="<?=((float)$profit>0?'up':'')?>">From recorded sales</small>
  </div>
  <div class="card">
    <span>Stock value</span>
    <b><?=money($totalValue)?></b>
    <small>At recorded cost</small>
  </div>
  <div class="card">
    <span>Need reorder</span>
    <b class="<?=$stock>0?'low':''?>"><?=$stock?></b>
    <small class="<?=$stock>0?'dn':'up'?>"><?=$stock>0?'Action needed':'All good'?></small>
  </div>
  <div class="card">
    <span>Expiry alerts (60d)</span>
    <b class="<?=$expiry>0?'warn':''?>"><?=$expiry?></b>
    <small class="<?=$expiry>0?'dn':'up'?>"><?=$expiry>0?'Check stock':'No alerts'?></small>
  </div>
</div>

<div class="grid-2">
  <section class="panel">
    <h3>Quick actions</h3>
    <div class="actions">
      <a class="btn pri" href="sales.php">🛒 New sale / POS</a>
      <a class="btn secondary" href="products.php">📦 Add product</a>
      <a class="btn secondary" href="purchases.php">🔄 Record purchase</a>
      <a class="btn light" href="reports.php">📄 View reports</a>
    </div>
  </section>
  <section class="panel">
    <h3>Branch overview</h3>
    <div class="note" style="margin:0">Active branch: <strong>Branch #<?=e((string)$b)?></strong>. Data is automatically scoped to your branch. Administrators can switch branches via the sidebar selector.</div>
  </section>
</div>

<section class="panel">
  <div class="toolbar">
    <h3>Stock and expiry alerts</h3>
  </div>
  <div class="wrap">
    <table>
      <thead>
        <tr>
          <th>Product</th>
          <th>SKU</th>
          <th>Quantity</th>
          <th>Reorder level</th>
          <th>Expiry date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($alerts as $p):?>
        <tr>
          <td><strong><?=e($p['name'])?></strong></td>
          <td><?=e($p['sku'])?></td>
          <td><?=e($p['qty'].' '.$p['unit'])?></td>
          <td><?=e($p['reorder_level'])?></td>
          <td><?=e($p['expiry_date']??'—')?></td>
          <td>
            <?php if((float)$p['qty']<=(float)$p['reorder_level']):?>
              <span class="tag low">Low stock</span>
            <?php else:?>
              <span class="tag warn">Expiring</span>
            <?php endif?>
          </td>
        </tr>
        <?php endforeach?>
        <?php if(!$alerts):?>
        <tr><td colspan="6" class="empty">Everything is above reorder level and no near-expiry items.</td></tr>
        <?php endif?>
      </tbody>
    </table>
  </div>
</section>
<p class="note">*Gross profit is calculated from sale item cost snapshots. This is an operational inventory report.</p>
<?php page_footer(); ?>
