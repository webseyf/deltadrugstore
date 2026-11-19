<?php
require 'includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cat = post_str('category', 100);
    $amt = post_num('amount');
    $date = $_POST['expense_date'] ?? date('Y-m-d');
    if ($cat && $amt > 0) {
        $s = $pdo->prepare('INSERT INTO expenses(branch_id,category,description,amount,expense_date,user_id) VALUES(?,?,?,?,?,?)');
        $s->execute([$branchId, $cat, post_str('description', 255), $amt, $date, $_SESSION['user']['id']]);
        audit($pdo, 'expense.created', $cat . ' ' . money($amt));
        flash('success', 'Expense saved.');
    }
    redirect('expenses.php');
}
$s = $pdo->prepare('SELECT * FROM expenses WHERE branch_id=? ORDER BY expense_date DESC,id DESC LIMIT 100');
$s->execute([$branchId]);
$rows = $s->fetchAll();
$totalExpenses = array_sum(array_column($rows, 'amount'));
page_header('Expenses');
?>
<div class="cards">
  <div class="card">
    <span>Total recorded expenses</span>
    <b><?=money($totalExpenses)?></b>
    <small><?=count($rows)?> entries</small>
  </div>
</div>

<section class="panel">
  <h3>Record an expense</h3>
  <form method="post" style="box-shadow:none;padding:0;background:none;border:0;margin:0">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>">
    <div class="form-grid">
      <div>
        <label>Category *</label>
        <select name="category" required>
          <option value="Rent">Rent</option>
          <option value="Salaries">Salaries</option>
          <option value="Utilities">Utilities (Water, Power, Net)</option>
          <option value="Transport">Transport / Logistics</option>
          <option value="Supplies">Supplies & Packaging</option>
          <option value="Maintenance">Maintenance</option>
          <option value="Taxes & fees">Taxes & Fees</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div><label>Description</label><input name="description" placeholder="Notes (e.g. Shop monthly rent)"></div>
      <div><label>Amount (<?=e(CURRENCY)?>) *</label><input type="number" min=".01" step=".01" name="amount" required></div>
      <div><label>Date *</label><input type="date" name="expense_date" value="<?=date('Y-m-d')?>" required></div>
      <div><button class="pri" style="width:100%">Save expense</button></div>
    </div>
  </form>
</section>

<section class="panel">
  <h3>Recent expenses</h3>
  <div class="wrap">
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Category</th>
          <th>Description</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?=e($r['expense_date'])?></td>
          <td><span class="tag"><?=e($r['category'])?></span></td>
          <td><?=e($r['description'] ?: '—')?></td>
          <td><b><?=money($r['amount'])?></b></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
        <tr><td colspan="4" class="empty">No expenses recorded yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php page_footer(); ?>
