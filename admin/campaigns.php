<?php
require __DIR__ . '/auth.php';

// ---- POST actions: set_status, delete ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['campaign_id'] ?? 0);

    if (!hash_equals($_SESSION['csrf_admin'], $_POST['csrf'] ?? '')) {
        $_SESSION['flash_admin'] = ['danger', 'Session expired. Please try again.'];
    } elseif ($action === 'set_status') {
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['draft', 'active', 'closed'], true)) {
            $u = $pdo->prepare("UPDATE campaigns SET status = ? WHERE campaign_id = ?");
            $u->execute([$status, $id]);
            $_SESSION['flash_admin'] = ['success', 'Campaign status changed to ' . $status . '.'];
        } else {
            $_SESSION['flash_admin'] = ['danger', 'Invalid status.'];
        }
    } elseif ($action === 'delete') {
        $c = $pdo->prepare(
            "SELECT (SELECT COUNT(*) FROM donations WHERE campaign_id = ?) +
                    (SELECT COUNT(*) FROM fund_allocations WHERE campaign_id = ?)"
        );
        $c->execute([$id, $id]);
        if ((int) $c->fetchColumn() > 0) {
            $_SESSION['flash_admin'] = ['danger', 'This campaign has donations or allocations. Close it instead of deleting.'];
        } else {
            try {
                $del = $pdo->prepare("DELETE FROM campaigns WHERE campaign_id = ?");
                $del->execute([$id]);
                $_SESSION['flash_admin'] = $del->rowCount() > 0
                    ? ['success', 'Campaign deleted.'] : ['warning', 'Campaign not found.'];
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $_SESSION['flash_admin'] = ['danger', 'Could not delete this campaign.'];
            }
        }
    }
    header('Location: campaigns.php');
    exit;
}

$campaigns = $pdo->query(
  "SELECT c.campaign_id, c.title, c.target_amount, c.start_date, c.end_date, c.status,
          COALESCE(SUM(CASE WHEN d.status = 'confirmed' THEN d.amount END), 0) AS raised,
          COUNT(d.donation_id) AS gifts,
          (SELECT COUNT(*) FROM fund_allocations a WHERE a.campaign_id = c.campaign_id) AS allocs
   FROM campaigns c
   LEFT JOIN donations d ON d.campaign_id = c.campaign_id
   GROUP BY c.campaign_id, c.title, c.target_amount, c.start_date, c.end_date, c.status
   ORDER BY c.end_date DESC"
)->fetchAll();

$badge = ['active' => 'success', 'draft' => 'secondary', 'closed' => 'dark'];

$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);

$adminPage  = 'campaigns';
$adminTitle = 'Campaigns';
require __DIR__ . '/_header.php';
?>
<?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash[0]) ?>"><?= htmlspecialchars($flash[1]) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-end mb-3">
  <a href="campaign_form.php" class="btn btn-donate">+ New campaign</a>
</div>

<div class="card"><div class="card-body">
  <?php if (!$campaigns): ?>
    <p class="text-muted mb-0">No campaigns yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr>
        <th>Title</th><th>Status</th><th>Dates</th>
        <th class="text-end">Raised / Target</th><th class="text-end">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($campaigns as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['title']) ?></td>
          <td><span class="badge text-bg-<?= $badge[$c['status']] ?? 'secondary' ?>"><?= htmlspecialchars($c['status']) ?></span></td>
          <td class="small"><?= htmlspecialchars($c['start_date']) ?><br>to <?= htmlspecialchars($c['end_date']) ?></td>
          <td class="text-end small">&#8377;<?= number_format($c['raised'], 0) ?> / &#8377;<?= number_format($c['target_amount'], 0) ?></td>
          <td class="text-end text-nowrap">
            <a class="btn btn-sm btn-outline-success" href="campaign_form.php?id=<?= (int) $c['campaign_id'] ?>">Edit</a>

            <?php if ($c['status'] !== 'active'): ?>
              <form method="post" class="d-inline">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_admin']) ?>">
                <input type="hidden" name="action" value="set_status">
                <input type="hidden" name="campaign_id" value="<?= (int) $c['campaign_id'] ?>">
                <input type="hidden" name="status" value="active">
                <button class="btn btn-sm btn-success" type="submit">Activate</button>
              </form>
            <?php endif; ?>
            <?php if ($c['status'] === 'active'): ?>
              <form method="post" class="d-inline" onsubmit="return confirm('Close this campaign? It will disappear from the public site.');">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_admin']) ?>">
                <input type="hidden" name="action" value="set_status">
                <input type="hidden" name="campaign_id" value="<?= (int) $c['campaign_id'] ?>">
                <input type="hidden" name="status" value="closed">
                <button class="btn btn-sm btn-outline-dark" type="submit">Close</button>
              </form>
            <?php endif; ?>

            <?php if ((int) $c['gifts'] === 0 && (int) $c['allocs'] === 0): ?>
              <form method="post" class="d-inline" onsubmit="return confirm('Delete this campaign permanently?');">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_admin']) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="campaign_id" value="<?= (int) $c['campaign_id'] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
              </form>
            <?php else: ?>
              <button class="btn btn-sm btn-outline-secondary" disabled title="Has donations or allocations">Delete</button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div></div>
<?php require __DIR__ . '/_footer.php'; ?>