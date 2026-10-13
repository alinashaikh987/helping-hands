<?php
require __DIR__ . '/auth.php';

$transitions = [
    'planned'   => ['approved', 'cancelled'],
    'approved'  => ['cancelled'],
    'cancelled' => [],
];
$allStatuses = array_keys($transitions);

// ---- POST: change status ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_status') {
    $id  = (int) ($_POST['allocation_id'] ?? 0);
    $new = $_POST['new_status'] ?? '';

    if (!hash_equals($_SESSION['csrf_admin'], $_POST['csrf'] ?? '')) {
        $_SESSION['flash_admin'] = ['danger', 'Session expired. Please try again.'];
    } else {
        $stmt = $pdo->prepare("SELECT status FROM fund_allocations WHERE allocation_id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();

        if ($current === false) {
            $_SESSION['flash_admin'] = ['warning', 'Allocation not found.'];
        } elseif (!in_array($new, $transitions[$current], true)) {
            $_SESSION['flash_admin'] = ['danger', "A $current allocation cannot be changed to '$new'."];
        } else {
            $u = $pdo->prepare("UPDATE fund_allocations SET status = ? WHERE allocation_id = ? AND status = ?");
            $u->execute([$new, $id, $current]);
            $_SESSION['flash_admin'] = $u->rowCount() > 0
                ? ['success', "Allocation #$id marked as $new."]
                : ['warning', 'Nothing changed. It was updated by someone else.'];
        }
    }
    header('Location: allocations.php');
    exit;
}

// ---- Summary per campaign ----
$summary = $pdo->query(
  "SELECT c.campaign_id, c.title,
          (SELECT COALESCE(SUM(amount), 0) FROM donations
             WHERE campaign_id = c.campaign_id AND status = 'confirmed') AS raised,
          (SELECT COALESCE(SUM(amount), 0) FROM fund_allocations
             WHERE campaign_id = c.campaign_id AND status IN ('planned','approved')) AS allocated
   FROM campaigns c ORDER BY c.title"
)->fetchAll();

// ---- Filters + list ----
$fCampaign = (int) ($_GET['campaign_id'] ?? 0);
$fStatus   = in_array($_GET['status'] ?? '', $allStatuses, true) ? $_GET['status'] : '';

$where = []; $params = [];
if ($fCampaign > 0)  { $where[] = 'a.campaign_id = ?'; $params[] = $fCampaign; }
if ($fStatus !== '') { $where[] = 'a.status = ?';      $params[] = $fStatus; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare(
  "SELECT a.allocation_id, c.title, a.purpose, a.amount, a.allocation_date, a.status
   FROM fund_allocations a
   JOIN campaigns c ON c.campaign_id = a.campaign_id
   $whereSql
   ORDER BY a.allocation_date DESC, a.allocation_id DESC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$badge = ['planned' => 'warning', 'approved' => 'success', 'cancelled' => 'secondary'];
$label = ['approved' => 'Approve', 'cancelled' => 'Cancel'];

$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);

$adminPage  = 'allocations';
$adminTitle = 'Fund Allocation';
require __DIR__ . '/_header.php';
?>
<?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash[0]) ?>"><?= htmlspecialchars($flash[1]) ?></div>
<?php endif; ?>

<div class="card mb-4"><div class="card-body">
  <h2 class="h5 card-title">Funds by campaign</h2>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr>
        <th>Campaign</th><th class="text-end">Confirmed raised</th>
        <th class="text-end">Allocated</th><th class="text-end">Unallocated</th>
      </tr></thead>
      <tbody>
      <?php foreach ($summary as $s):
        $free = (float) $s['raised'] - (float) $s['allocated']; ?>
        <tr>
          <td><?= htmlspecialchars($s['title']) ?></td>
          <td class="text-end">&#8377;<?= number_format((float) $s['raised'], 2) ?></td>
          <td class="text-end">&#8377;<?= number_format((float) $s['allocated'], 2) ?></td>
          <td class="text-end <?= $free < 0 ? 'text-danger fw-bold' : '' ?>">
            &#8377;<?= number_format($free, 2) ?>
            <?= $free < 0 ? ' (over-allocated)' : '' ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="small text-muted mt-2 mb-0">
    "Allocated" means money assigned to a purpose. It is not proof that the money has been spent.
  </p>
</div></div>

<div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
  <form method="get" class="d-flex gap-2">
    <select name="campaign_id" class="form-select form-select-sm">
      <option value="0">All campaigns</option>
      <?php foreach ($summary as $s): ?>
        <option value="<?= (int) $s['campaign_id'] ?>" <?= $fCampaign === (int) $s['campaign_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($s['title']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="form-select form-select-sm">
      <option value="">All statuses</option>
      <?php foreach ($allStatuses as $st): ?>
        <option value="<?= $st ?>" <?= $fStatus === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-outline-success" type="submit">Filter</button>
    <a href="allocations.php" class="btn btn-sm btn-link">Reset</a>
  </form>
  <a href="allocation_form.php" class="btn btn-donate">+ New allocation</a>
</div>

<div class="card"><div class="card-body">
  <?php if (!$rows): ?>
    <p class="text-muted mb-0">No allocations match.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr>
        <th>#</th><th>Date</th><th>Campaign</th><th>Purpose</th>
        <th class="text-end">Amount</th><th>Status</th><th class="text-end">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int) $r['allocation_id'] ?></td>
          <td><?= htmlspecialchars($r['allocation_date']) ?></td>
          <td><?= htmlspecialchars($r['title']) ?></td>
          <td><?= htmlspecialchars($r['purpose']) ?></td>
          <td class="text-end">&#8377;<?= number_format($r['amount'], 2) ?></td>
          <td><span class="badge text-bg-<?= $badge[$r['status']] ?? 'secondary' ?>"><?= htmlspecialchars($r['status']) ?></span></td>
          <td class="text-end text-nowrap">
            <?php foreach ($transitions[$r['status']] as $next): ?>
              <form method="post" class="d-inline"
                    onsubmit="return confirm('<?= $label[$next] ?> allocation #<?= (int) $r['allocation_id'] ?>?');">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_admin']) ?>">
                <input type="hidden" name="action" value="set_status">
                <input type="hidden" name="allocation_id" value="<?= (int) $r['allocation_id'] ?>">
                <input type="hidden" name="new_status" value="<?= $next ?>">
                <button class="btn btn-sm <?= $next === 'approved' ? 'btn-success' : 'btn-outline-danger' ?>" type="submit">
                  <?= $label[$next] ?></button>
              </form>
            <?php endforeach; ?>
            <?php if (!$transitions[$r['status']]): ?><span class="text-muted small">final</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div></div>
<?php require __DIR__ . '/_footer.php'; ?>