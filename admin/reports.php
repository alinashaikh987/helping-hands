<?php
require __DIR__ . '/auth.php';

function valid_date(string $d): bool {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}
// Builds " AND col >= ? AND col <= ?" and appends the values to $params
function date_cond(string $col, string $from, string $to, array &$params): string {
    $sql = '';
    if ($from !== '') { $sql .= " AND $col >= ?"; $params[] = $from; }
    if ($to !== '')   { $sql .= " AND $col <= ?"; $params[] = $to; }
    return $sql;
}

$types = [
    'campaign'   => 'Campaign-wise',
    'donor'      => 'Donor-wise',
    'monthly'    => 'Date-based (monthly)',
    'allocation' => 'Allocation summary',
];
$type = array_key_exists($_GET['type'] ?? '', $types) ? $_GET['type'] : 'campaign';
$from = valid_date($_GET['from'] ?? '') ? $_GET['from'] : '';
$to   = valid_date($_GET['to'] ?? '')   ? $_GET['to']   : '';
$rangeError = ($from !== '' && $to !== '' && $to < $from) ? 'The "To" date is before the "From" date.' : '';

$headers = []; $rows = []; $moneyCols = []; $sumCols = []; $note = '';

if (!$rangeError) {
    $p = [];
    switch ($type) {
        case 'donor':
            $cond = date_cond('d.donation_date', $from, $to, $p);
            $st = $pdo->prepare(
              "SELECT o.full_name, o.email,
                      COUNT(CASE WHEN d.status = 'confirmed' THEN 1 END) AS confirmed_gifts,
                      COALESCE(SUM(CASE WHEN d.status = 'confirmed' THEN d.amount END), 0) AS confirmed_total,
                      COUNT(CASE WHEN d.status = 'pending' THEN 1 END) AS pending_gifts,
                      MAX(d.donation_date) AS last_gift
               FROM donors o
               LEFT JOIN donations d ON d.donor_id = o.donor_id $cond
               GROUP BY o.donor_id, o.full_name, o.email
               ORDER BY confirmed_total DESC, o.full_name"
            );
            $st->execute($p);
            $headers   = ['Donor', 'Email', 'Confirmed gifts', 'Confirmed total (INR)', 'Pending gifts', 'Last gift (any status)'];
            foreach ($st->fetchAll() as $r) {
                $rows[] = [$r['full_name'], $r['email'], (int) $r['confirmed_gifts'],
                           $r['confirmed_total'], (int) $r['pending_gifts'], $r['last_gift'] ?? ''];
            }
            $moneyCols = [3]; $sumCols = [2, 3, 4];
            break;

        case 'monthly':
            $cond = date_cond('d.donation_date', $from, $to, $p);
            $st = $pdo->prepare(
              "SELECT DATE_FORMAT(d.donation_date, '%Y-%m') AS ym,
                      COUNT(*) AS gifts, SUM(d.amount) AS total
               FROM donations d
               WHERE d.status = 'confirmed' $cond
               GROUP BY ym ORDER BY ym"
            );
            $st->execute($p);
            $headers = ['Month', 'Confirmed gifts', 'Confirmed total (INR)'];
            foreach ($st->fetchAll() as $r) {
                $rows[] = [$r['ym'], (int) $r['gifts'], $r['total']];
            }
            $moneyCols = [2]; $sumCols = [1, 2];
            break;

        case 'allocation':
            $cond = date_cond('a.allocation_date', $from, $to, $p);
            $st = $pdo->prepare(
              "SELECT c.title,
                      SUM(CASE WHEN a.status = 'planned'   THEN a.amount ELSE 0 END) AS planned,
                      SUM(CASE WHEN a.status = 'approved'  THEN a.amount ELSE 0 END) AS approved,
                      SUM(CASE WHEN a.status = 'cancelled' THEN a.amount ELSE 0 END) AS cancelled
               FROM campaigns c
               LEFT JOIN fund_allocations a ON a.campaign_id = c.campaign_id $cond
               GROUP BY c.campaign_id, c.title
               ORDER BY c.title"
            );
            $st->execute($p);
            $headers = ['Campaign', 'Planned (INR)', 'Approved (INR)', 'Cancelled (INR)', 'Total allocated (INR)'];
            foreach ($st->fetchAll() as $r) {
                $active = (float) $r['planned'] + (float) $r['approved'];
                $rows[] = [$r['title'], $r['planned'], $r['approved'], $r['cancelled'], number_format($active, 2, '.', '')];
            }
            $moneyCols = [1, 2, 3, 4]; $sumCols = [1, 2, 3, 4];
            $note = 'Total allocated = planned + approved. "Allocated" means assigned to a purpose, not proof of spending.';
            break;

        default: // campaign
            $cond = date_cond('d.donation_date', $from, $to, $p);
            $st = $pdo->prepare(
              "SELECT c.title, c.status, c.target_amount,
                      COUNT(CASE WHEN d.status = 'confirmed' THEN 1 END) AS gifts,
                      COALESCE(SUM(CASE WHEN d.status = 'confirmed' THEN d.amount END), 0) AS raised,
                      COALESCE(SUM(CASE WHEN d.status = 'pending'   THEN d.amount END), 0) AS pending_amt
               FROM campaigns c
               LEFT JOIN donations d ON d.campaign_id = c.campaign_id $cond
               GROUP BY c.campaign_id, c.title, c.status, c.target_amount
               ORDER BY raised DESC, c.title"
            );
            $st->execute($p);
            $headers = ['Campaign', 'Status', 'Target (INR)', 'Confirmed gifts', 'Confirmed raised (INR)', 'Pending (INR, not counted)', '% of target'];
            foreach ($st->fetchAll() as $r) {
                $pct = (float) $r['target_amount'] > 0 ? round((float) $r['raised'] / (float) $r['target_amount'] * 100, 1) : 0;
                $rows[] = [$r['title'], $r['status'], $r['target_amount'], (int) $r['gifts'], $r['raised'], $r['pending_amt'], $pct];
            }
            $moneyCols = [2, 4, 5]; $sumCols = [3, 4, 5];
            $note = '% of target compares confirmed money in the selected range with the full campaign target.';
    }

    // Totals row
    if ($rows && $sumCols) {
        $total = array_fill(0, count($headers), '');
        $total[0] = 'Total';
        foreach ($sumCols as $i) {
            $total[$i] = array_sum(array_map(fn($r) => (float) $r[$i], $rows));
            if (!in_array($i, $moneyCols, true)) { $total[$i] = (int) $total[$i]; }
        }
        $rows[] = $total;
    }
}

// ---- CSV export (must happen before any HTML output) ----
if (!$rangeError && isset($_GET['export'])) {
    $name = 'report-' . $type . '-' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    $safe = function ($v) {
        if (is_string($v) && $v !== '' && preg_match('/^[=+\-@\t\r]/', $v)) { return "'" . $v; }
        return $v;
    };
    fputcsv($out, $headers);
    foreach ($rows as $r) { fputcsv($out, array_map($safe, $r)); }
    fclose($out);
    exit;
}

$exportQs = http_build_query(array_filter(
    ['type' => $type, 'from' => $from, 'to' => $to, 'export' => 1], fn($v) => $v !== ''
));

$adminPage  = 'reports';
$adminTitle = 'Reports';
require __DIR__ . '/_header.php';
?>
<div class="no-print">
  <ul class="nav nav-pills mb-3">
    <?php foreach ($types as $key => $label): ?>
      <li class="nav-item">
        <a class="nav-link <?= $type === $key ? 'active bg-success' : 'text-success' ?>"
           href="reports.php?<?= htmlspecialchars(http_build_query(array_filter(['type' => $key, 'from' => $from, 'to' => $to]))) ?>">
          <?= htmlspecialchars($label) ?></a>
      </li>
    <?php endforeach; ?>
  </ul>

  <div class="card mb-3"><div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
      <div class="col-md-3">
        <label class="form-label small mb-1">From</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($from) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">To</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($to) ?>">
      </div>