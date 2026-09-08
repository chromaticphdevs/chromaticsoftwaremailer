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
 * Build and configure a PHPMailer instance from the PERSONAL_MAIL_* .env credentials.
 * Separate account from createMailer() — used only for the resume-blast page.
 */
function createPersonalMailer(): PHPMailer
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = getenv('PERSONAL_MAIL_HOST');
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('PERSONAL_MAIL_USERNAME');
    $mail->Password   = getenv('PERSONAL_MAIL_PASSWORD');
    $mail->SMTPSecure = getenv('PERSONAL_MAIL_ENCRYPTION') ?: PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int) (getenv('PERSONAL_MAIL_PORT') ?: 587);

    $mail->setFrom(
        getenv('PERSONAL_MAIL_FROM_ADDRESS'),
        getenv('PERSONAL_MAIL_FROM_NAME') ?: getenv('PERSONAL_MAIL_FROM_ADDRESS')
    );

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';

    return $mail;
}

/**
 * Load the company profile email template and swap in company-name-aware wording.
 */
function buildProfileEmailBody(string $recipientName): string
{
    $template = file_get_contents(__DIR__ . '/../templates/profile_email.html');
    $hasName  = trim($recipientName) !== '';
    $escaped  = htmlspecialchars($recipientName, ENT_QUOTES);

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

define('COMPANY_PROFILE_PDF_PATH', __DIR__ . '/../assets/Chromatic-Softwares-Company-Profile.pdf');

/**
 * Send the company profile email (with the PDF profile attached) to one recipient.
 * Returns [success(bool), errorMessage(?string)].
 */
function sendCompanyProfileEmail(PHPMailer $mail, string $recipientName, string $recipientEmail, string $subject): array
{
    try {
        $mail->clearAddresses();
        $mail->clearAttachments();

        $mail->addAddress($recipientEmail, $recipientName);
        $mail->Subject = $subject;
        $mail->Body    = buildProfileEmailBody($recipientName);
        $mail->AltBody = buildPlainGreeting($recipientName) . "\n\n" .
            "We're Chromatic Softwares, a software development company based in Metro Manila, " .
            "Philippines. We design and build custom software solutions for small and medium " .
            "businesses — from web and mobile applications to internal business systems, including " .
            "our Timekeeping, Attendance & HRIS system.\n\n" .
            "Our company profile is attached to this email.\n\n" .
            "Reply to this email if you'd like to talk further.\n\n" .
            "Best regards,\n" .
            "The Chromatic Softwares Team\n" .
            "chromaticsoftwares@gmail.com | +63 976 235 2221 | Metro Manila, Philippines\n" .
            "https://chromaticph.com/";

        if (is_file(COMPANY_PROFILE_PDF_PATH)) {
            $mail->addAttachment(COMPANY_PROFILE_PDF_PATH, 'Chromatic Softwares - Company Profile.pdf');
        }

        $mail->send();
        return [true, null];
    } catch (Exception $e) {
        return [false, $mail->ErrorInfo];
    }
}

/**
 * Load the resume email template and swap in company-name-aware wording plus sender name.
 */
function buildResumeEmailBody(string $companyName): string
{
    $template = file_get_contents(__DIR__ . '/../templates/resume_email.html');
    $hasName  = trim($companyName) !== '';
    $escaped  = htmlspecialchars($companyName, ENT_QUOTES);

    $greetingLine = $hasName
        ? "Hello <strong>{$escaped}</strong> team,"
        : "Hello,";

    $teamReference = $hasName
        ? "<strong>{$escaped}</strong>'s team"
        : "your team";

    $senderName = htmlspecialchars(getenv('PERSONAL_MAIL_FROM_NAME') ?: 'the applicant', ENT_QUOTES);

    return str_replace(
        ['{{greeting_line}}', '{{team_reference}}', '{{sender_name}}'],
        [$greetingLine, $teamReference, $senderName],
        $template
    );
}

define('RESUME_PDF_PATH', __DIR__ . '/../assets/COMPLETE_RESUME.pdf');

/**
 * Send the resume email (with resume PDF attached) to one company, using the
 * personal mailer account. Returns [success(bool), errorMessage(?string)].
 */
function sendResumeEmail(PHPMailer $mail, string $companyName, string $companyEmail, string $subject): array
{
    try {
        $mail->clearAddresses();
        $mail->clearAttachments();

        $senderName = getenv('PERSONAL_MAIL_FROM_NAME') ?: 'the applicant';

        $mail->addAddress($companyEmail, $companyName);
        $mail->Subject = $subject;
        $mail->Body    = buildResumeEmailBody($companyName);
        $teamReference = $companyName !== '' ? "{$companyName}'s team" : 'your team';

        $mail->AltBody = ($companyName !== '' ? "Hello {$companyName} team," : "Hello,") . "\n\n" .
            "I hope this message finds you well. I'm reaching out to express my interest in any " .
            "software development opportunities {$teamReference} may have available.\n\n" .
            "I have 7 years of software development experience, working with different companies and " .
            "teams as a contractor. I also spent 2 years as an employee of an international company " .
            "that develops crash management software used across the different states in the USA.\n\n" .
            "I've also had the opportunity to work with Japanese companies. Although our team was mainly " .
            "Filipino, I learned a great deal from the Japanese work ethic, particularly their emphasis " .
            "on discipline, quality, responsibility, and continuous improvement — values that I continue " .
            "to carry in my work today.\n\n" .
            "I'm currently looking for a software development opportunity where I can contribute to " .
            "building and improving business software applications, whether web or mobile.\n\n" .
            "I've attached my resume for your review. I would be happy to discuss how my experience and " .
            "skills could contribute to your team.\n\n" .
            "Best regards,\n" .
            $senderName;

        if (is_file(RESUME_PDF_PATH)) {
            $mail->addAttachment(RESUME_PDF_PATH, $senderName . ' - Resume.pdf');
        }

        $mail->send();
        return [true, null];
    } catch (Exception $e) {
        return [false, $mail->ErrorInfo];
    }
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
