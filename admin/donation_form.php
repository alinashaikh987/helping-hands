<?php
require __DIR__ . '/auth.php';

function valid_date(string $d): bool {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

$donors    = $pdo->query("SELECT donor_id, full_name, email FROM donors ORDER BY full_name")->fetchAll();
$campaigns = $pdo->query("SELECT campaign_id, title FROM campaigns WHERE status = 'active' ORDER BY title")->fetchAll();
$donorIds    = array_column($donors, 'donor_id');
$campaignIds = array_column($campaigns, 'campaign_id');
$modes = ['upi' => 'UPI', 'bank_transfer' => 'Bank transfer', 'cash' => 'Cash', 'cheque' => 'Cheque', 'card' => 'Card'];

$errors = [];
$old = ['donor_id' => '', 'campaign_id' => '', 'amount' => '', 'payment_mode' => '',
        'status' => 'confirmed', 'donation_date' => date('Y-m-d')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) {
        $old[$k] = trim($_POST[$k] ?? '');
    }

    if (!hash_equals($_SESSION['csrf_admin'], $_POST['csrf'] ?? '')) {
        $errors[] = 'Session expired. Please try again.';
    }
    if (!in_array((int) $old['donor_id'], $donorIds, true)) {
        $errors[] = 'Please choose a donor.';
    }
    if (!in_array((int) $old['campaign_id'], $campaignIds, true)) {
        $errors[] = 'Please choose an active campaign.';
    }
    if (!is_numeric($old['amount']) || (float) $old['amount'] < 1 || (float) $old['amount'] > 10000000) {
        $errors[] = 'Amount must be between 1 and 10,000,000.';
    }
    if (!array_key_exists($old['payment_mode'], $modes)) {
        $errors[] = 'Please choose a payment mode.';
    }
    if (!in_array($old['status'], ['pending', 'confirmed'], true)) {
        $errors[] = 'Status must be pending or confirmed.';
    }
    if (!valid_date($old['donation_date'])) {
        $errors[] = 'Please enter a valid date.';
    } elseif ($old['donation_date'] > date('Y-m-d')) {
        $errors[] = 'The donation date cannot be in the future.';
    }

    if (!$errors) {
        try {
            $pdo->prepare(
                "INSERT INTO donations (donor_id, campaign_id, amount, payment_mode, status, donation_date)
                 VALUES (?, ?, ?, ?, ?, ?)"
            )->execute([
                (int) $old['donor_id'], (int) $old['campaign_id'], round((float) $old['amount'], 2),
                $old['payment_mode'], $old['status'], $old['donation_date'],
            ]);
            $_SESSION['flash_admin'] = ['success', 'Donation recorded.'];
            header('Location: donations.php');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'Could not save the donation. Please try again.';
        }
    }
}

$adminPage  = 'donations';
$adminTitle = 'Record donation';
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
      <label for="donor_id" class="form-label">Donor</label>
      <select class="form-select" id="donor_id" name="donor_id">
        <option value="">Choose a donor...</option>
        <?php foreach ($donors as $d): ?>
          <option value="<?= (int) $d['donor_id'] ?>" <?= (string) $d['donor_id'] === $old['donor_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['full_name'] . ' (' . $d['email'] . ')') ?></option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">New donor? <a href="donor_form.php">Add them first</a>.</div>
    </div>

    <div class="mb-3">
      <label for="campaign_id" class="form-label">Campaign</label>
      <select class="form-select" id="campaign_id" name="campaign_id">
        <option value="">Choose a campaign...</option>
        <?php foreach ($campaigns as $c): ?>
          <option value="<?= (int) $c['campaign_id'] ?>" <?= (string) $c['campaign_id'] === $old['campaign_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label for="amount" class="form-label">Amount (&#8377;)</label>
        <input type="number" class="form-control" id="amount" name="amount" min="1" step="0.01"
               value="<?= htmlspecialchars($old['amount']) ?>">
      </div>
      <div class="col-md-6 mb-3">
        <label for="payment_mode" class="form-label">Payment mode</label>
        <select class="form-select" id="payment_mode" name="payment_mode">
          <option value="">Choose...</option>
          <?php foreach ($modes as $val => $label): ?>
            <option value="<?= $val ?>" <?= $old['payment_mode'] === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label for="donation_date" class="form-label">Donation date</label>
        <input type="date" class="form-control" id="donation_date" name="donation_date"
               max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($old['donation_date']) ?>">
      </div>
      <div class="col-md-6 mb-3">
        <label for="status" class="form-label">Status</label>
        <select class="form-select" id="status" name="status">
          <option value="confirmed" <?= $old['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed (money verified)</option>
          <option value="pending"   <?= $old['status'] === 'pending'   ? 'selected' : '' ?>>Pending</option>
        </select>
      </div>
    </div>

    <button type="submit" class="btn btn-success px-4">Record donation</button>
    <a href="donations.php" class="btn btn-link">Cancel</a>
  </form>
</div></div>
<?php require __DIR__ . '/_footer.php'; ?>