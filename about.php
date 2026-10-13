<?php
$pageTitle   = 'About Us';
$currentPage = 'about';
require __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
  <div class="container">
    <h1 class="mb-1">About Us</h1>
    <p>Who we are and why we do what we do.</p>
  </div>
</section>

<section class="container py-5">
  <div class="row g-5 align-items-center">
    <div class="col-lg-7">
      <h2 class="section-title">Who we are</h2>
      <p>Helping Hands Foundation is a fictional non-profit organization created for this college project. It works to bring education and clean drinking water to communities that need them most.</p>
      <p>Every donation is recorded, every campaign has a clear target, and every allocation of funds is tracked, so supporters can see how their gifts are used.</p>
    </div>
    <div class="col-lg-5">
      <div class="mission-box mb-4">
        <h2 class="h4 section-title">Our Mission</h2>
        <p class="mb-0">To give every child access to education and every village access to safe drinking water, using donations responsibly and transparently.</p>
      </div>
      <div class="mission-box">
        <h2 class="h4 section-title">Our Vision</h2>
        <p class="mb-0">A community where no child misses school and no family walks miles for clean water.</p>
      </div>
    </div>
  </div>
</section>

<section class="section-light py-5">
  <div class="container">
    <h2 class="section-title mb-4">Our Objectives</h2>
    <div class="row g-4">
      <div class="col-md-4"><div class="card h-100"><div class="card-body">
        <h3 class="h5 card-title">Education</h3>
        <p class="card-text mb-0">Provide school kits, books and learning support to children from low-income families.</p>
      </div></div></div>
      <div class="col-md-4"><div class="card h-100"><div class="card-body">
        <h3 class="h5 card-title">Clean water</h3>
        <p class="card-text mb-0">Install water filters and safe drinking points in villages without reliable access.</p>
      </div></div></div>
      <div class="col-md-4"><div class="card h-100"><div class="card-body">
        <h3 class="h5 card-title">Transparency</h3>
        <p class="card-text mb-0">Report confirmed donations and fund allocations openly, so every rupee can be traced.</p>
      </div></div></div>
    </div>
  </div>
</section>

<section class="container py-5">
  <h2 class="section-title">Our social impact</h2>
  <p>Our work is measured by the people it reaches. The goals below are the targets of our current campaigns:</p>
  <ul>
    <li>School kits for 500 children through the <strong>Books for Every Child</strong> campaign.</li>
    <li>Water filters in 10 villages through the <strong>Clean Water Drive</strong> campaign.</li>
    <li>A public record of confirmed donations, shown on the home page.</li>
  </ul>
  <a href="campaigns.php" class="btn btn-donate" style="background: var(--hh-green); color: #fff;">See our campaigns</a>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>