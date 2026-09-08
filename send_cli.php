<?php
declare(strict_types=1);

/**
 * Usage: php send_cli.php companies.csv "Email subject here" [--resend]
 * Recommended for large lists — runs in the terminal, no HTTP timeout risk.
 * By default, companies already emailed successfully are skipped; pass --resend to email them anyway.
 */

require __DIR__ . '/includes/env.php';
loadEnv(__DIR__ . '/.env');

require __DIR__ . '/includes/csv.php';
require __DIR__ . '/includes/mailer.php';
require __DIR__ . '/includes/log.php';
require __DIR__ . '/includes/db.php';

$args = array_slice($argv, 1);
$allowResend = in_array('--resend', $args, true);
$args = array_values(array_filter($args, fn($a) => $a !== '--resend'));

$csvPath = $args[0] ?? null;
$subject = $args[1] ?? 'Introducing Our HRIS System';

if (!$csvPath || !file_exists($csvPath)) {
    fwrite(STDERR, "Usage: php send_cli.php <path-to-companies.csv> \"<subject>\"\n");
    exit(1);
}

[$companies, $parseErrors] = parseCompaniesCsv($csvPath);

foreach ($parseErrors as $e) {
    echo "[SKIP] $e\n";
}

$mail  = createMailer();
$delay = (int) (getenv('MAIL_SEND_DELAY_MICROSECONDS') ?: 500000);

$sent = 0;
$failed = 0;
$skipped = 0;

foreach ($companies as $i => $company) {
    if (!$allowResend && hasAlreadySent($company['email'])) {
        $skipped++;
        echo "[SKIP] {$company['name']} <{$company['email']}> — already sent\n";
        continue;
    }

    [$success, $error] = sendCompanyEmail($mail, $company['name'], $company['email'], $subject);

    if ($success) {
        $sent++;
        echo "[OK]   {$company['name']} <{$company['email']}>\n";
    } else {
        $failed++;
        echo "[FAIL] {$company['name']} <{$company['email']}> — $error\n";
    }

    logSentEmail($company['name'], $company['email'], $subject, $success, $error);
    if ($success) {
        markSent($company['email'], $company['name'], $subject);
    }

    if ($delay > 0) {
        usleep($delay);
    }
}

echo "\nDone. Sent: $sent, Failed: $failed, Skipped (already sent): $skipped, Skipped (bad rows): " . count($parseErrors) . "\n";
