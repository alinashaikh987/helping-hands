<?php
require __DIR__ . '/auth.php';

function valid_date(string $d): bool {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}
function cents($v): int { return (int) round((float) $v * 100); }

$campaigns = $pdo->query(
  "SELECT c.campaign_id, c.title,
          (SELECT COALESCE(SUM(amount), 0) FROM donations
             WHERE campaign_id = c.campaign_id AND status = 'confirmed')
        - (SELECT COALESCE(SUM(amount), 0) FROM fund_allocations
             WHERE campaign_id = c.campaign_id AND status IN ('planned','approved')) AS available
   FROM campaigns c ORDER BY c.title"
)->fetchAll();
$campaignIds = array_column($campaigns, 'campaign_id');

$errors = [];
$old = ['campaign_id' => '', 'purpose' => '', 'amount' => '',
        'allocation_date' => date('Y-m-d'), 'status' => 'planned', 'notes' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) {
        $old[$k] = trim($_POST[$k] ?? '');
    }

    if (!hash_equals($_SESSION['csrf_admin'], $_POST['csrf'] ?? '')) {
        $errors[] = 'Session expired. Please try again.';
    }
    if (!in_array((int) $old['campaign_id'], $campaignIds, true)) {
        $errors[] = 'Please choose a campaign.';
    }
    if ($old['purpose'] === '' || mb_strlen($old['purpose']) > 200) {
        $errors[] = 'Purpose is required (up to 200 characters).';
    }
    if (!is_numeric($old['amount']) || (float) $old['amount'] <= 0 || (float) $old['amount'] > 999999999) {
        $errors[] = 'Amount must be greater than 0.';
    }
    if (!valid_date($old['allocation_date'])) {
        $errors[] = 'Please enter a valid date.';
    } elseif ($old['allocation_date'] > date('Y-m-d')) {
        $errors[] = 'The allocation date cannot be in the future.';
    }
    if (!in_array($old['status'], ['planned', 'approved'], true)) {
        $errors[] = 'Status must be planned or approved.';
    }
    if (mb_strlen($old['notes']) > 2000) {
        $errors[] = 'Notes are too long (maximum 2000 characters).';
    }

    if (!$errors) {
        $campaignId = (int) $old['campaign_id'];
        $amount     = round((float) $old['amount'], 2);
        try {
            $pdo->beginTransaction();

            // Lock this campaign so two admins cannot over-allocate at the same time
            $pdo->prepare("SELECT campaign_id FROM campaigns WHERE campaign_id = ? FOR UPDATE")
                ->execute([$campaignId]);

            $r = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM donations
                                WHERE campaign_id = ? AND status = 'confirmed'");
            $r->execute([$campaignId]);
            $raised = $r->fetchColumn();

            $a = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM fund_allocations
                                WHERE campaign_id = ? AND status IN ('planned','approved')");
            $a->execute([$campaignId]);
            $allocated = $a->fetchColumn();

            $available = cents($raised) - cents($allocated);

            if (cents($amount) > $available) {
                $pdo->rollBack();
                $errors[] = 'Only ₹' . number_format(max(0, $available) / 100, 2)
                          . ' of confirmed funds is unallocated for this campaign.';
            } else {
                $pdo->prepare(
                    "INSERT INTO fund_allocations (campaign_id, purpose, amount, allocation_date, status, notes)
                     VALUES (?, ?, ?, ?, ?, ?)"
                )->execute([
                    $campaignId, $old['purpose'], $amount, $old['allocation_date'],
                    $old['status'], $old['notes'] !== '' ? $old['notes'] : null,
                ]);
                $pdo->commit();
                $_SESSION['flash_admin'] = ['success', 'Allocation recorded.'];
                header('Location: allocations.php');
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log($e->getMessage());
            $errors[] = 'Could not save the allocation. Please try again.';
        }
    }
}

$adminPage  = 'allocations';
$adminTitle = 'New allocation';
require __DIR__ . '/_header.php';
?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<div class="card" style="max-width: 720px;"><div class="card-body p-4">
  <form method="post" novalidate>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_admin']) ?>">

    <div class="mb-3">
      <label for="campaign_id" class="form-label">Campaign</label>
      <select class="form-select" id="campaign_id" name="campaign_id">
        <option value="">Choose a campaign...</option>
        <?php foreach ($campaigns as $c): ?>
          <option value="<?= (int) $c['campaign_id'] ?>" <?= (string) $c['campaign_id'] === $old['campaign_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['title']) ?> (unallocated: &#8377;<?= number_format((float) $c['available'], 2) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label for="purpose" class="form-label">Purpose</label>
      <input type="text" class="form-control" id="purpose" name="purpose" maxlength="200"
             value="<?= htmlspecialchars($old['purpose']) ?>">
    </div>

    <div class="row">
      <div class="col-md-4 mb-3">
        <label for="amount" class="form-label">Amount (&#8377;)</label>
        <input type="number" class="form-control" id="amount" name="amount" min="0.01" step="0.01"
               value="<?= htmlspecialchars($old['amount']) ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label for="allocation_date" class="form-label">Date</label>
        <input type="date" class="form-control" id="allocation_date" name="allocation_date"
               max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($old['allocation_date']) ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label for="status" class="form-label">Status</label>
        <select class="form-select" id="status" name="status">
          <option value="planned"  <?= $old['status'] === 'planned'  ? 'selected' : '' ?>>Planned</option>
          <option value="approved" <?= $old['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
        </select>
      </div>
    </div>

    <div class="mb-3">
      <label for="notes" class="form-label">Notes (optional)</label>
      <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="2000"><?= htmlspecialchars($old['notes']) ?></textarea>
    </div>

    <p class="small text-muted">An allocation assigns money to a purpose. It does not record that the money was spent.</p>
    <button type="submit" class="btn btn-success px-4">Save allocation</button>
    <a href="allocations.php" class="btn btn-link">Cancel</a>
  </form>
</div></div>
<?php require __DIR__ . '/_footer.php'; ?>