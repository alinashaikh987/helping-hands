<?php
require __DIR__ . '/auth.php';

$id     = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$errors = [];
$old    = ['full_name' => '', 'email' => '', 'phone' => ''];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT full_name, email, phone FROM donors WHERE donor_id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        $_SESSION['flash_admin'] = ['warning', 'Donor not found.'];
        header('Location: donors.php');
        exit;
    }
    $old = ['full_name' => $row['full_name'], 'email' => $row['email'], 'phone' => $row['phone'] ?? ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) {
        $old[$k] = trim($_POST[$k] ?? '');
    }

    if (!hash_equals($_SESSION['csrf_admin'], $_POST['csrf'] ?? '')) {
        $errors[] = 'Session expired. Please try again.';
    }
    if ($old['full_name'] === '' || mb_strlen($old['full_name']) > 100) {
        $errors[] = 'Name is required (up to 100 characters).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['email']) > 150) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($old['phone'] !== '' && !preg_match('/^[0-9+\- ]{7,20}$/', $old['phone'])) {
        $errors[] = 'Phone may contain only digits, spaces, + and - (7 to 20 characters).';
    }

    if (!$errors) {
        // Email must be unique, ignoring this donor's own row when editing
        $dup = $pdo->prepare("SELECT COUNT(*) FROM donors WHERE email = ? AND donor_id <> ?");
        $dup->execute([$old['email'], $id]);
        if ((int) $dup->fetchColumn() > 0) {
            $errors[] = 'Another donor already uses this email.';
        }
    }

    if (!$errors) {
        $phone = $old['phone'] !== '' ? $old['phone'] : null;
        try {
            if ($isEdit) {
                $pdo->prepare("UPDATE donors SET full_name = ?, email = ?, phone = ? WHERE donor_id = ?")
                    ->execute([$old['full_name'], $old['email'], $phone, $id]);
                $_SESSION['flash_admin'] = ['success', 'Donor updated.'];
            } else {
                $pdo->prepare("INSERT INTO donors (full_name, email, phone) VALUES (?, ?, ?)")
                    ->execute([$old['full_name'], $old['email'], $phone]);
                $_SESSION['flash_admin'] = ['success', 'Donor added.'];
            }
            header('Location: donors.php');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'Could not save the donor. Please try again.';
        }
    }
}

$adminPage  = 'donors';
$adminTitle = $isEdit ? 'Edit donor' : 'Add donor';
require __DIR__ . '/_header.php';
?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<div class="card" style="max-width: 640px;"><div class="card-body p-4">
  <form method="post" novalidate>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_admin']) ?>">
    <div class="mb-3">
      <label for="full_name" class="form-label">Full name</label>
      <input type="text" class="form-control" id="full_name" name="full_name" maxlength="100"
             value="<?= htmlspecialchars($old['full_name']) ?>">
    </div>
    <div class="mb-3">
      <label for="email" class="form-label">Email</label>
      <input type="email" class="form-control" id="email" name="email" maxlength="150"
             value="<?= htmlspecialchars($old['email']) ?>">
    </div>
    <div class="mb-3">
      <label for="phone" class="form-label">Phone (optional)</label>
      <input type="text" class="form-control" id="phone" name="phone" maxlength="20"
             value="<?= htmlspecialchars($old['phone']) ?>">
    </div>
    <button type="submit" class="btn btn-success px-4"><?= $isEdit ? 'Save changes' : 'Add donor' ?></button>
    <a href="donors.php" class="btn btn-link">Cancel</a>
  </form>
</div></div>
<?php require __DIR__ . '/_footer.php'; ?>