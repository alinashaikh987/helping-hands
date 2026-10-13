<?php
require __DIR__ . '/config/db.php';

$sql = "SELECT c.campaign_id, c.title, c.description, c.target_amount, c.end_date,
               COALESCE(SUM(d.amount), 0) AS raised
        FROM campaigns c
        LEFT JOIN donations d
               ON d.campaign_id = c.campaign_id AND d.status = 'confirmed'
        WHERE c.status = 'active'
        GROUP BY c.campaign_id, c.title, c.description, c.target_amount, c.end_date
        ORDER BY c.end_date";

$campaigns = $pdo->query($sql)->fetchAll();
?>
<?php
$pageTitle   = 'Campaigns';
$currentPage = 'campaigns';
require __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
  <div class="container">
    <h1 class="mb-1">Active Campaigns</h1>
    <p>Choose a cause and help us make a difference.</p>
  </div>
</section>
<div class="container py-5">

  <?php if (count($campaigns) === 0): ?>
    <div class="alert alert-info">No active campaigns right now. Please check back soon.</div>
  <?php endif; ?>

  <div class="row g-4">
    <?php foreach ($campaigns as $c): ?>
      <?php $percent = min(100, (int) round($c['raised'] / $c['target_amount'] * 100)); ?>
      <div class="col-md-6">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h2 class="h5 card-title"><?= htmlspecialchars($c['title']) ?></h2>
            <p class="card-text"><?= htmlspecialchars($c['description'] ?? '') ?></p>
            <div class="progress mb-2" role="progressbar" aria-label="Funding progress">
              <div class="progress-bar" style="width: <?= $percent ?>%"><?= $percent ?>%</div>
            </div>
            <p class="mb-1">Raised: &#8377;<?= number_format($c['raised'], 2) ?>
               of &#8377;<?= number_format($c['target_amount'], 2) ?></p>
            <small class="text-muted">Ends on <?= htmlspecialchars($c['end_date']) ?></small>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>