<?php
require __DIR__ . '/auth.php';

$s = $pdo->query(
  "SELECT
     (SELECT COUNT(*) FROM donors) AS donors,
     (SELECT COUNT(*) FROM donations WHERE status = 'confirmed') AS confirmed_count,
     (SELECT COALESCE(SUM(amount), 0) FROM donations WHERE status = 'confirmed') AS confirmed_total,
     (SELECT COUNT(*) FROM campaigns WHERE status = 'active') AS active_campaigns,
     (SELECT COALESCE(SUM(amount), 0) FROM fund_allocations
        WHERE status IN ('planned','approved')) AS allocated,
     (SELECT COUNT(*) FROM donations WHERE status = 'pending') AS pending_count,
     (SELECT COUNT(*) FROM contact_messages WHERE is_read = 0) AS unread"
)->fetch();

$recent = $pdo->query(
  "SELECT d.donation_id, o.full_name, c.title, d.amount, d.status, d.donation_date
   FROM donations d
   JOIN donors o    ON o.donor_id = d.donor_id
   JOIN campaigns c ON c.campaign_id = d.campaign_id
   ORDER BY d.donation_id DESC LIMIT 5"
)->fetchAll();

$progress = $pdo->query(
  "SELECT c.title, c.target_amount, COALESCE(SUM(d.amount), 0) AS raised
   FROM campaigns c
   LEFT JOIN donations d ON d.campaign_id = c.campaign_id AND d.status = 'confirmed'
   WHERE c.status = 'active'
   GROUP BY c.campaign_id, c.title, c.target_amount
   ORDER BY c.title"
)->fetchAll();

$badge = ['confirmed' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'refunded' => 'secondary'];

$adminPage  = 'dashboard';
$adminTitle = 'Dashboard';
require __DIR__ . '/_header.php';
?>
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3"><div class="card stat-card">
    <div class="stat-number"><?= number_format((int) $s['donors']) ?></div>
    <div class="stat-label">Registered donors</div></div></div>
  <div class="col-6 col-xl-3"><div class="card stat-card">
    <div class="stat-number">&#8377;<?= number_format((float) $s['confirmed_total'], 0) ?></div>
    <div class="stat-label">Confirmed donations (<?= (int) $s['confirmed_count'] ?>)</div></div></div>
  <div class="col-6 col-xl-3"><div class="card stat-card">
    <div class="stat-number"><?= number_format((int) $s['active_campaigns']) ?></div>
    <div class="stat-label">Active campaigns</div></div></div>
  <div class="col-6 col-xl-3"><div class="card stat-card">
    <div class="stat-number">&#8377;<?= number_format((float) $s['allocated'], 0) ?></div>
    <div class="stat-label">Funds allocated</div></div></div>
</div>

<?php if ($s['pending_count'] > 0 || $s['unread'] > 0): ?>
  <div class="alert alert-warning">
    <?= (int) $s['pending_count'] ?> pending donation(s) awaiting confirmation and
    <?= (int) $s['unread'] ?> unread message(s).
  </div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-xl-7">
    <div class="card"><div class="card-body">
      <h2 class="h5 card-title">Recent donations</h2>
      <?php if (!$recent): ?>
        <p class="text-muted mb-0">No donations recorded yet.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead><tr><th>Donor</th><th>Campaign</th><th class="text-end">Amount</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach ($recent as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['full_name']) ?></td>
              <td><?= htmlspecialchars($r['title']) ?></td>
              <td class="text-end">&#8377;<?= number_format($r['amount'], 2) ?></td>
              <td><span class="badge text-bg-<?= $badge[$r['status']] ?? 'secondary' ?>"><?= htmlspecialchars($r['status']) ?></span></td>
              <td><?= htmlspecialchars($r['donation_date']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div></div>
  </div>

  <div class="col-xl-5">
    <div class="card"><div class="card-body">
      <h2 class="h5 card-title">Campaign funding progress</h2>
      <?php if (!$progress): ?>
        <p class="text-muted mb-0">No active campaigns.</p>
      <?php endif; ?>
      <?php foreach ($progress as $p):
        $pct = min(100, (int) round($p['raised'] / $p['target_amount'] * 100)); ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between small">
            <strong><?= htmlspecialchars($p['title']) ?></strong>
            <span>&#8377;<?= number_format($p['raised'], 0) ?> / &#8377;<?= number_format($p['target_amount'], 0) ?></span>
          </div>
          <div class="progress" role="progressbar" aria-label="Funding progress">
            <div class="progress-bar" style="width: <?= $pct ?>%"><?= $pct ?>%</div>
          </div>
        </div>
      <?php endforeach; ?>
    </div></div>
  </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>