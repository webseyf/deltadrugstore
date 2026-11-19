<?php
require 'includes/bootstrap.php';
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$params = [$branchId, $from . ' 00:00:00', $to . ' 23:59:59'];
$s = $pdo->prepare('SELECT COUNT(*) invoices,COALESCE(SUM(total),0) revenue,COALESCE(SUM(cost_total),0) cogs,COALESCE(SUM(total-cost_total),0) gross FROM sales WHERE branch_id=? AND created_at BETWEEN ? AND ?');
$s->execute($params);
$r = $s->fetch();
$s = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE branch_id=? AND expense_date BETWEEN ? AND ?');
$s->execute([$branchId, $from, $to]);
$expense = (float)$s->fetchColumn();
$s = $pdo->prepare('SELECT DATE(created_at) day,COUNT(*) invoices,SUM(total) revenue,SUM(total-cost_total) gross FROM sales WHERE branch_id=? AND created_at BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY day DESC');
$s->execute($params);
$days = $s->fetchAll();
$netProfit = (float)$r['gross'] - $expense;
$margin = (float)$r['revenue'] > 0 ? number_format(((float)$r['gross'] / (float)$r['revenue']) * 100, 1) . '%' : '0%';
page_header('Reports');
?>
<section class="panel no-print">
  <form method="get" style="box-shadow:none;padding:0;background:none;border:0;margin:0" class="form-grid">
    <div><label>From date</label><input type="date" name="from" value="<?=e($from)?>"></div>
    <div><label>To date</label><input type="date" name="to" value="<?=e($to)?>"></div>
    <div><button class="pri" style="width:100%">Generate report</button></div>
  </form>
</section>

<div class="cards">
  <div class="card">
    <span>Revenue</span>
    <b><?=money($r['revenue'])?></b>
    <small><?=$r['invoices']?> sales lines</small>
  </div>
  <div class="card">
    <span>Cost of goods</span>
    <b><?=money($r['cogs'])?></b>
    <small>Cost snapshot</small>
  </div>
  <div class="card">
    <span>Gross profit</span>
    <b class="<?=(float)$r['gross']>=0?'ok':'low'?>"><?=money($r['gross'])?></b>
    <small><?=$margin?> gross margin</small>
  </div>
  <div class="card">
    <span>Period expenses</span>
    <b class="<?=$expense>0?'warn':''?>"><?=money($expense)?></b>
    <small>Recorded expenses</small>
  </div>
  <div class="card">
    <span>Operating result*</span>
    <b class="<?=$netProfit>=0?'ok':'low'?>"><?=money($netProfit)?></b>
    <small>Profit − expenses</small>
  </div>
</div>

<section class="panel">
  <div class="toolbar">
    <h3>Daily sales breakdown</h3>
    <button class="secondary no-print" onclick="window.print()">🖨 Print report</button>
  </div>
  <div class="wrap">
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Invoices</th>
          <th>Revenue</th>
          <th>Gross profit</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($days as $d): ?>
        <tr>
          <td><strong><?=e($d['day'])?></strong></td>
          <td><?=$d['invoices']?></td>
          <td><?=money($d['revenue'])?></td>
          <td class="<?=(float)$d['gross']>=0?'ok':'low'?>"><?=money($d['gross'])?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$days): ?>
        <tr><td colspan="4" class="empty">No sales recorded for this period.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<p class="note">*Operating result is calculated from sales cost snapshots and entered expenses. It is an operational management estimate.</p>
<?php page_footer(); ?>
