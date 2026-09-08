<?php
declare(strict_types=1);

require __DIR__ . '/includes/env.php';
loadEnv(__DIR__ . '/.env');

require __DIR__ . '/includes/mailer.php';
require __DIR__ . '/includes/log.php';
require __DIR__ . '/includes/db.php';

const PROFILE_CAMPAIGN = 'company_profile';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$recipientName  = trim((string) ($_POST['recipient_name'] ?? ''));
$recipientEmail = trim((string) ($_POST['recipient_email'] ?? ''));
$subject        = trim((string) ($_POST['subject'] ?? 'Chromatic Softwares — Company Profile'));
$allowResend    = !empty($_POST['resend']);

if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    die('Please provide a valid recipient email. <a href="profile.php">Go back</a>');
}

$skipped = false;
$success = null;
$error   = null;

if (!$allowResend && hasAlreadySent($recipientEmail, PROFILE_CAMPAIGN)) {
    $skipped = true;
} else {
    $mail = createMailer();
    [$success, $error] = sendCompanyProfileEmail($mail, $recipientName, $recipientEmail, $subject);

    logSentEmail($recipientName, $recipientEmail, $subject, $success, $error);
    if ($success) {
        markSent($recipientEmail, $recipientName, $subject, PROFILE_CAMPAIGN);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Results</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; max-width: 560px; margin: 60px auto; color: #222; }
        .ok { color: #16a34a; font-weight: bold; }
        .fail { color: #dc2626; font-weight: bold; }
        .skip { color: #b45309; font-weight: bold; }
        .box { margin-top: 16px; padding: 16px; border-radius: 6px; background: #f5f5f5; font-size: 14px; }
        a.back { display: inline-block; margin-top: 24px; }
    </style>
</head>
<body>

    <h1>Send Results</h1>

    <div class="box">
        <p>
            To: <strong><?= htmlspecialchars($recipientEmail) ?></strong>
            <?= $recipientName !== '' ? '(' . htmlspecialchars($recipientName) . ')' : '' ?>
        </p>
        <?php if ($skipped): ?>
            <p class="skip">Skipped — a company profile email was already sent to this recipient.</p>
        <?php elseif ($success): ?>
            <p class="ok">Sent successfully, with the company profile PDF attached.</p>
        <?php else: ?>
            <p class="fail">Failed to send.</p>
            <p><?= htmlspecialchars((string) $error) ?></p>
        <?php endif; ?>
    </div>

    <a class="back" href="profile.php">&larr; Send another company profile</a>
    &nbsp;|&nbsp;
    <a class="back" href="log.php">View full send log &rarr;</a>

</body>
</html>
