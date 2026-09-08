<?php
/**
 * Local SQLite database (single file, no server needed).
 * File: logs/app.sqlite (created automatically, git-ignored).
 */

define('DB_PATH', __DIR__ . '/../logs/app.sqlite');

const DEFAULT_CAMPAIGN = 'hris_intro';

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
            email         TEXT NOT NULL,
            campaign      TEXT NOT NULL DEFAULT "' . DEFAULT_CAMPAIGN . '",
            company_name  TEXT NOT NULL DEFAULT "",
            last_subject  TEXT NOT NULL DEFAULT "",
            times_sent    INTEGER NOT NULL DEFAULT 0,
            first_sent_at TEXT NOT NULL,
            last_sent_at  TEXT NOT NULL,
            PRIMARY KEY (email, campaign)
        )
    ');

    migrateSentEmailsTable($pdo);

    return $pdo;
}

/**
 * Older databases have a "sent_emails" table keyed by email alone (single campaign,
 * the original HRIS intro blast). Rebuild it with a (email, campaign) key so a
 * different campaign — e.g. the company profile email — isn't blocked as a duplicate,
 * while preserving every existing row as the "hris_intro" campaign.
 */
function migrateSentEmailsTable(PDO $pdo): void
{
    $columns = array_column($pdo->query('PRAGMA table_info(sent_emails)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (in_array('campaign', $columns, true)) {
        return;
    }

    $existingRows = $pdo->query('SELECT * FROM sent_emails')->fetchAll(PDO::FETCH_ASSOC);

    $pdo->exec('DROP TABLE sent_emails');
    $pdo->exec('
        CREATE TABLE sent_emails (
            email         TEXT NOT NULL,
            campaign      TEXT NOT NULL DEFAULT "' . DEFAULT_CAMPAIGN . '",
            company_name  TEXT NOT NULL DEFAULT "",
            last_subject  TEXT NOT NULL DEFAULT "",
            times_sent    INTEGER NOT NULL DEFAULT 0,
            first_sent_at TEXT NOT NULL,
            last_sent_at  TEXT NOT NULL,
            PRIMARY KEY (email, campaign)
        )
    ');

    if (empty($existingRows)) {
        return;
    }

    $insert = $pdo->prepare('
        INSERT INTO sent_emails (email, campaign, company_name, last_subject, times_sent, first_sent_at, last_sent_at)
        VALUES (:email, "' . DEFAULT_CAMPAIGN . '", :name, :subject, :times, :first, :last)
    ');
    foreach ($existingRows as $row) {
        $insert->execute([
            'email'   => $row['email'],
            'name'    => $row['company_name'],
            'subject' => $row['last_subject'],
            'times'   => $row['times_sent'],
            'first'   => $row['first_sent_at'],
            'last'    => $row['last_sent_at'],
        ]);
    }
}

/**
 * True if this email has at least one successful send on record for the given campaign.
 */
function hasAlreadySent(string $email, string $campaign = DEFAULT_CAMPAIGN): bool
{
    $stmt = getDb()->prepare('SELECT 1 FROM sent_emails WHERE email = :email AND campaign = :campaign LIMIT 1');
    $stmt->execute(['email' => strtolower(trim($email)), 'campaign' => $campaign]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Record a successful send (insert or bump the counter for an existing recipient).
 */
function markSent(string $email, string $companyName, string $subject, string $campaign = DEFAULT_CAMPAIGN): void
{
    $email = strtolower(trim($email));
    $now   = date('Y-m-d H:i:s');

    $stmt = getDb()->prepare('
        INSERT INTO sent_emails (email, campaign, company_name, last_subject, times_sent, first_sent_at, last_sent_at)
        VALUES (:email, :campaign, :name, :subject, 1, :now, :now)
        ON CONFLICT(email, campaign) DO UPDATE SET
            company_name = excluded.company_name,
            last_subject = excluded.last_subject,
            times_sent   = times_sent + 1,
            last_sent_at = excluded.last_sent_at
    ');
    $stmt->execute(['email' => $email, 'campaign' => $campaign, 'name' => $companyName, 'subject' => $subject, 'now' => $now]);
}

/**
 * All recipients that have ever received an email, most recently sent first.
 * Pass a campaign to filter to just that campaign.
 */
function getAlreadySentRecipients(?string $campaign = null): array
{
    if ($campaign !== null) {
        $stmt = getDb()->prepare('SELECT * FROM sent_emails WHERE campaign = :campaign ORDER BY last_sent_at DESC');
        $stmt->execute(['campaign' => $campaign]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmt = getDb()->query('SELECT * FROM sent_emails ORDER BY last_sent_at DESC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
