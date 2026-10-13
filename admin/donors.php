<?php
require __DIR__ . '/auth.php';

// ---- Delete (POST only) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!hash_equals($_SESSION['csrf_admin'], $_POST['csrf'] ?? '')) {
        $_SESSION['flash_admin'] = ['danger', 'Session expired. Please try again.'];
    } else {
        $id = (int) ($_POST['donor_id'] ?? 0);
        $c = $pdo->prepare("SELECT COUNT(*) FROM donations WHERE donor_id = ?");
        $c->execute([$id]);
        if ((int) $c->fetchColumn() > 0) {
            $_SESSION['flash_admin'] = ['danger', 'This donor has donation records and cannot be deleted.'];
        } else {
            try {
                $del = $pdo->prepare("DELETE FROM donors WHERE donor_id = ?");
                $del->execute([$id]);
                $_SESSION['flash_admin'] = $del->rowCount() > 0
                    ? ['success', 'Donor deleted.']
                    : ['warning', 'Donor not found.'];
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $_SESSION['flash_admin'] = ['danger', 'Could not delete this donor.'];
            }
        }
    }
    header('Location: donors.php');
    exit;
}

// ---- Search + list ----
$q = trim($_GET['q'] ?? '');
$stmt = $pdo->prepare(
  "SELECT o.donor_id, o.full_name, o.email, o.phone, o.created_at,
          COUNT(d.donation_id) AS gifts,
          COALESCE(SUM(CASE WHEN d.status = 'confirmed' THEN d.amount END), 0) AS confirmed_total
   FROM donors o
   LEFT JOIN donations d ON d.donor_id = o.donor_id
   WHERE o.full_name LIKE ? OR o.email LIKE ?
   GROUP BY o.donor_id, o.full_name, o.email, o.phone, o.created_at
   ORDER BY o.full_name"
);
$stmt->execute(['%' . $q . '%', '%' . $q . '%']);
$donors = $stmt->fetchAll();

$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);

$adminPage  = 'donors';
$adminTitle = 'Donors';
require __DIR__ . '/_header.php';
?>
<?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash[0]) ?>"><?= htmlspecialchars($flash[1]) ?></div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
  <form method="get" class="d-flex gap-2">
    <input type="text" name="q" class="form-control" placeholder="Search name or email"
           value="<?= htmlspecialchars($q) ?>">
    <button class="btn btn-outline-success" type="submit">Search</button>
    <?php if ($q !== ''): ?><a href="donors.php" class="btn btn-link">Clear</a><?php endif; ?>
  </form>
  <a href="donor_form.php" class="btn btn-donate">+ Add donor</a>
</div>

<div class="card"><div class="card-body">
  <?php if (!$donors): ?>
    <p class="text-muted mb-0">No donors found.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr>
        <th>Name</th><th>Email</th><th>Phone</th>
        <th class="text-end">Gifts</th><th class="text-end">Confirmed total</th><th class="text-end">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($donors as $d): ?>
        <tr>
          <td><?= htmlspecialchars($d['full_name']) ?></td>
          <td><?= htmlspecialchars($d['email']) ?></td>
          <td><?= htmlspecialchars($d['phone'] ?? '-') ?></td>
          <td class="text-end"><?= (int) $d['gifts'] ?></td>
          <td class="text-end">&#8377;<?= number_format($d['confirmed_total'], 2) ?></td>
          <td class="text-end text-nowrap">
            <a class="btn btn-sm btn-outline-success" href="donor_form.php?id=<?= (int) $d['donor_id'] ?>">Edit</a>
            <?php if ((int) $d['gifts'] === 0): ?>
              <form method="post" class="d-inline"
                    onsubmit="return confirm('Delete this donor permanently?');">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_admin']) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="donor_id" value="<?= (int) $d['donor_id'] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
              </form>
            <?php else: ?>
              <button class="btn btn-sm btn-outline-secondary" disabled
                      title="Has donation records">Delete</button>
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