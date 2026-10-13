<?php
$pageTitle   = $pageTitle   ?? 'Helping Hands Foundation';
$currentPage = $currentPage ?? '';
$base        = $base        ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> | Helping Hands Foundation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="<?= $base ?>assets/css/style.css?v=3" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-hh">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $base ?>index.php">Helping Hands</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <?php
        $links = ['index' => 'Home', 'about' => 'About', 'campaigns' => 'Campaigns', 'contact' => 'Contact'];
        foreach ($links as $key => $label): ?>
          <li class="nav-item">
            <a class="nav-link <?= $currentPage === $key ? 'active' : '' ?>"
               href="<?= $base . $key ?>.php"><?= $label ?></a>
          </li>
        <?php endforeach; ?>
        <li class="nav-item ms-lg-2">
          <a class="btn btn-donate" href="<?= $base ?>donate.php">Donate</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<main>
