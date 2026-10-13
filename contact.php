<?php
require __DIR__ . '/config/session.php';
require __DIR__ . '/config/db.php';

if (empty($_SESSION['csrf_contact'])) {
    $_SESSION['csrf_contact'] = bin2hex(random_bytes(32));
}

$errors = [];
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) {
        $old[$k] = trim($_POST[$k] ?? '');
    }

    if (!hash_equals($_SESSION['csrf_contact'], $_POST['csrf'] ?? '')) {
        $errors[] = 'Your session expired. Please try again.';
    }
    if ($old['name'] === '' || mb_strlen($old['name']) > 100) {
        $errors[] = 'Please enter your name (up to 100 characters).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['email']) > 150) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($old['subject'] === '' || mb_strlen($old['subject']) > 150) {
        $errors[] = 'Please enter a subject (up to 150 characters).';
    }
    if (mb_strlen($old['message']) < 10 || mb_strlen($old['message']) > 2000) {
        $errors[] = 'Your message must be between 10 and 2000 characters.';
    }

    // Honeypot: real visitors never fill this hidden field
    if (!empty($_POST['website'])) {
        $_SESSION['flash_contact'] = 'Thank you! Your message has been sent.';
        header('Location: contact.php');
        exit;
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM contact_messages
                 WHERE email = ? AND message = ?
                   AND created_at > (NOW() - INTERVAL 10 MINUTE)"
            );
            $stmt->execute([$old['email'], $old['message']]);

            if ((int) $stmt->fetchColumn() > 0) {
                $errors[] = 'We have already received this message. Please wait for our reply.';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([$old['name'], $old['email'], $old['subject'], $old['message']]);

                $_SESSION['csrf_contact'] = bin2hex(random_bytes(32));
                $_SESSION['flash_contact'] = 'Thank you! Your message has been sent. We will get back to you soon.';
                header('Location: contact.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'Sorry, something went wrong while saving. Please try again.';
        }
    }
}

$flash = $_SESSION['flash_contact'] ?? null;
unset($_SESSION['flash_contact']);

$pageTitle   = 'Contact';
$currentPage = 'contact';
require __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
  <div class="container">
    <h1 class="mb-1">Contact Us</h1>
    <p>Questions, ideas or want to volunteer? We would love to hear from you.</p>
  </div>
</section>

<div class="container py-5">
  <div class="row g-4">
    <div class="col-lg-7">

      <?php if ($flash): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
      <?php endif; ?>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="card"><div class="card-body p-4">
        <form method="post" action="contact.php" novalidate data-validate>
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_contact']) ?>">

          <div style="position:absolute; left:-9999px;" aria-hidden="true">
            <label for="website">Leave this field empty</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="name" class="form-label">Your name</label>
              <input type="text" class="form-control" id="name" name="name" maxlength="100" required
                     value="<?= htmlspecialchars($old['name']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" maxlength="150" required
                     value="<?= htmlspecialchars($old['email']) ?>">
            </div>
          </div>
          <div class="mb-3">
            <label for="subject" class="form-label">Subject</label>
            <input type="text" class="form-control" id="subject" name="subject" maxlength="150" required
                   value="<?= htmlspecialchars($old['subject']) ?>">
          </div>
          <div class="mb-3">
            <label for="message" class="form-label">Message</label>
            <textarea class="form-control" id="message" name="message" rows="5" maxlength="2000" required><?= htmlspecialchars($old['message']) ?></textarea>
          </div>
          <button type="submit" class="btn w-100 py-2" style="background: var(--hh-green); color: #fff;">Send Message</button>
        </form>
      </div></div>

    </div>

    <div class="col-lg-5">
      <div class="mission-box mb-4">
        <h2 class="h4 section-title">Get in touch</h2>
        <p class="mb-1"><strong>Email:</strong> info@helpinghands.example</p>
        <p class="mb-1"><strong>Phone:</strong> +91 90000 00000</p>
        <p class="mb-0"><strong>Address:</strong> 12 Green Park Road, Nagpur, Maharashtra (fictional)</p>
      </div>
      <div class="mission-box">
        <h2 class="h4 section-title">Office hours</h2>
        <p class="mb-0">Monday to Saturday, 10:00 AM to 5:00 PM</p>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>