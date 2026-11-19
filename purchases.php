<?php
require 'includes/bootstrap.php';
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='receive'){
 $pid=(int)($_POST['product_id']??0);$qty=post_num('qty');$cost=post_num('unit_cost');$supplier=(int)($_POST['supplier_id']??0)?:null;$invoice=post_str('invoice_no',80)?:('DDS-P-'.date('Ymd-His'));
 if(!$pid||$qty<=0){flash('error','Choose a product and enter a quantity greater than zero.');redirect('purchases.php');}
 $pdo->beginTransaction();try{$s=$pdo->prepare('SELECT * FROM products WHERE id=? AND branch_id=? FOR UPDATE');$s->execute([$pid,$branchId]);$p=$s->fetch();if(!$p)throw new RuntimeException('Product not found.');
 $total=$qty*$cost;$s=$pdo->prepare("INSERT INTO purchases(branch_id,supplier_id,invoice_no,subtotal,total,user_id) VALUES(?,?,?,?,?,?)");$s->execute([$branchId,$supplier,$invoice,$total,$total,$_SESSION['user']['id']]);$purchaseId=(int)$pdo->lastInsertId();
 $pdo->prepare('INSERT INTO purchase_items(purchase_id,product_id,qty,unit_cost,line_total) VALUES(?,?,?,?,?)')->execute([$purchaseId,$pid,$qty,$cost,$total]);
 $new=(float)$p['qty']+$qty;$pdo->prepare('UPDATE products SET qty=?,cost_price=? WHERE id=?')->execute([$new,$cost,$pid]);
 $pdo->prepare("INSERT INTO stock_movements(branch_id,product_id,movement_type,reference_type,reference_id,qty_in,balance_after,note,user_id) VALUES(?,?,'purchase','purchase',?,?,?,?,?)")->execute([$branchId,$pid,$purchaseId,$qty,$new,$invoice,$_SESSION['user']['id']]);
 $pdo->commit();audit($pdo,'purchase.received',$invoice.' total '.money($total));flash('success','Purchase received and stock updated.');
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash('error',$e->getMessage());}redirect('purchases.php');
}
$s=$pdo->prepare('SELECT id,name,sku,qty,unit FROM products WHERE branch_id=? AND active=1 ORDER BY name');$s->execute([$branchId]);$products=$s->fetchAll();$suppliers=$pdo->query('SELECT id,name FROM suppliers ORDER BY name')->fetchAll();
page_header('Purchases');
?>
<section class="panel">
  <h3>Receive stock / record purchase</h3>
  <form method="post" style="box-shadow:none;padding:0;background:none;border:0;margin:0">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>">
    <input type="hidden" name="action" value="receive">
    <div class="form-grid">
      <div>
        <label>Supplier</label>
        <select name="supplier_id">
          <option value="">Walk-in / General supplier</option>
          <?php foreach($suppliers as $x):?>
            <option value="<?=$x['id']?>"><?=e($x['name'])?></option>
          <?php endforeach?>
        </select>
      </div>
      <div>
        <label>Invoice number</label>
        <input name="invoice_no" placeholder="Supplier invoice ref">
      </div>
      <div>
        <label>Product</label>
        <select name="product_id" required>
          <?php foreach($products as $p):?>
            <option value="<?=$p['id']?>"><?=e($p['name'].' ('.$p['sku'].')')?></option>
          <?php endforeach?>
        </select>
      </div>
      <div>
        <label>Quantity received</label>
        <input type="number" step=".001" min=".001" name="qty" required>
      </div>
      <div>
        <label>Unit cost (<?=e(CURRENCY)?>)</label>
        <input type="number" step=".01" min="0" name="unit_cost" required>
      </div>
      <div>
        <button class="pri" style="width:100%">Save & update stock</button>
      </div>
    </div>
  </form>
</section>

<section class="panel">
  <h3>Recent purchases</h3>
  <?php 
  $s=$pdo->prepare('SELECT p.*,sp.name supplier FROM purchases p LEFT JOIN suppliers sp ON sp.id=p.supplier_id WHERE p.branch_id=? ORDER BY p.id DESC LIMIT 30');
  $s->execute([$branchId]);
  ?>
  <div class="wrap">
    <table>
      <thead>
        <tr>
          <th>Invoice</th>
          <th>Supplier</th>
          <th>Date</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($s as $r):?>
        <tr>
          <td><?=e($r['invoice_no'])?></td>
          <td><?=e($r['supplier']??'—')?></td>
          <td><?=e($r['created_at'])?></td>
          <td><b><?=money($r['total'])?></b></td>
        </tr>
        <?php endforeach?>
        <?php if(!$s->rowCount()):?>
        <tr><td colspan="4" class="empty">No purchases recorded yet.</td></tr>
        <?php endif?>
      </tbody>
    </table>
  </div>
</section>
<?php page_footer(); ?>
