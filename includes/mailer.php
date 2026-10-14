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
define('AGENT_PHOTO_PATH', __DIR__ . '/../assets/agent-mark-gonzales.jpg');

function buildEmailBody(string $companyName, string $agentPhotoSrc = 'cid:agent_photo'): string
{
    $template = file_get_contents(__DIR__ . '/../templates/email_v2.html');
    $hasName  = trim($companyName) !== '';
    $escaped  = htmlspecialchars($companyName, ENT_QUOTES);

    $greetingLine = $hasName
        ? "Hello <strong>{$escaped}</strong> team,"
        : "Hello there,";

    $teamReference = $hasName
        ? "<strong>{$escaped}</strong>'s team"
        : "your team";

    return str_replace(
        ['{{greeting_line}}', '{{team_reference}}', '{{agent_photo_src}}'],
        [$greetingLine, $teamReference, $agentPhotoSrc],
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
function buildResumeEmailBody(string $companyName, string $photoSrc = 'cid:profile-photo'): string
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
        ['{{greeting_line}}', '{{team_reference}}', '{{sender_name}}', '{{photo_src}}'],
        [$greetingLine, $teamReference, $senderName, $photoSrc],
        $template
    );
}

define('RESUME_PDF_PATH', __DIR__ . '/../assets/COMPLETE_RESUME.pdf');
define('RESUME_PHOTO_PATH', __DIR__ . '/../assets/profile-photo.jpg');

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
        if (is_file(RESUME_PHOTO_PATH)) {
            $mail->addEmbeddedImage(RESUME_PHOTO_PATH, 'profile-photo', 'profile-photo.jpg');
        }
        $teamReference = $companyName !== '' ? "{$companyName}'s team" : 'your team';

        $mail->AltBody = ($companyName !== '' ? "Hello {$companyName} team," : "Hello,") . "\n\n" .
            "I'm reaching out to inquire about software development opportunities with {$teamReference}. " .
            "I have seven years of experience developing and maintaining business applications, working " .
            "with different companies as a contractor and as part of an international software team.\n\n" .
            "My experience spans web and mobile development, backend services, API integrations, database " .
            "design, and business systems such as HR and payroll, property management, and transportation " .
            "applications. I also spent two years with an international company developing crash-management " .
            "software used across multiple states in the United States.\n\n" .
            "Working with Japanese companies also helped reinforce the importance of discipline, quality, " .
            "accountability, and continuous improvement in software development.\n\n" .
            "I'm looking for an opportunity where I can contribute to building reliable, practical software " .
            "that solves real business problems. I've attached my resume for your consideration and would " .
            "welcome the chance to discuss how my experience could support {$teamReference}.\n\n" .
            "Thank you for your time.\n\n" .
            "Best regards,\n" .
            $senderName . "\n" .
            "gonzalesmarkangeloph@gmail.com | 0906 338 7451";

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
 * Wrap freeform rich-text HTML (from the custom mailer page) inside the shared
 * email chrome (header/footer). No greeting/company-name substitution — the
 * content is exactly what was typed into the editor.
 */
function buildMailerEmailBody(string $contentHtml): string
{
    $template = file_get_contents(__DIR__ . '/../templates/mailer_email.html');

    // Strip anything that shouldn't ride along in an email body / doesn't belong
    // coming from a rich-text editor.
    $safeContent = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $contentHtml) ?? $contentHtml;

    return str_replace('{{content}}', $safeContent, $template);
}

/**
 * Send a one-off custom email (recipient + subject + rich-text body) built via
 * the mailer page, with optional attachments.
 *
 * $attachments is a list of ['tmp_name' => string, 'name' => string] pairs —
 * typically taken straight from $_FILES, so PHP's own request-scoped temp file
 * is what gets attached. Nothing is ever copied into a project folder, so no
 * attachment is persisted on the server beyond the single send request.
 *
 * Returns [success(bool), errorMessage(?string)].
 */
function sendMailerEmail(
    PHPMailer $mail,
    string $recipientEmail,
    string $recipientName,
    string $subject,
    string $contentHtml,
    array $attachments = []
): array {
    try {
        $mail->clearAddresses();
        $mail->clearAttachments();

        $mail->addAddress($recipientEmail, $recipientName);
        $mail->Subject = $subject;
        $mail->Body    = buildMailerEmailBody($contentHtml);
        $mail->AltBody = trim(strip_tags($contentHtml));

        foreach ($attachments as $attachment) {
            if (is_file($attachment['tmp_name'])) {
                $mail->addAttachment($attachment['tmp_name'], $attachment['name']);
            }
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
        if (is_file(AGENT_PHOTO_PATH)) {
            $mail->addEmbeddedImage(AGENT_PHOTO_PATH, 'agent_photo', 'mark-gonzales.jpg');
        }
        $mail->AltBody = buildPlainGreeting($companyName) . "

" .
            "Your people. Your process. Timekeeping, attendance, and payroll — connected.

" .
            "We're Chromatic Softwares — we help small and medium businesses digitalize and " .
            "simplify their day-to-day operations. Our HRIS brings employee timekeeping, " .
            "attendance review and payroll processing into one connected solution, customizable " .
            "to the way your business operates.

" .
            "What you can do:
" .
            "- GPS geotagging for attendance records
" .
            "- Selfie and QR code clock-in / clock-out
" .
            "- Biometric-ready, real-time timekeeping
" .
            "- Multi-branch timekeeping from one dashboard
" .
            "- Payroll batching with online payslips, bonuses, government contributions and loans
" .
            "- Mobile access for employees, including payslips

" .
            "Learn more: https://chromaticph.com/solutions/timekeeping-hris

" .
            "Reply to this email and we'll set up a quick walkthrough.

" .
            "Best regards,
" .
            "The Chromatic Softwares Team
" .
            "Mark Gonzales, Solutions Architect (HRIS Agent)
" .
            "https://www.facebook.com/chromaticph

" .
            "chromaticsoftwares@gmail.com | +63 976 235 2221
" .
            "https://chromaticph.com/";

        $mail->send();
        return [true, null];
    } catch (Exception $e) {
        return [false, $mail->ErrorInfo];
    }
}
