<?php
declare(strict_types=1);

// Renders the exact email body for a campaign without sending anything.
// Used by the pre-send preview modal (includes/preview.php).

require __DIR__ . '/includes/env.php';
loadEnv(__DIR__ . '/.env');
require __DIR__ . '/includes/mailer.php';

$type = (string) ($_POST['type'] ?? '');
$name = trim((string) ($_POST['name'] ?? ''));

switch ($type) {
    case 'hris':
        $html = buildEmailBody($name, 'assets/agent-mark-gonzales.jpg');
        break;
    case 'profile':
        $html = buildProfileEmailBody($name);
        break;
    case 'resume':
        $html = buildResumeEmailBody($name, 'assets/profile-photo.jpg');
        break;
    case 'mailer':
        $html = buildMailerEmailBody((string) ($_POST['content'] ?? ''));
        break;
    default:
        http_response_code(400);
        $html = 'Unknown email type.';
}

header('Content-Type: text/html; charset=UTF-8');
echo $html;
