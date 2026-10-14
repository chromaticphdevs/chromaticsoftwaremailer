<?php
declare(strict_types=1);

require __DIR__ . '/includes/env.php';
loadEnv(__DIR__ . '/.env');

require __DIR__ . '/includes/mailer.php';
require __DIR__ . '/includes/log.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mailer.php');
    exit;
}

$recipientName  = trim((string) ($_POST['recipient_name'] ?? ''));
$recipientEmail = trim((string) ($_POST['recipient_email'] ?? ''));
$subject        = trim((string) ($_POST['subject'] ?? ''));
$content        = (string) ($_POST['content'] ?? '');

if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    die('Please provide a valid recipient email. <a href="mailer.php">Go back</a>');
}
if ($subject === '') {
    die('Please provide a subject. <a href="mailer.php">Go back</a>');
}

// Build the attachment list straight from PHP's own request-scoped upload temp
// files ($_FILES tmp_name) — nothing here is ever copied into a project folder,
// so no attachment is persisted on the server beyond this single request.
$attachments = [];
if (!empty($_FILES['attachments']['name'][0] ?? null)) {
    foreach ($_FILES['attachments']['name'] as $i => $name) {
        if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        $attachments[] = [
            'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
            'name'     => $name,
        ];
    }
}

$mail = createMailer();
[$success, $error] = sendMailerEmail($mail, $recipientEmail, $recipientName, $subject, $content, $attachments);

logSentEmail($recipientName, $recipientEmail, $subject, $success, $error);
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
        <?php if ($success): ?>
            <p class="ok">Sent successfully<?= $attachments ? ' with ' . count($attachments) . ' attachment(s).' : '.' ?></p>
        <?php else: ?>
            <p class="fail">Failed to send.</p>
            <p><?= htmlspecialchars((string) $error) ?></p>
        <?php endif; ?>
    </div>

    <a class="back" href="mailer.php">&larr; Write another email</a>
    &nbsp;|&nbsp;
    <a class="back" href="log.php">View full send log &rarr;</a>

    <?php if ($success): ?>
    <script>
        try { localStorage.removeItem('mailerDraft'); } catch (e) {}
    </script>
    <?php endif; ?>

</body>
</html>
