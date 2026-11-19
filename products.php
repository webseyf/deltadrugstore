<?php
require 'includes/bootstrap.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $action=$_POST['action']??'';
 if($action==='save'){
  $id=(int)($_POST['id']??0);$sku=post_str('sku',60);$name=post_str('name',180);$unit=post_str('unit',40)?:'pcs';
  if(!$name||!$sku){flash('error','Product name and SKU are required.');redirect('products.php');}
  try{
   if($id){$s=$pdo->prepare('UPDATE products SET sku=?,name=?,unit=?,cost_price=?,selling_price=?,qty=?,reorder_level=?,batch_no=?,expiry_date=? WHERE id=? AND branch_id=?');$s->execute([$sku,$name,$unit,post_num('cost_price'),post_num('selling_price'),post_num('qty'),post_num('reorder_level'),post_str('batch_no',100)?:null,$_POST['expiry_date']?:null,$id,$branchId]);audit($pdo,'product.updated',$sku);}
   else{$s=$pdo->prepare('INSERT INTO products(branch_id,sku,name,unit,cost_price,selling_price,qty,reorder_level,batch_no,expiry_date) VALUES(?,?,?,?,?,?,?,?,?,?)');$s->execute([$branchId,$sku,$name,$unit,post_num('cost_price'),post_num('selling_price'),post_num('qty'),post_num('reorder_level'),post_str('batch_no',100)?:null,$_POST['expiry_date']?:null]);$pid=(int)$pdo->lastInsertId();if(post_num('qty')>0){$pdo->prepare("INSERT INTO stock_movements(branch_id,product_id,movement_type,qty_in,balance_after,note,user_id) VALUES(?,?,'opening',?,?,?,?)")->execute([$branchId,$pid,post_num('qty'),post_num('qty'),'Opening balance',$_SESSION['user']['id']]);}audit($pdo,'product.created',$sku);}
   flash('success','Product saved.');
  }catch(PDOException $e){flash('error','Could not save product. Check that SKU is unique in this branch.');}
  redirect('products.php');
 }
 if($action==='adjust'){
  $id=(int)$_POST['id'];$qty=(float)$_POST['qty'];$note=post_str('note',255)?:'Manual stock adjustment';
  $pdo->beginTransaction();$s=$pdo->prepare('SELECT qty FROM products WHERE id=? AND branch_id=? FOR UPDATE');$s->execute([$id,$branchId]);$old=$s->fetchColumn();
  if($old===false){$pdo->rollBack();flash('error','Product not found.');redirect('products.php');}
  $delta=$qty-(float)$old;$pdo->prepare('UPDATE products SET qty=? WHERE id=? AND branch_id=?')->execute([$qty,$id,$branchId]);
  $pdo->prepare("INSERT INTO stock_movements(branch_id,product_id,movement_type,qty_in,qty_out,balance_after,note,user_id) VALUES(?,?,'adjustment',?,?,?,?,?)")->execute([$branchId,$id,max(0,$delta),max(0,-$delta),$qty,$note,$_SESSION['user']['id']]);
  $pdo->commit();audit($pdo,'stock.adjusted','Product #'.$id.' quantity '.$old.' → '.$qty);flash('success','Stock adjusted.');redirect('products.php');
 }
 if($action==='deactivate' && is_admin()){$pdo->prepare('UPDATE products SET active=0 WHERE id=? AND branch_id=?')->execute([(int)$_POST['id'],$branchId]);audit($pdo,'product.deactivated','Product #'.(int)$_POST['id']);flash('success','Product deactivated.');redirect('products.php');}
}
$edit=null;if(isset($_GET['edit'])){$s=$pdo->prepare('SELECT * FROM products WHERE id=? AND branch_id=?');$s->execute([(int)$_GET['edit'],$branchId]);$edit=$s->fetch();}
$q=trim((string)($_GET['q']??''));$s=$pdo->prepare("SELECT * FROM products WHERE branch_id=? AND active=1 AND (name LIKE ? OR sku LIKE ? OR barcode LIKE ?) ORDER BY name");$like='%'.$q.'%';$s->execute([$branchId,$like,$like,$like]);$rows=$s->fetchAll();
page_header('Inventory');
?>
<section class="panel">
  <h3><?=$edit?'Edit product':'Add product'?></h3>
  <form method="post" style="box-shadow:none;padding:0;background:none;border:0;margin:0">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?=$edit['id']??0?>">
    <div class="form-grid">
      <div><label>Product name *</label><input name="name" required value="<?=e($edit['name']??'')?>"></div>
      <div><label>SKU *</label><input name="sku" required value="<?=e($edit['sku']??'')?>"></div>
      <div><label>Unit</label><input name="unit" value="<?=e($edit['unit']??'pcs')?>"></div>
      <div><label>Cost price (<?=e(CURRENCY)?>)</label><input type="number" step=".01" min="0" name="cost_price" value="<?=e($edit['cost_price']??'0')?>"></div>
      <div><label>Selling price (<?=e(CURRENCY)?>)</label><input type="number" step=".01" min="0" name="selling_price" value="<?=e($edit['selling_price']??'0')?>"></div>
      <div><label>Current quantity</label><input type="number" step=".001" min="0" name="qty" value="<?=e($edit['qty']??'0')?>"></div>
      <div><label>Reorder level</label><input type="number" step=".001" min="0" name="reorder_level" value="<?=e($edit['reorder_level']??'5')?>"></div>
      <div><label>Batch number</label><input name="batch_no" value="<?=e($edit['batch_no']??'')?>"></div>
      <div><label>Expiry date</label><input type="date" name="expiry_date" value="<?=e($edit['expiry_date']??'')?>"></div>
      <div style="display:flex;gap:6px">
        <button class="pri">Save product</button>
        <?php if($edit):?><a class="btn secondary" href="products.php">Cancel</a><?php endif?>
      </div>
    </div>
  </form>
</section>

<section class="panel">
  <div class="toolbar">
    <h3>Product inventory (<?=count($rows)?> items)</h3>
    <form method="get" class="inline-form">
      <input name="q" placeholder="Search name or SKU" value="<?=e($q)?>" style="width:200px">
      <button class="secondary">Search</button>
      <?php if($q):?><a class="btn light" href="products.php">Clear</a><?php endif?>
    </form>
  </div>
  <div class="wrap">
    <table>
      <thead>
        <tr>
          <th>Product</th>
          <th>SKU</th>
          <th>Cost</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Batch / expiry</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $p):?>
        <tr>
          <td><strong><?=e($p['name'])?></strong></td>
          <td><?=e($p['sku'])?></td>
          <td><?=money($p['cost_price'])?></td>
          <td><?=money($p['selling_price'])?></td>
          <td>
            <?=e($p['qty'].' '.$p['unit'])?>
            <?=$p['qty']<=$p['reorder_level']?'<span class="tag low">Low</span>':''?>
          </td>
          <td><?=e($p['batch_no']??'—')?> / <?=e($p['expiry_date']??'—')?></td>
          <td>
            <div class="actions">
              <a class="btn light" href="?edit=<?=$p['id']?>">Edit</a>
              <form method="post" class="inline-form">
                <input type="hidden" name="csrf" value="<?=csrf_token()?>">
                <input type="hidden" name="action" value="adjust">
                <input type="hidden" name="id" value="<?=$p['id']?>">
                <input type="number" step=".001" min="0" name="qty" value="<?=e($p['qty'])?>" title="New quantity" style="width:75px">
                <input name="note" placeholder="Reason" style="width:90px">
                <button class="secondary">Adjust</button>
              </form>
              <?php if(is_admin()):?>
              <form method="post" onsubmit="return confirm('Deactivate product?')" class="inline-form">
                <input type="hidden" name="csrf" value="<?=csrf_token()?>">
                <input type="hidden" name="action" value="deactivate">
                <input type="hidden" name="id" value="<?=$p['id']?>">
                <button class="danger">Deactivate</button>
              </form>
              <?php endif?>
            </div>
          </td>
        </tr>
        <?php endforeach?>
        <?php if(!$rows):?>
        <tr><td colspan="7" class="empty">No products found.</td></tr>
        <?php endif?>
      </tbody>
    </table>
  </div>
</section>
<?php page_footer(); ?>
