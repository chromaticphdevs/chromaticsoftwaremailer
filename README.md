# HRIS Email Blaster

A basic PHP tool that reads a CSV of companies (name + email) and sends each one
an email introducing your HRIS system.

## 1. Install dependencies

This uses [PHPMailer](https://github.com/PHPMailer/PHPMailer) for reliable SMTP sending.

```bash
composer install
```

If you don't have Composer, install it first: https://getcomposer.org

## 2. Configure your mailer credentials

```bash
cp .env.example .env
```

Edit `.env` and fill in your real SMTP details:

```
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Your Company HRIS Team"
```

**Never commit `.env`** — it's already in `.gitignore`.

Notes on credentials:
- **Gmail**: you need an **App Password**, not your normal login password
  (requires 2-Step Verification enabled on the Google account).
- **Other providers** (Outlook/Office365, SendGrid, Mailgun, your own SMTP server):
  use the SMTP host/port/credentials they give you — the code doesn't change.

## 3. Prepare your CSV

Two columns: `company_name,email`. A header row is optional (auto-detected).
**Only the email is required** — if `company_name` is blank, the email still sends,
using generic wording ("Hello there," / "walk your team through...") instead of the
company name.

```csv
company_name,email
Acme Corporation,contact@acme.example
Globex Inc,hr@globex.example
,recruitment@initech.example
```

See `sample_companies.csv` for reference.

## 4. Edit the email content

`templates/email.html` is already branded for **Chromatic Softwares** — it uses your
site's blue (`#1d4ed8`), logo, tagline, and contact details (email, phone, Metro Manila,
website, Facebook), plus a summary of the Timekeeping, Attendance & HRIS features and a
"See the HRIS System" button linking to `chromaticph.com/solutions/timekeeping-hris`.

It has one placeholder, `{{company_name}}`, swapped in per recipient. Edit the copy
directly in that file if you want to tweak the pitch.

## 5. Send

### Option A — Web form (good for small/medium lists)

```bash
php -S localhost:8000
```

Open http://localhost:8000, upload your CSV, set a subject, and click Send.
Note: very large lists sent over HTTP can hit browser/server timeouts.

### Option B — Command line (recommended for large lists)

```bash
php send_cli.php sample_companies.csv "Introducing Our HRIS System"
```

This runs with no timeout limit and prints a live log of each send.

## Viewing sent emails

Every send attempt (web or CLI) is logged automatically to `logs/sent_log.csv`
(created on first send). Open `log.php` in the browser to see a searchable,
filterable table of every company you've emailed, with timestamp and status —
so you can check who's already been contacted before sending a new batch.

## How it works

- `includes/csv.php` — parses and validates the CSV (skips blank/invalid rows).
- `includes/mailer.php` — configures PHPMailer from `.env` and sends one email.
- `includes/log.php` — records every send attempt to `logs/sent_log.csv`.
- `templates/email.html` — the email body template.
- `index.php` / `send.php` — the web upload form and its handler.
- `log.php` — view/filter the history of everything sent so far.
- `send_cli.php` — terminal alternative for big batches.

## Rate limiting

Most SMTP providers (especially free ones like Gmail) throttle or block accounts
that send too fast or in bulk. The `MAIL_SEND_DELAY_MICROSECONDS` setting in
`.env` adds a small pause between sends. For genuinely large campaigns (hundreds+
of companies), consider a transactional email provider (SendGrid, Mailgun,
Amazon SES, Postmark) instead of a personal Gmail/Outlook account.
