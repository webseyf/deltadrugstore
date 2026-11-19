<?php
require 'includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkout') {
    $ids = $_POST['product_id'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $discount = post_num('discount');
    $method = $_POST['payment_method'] ?? 'cash';
    if (!in_array($method, ['cash', 'bank', 'mobile', 'credit'], true)) $method = 'cash';
    $pdo->beginTransaction();
    try {
        $lines = [];
        $subtotal = 0;
        $costTotal = 0;
        foreach ($ids as $i => $raw) {
            $pid = (int)$raw;
            $qty = (float)($qtys[$i] ?? 0);
            if ($pid <= 0 || $qty <= 0) continue;
            $s = $pdo->prepare('SELECT * FROM products WHERE id=? AND branch_id=? AND active=1 FOR UPDATE');
            $s->execute([$pid, $branchId]);
            $p = $s->fetch();
            if (!$p || (float)$p['qty'] < $qty) {
                throw new RuntimeException('Insufficient stock or product unavailable: ' . ($p['name'] ?? $pid));
            }
            $line = $qty * (float)$p['selling_price'];
            $subtotal += $line;
            $costTotal += $qty * (float)$p['cost_price'];
            $lines[] = [$p, $qty, $line];
        }
        if (!$lines) throw new RuntimeException('Add at least one product.');
        if ($discount > $subtotal) throw new RuntimeException('Discount cannot exceed subtotal.');
        $total = $subtotal - $discount;
        $invoice = 'DDS-S-' . date('Ymd-His') . '-' . random_int(100, 999);
        $s = $pdo->prepare('INSERT INTO sales(branch_id,invoice_no,subtotal,discount,total,cost_total,payment_method,user_id) VALUES(?,?,?,?,?,?,?,?)');
        $s->execute([$branchId, $invoice, $subtotal, $discount, $total, $costTotal, $method, $_SESSION['user']['id']]);
        $saleId = (int)$pdo->lastInsertId();
        foreach ($lines as [$p, $qty, $line]) {
            $pdo->prepare('INSERT INTO sale_items(sale_id,product_id,qty,unit_price,unit_cost,line_total) VALUES(?,?,?,?,?,?)')->execute([$saleId, $p['id'], $qty, $p['selling_price'], $p['cost_price'], $line]);
            $new = (float)$p['qty'] - $qty;
            $pdo->prepare('UPDATE products SET qty=? WHERE id=?')->execute([$new, $p['id']]);
            $pdo->prepare("INSERT INTO stock_movements(branch_id,product_id,movement_type,reference_type,reference_id,qty_out,balance_after,note,user_id) VALUES(?,?,'sale','sale',?,?,?,?,?)")->execute([$branchId, $p['id'], $saleId, $qty, $new, $invoice, $_SESSION['user']['id']]);
        }
        $pdo->commit();
        audit($pdo, 'sale.created', $invoice . ' total ' . money($total));
        redirect('receipt.php?id=' . $saleId);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', $e->getMessage());
        redirect('sales.php');
    }
}
$s = $pdo->prepare('SELECT id,name,sku,qty,unit,selling_price FROM products WHERE branch_id=? AND active=1 AND qty>0 ORDER BY name');
$s->execute([$branchId]);
$products = $s->fetchAll();
page_header('Sales / POS');
?>
<section class="panel">
  <h3>Record a sale</h3>
  <form method="post" id="saleForm" style="box-shadow:none;padding:0;background:none;border:0;margin:0">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>">
    <input type="hidden" name="action" value="checkout">
    <div class="form-grid">
      <div>
        <label>Select product</label>
        <select id="productSelect">
          <option value="">Choose product to sell</option>
          <?php foreach ($products as $p): ?>
            <option value="<?=$p['id']?>" data-price="<?=$p['selling_price']?>" data-stock="<?=$p['qty']?>" data-unit="<?=e($p['unit'])?>">
              <?=e($p['name'].' · '.$p['sku'].' (Stock: '.$p['qty'].' '.$p['unit'].')')?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Discount (<?=e(CURRENCY)?>)</label>
        <input name="discount" id="discount" type="number" step=".01" min="0" value="0">
      </div>
      <div>
        <label>Payment method</label>
        <select name="payment_method">
          <option value="cash">Cash</option>
          <option value="mobile">Mobile Money</option>
          <option value="bank">Bank / C.B.E</option>
          <option value="credit">Credit (Debt)</option>
        </select>
      </div>
      <div>
        <button type="button" class="pri" id="addLine" style="width:100%">+ Add to sale</button>
      </div>
    </div>

    <h3 style="margin-top:22px">Current sale items</h3>
    <div class="wrap">
      <table id="cart">
        <thead>
          <tr>
            <th>Product</th>
            <th style="width:130px">Quantity</th>
            <th>Unit price</th>
            <th>Line total</th>
            <th style="width:80px"></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

    <div class="toolbar" style="margin-top:14px">
      <div style="font-size:15px;font-weight:700">
        Subtotal: <strong id="subtotal">0.00</strong> <?=e(CURRENCY)?> · 
        Total: <strong class="total" id="grandTotal">0.00</strong> <?=e(CURRENCY)?>
      </div>
      <button class="pri">Complete sale & print receipt</button>
    </div>
  </form>
</section>

<section class="panel">
  <h3>Recent sales</h3>
  <?php 
  $s = $pdo->prepare('SELECT id,invoice_no,total,payment_method,created_at FROM sales WHERE branch_id=? ORDER BY id DESC LIMIT 15');
  $s->execute([$branchId]);
  ?>
  <div class="wrap">
    <table>
      <thead>
        <tr>
          <th>Invoice</th>
          <th>Date</th>
          <th>Payment</th>
          <th>Total</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($s as $r): ?>
        <tr>
          <td><?=e($r['invoice_no'])?></td>
          <td><?=e($r['created_at'])?></td>
          <td><span class="tag"><?=e(ucfirst($r['payment_method']))?></span></td>
          <td><b><?=money($r['total'])?></b></td>
          <td><a class="btn light" href="receipt.php?id=<?=$r['id']?>">Receipt</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$s->rowCount()): ?>
        <tr><td colspan="5" class="empty">No sales recorded yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
const select = document.getElementById('productSelect'), tbody = document.querySelector('#cart tbody');
document.getElementById('addLine').addEventListener('click', () => {
    const o = select.selectedOptions[0];
    if (!o || !o.value) return;
    let row = tbody.querySelector('[data-id="' + o.value + '"]');
    if (row) {
        const q = row.querySelector('.qty');
        q.value = Number(q.value) + 1;
        calc();
        return;
    }
    row = document.createElement('tr');
    row.dataset.id = o.value;
    row.innerHTML = '<td></td><td><input class="qty" type="number" min=".001" step=".001" value="1" name="qty[]"></td><td class="price"></td><td class="line"></td><td><button type="button" class="danger remove">Remove</button><input type="hidden" name="product_id[]" value="' + o.value + '"></td>';
    row.cells[0].textContent = o.textContent;
    row.querySelector('.price').textContent = Number(o.dataset.price).toFixed(2);
    row.dataset.price = o.dataset.price;
    row.dataset.stock = o.dataset.stock;
    tbody.appendChild(row);
    row.querySelector('.qty').addEventListener('input', calc);
    row.querySelector('.remove').addEventListener('click', () => {
        row.remove();
        calc();
    });
    calc();
});

function calc() {
    let sub = 0;
    tbody.querySelectorAll('tr').forEach(r => {
        let q = Number(r.querySelector('.qty').value) || 0, p = Number(r.dataset.price);
        if (q > Number(r.dataset.stock)) {
            r.querySelector('.qty').setCustomValidity('Quantity exceeds available stock');
        } else {
            r.querySelector('.qty').setCustomValidity('');
        }
        let t = q * p;
        sub += t;
        r.querySelector('.line').textContent = t.toFixed(2);
    });
    let d = Number(document.getElementById('discount').value) || 0;
    document.getElementById('subtotal').textContent = sub.toFixed(2);
    document.getElementById('grandTotal').textContent = Math.max(0, sub - d).toFixed(2);
}

document.getElementById('discount').addEventListener('input', calc);
document.getElementById('saleForm').addEventListener('submit', e => {
    if (!tbody.children.length) {
        e.preventDefault();
        alert('Add at least one product.');
    } else {
        for (const q of tbody.querySelectorAll('.qty')) {
            if (!q.reportValidity()) {
                e.preventDefault();
                break;
            }
        }
    }
});
</script>
<?php page_footer(); ?>
