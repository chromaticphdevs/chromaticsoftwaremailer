<?php
require __DIR__ . '/includes/db.php';

$campaignLabels = [
    ''                => 'All campaigns',
    'hris_intro'      => 'HRIS Intro',
    'company_profile' => 'Company Profile',
    'resume'          => 'Resume',
];

$filterCampaign = trim((string) ($_GET['campaign'] ?? ''));
$recipients = getAlreadySentRecipients($filterCampaign !== '' ? $filterCampaign : null);

$filter = trim((string) ($_GET['q'] ?? ''));
if ($filter !== '') {
    $recipients = array_filter($recipients, fn($r) =>
        stripos($r['email'], $filter) !== false ||
        stripos($r['company_name'], $filter) !== false
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Already-Emailed Companies</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; max-width: 900px; margin: 40px auto; color: #222; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f5f5f5; }
        .summary { margin-top: 10px; font-size: 15px; }
        form.filters { margin-top: 20px; display: flex; gap: 10px; align-items: center; }
        form.filters input {
            padding: 6px 8px; border: 1px solid #ccc; border-radius: 4px; flex: 1;
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

    <h1>Already-Emailed Companies</h1>
    <div class="summary">
        Unique recipients: <strong><?= count($recipients) ?></strong>
    </div>

    <form class="filters" method="get">
        <input type="text" name="q" placeholder="Filter by company or email"
               value="<?= htmlspecialchars($filter) ?>">
        <select name="campaign">
            <?php foreach ($campaignLabels as $value => $label): ?>
                <option value="<?= htmlspecialchars($value) ?>" <?= $filterCampaign === $value ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filter</button>
        <a href="recipients.php">Clear</a>
    </form>

    <?php if (empty($recipients)): ?>
        <p class="empty">No one has been emailed yet.</p>
    <?php else: ?>
    <table>
        <tr>
            <th>Company</th>
            <th>Email</th>
            <th>Campaign</th>
            <th>Times Sent</th>
            <th>First Sent</th>
            <th>Last Sent</th>
            <th>Last Subject</th>
        </tr>
        <?php foreach ($recipients as $r): ?>
        <tr>
            <td><?= $r['company_name'] !== '' ? htmlspecialchars($r['company_name']) : '<em style="color:#999;">(no name)</em>' ?></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td><?= htmlspecialchars($campaignLabels[$r['campaign']] ?? $r['campaign']) ?></td>
            <td><?= (int) $r['times_sent'] ?></td>
            <td><?= htmlspecialchars($r['first_sent_at']) ?></td>
            <td><?= htmlspecialchars($r['last_sent_at']) ?></td>
            <td><?= htmlspecialchars($r['last_subject']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <a class="back" href="index.php">&larr; Back to send form</a>
    &nbsp;|&nbsp;
    <a class="back" href="log.php">View full send log &rarr;</a>

</body>
</html>
