<?php
require 'includes/bootstrap.php';
$id = (int)($_GET['id'] ?? 0);
$s = $pdo->prepare('SELECT s.*,u.full_name FROM sales s JOIN users u ON u.id=s.user_id WHERE s.id=? AND s.branch_id=?');
$s->execute([$id, $branchId]);
$sale = $s->fetch();
if (!$sale) {
    http_response_code(404);
    exit('Receipt not found');
}
$s = $pdo->prepare('SELECT si.*,p.name,p.sku,p.unit FROM sale_items si JOIN products p ON p.id=si.product_id WHERE si.sale_id=?');
$s->execute([$id]);
$items = $s->fetchAll();
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Receipt <?=e($sale['invoice_no'])?> · <?=e(APP_NAME)?></title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .receipt-box {
      max-width: 480px;
      margin: 40px auto;
      background: var(--card);
      border: 1px solid var(--bd);
      border-radius: 14px;
      padding: 24px;
      box-shadow: var(--sh);
    }
    .receipt-header {
      text-align: center;
      margin-bottom: 20px;
    }
    .receipt-header h2 {
      margin: 0;
      font-size: 20px;
    }
    .receipt-meta {
      font-size: 13px;
      color: var(--mut);
      margin-bottom: 16px;
      line-height: 1.6;
    }
    .receipt-totals {
      text-align: right;
      margin-top: 16px;
      font-size: 14px;
      line-height: 1.6;
    }
    @media print {
      body { background: #fff; color: #000; }
      .receipt-box { box-shadow: none; border: none; padding: 0; margin: 0 auto; max-width: 100%; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>
  <div class="receipt-box">
    <div class="receipt-header">
      <div style="font-size:32px;margin-bottom:4px">💊</div>
      <h2><?=e(APP_NAME)?></h2>
      <div class="note" style="margin:2px 0 0">Official Sales Receipt</div>
      <div style="font-size:12px;color:var(--mut);font-family:monospace;margin-top:4px"><?=e($sale['invoice_no'])?></div>
    </div>

    <div class="receipt-meta">
      <strong>Date:</strong> <?=e($sale['created_at'])?><br>
      <strong>Served by:</strong> <?=e($sale['full_name'])?><br>
      <strong>Payment method:</strong> <?=e(ucfirst($sale['payment_method']))?>
    </div>

    <div class="wrap" style="box-shadow:none;border-radius:10px;margin-bottom:0">
      <table>
        <thead>
          <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $i): ?>
          <tr>
            <td><strong><?=e($i['name'])?></strong></td>
            <td><?=e($i['qty'].' '.$i['unit'])?></td>
            <td><?=money($i['unit_price'])?></td>
            <td><b><?=money($i['line_total'])?></b></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="receipt-totals">
      <div>Subtotal: <?=money($sale['subtotal'])?></div>
      <div>Discount: <?=money($sale['discount'])?></div>
      <div style="font-size:18px;font-weight:800;color:var(--ac);margin-top:6px">TOTAL: <?=money($sale['total'])?></div>
    </div>

    <p style="text-align:center;font-size:12px;color:var(--mut);margin-top:24px">Thank you for choosing Delta Drug Store!</p>

    <div class="actions no-print" style="justify-content:center;margin-top:24px">
      <button class="pri" onclick="window.print()">🖨 Print receipt</button>
      <a class="btn secondary" href="sales.php">Back to POS</a>
    </div>
  </div>
</body>
</html>
