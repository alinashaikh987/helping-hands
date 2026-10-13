<?php
$adminPage  = $adminPage  ?? '';
$adminTitle = $adminTitle ?? 'Admin';
$unreadCount = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
$nav = [
  'dashboard'   => ['Dashboard', 'dashboard.php'],
  'donors'      => ['Donors', 'donors.php'],
  'donations'   => ['Donations', 'donations.php'],
  'messages'    => ['Messages', 'messages.php'],
  'campaigns'   => ['Campaigns', 'campaigns.php'],
  'allocations' => ['Fund Allocation', 'allocations.php'],
  'reports'     => ['Reports', 'reports.php'],

];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($adminTitle) ?> | Helping Hands Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="../assets/css/style.css?v=3" rel="stylesheet">
  <link href="../assets/css/admin.css?v=1" rel="stylesheet">
</head>
<body>
<div class="admin-wrap">
  <aside class="admin-sidebar">
    <div class="admin-brand">Helping Hands</div>
    <nav class="nav flex-column">
      <?php foreach ($nav as $key => [$label, $href]): ?>
        <a class="nav-link <?= $adminPage === $key ? 'active' : '' ?>" href="<?= $href ?>"><?= $label ?><?php if ($key === 'messages' && $unreadCount > 0): ?> <span class="badge text-bg-warning"><?= $unreadCount ?></span><?php endif; ?></a>                 
      <?php endforeach; ?>
    </nav>
    <form method="post" action="logout.php" class="mt-4">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_admin']) ?>">
      <button type="submit" class="btn btn-outline-light btn-sm w-100">Log out</button>
    </form>
  </aside>
  <main class="admin-main">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 section-title mb-0"><?= htmlspecialchars($adminTitle) ?></h1>
      <span class="text-muted small">Signed in as <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? '') ?></strong></span>
    </div>