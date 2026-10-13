<?php
require __DIR__ . '/config/db.php';

$stats = $pdo->query(
  "SELECT
     (SELECT COUNT(*) FROM donors) AS donors,
     (SELECT COALESCE(SUM(amount), 0) FROM donations WHERE status = 'confirmed') AS raised,
     (SELECT COUNT(*) FROM campaigns WHERE status = 'active') AS active_campaigns,
     (SELECT COALESCE(SUM(amount), 0) FROM fund_allocations
        WHERE status IN ('planned','approved')) AS allocated"
)->fetch();

$featured = $pdo->query(
  "SELECT c.title, c.description, c.target_amount,
          COALESCE(SUM(d.amount), 0) AS raised
   FROM campaigns c
   LEFT JOIN donations d
          ON d.campaign_id = c.campaign_id AND d.status = 'confirmed'
   WHERE c.status = 'active'
   GROUP BY c.campaign_id, c.title, c.description, c.target_amount
   ORDER BY c.end_date
   LIMIT 3"
)->fetchAll();

$pageTitle   = 'Home';
$currentPage = 'index';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container">
    <span class="eyebrow">Helping Hands Foundation</span>
    <h1>Small gifts.<br><span>Big change</span> for every child.</h1>
    <p class="lead">We bring education and clean water to communities that need them most. Every rupee you give is tracked and reported openly.</p>
    <a href="donate.php" class="btn btn-donate me-2">Donate Now</a>
    <a href="campaigns.php" class="btn btn-outline-light">View Campaigns</a>
  </div>
</section>

<section class="stats-wrap">
  <div class="container">
    <div class="row g-3">
      <div class="col-6 col-lg-3"><div class="card stat-card">
        <div class="stat-number"><?= number_format((int) $stats['donors']) ?></div>
        <div class="stat-label">Registered donors</div></div></div>
      <div class="col-6 col-lg-3"><div class="card stat-card">
        <div class="stat-number">&#8377;<?= number_format((float) $stats['raised'], 0) ?></div>
        <div class="stat-label">Confirmed donations</div></div></div>
      <div class="col-6 col-lg-3"><div class="card stat-card">
        <div class="stat-number"><?= number_format((int) $stats['active_campaigns']) ?></div>
        <div class="stat-label">Active campaigns</div></div></div>
      <div class="col-6 col-lg-3"><div class="card stat-card">
        <div class="stat-number">&#8377;<?= number_format((float) $stats['allocated'], 0) ?></div>
        <div class="stat-label">Funds allocated</div></div></div>
    </div>
  </div>
</section>

<section class="container py-5">
  <div class="row g-4">
    <div class="col-md-6"><div class="mission-box">
      <h2 class="h4 section-title">Our Mission</h2>
      <p>To give every child access to education and every village access to safe drinking water, using donations responsibly and transparently.</p>
    </div></div>
    <div class="col-md-6"><div class="mission-box">
      <h2 class="h4 section-title">Our Vision</h2>
      <p>A community where no child misses school and no family walks miles for clean water.</p>
    </div></div>
  </div>
</section>

<section class="section-light py-5">
  <div class="container">
    <h2 class="section-title">Featured Campaigns</h2>
    <p class="mb-4">Support a cause that matters to you.</p>
    <?php if (count($featured) === 0): ?>
      <div class="alert alert-info">No active campaigns right now.</div>
    <?php endif; ?>
    <div class="row g-4">
      <?php foreach ($featured as $c): ?>
        <?php $percent = min(100, (int) round($c['raised'] / $c['target_amount'] * 100)); ?>
        <div class="col-md-6 col-lg-4">
          <div class="card h-100">
            <div class="card-body">
              <h3 class="h5 card-title"><?= htmlspecialchars($c['title']) ?></h3>
              <p class="card-text"><?= htmlspecialchars($c['description'] ?? '') ?></p>
              <div class="progress mb-2" role="progressbar" aria-label="Funding progress">
                <div class="progress-bar" style="width: <?= $percent ?>%"><?= $percent ?>%</div>
              </div>
              <small>&#8377;<?= number_format($c['raised'], 0) ?> raised of &#8377;<?= number_format($c['target_amount'], 0) ?></small>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
      <a href="campaigns.php" class="btn btn-success rounded-pill px-4">See all campaigns</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>