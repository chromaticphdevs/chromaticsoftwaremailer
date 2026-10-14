<?php
declare(strict_types=1);

// View-only preview of the new email design (templates/email_v2.html).
// Renders in the browser — sends nothing. Optional: ?name=Acme+Inc

$name    = trim((string) ($_GET['name'] ?? 'Acme Inc'));
$hasName = $name !== '';
$escaped = htmlspecialchars($name, ENT_QUOTES);

$html = file_get_contents(__DIR__ . '/templates/email_v2.html');

echo str_replace(
    ['{{greeting_line}}', '{{team_reference}}', '{{agent_photo_src}}'],
    [
        $hasName ? "Hello <strong>{$escaped}</strong> team," : 'Hello there,',
        $hasName ? "<strong>{$escaped}</strong>'s team" : 'your team',
        'assets/agent-mark-gonzales.jpg',
    ],
    $html
);
