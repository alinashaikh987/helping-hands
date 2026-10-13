<?php
require __DIR__ . '/auth.php';

$transitions = [
    'pending'   => ['confirmed', 'failed'],
    'confirmed' => ['refunded'],
    'failed'    => [],
    'refunded'  => [],
];
$allStatuses = array_keys($transitions);

// ---- POST: change status ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_status') {
    $id  = (int) ($_POST['donation_id'] ?? 0);
    $new = $_POST['new_status'] ?? '';

    if (!hash_equals($_SESSION['csrf_admin'], $_POST['csrf'] ?? '')) {
        $_SESSION['flash_admin'] = ['danger', 'Session expired. Please try again.'];
    } else {
        $stmt = $pdo->prepare("SELECT status FROM donations WHERE donation_id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();

        if ($current === false) {
            $_SESSION['flash_admin'] = ['warning', 'Donation not found.'];
        } elseif (!in_array($new, $transitions[$current], true)) {
            $_SESSION['flash_admin'] = ['danger', "A $current donation cannot be changed to '$new'."];
        } else {
            $u = $pdo->prepare("UPDATE donations SET status = ? WHERE donation_id = ? AND status = ?");
            $u->execute([$new, $id, $current]);
            $_SESSION['flash_admin'] = $u->rowCount() > 0
                ? ['success', "Donation #$id marked as $new."]
                : ['warning', 'Nothing changed. The donation was updated by someone else.'];
        }
    }
    // Return to the same filtered page (only safe characters allowed)
    $qs = $_POST['qs'] ?? '';
    if (!preg_match('/^[A-Za-z0-9_=&%.\-]*$/', $qs)) { $qs = ''; }
    header('Location: donations.php' . ($qs !== '' ? '?' . $qs : ''));
    exit;
}

// ---- Filters ----
function valid_date(string $d): bool {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

$f = [
    'donor_id'    => (int) ($_GET['donor_id'] ?? 0),
    'campaign_id' => (int) ($_GET['campaign_id'] ?? 0),
    'status'      => in_array($_GET['status'] ?? '', $allStatuses, true) ? $_GET['status'] : '',
    'from'        => valid_date($_GET['from'] ?? '') ? $_GET['from'] : '',
    'to'          => valid_date($_GET['to'] ?? '') ? $_GET['to'] : '',
];

$where = [];
$params = [];
if ($f['donor_id'] > 0)    { $where[] = 'd.donor_id = ?';       $params[] = $f['donor_id']; }
if ($f['campaign_id'] > 0) { $where[] = 'd.campaign_id = ?';    $params[] = $f['campaign_id']; }
if ($f['status'] !== '')   { $where[] = 'd.status = ?';         $params[] = $f['status']; }
if ($f['from'] !== '')     { $where[] = 'd.donation_date >= ?'; $params[] = $f['from']; }
if ($f['to'] !== '')       { $where[] = 'd.donation_date <= ?'; $params[] = $f['to']; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ---- Totals for the current filter ----
$t = $pdo->prepare(
    "SELECT COUNT(*) AS n,
            COALESCE(SUM(CASE WHEN d.status = 'confirmed' THEN d.amount END), 0) AS confirmed_total
     FROM donations d $whereSql"
);
$t->execute($params);
$totals = $t->fetch();

// ---- Pagination ----
$perPage = 15;
$pages   = max(1, (int) ceil($totals['n'] / $perPage));
$page    = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
$offset  = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT d.donation_id, o.full_name, c.title, d.amount, d.payment_mode, d.status, d.donation_date
     FROM donations d
     JOIN donors o    ON o.donor_id = d.donor_id
     JOIN campaigns c ON c.campaign_id = d.campaign_id
     $whereSql
     ORDER BY d.donation_date DESC, d.donation_id DESC
     LIMIT " . (int) $perPage . " OFFSET " . (int) $offset
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$donorList    = $pdo->query("SELECT donor_id, full_name FROM donors ORDER BY full_name")->fetchAll();
$campaignList = $pdo->query("SELECT campaign_id, title FROM campaigns ORDER BY title")->fetchAll();

$badge = ['confirmed' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'refunded' => 'secondary'];
$activeFilters = array_filter($f, fn($v) => $v !== '' && $v !== 0);
$qsNow = http_build_query(array_merge($activeFilters, $page > 1 ? ['page' => $page] : []));

$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);

$adminPage  = 'donations';
$adminTitle = 'Donations';
require __DIR__ . '/_header.php';
?>
<?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash[0]) ?>"><?= htmlspecialchars($flash[1]) ?></div>
<?php endif; ?>

<div class="card mb-3"><div class="card-body">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label small mb-1">Donor</label>
      <select name="donor_id" class="form-select form-select-sm">
        <option value="0">All donors</option>
        <?php foreach ($donorList as $d): ?>
          <option value="<?= (int) $d['donor_id'] ?>" <?= $f['donor_id'] === (int) $d['donor_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small mb-1">Campaign</label>
      <select name="campaign_id" class="form-select form-select-sm">
        <option value="0">All campaigns</option>
        <?php foreach ($campaignList as $c): ?>
          <option value="<?= (int) $c['campaign_id'] ?>" <?= $f['campaign_id'] === (int) $c['campaign_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small mb-1">Status</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">All</option>
        <?php foreach ($allStatuses as $s): ?>
          <option value="<?= $s ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small mb-1">From</label>
      <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($f['from']) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small mb-1">To</label>
      <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($f['to']) ?>">
    </div>
    <div class="col-12 d-flex gap-2">
      <button class="btn btn-sm btn-success" type="submit">Apply filters</button>
      <a href="donations.php" class="btn btn-sm btn-link">Reset</a>
      <a href="donation_form.php" class="btn btn-sm btn-donate ms-auto">+ Record donation</a>
    </div>
  </form>
</div></div>

<p class="text-muted small">
  <?= (int) $totals['n'] ?> donation(s) match.
  Confirmed total in this view: <strong>&#8377;<?= number_format((float) $totals['confirmed_total'], 2) ?></strong>
</p>

<div class="card"><div class="card-body">
  <?php if (!$rows): ?>
    <p class="text-muted mb-0">No donations match these filters.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr>
        <th>#</th><th>Date</th><th>Donor</th><th>Campaign</th>
        <th class="text-end">Amount</th><th>Mode</th><th>Status</th><th class="text-end">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int) $r['donation_id'] ?></td>
          <td><?= htmlspecialchars($r['donation_date']) ?></td>
          <td><?= htmlspecialchars($r['full_name']) ?></td>
          <td><?= htmlspecialchars($r['title']) ?></td>
          <td class="text-end">&#8377;<?= number_format($r['amount'], 2) ?></td>
          <td><?= htmlspecialchars(str_replace('_', ' ', $r['payment_mode'])) ?></td>
          <td><span class="badge text-bg-<?= $badge[$r['status']] ?? 'secondary' ?>"><?= htmlspecialchars($r['status']) ?></span></td>
          <td class="text-end text-nowrap">
            <?php foreach ($transitions[$r['status']] as $next): ?>
              <form method="post" class="d-inline"
                    onsubmit="return confirm('Mark donation #<?= (int) $r['donation_id'] ?> as <?= $next ?>?');">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_admin']) ?>">
                <input type="hidden" name="action" value="set_status">
                <input type="hidden" name="donation_id" value="<?= (int) $r['donation_id'] ?>">
                <input type="hidden" name="new_status" value="<?= $next ?>">
                <input type="hidden" name="qs" value="<?= htmlspecialchars($qsNow) ?>">
                <button class="btn btn-sm <?= $next === 'confirmed' ? 'btn-success' : 'btn-outline-secondary' ?>" type="submit">
                  <?= ucfirst($next === 'confirmed' ? 'Confirm' : ($next === 'failed' ? 'Fail' : 'Refund')) ?>
                </button>
              </form>
            <?php endforeach; ?>
            <?php if (!$transitions[$r['status']]): ?><span class="text-muted small">final</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <nav class="mt-3"><ul class="pagination pagination-sm mb-0">
      <?php for ($p = 1; $p <= $pages; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
          <a class="page-link" href="donations.php?<?= htmlspecialchars(http_build_query(array_merge($activeFilters, ['page' => $p]))) ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
    </ul></nav>
  <?php endif; ?>
  <?php endif; ?>
</div></div>
<?php require __DIR__ . '/_footer.php'; ?>