<?php
require 'includes/bootstrap.php';
$b=$branchId;
function scalar(PDO $pdo,string $sql,array $p=[]): mixed {$s=$pdo->prepare($sql);$s->execute($p);return $s->fetchColumn();}
$today=scalar($pdo,"SELECT COALESCE(SUM(total),0) FROM sales WHERE branch_id=? AND DATE(created_at)=CURDATE()",[$b]);
$month=scalar($pdo,"SELECT COALESCE(SUM(total),0) FROM sales WHERE branch_id=? AND YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())",[$b]);
$profit=scalar($pdo,"SELECT COALESCE(SUM(total-cost_total),0) FROM sales WHERE branch_id=? AND DATE(created_at)=CURDATE()",[$b]);
$stock=scalar($pdo,"SELECT COUNT(*) FROM products WHERE branch_id=? AND active=1 AND qty<=reorder_level",[$b]);
$expiry=scalar($pdo,"SELECT COUNT(*) FROM products WHERE branch_id=? AND active=1 AND expiry_date IS NOT NULL AND expiry_date<=DATE_ADD(CURDATE(),INTERVAL 60 DAY)",[$b]);
$s=$pdo->prepare("SELECT * FROM products WHERE branch_id=? AND active=1 AND (qty<=reorder_level OR (expiry_date IS NOT NULL AND expiry_date<=DATE_ADD(CURDATE(),INTERVAL 60 DAY))) ORDER BY expiry_date IS NULL,expiry_date LIMIT 10");$s->execute([$b]);$alerts=$s->fetchAll();
page_header('Dashboard');
?>
<div class="cards">
<div class="card"><div class="metric-label">Sales today</div><div class="metric"><?=money($today)?></div></div>
<div class="card"><div class="metric-label">Sales this month</div><div class="metric"><?=money($month)?></div></div>
<div class="card"><div class="metric-label">Gross profit today*</div><div class="metric"><?=money($profit)?></div></div>
<div class="card"><div class="metric-label">Low stock products</div><div class="metric"><?=$stock?></div></div>
<div class="card"><div class="metric-label">Expiry alerts (60 days)</div><div class="metric"><?=$expiry?></div></div>
</div>
<div class="grid-2"><section class="panel"><h2>Quick actions</h2><div class="actions"><a class="btn" href="sales.php">New sale / POS</a><a class="btn secondary" href="products.php">Add product</a><a class="btn secondary" href="purchases.php">Record purchase</a><a class="btn light" href="reports.php">View reports</a></div></section>
<section class="panel"><h2>Stock value</h2><div class="total"><?=money((float)scalar($pdo,'SELECT COALESCE(SUM(qty*cost_price),0) FROM products WHERE branch_id=? AND active=1',[$b]))?></div><div class="muted">Estimated at recorded cost price.</div></section></div>
<section class="panel"><h2>Stock and expiry alerts</h2><div class="table-wrap"><table><thead><tr><th>Product</th><th>SKU</th><th>Quantity</th><th>Reorder level</th><th>Expiry</th><th>Status</th></tr></thead><tbody>
<?php foreach($alerts as $p):?><tr><td><?=e($p['name'])?></td><td><?=e($p['sku'])?></td><td><?=e($p['qty'].' '.$p['unit'])?></td><td><?=e($p['reorder_level'])?></td><td><?=e($p['expiry_date']??'—')?></td><td><?php if((float)$p['qty']<=(float)$p['reorder_level']):?><span class="badge red">Low stock</span><?php else:?><span class="badge warn">Expiry soon</span><?php endif?></td></tr><?php endforeach?>
<?php if(!$alerts):?><tr><td colspan="6" class="empty">No stock alerts.</td></tr><?php endif?></tbody></table></div></section>
<p class="muted">*Gross profit is calculated from sale item cost snapshots. This is not a full statutory accounting ledger.</p>
<?php page_footer(); ?>
