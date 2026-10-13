<?php
require __DIR__ . '/auth.php';

$id     = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$errors = [];
$old = ['title' => '', 'description' => '', 'target_amount' => '',
        'start_date' => date('Y-m-d'), 'end_date' => '', 'status' => 'draft'];

if ($isEdit) {
    $stmt = $pdo->prepare(
        "SELECT title, description, target_amount, start_date, end_date, status
         FROM campaigns WHERE campaign_id = ?"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        $_SESSION['flash_admin'] = ['warning', 'Campaign not found.'];
        header('Location: campaigns.php');
        exit;
    }
    foreach ($old as $k => $_) {
        $old[$k] = (string) ($row[$k] ?? '');
    }
}

function valid_date(string $d): bool {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) {
        $old[$k] = trim($_POST[$k] ?? '');
    }

    if (!hash_equals($_SESSION['csrf_admin'], $_POST['csrf'] ?? '')) {
        $errors[] = 'Session expired. Please try again.';
    }
    if ($old['title'] === '' || mb_strlen($old['title']) > 150) {
        $errors[] = 'Title is required (up to 150 characters).';
    }
    if (mb_strlen($old['description']) > 2000) {
        $errors[] = 'Description is too long (maximum 2000 characters).';
    }
    if (!is_numeric($old['target_amount']) || (float) $old['target_amount'] <= 0
        || (float) $old['target_amount'] > 999999999) {
        $errors[] = 'Target amount must be greater than 0.';
    }
    if (!valid_date($old['start_date'])) {
        $errors[] = 'Please enter a valid start date.';
    }
    if (!valid_date($old['end_date'])) {
        $errors[] = 'Please enter a valid end date.';
    }
    if (!$errors && $old['end_date'] < $old['start_date']) {
        $errors[] = 'End date cannot be before the start date.';
    }
    if (!in_array($old['status'], ['draft', 'active', 'closed'], true)) {
        $errors[] = 'Please choose a valid status.';
    }

    if (!$errors) {
        $desc   = $old['description'] !== '' ? $old['description'] : null;
        $target = round((float) $old['target_amount'], 2);
        try {
            if ($isEdit) {
                $pdo->prepare(
                    "UPDATE campaigns SET title = ?, description = ?, target_amount = ?,
                            start_date = ?, end_date = ?, status = ?
                     WHERE campaign_id = ?"
                )->execute([$old['title'], $desc, $target, $old['start_date'], $old['end_date'], $old['status'], $id]);
                $_SESSION['flash_admin'] = ['success', 'Campaign updated.'];
            } else {
                $pdo->prepare(
                    "INSERT INTO campaigns (title, description, target_amount, start_date, end_date, status)
                     VALUES (?, ?, ?, ?, ?, ?)"
                )->execute([$old['title'], $desc, $target, $old['start_date'], $old['end_date'], $old['status']]);
                $_SESSION['flash_admin'] = ['success', 'Campaign created.'];
            }
            header('Location: campaigns.php');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'Could not save the campaign. Please try again.';
        }
    }
}

$adminPage  = 'campaigns';
$adminTitle = $isEdit ? 'Edit campaign' : 'New campaign';
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
      <label for="title" class="form-label">Title</label>
      <input type="text" class="form-control" id="title" name="title" maxlength="150"
             value="<?= htmlspecialchars($old['title']) ?>">
    </div>
    <div class="mb-3">
      <label for="description" class="form-label">Description</label>
      <textarea class="form-control" id="description" name="description" rows="4" maxlength="2000"><?= htmlspecialchars($old['description']) ?></textarea>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label for="target_amount" class="form-label">Target amount (&#8377;)</label>
        <input type="number" class="form-control" id="target_amount" name="target_amount" min="1" step="0.01"
               value="<?= htmlspecialchars($old['target_amount']) ?>">
      </div>
      <div class="col-md-6 mb-3">
        <label for="status" class="form-label">Status</label>
        <select class="form-select" id="status" name="status">
          <?php foreach (['draft', 'active', 'closed'] as $s): ?>
            <option value="<?= $s ?>" <?= $old['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label for="start_date" class="form-label">Start date</label>
        <input type="date" class="form-control" id="start_date" name="start_date"
               value="<?= htmlspecialchars($old['start_date']) ?>">
      </div>
      <div class="col-md-6 mb-3">
        <label for="end_date" class="form-label">End date</label>
        <input type="date" class="form-control" id="end_date" name="end_date"
               value="<?= htmlspecialchars($old['end_date']) ?>">
      </div>
    </div>
    <button type="submit" class="btn btn-success px-4"><?= $isEdit ? 'Save changes' : 'Create campaign' ?></button>
    <a href="campaigns.php" class="btn btn-link">Cancel</a>
  </form>
</div></div>
<?php require __DIR__ . '/_footer.php'; ?>