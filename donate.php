<?php
require __DIR__ . '/config/session.php';
require __DIR__ . '/config/db.php';

// CSRF token
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
// Active campaigns for the dropdown
$campaigns = $pdo->query(
    "SELECT campaign_id, title FROM campaigns WHERE status = 'active' ORDER BY title"
)->fetchAll();
$validIds = array_column($campaigns, 'campaign_id');

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'campaign_id' => '', 'amount' => '', 'payment_mode' => ''];
$modes = ['upi' => 'UPI', 'bank_transfer' => 'Bank transfer', 'cash' => 'Cash', 'cheque' => 'Cheque', 'card' => 'Card'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) {
        $old[$k] = trim($_POST[$k] ?? '');
    }

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $errors[] = 'Your session expired. Please try again.';
    }
    if ($old['name'] === '' || mb_strlen($old['name']) > 100) {
        $errors[] = 'Please enter your name (up to 100 characters).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['email']) > 150) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($old['phone'] !== '' && !preg_match('/^[0-9+\- ]{7,20}$/', $old['phone'])) {
        $errors[] = 'Phone may contain only digits, spaces, + and - (7 to 20 characters).';
    }
    if (!in_array((int) $old['campaign_id'], $validIds, true)) {
        $errors[] = 'Please choose an active campaign.';
    }
    if (!is_numeric($old['amount']) || (float) $old['amount'] < 1 || (float) $old['amount'] > 10000000) {
        $errors[] = 'Amount must be between 1 and 10,000,000.';
    }
    if (!array_key_exists($old['payment_mode'], $modes)) {
        $errors[] = 'Please choose a payment mode.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT donor_id FROM donors WHERE email = ?");
            $stmt->execute([$old['email']]);
            $donorId = $stmt->fetchColumn();

            if (!$donorId) {
                $stmt = $pdo->prepare("INSERT INTO donors (full_name, email, phone) VALUES (?, ?, ?)");
                $stmt->execute([$old['name'], $old['email'], $old['phone'] !== '' ? $old['phone'] : null]);
                $donorId = $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare(
                "INSERT INTO donations (donor_id, campaign_id, amount, payment_mode, status)
                 VALUES (?, ?, ?, ?, 'pending')"
            );
            $stmt->execute([$donorId, (int) $old['campaign_id'], round((float) $old['amount'], 2), $old['payment_mode']]);

            $pdo->commit();

            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            $_SESSION['flash'] = 'Thank you! Your donation has been recorded as pending and will be confirmed by our team.';
            header('Location: donate.php');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log($e->getMessage());
            $errors[] = 'Sorry, something went wrong while saving. Please try again.';
        }
    }
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle   = 'Donate';
$currentPage = 'donate';
require __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
  <div class="container">
    <h1 class="mb-1">Make a Donation</h1>
    <p>Your gift is recorded as pending until our team confirms it.</p>
  </div>
</section>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-7">

      <?php if ($flash): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
      <?php endif; ?>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="card"><div class="card-body p-4">
       <form method="post" action="donate.php" novalidate data-validate>
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

          <div class="mb-3">
            <label for="name" class="form-label">Full name</label>
            <input type="text" class="form-control" id="name" name="name" maxlength="100" required
                   value="<?= htmlspecialchars($old['name']) ?>">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" maxlength="150" required
                     value="<?= htmlspecialchars($old['email']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label for="phone" class="form-label">Phone (optional)</label>
              <input type="text" class="form-control" id="phone" name="phone" maxlength="20"
                     value="<?= htmlspecialchars($old['phone']) ?>">
            </div>
          </div>
          <div class="mb-3">
            <label for="campaign_id" class="form-label">Campaign</label>
            <select class="form-select" id="campaign_id" name="campaign_id" required>
              <option value="">Choose a campaign...</option>
              <?php foreach ($campaigns as $c): ?>
                <option value="<?= (int) $c['campaign_id'] ?>"
                  <?= (string) $c['campaign_id'] === $old['campaign_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['title']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="amount" class="form-label">Amount (&#8377;)</label>
              <input type="number" class="form-control" id="amount" name="amount" min="1" step="0.01" required
                     value="<?= htmlspecialchars($old['amount']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label for="payment_mode" class="form-label">Payment mode</label>
              <select class="form-select" id="payment_mode" name="payment_mode" required>
                <option value="">Choose...</option>
                <?php foreach ($modes as $val => $label): ?>
                  <option value="<?= $val ?>" <?= $old['payment_mode'] === $val ? 'selected' : '' ?>>
                    <?= $label ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <button type="submit" class="btn btn-donate w-100 py-2" style="background: var(--hh-green); color: #fff;">
            Submit Donation
          </button>
          <p class="small text-muted mt-3 mb-0">
            This is a demonstration. No real payment is taken. Donations stay <strong>pending</strong> until an admin confirms them.
          </p>
        </form>
      </div></div>

    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>