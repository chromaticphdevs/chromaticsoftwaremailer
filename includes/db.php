<?php
/**
 * Local SQLite database (single file, no server needed).
 * File: logs/app.sqlite (created automatically, git-ignored).
 */

define('DB_PATH', __DIR__ . '/../logs/app.sqlite');

function getDb(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS sent_emails (
            email         TEXT PRIMARY KEY,
            company_name  TEXT NOT NULL DEFAULT "",
            last_subject  TEXT NOT NULL DEFAULT "",
            times_sent    INTEGER NOT NULL DEFAULT 0,
            first_sent_at TEXT NOT NULL,
            last_sent_at  TEXT NOT NULL
        )
    ');

    return $pdo;
}

/**
 * True if this email has at least one successful send on record.
 */
function hasAlreadySent(string $email): bool
{
    $stmt = getDb()->prepare('SELECT 1 FROM sent_emails WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => strtolower(trim($email))]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Record a successful send (insert or bump the counter for an existing recipient).
 */
function markSent(string $email, string $companyName, string $subject): void
{
    $email = strtolower(trim($email));
    $now   = date('Y-m-d H:i:s');

    $stmt = getDb()->prepare('
        INSERT INTO sent_emails (email, company_name, last_subject, times_sent, first_sent_at, last_sent_at)
        VALUES (:email, :name, :subject, 1, :now, :now)
        ON CONFLICT(email) DO UPDATE SET
            company_name = excluded.company_name,
            last_subject = excluded.last_subject,
            times_sent   = times_sent + 1,
            last_sent_at = excluded.last_sent_at
    ');
    $stmt->execute(['email' => $email, 'name' => $companyName, 'subject' => $subject, 'now' => $now]);
}

/**
 * All recipients that have ever received an email, most recently sent first.
 */
function getAlreadySentRecipients(): array
{
    $stmt = getDb()->query('SELECT * FROM sent_emails ORDER BY last_sent_at DESC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
