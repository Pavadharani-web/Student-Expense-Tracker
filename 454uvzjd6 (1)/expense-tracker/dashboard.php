<?php
require_once 'config/database.php';
$uid = require_login();

$month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
$q   = trim($_GET['q'] ?? '');
$cat = in_array($_GET['category'] ?? '', CATEGORIES) ? $_GET['category'] : '';
$start = $month . '-01';
$end   = date('Y-m-t', strtotime($start));

// 1) Monthly summary: SUM with CASE
$s = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN type='income'  THEN amount END),0) AS inc,
                           COALESCE(SUM(CASE WHEN type='expense' THEN amount END),0) AS exp
                    FROM transactions WHERE user_id = ? AND txn_date BETWEEN ? AND ?");
$s->execute([$uid, $start, $end]);
$tot = $s->fetch();
$balance = $tot['inc'] - $tot['exp'];

// 2) Expenses by category: GROUP BY
$s = $pdo->prepare("SELECT category, SUM(amount) AS total FROM transactions
                    WHERE user_id = ? AND type = 'expense' AND txn_date BETWEEN ? AND ?
                    GROUP BY category ORDER BY total DESC");
$s->execute([$uid, $start, $end]);
$byCat = $s->fetchAll();

// 3) Transaction list with search + category filter
$sql = 'SELECT * FROM transactions WHERE user_id = ? AND txn_date BETWEEN ? AND ?';
$p = [$uid, $start, $end];
if ($q !== '')   { $sql .= ' AND (description LIKE ? OR category LIKE ?)'; $p[] = "%$q%"; $p[] = "%$q%"; }
if ($cat !== '') { $sql .= ' AND category = ?'; $p[] = $cat; }
$s = $pdo->prepare($sql . ' ORDER BY txn_date DESC, id DESC');
$s->execute($p);
$rows = $s->fetchAll();

$share = ($tot['inc'] + $tot['exp']) > 0 ? round($tot['inc'] / ($tot['inc'] + $tot['exp']) * 100) : 0;
render_header('Dashboard');
?>
<section class="hero-balance">
  <p class="lbl"><?= e(date('F Y', strtotime($start))) ?> balance</p>
  <h1 class="big <?= $balance < 0 ? 'neg' : '' ?>" data-count="<?= (float)$balance ?>"><?= inr($balance) ?></h1>
  <div class="split <?= ($tot['inc'] + $tot['exp']) == 0 ? 'empty' : '' ?>" role="img" aria-label="Income compared with expenses"><i style="--w:<?= $share ?>%"></i></div>
  <div class="hero-figs">
    <div><span class="dot in"></span>Income <b data-count="<?= (float)$tot['inc'] ?>"><?= inr($tot['inc']) ?></b></div>
    <div><span class="dot out"></span>Expenses <b data-count="<?= (float)$tot['exp'] ?>"><?= inr($tot['exp']) ?></b></div>
  </div>
</section>

<form method="get" class="search-bar">
  <input type="month" name="month" value="<?= e($month) ?>" data-auto>
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search description or category">
  <select name="category" data-auto>
    <option value="">All categories</option>
    <?php foreach (CATEGORIES as $c): ?><option <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
  </select>
  <button class="btn" type="submit">Search</button>
</form>

<div class="card"><h3>Spending by category</h3>
  <?php if (!$byCat): ?><p class="muted">No expenses this month.</p><?php endif; ?>
  <?php foreach ($byCat as $i => $r): $pct = $tot['exp'] > 0 ? round($r['total'] / $tot['exp'] * 100) : 0; ?>
    <div class="bar-row <?= cat_class($r['category']) ?>"><span><i class="dot"></i><?= e($r['category']) ?></span>
      <div class="bar"><i style="--w:<?= $pct ?>%;--i:<?= $i ?>"></i></div>
      <b><?= inr($r['total']) ?></b></div>
  <?php endforeach; ?>
</div>

<div class="toolbar">
  <h3>Transactions (<?= count($rows) ?>)</h3>
  <div class="btns">
    <a class="btn income" href="add_transaction.php?type=income">+ Add income</a>
    <a class="btn danger" href="add_transaction.php?type=expense">+ Add expense</a>
  </div>
</div>
<?php if (!$rows): ?>
<div class="card empty-state"><p class="muted">No transactions found for this view.</p>
  <a class="btn" href="add_transaction.php">Add your first transaction</a></div>
<?php else: ?>
<div class="table-wrap"><table>
  <thead><tr><th>Date</th><th>Category</th><th>Description</th><th class="r">Amount</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $t): ?>
  <tr>
    <td><?= e(date('d M', strtotime($t['txn_date']))) ?></td>
    <td><span class="chip <?= cat_class($t['category']) ?>"><i class="dot"></i><?= e($t['category']) ?></span></td>
    <td><?= e($t['description']) ?></td>
    <td class="r <?= $t['type'] === 'income' ? 'inc' : 'exp' ?>"><?= $t['type'] === 'income' ? '+' : '-' ?><?= inr($t['amount']) ?></td>
    <td class="act">
      <a class="btn small ghost" href="edit_transaction.php?id=<?= (int)$t['id'] ?>">Edit</a>
      <form method="post" action="delete_transaction.php" data-confirm="Delete this transaction?">
        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
        <input type="hidden" name="month" value="<?= e($month) ?>">
        <button class="btn small danger" type="submit">Delete</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php render_footer(); ?>
