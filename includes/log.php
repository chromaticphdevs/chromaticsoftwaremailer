<?php
/**
 * Simple CSV-based send log. Appends one row per email attempt.
 * File: logs/sent_log.csv (created automatically, git-ignored).
 */

define('SENT_LOG_PATH', __DIR__ . '/../logs/sent_log.csv');

function logSentEmail(string $companyName, string $companyEmail, string $subject, bool $success, ?string $error = null): void
{
    $dir = dirname(SENT_LOG_PATH);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $isNewFile = !file_exists(SENT_LOG_PATH);

    $handle = fopen(SENT_LOG_PATH, 'a');
    if ($handle === false) {
        return; // don't block sending if logging fails
    }

    if ($isNewFile) {
        fputcsv($handle, ['timestamp', 'company_name', 'email', 'subject', 'status', 'error']);
    }

    fputcsv($handle, [
        date('Y-m-d H:i:s'),
        $companyName,
        $companyEmail,
        $subject,
        $success ? 'sent' : 'failed',
        $error ?? '',
    ]);

    fclose($handle);
}

/**
 * Read the full send log, most recent first.
 */
function getSentLog(): array
{
    if (!file_exists(SENT_LOG_PATH)) {
        return [];
    }

    $rows = [];
    $handle = fopen(SENT_LOG_PATH, 'r');
    $header = fgetcsv($handle); // skip header

    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) < 6) {
            continue;
        }
        $rows[] = [
            'timestamp' => $data[0],
            'name'      => $data[1],
            'email'     => $data[2],
            'subject'   => $data[3],
            'status'    => $data[4],
            'error'     => $data[5],
        ];
    }

    fclose($handle);

    return array_reverse($rows);
}
