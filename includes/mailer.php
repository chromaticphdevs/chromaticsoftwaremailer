<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Build and configure a PHPMailer instance from .env credentials.
 */
function createMailer(): PHPMailer
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = getenv('MAIL_HOST');
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('MAIL_USERNAME');
    $mail->Password   = getenv('MAIL_PASSWORD');
    $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') ?: PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int) (getenv('MAIL_PORT') ?: 587);

    $mail->setFrom(
        getenv('MAIL_FROM_ADDRESS'),
        getenv('MAIL_FROM_NAME') ?: getenv('MAIL_FROM_ADDRESS')
    );

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';

    return $mail;
}

/**
 * Load the HTML email template and swap in company-name-aware wording.
 * Falls back to generic phrasing when no company name is available.
 */
function buildEmailBody(string $companyName): string
{
    $template = file_get_contents(__DIR__ . '/../templates/email.html');
    $hasName  = trim($companyName) !== '';
    $escaped  = htmlspecialchars($companyName, ENT_QUOTES);

    $greetingLine = $hasName
        ? "Hello <strong>{$escaped}</strong> team,"
        : "Hello there,";

    $teamReference = $hasName
        ? "<strong>{$escaped}</strong>'s team"
        : "your team";

    return str_replace(
        ['{{greeting_line}}', '{{team_reference}}'],
        [$greetingLine, $teamReference],
        $template
    );
}

/**
 * Plain-text greeting used for the AltBody fallback.
 */
function buildPlainGreeting(string $companyName): string
{
    return trim($companyName) !== '' ? "Hello {$companyName}," : "Hello there,";
}

/**
 * Send a single email to one company. Returns [success(bool), errorMessage(?string)].
 */
function sendCompanyEmail(PHPMailer $mail, string $companyName, string $companyEmail, string $subject): array
{
    try {
        $mail->clearAddresses();
        $mail->clearAttachments();

        $mail->addAddress($companyEmail, $companyName);
        $mail->Subject = $subject;
        $mail->Body    = buildEmailBody($companyName);
        $mail->AltBody = buildPlainGreeting($companyName) . "\n\n" .
            "We're Chromatic Softwares — we design and build custom software for small and " .
            "medium businesses. We'd like to introduce our Timekeeping, Attendance & HRIS " .
            "system: real-time timekeeping, shift management, a mobile app with geolocation " .
            "time-in/time-out, payroll batching with online payslips, and multi-branch support " .
            "— all in one platform.\n\n" .
            "Learn more: https://chromaticph.com/solutions/timekeeping-hris\n\n" .
            "Reply to this email if you'd like a quick walkthrough.\n\n" .
            "Best regards,\n" .
            "The Chromatic Softwares Team\n" .
            "chromaticsoftwares@gmail.com | +63 976 235 2221 | Metro Manila, Philippines\n" .
            "https://chromaticph.com/";

        $mail->send();
        return [true, null];
    } catch (Exception $e) {
        return [false, $mail->ErrorInfo];
    }
}
