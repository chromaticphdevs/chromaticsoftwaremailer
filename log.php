<?php
require __DIR__ . '/includes/log.php';

$logRows = getSentLog();

$totalSent   = count(array_filter($logRows, fn($r) => $r['status'] === 'sent'));
$totalFailed = count(array_filter($logRows, fn($r) => $r['status'] === 'failed'));

// Optional simple filter: ?company=acme or ?status=sent
$filterCompany = trim((string) ($_GET['company'] ?? ''));
$filterStatus  = trim((string) ($_GET['status'] ?? ''));

if ($filterCompany !== '') {
    $logRows = array_filter($logRows, fn($r) =>
        stripos($r['name'], $filterCompany) !== false ||
        stripos($r['email'], $filterCompany) !== false
    );
}
if ($filterStatus !== '' && in_array($filterStatus, ['sent', 'failed'], true)) {
    $logRows = array_filter($logRows, fn($r) => $r['status'] === $filterStatus);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sent Email Log</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; max-width: 900px; margin: 40px auto; color: #222; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f5f5f5; }
        .ok { color: #16a34a; font-weight: bold; }
        .fail { color: #dc2626; font-weight: bold; }
        .summary { margin-top: 10px; font-size: 15px; }
        form.filters { margin-top: 20px; display: flex; gap: 10px; align-items: center; }
        form.filters input, form.filters select {
            padding: 6px 8px; border: 1px solid #ccc; border-radius: 4px;
        }
        form.filters button {
            padding: 6px 14px; background: #2563eb; color: #fff; border: none;
            border-radius: 4px; cursor: pointer;
        }
        a.back { display: inline-block; margin-top: 24px; }
        .empty { color: #666; margin-top: 20px; }
    </style>
</head>
<body>

    <h1>Sent Email Log</h1>
    <div class="summary">
        Total sent: <strong><?= $totalSent ?></strong> &nbsp;|&nbsp;
        Total failed: <strong><?= $totalFailed ?></strong>
    </div>

    <form class="filters" method="get">
        <input type="text" name="company" placeholder="Filter by company or email"
               value="<?= htmlspecialchars($filterCompany) ?>">
        <select name="status">
            <option value="">All statuses</option>
            <option value="sent" <?= $filterStatus === 'sent' ? 'selected' : '' ?>>Sent</option>
            <option value="failed" <?= $filterStatus === 'failed' ? 'selected' : '' ?>>Failed</option>
        </select>
        <button type="submit">Filter</button>
        <a href="log.php">Clear</a>
    </form>

    <?php if (empty($logRows)): ?>
        <p class="empty">No emails logged yet.</p>
    <?php else: ?>
    <table>
        <tr>
            <th>Date/Time</th>
            <th>Company</th>
            <th>Email</th>
            <th>Subject</th>
            <th>Status</th>
            <th>Error</th>
        </tr>
        <?php foreach ($logRows as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['timestamp']) ?></td>
            <td><?= $r['name'] !== '' ? htmlspecialchars($r['name']) : '<em style="color:#999;">(no name)</em>' ?></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td><?= htmlspecialchars($r['subject']) ?></td>
            <td class="<?= $r['status'] === 'sent' ? 'ok' : 'fail' ?>">
                <?= htmlspecialchars(ucfirst($r['status'])) ?>
            </td>
            <td><?= htmlspecialchars($r['error']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <a class="back" href="index.php">&larr; Back to send form</a>
    &nbsp;|&nbsp;
    <a class="back" href="recipients.php">View already-emailed companies &rarr;</a>

</body>
</html>
