<?php
require __DIR__ . '/auth.php';

// ---- POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int) ($_POST['message_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (!hash_equals($_SESSION['csrf_admin'], $_POST['csrf'] ?? '')) {
        $_SESSION['flash_admin'] = ['danger', 'Session expired. Please try again.'];
    } elseif ($action === 'mark_read' || $action === 'mark_unread') {
        $pdo->prepare("UPDATE contact_messages SET is_read = ? WHERE message_id = ?")
            ->execute([$action === 'mark_read' ? 1 : 0, $id]);
        $_SESSION['flash_admin'] = ['success', 'Message updated.'];
    } elseif ($action === 'delete') {
        $d = $pdo->prepare("DELETE FROM contact_messages WHERE message_id = ?");
        $d->execute([$id]);
        $_SESSION['flash_admin'] = $d->rowCount() > 0
            ? ['success', 'Message deleted.'] : ['warning', 'Message not found.'];
    }
    header('Location: messages.php');
    exit;
}

$filter = ($_GET['show'] ?? '') === 'unread' ? 'unread' : 'all';
$sql = "SELECT message_id, name, email, subject, message, is_read, created_at FROM contact_messages "
     . ($filter === 'unread' ? "WHERE is_read = 0 " : "")
     . "ORDER BY is_read ASC, created_at DESC";
$messages = $pdo->query($sql)->fetchAll();

$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);

$adminPage  = 'messages';
$adminTitle = 'Messages';
require __DIR__ . '/_header.php';
?>
<?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash[0]) ?>"><?= htmlspecialchars($flash[1]) ?></div>
<?php endif; ?>

<ul class="nav nav-pills mb-3">
  <li class="nav-item"><a class="nav-link <?= $filter === 'all' ? 'active bg-success' : 'text-success' ?>" href="messages.php">All</a></li>
  <li class="nav-item"><a class="nav-link <?= $filter === 'unread' ? 'active bg-success' : 'text-success' ?>" href="messages.php?show=unread">Unread</a></li>
</ul>

<?php if (!$messages): ?>
  <div class="card"><div class="card-body text-muted">No messages.</div></div>
<?php endif; ?>

<?php foreach ($messages as $m): ?>
  <div class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between flex-wrap gap-2">
      <div>
        <h2 class="h6 mb-0">
          <?= htmlspecialchars($m['subject']) ?>
          <?php if (!$m['is_read']): ?><span class="badge text-bg-warning ms-1">New</span><?php endif; ?>
        </h2>
        <div class="small text-muted">
          From <strong><?= htmlspecialchars($m['name']) ?></strong>
          (<a href="mailto:<?= htmlspecialchars($m['email']) ?>"><?= htmlspecialchars($m['email']) ?></a>)
          on <?= htmlspecialchars($m['created_at']) ?>
        </div>
      </div>
      <div class="d-flex gap-2 align-items-start">
        <form method="post">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_admin']) ?>">
          <input type="hidden" name="message_id" value="<?= (int) $m['message_id'] ?>">
          <input type="hidden" name="action" value="<?= $m['is_read'] ? 'mark_unread' : 'mark_read' ?>">
          <button class="btn btn-sm btn-outline-success" type="submit"><?= $m['is_read'] ? 'Mark unread' : 'Mark read' ?></button>
        </form>
        <form method="post" onsubmit="return confirm('Delete this message permanently?');">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_admin']) ?>">
          <input type="hidden" name="message_id" value="<?= (int) $m['message_id'] ?>">
          <input type="hidden" name="action" value="delete">
          <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
        </form>
      </div>
    </div>
    <p class="mt-3 mb-0"><?= nl2br(htmlspecialchars($m['message'])) ?></p>
  </div></div>
<?php endforeach; ?>
<?php require __DIR__ . '/_footer.php'; ?>