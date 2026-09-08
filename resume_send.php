<?php
declare(strict_types=1);

set_time_limit(0); // sending many emails over HTTP can take a while

require __DIR__ . '/includes/env.php';
loadEnv(__DIR__ . '/.env');

require __DIR__ . '/includes/csv.php';
require __DIR__ . '/includes/mailer.php';
require __DIR__ . '/includes/log.php';
require __DIR__ . '/includes/db.php';

const RESUME_CAMPAIGN = 'resume';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: resume.php');
    exit;
}

if (!getenv('PERSONAL_MAIL_USERNAME') || !getenv('PERSONAL_MAIL_PASSWORD') || !getenv('PERSONAL_MAIL_FROM_ADDRESS')) {
    die('PERSONAL_MAIL_* credentials are not fully set in .env yet. <a href="resume.php">Go back</a>');
}

$subject = trim((string) ($_POST['subject'] ?? 'Application for Software Development Opportunity'));
$allowResend = !empty($_POST['resend']);

$sendMode = ($_POST['send_mode'] ?? 'csv') === 'single' ? 'single' : 'csv';
$recipientEmail = trim((string) ($_POST['recipient_email'] ?? ''));

if ($sendMode === 'single' && $recipientEmail !== '') {
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        die('Please provide a valid recipient email. <a href="resume.php">Go back</a>');
    }
    $companies = [['name' => trim((string) ($_POST['recipient_name'] ?? '')), 'email' => $recipientEmail]];
    $parseErrors = [];
} else {
    if (empty($_FILES['csv_file']['tmp_name'])) {
        header('Location: resume.php');
        exit;
    }
    if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        die('Upload failed. Please try again.');
    }
    [$companies, $parseErrors] = parseCompaniesCsv($_FILES['csv_file']['tmp_name']);
}

$mail  = createPersonalMailer();
$delay = (int) (getenv('MAIL_SEND_DELAY_MICROSECONDS') ?: 500000);

$results = [];
$sentCount = 0;
$failCount = 0;
$skippedCount = 0;

foreach ($companies as $company) {
    if (!$allowResend && hasAlreadySent($company['email'], RESUME_CAMPAIGN)) {
        $results[] = [
            'name'    => $company['name'],
            'email'   => $company['email'],
            'success' => null, // skipped, not sent or failed
            'error'   => 'Already sent previously',
        ];
        $skippedCount++;
        continue;
    }

    [$success, $error] = sendResumeEmail($mail, $company['name'], $company['email'], $subject);

    $results[] = [
        'name'    => $company['name'],
        'email'   => $company['email'],
        'success' => $success,
        'error'   => $error,
    ];

    $success ? $sentCount++ : $failCount++;

    logSentEmail($company['name'], $company['email'], $subject, $success, $error);
    if ($success) {
        markSent($company['email'], $company['name'], $subject, RESUME_CAMPAIGN);
    }

    if ($delay > 0) {
        usleep($delay); // small pause between sends to be gentle on the SMTP server
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Results</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; max-width: 720px; margin: 40px auto; color: #222; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f5f5f5; }
        .ok { color: #16a34a; font-weight: bold; }
        .fail { color: #dc2626; font-weight: bold; }
        .skip { color: #b45309; font-weight: bold; }
        .summary { margin-top: 10px; font-size: 15px; }
        .errors { margin-top: 20px; color: #b45309; font-size: 13px; }
        a.back { display: inline-block; margin-top: 24px; }
    </style>
</head>
<body>

    <h1>Send Results</h1>
    <div class="summary">
        Sent: <strong><?= $sentCount ?></strong> &nbsp;|&nbsp;
        Failed: <strong><?= $failCount ?></strong> &nbsp;|&nbsp;
        Skipped (already sent): <strong><?= $skippedCount ?></strong> &nbsp;|&nbsp;
        Skipped (bad rows): <strong><?= count($parseErrors) ?></strong>
    </div>

    <table>
        <tr><th>Company</th><th>Email</th><th>Status</th><th>Detail</th></tr>
        <?php foreach ($results as $r): ?>
        <tr>
            <td><?= $r['name'] !== '' ? htmlspecialchars($r['name']) : '<em style="color:#999;">(no name)</em>' ?></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td class="<?= $r['success'] === null ? 'skip' : ($r['success'] ? 'ok' : 'fail') ?>">
                <?= $r['success'] === null ? 'Skipped' : ($r['success'] ? 'Sent' : 'Failed') ?>
            </td>
            <td><?= htmlspecialchars((string) $r['error']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php if ($parseErrors): ?>
    <div class="errors">
        <strong>Skipped rows:</strong>
        <ul>
            <?php foreach ($parseErrors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <a class="back" href="resume.php">&larr; Send another batch</a>
    &nbsp;|&nbsp;
    <a class="back" href="log.php">View full sent log &rarr;</a>

</body>
</html>
