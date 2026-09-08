<?php
/**
 * Parse a companies CSV file into a clean array of ['name' => ..., 'email' => ...].
 * Expects columns: company_name, email (header optional — auto-detected).
 * Returns [rows(array), errors(array of strings for skipped/bad rows)].
 */
function parseCompaniesCsv(string $filePath): array
{
    $rows   = [];
    $errors = [];

    $handle = fopen($filePath, 'r');
    if ($handle === false) {
        return [[], ['Could not open the uploaded CSV file.']];
    }

    $lineNumber = 0;
    $first      = true;

    while (($data = fgetcsv($handle)) !== false) {
        $lineNumber++;

        // Skip completely blank lines
        if (count($data) === 1 && trim((string) $data[0]) === '') {
            continue;
        }

        $col0 = trim((string) ($data[0] ?? ''));
        $col1 = trim((string) ($data[1] ?? ''));

        // Auto-detect and skip a header row like "company_name,email"
        if ($first) {
            $first = false;
            if (strcasecmp($col0, 'company_name') === 0 ||
                strcasecmp($col0, 'company') === 0 ||
                strcasecmp($col1, 'email') === 0) {
                continue;
            }
        }

        if ($col1 === '') {
            $errors[] = "Line $lineNumber: missing email — skipped.";
            continue;
        }

        if (!filter_var($col1, FILTER_VALIDATE_EMAIL)) {
            $label = $col0 !== '' ? "\"$col0\"" : '(no company name)';
            $errors[] = "Line $lineNumber: invalid email \"$col1\" for $label — skipped.";
            continue;
        }

        // company name is optional — empty string is fine, mailer.php handles the fallback wording
        $rows[] = ['name' => $col0, 'email' => $col1];
    }

    fclose($handle);

    return [$rows, $errors];
}
