<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resume Blaster</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; max-width: 560px; margin: 60px auto; color: #222; }
        h1 { font-size: 22px; }
        label { display: block; margin-top: 16px; font-weight: bold; }
        input[type="file"], input[type="text"] {
            width: 100%; padding: 8px; margin-top: 6px; box-sizing: border-box;
            border: 1px solid #ccc; border-radius: 4px;
        }
        button {
            margin-top: 24px; padding: 10px 20px; background: #2563eb; color: #fff;
            border: none; border-radius: 4px; cursor: pointer; font-size: 15px;
        }
        button:hover { background: #1d4ed8; }
        .hint { color: #666; font-size: 13px; margin-top: 4px; }
        .checkbox-row { display: flex; align-items: center; gap: 8px; margin-top: 16px; }
        .checkbox-row input { width: auto; margin: 0; }
        .checkbox-row label { display: inline; margin: 0; font-weight: normal; }
        .note { background: #fffbeb; border: 1px solid #fde68a; padding: 10px 12px; border-radius: 6px; font-size: 13px; color: #92400e; margin-top: 20px; }
    </style>
</head>
<body>

    <h1>Resume Blaster</h1>
    <p>Upload a CSV of companies to email them your resume. Sent from your own personal mail account, separate from the Chromatic Softwares mailer.</p>

    <form action="resume_send.php" method="post" enctype="multipart/form-data">

        <label for="csv_file">Companies CSV file</label>
        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
        <div class="hint">Columns: <code>company_name,email</code> (header row optional).</div>

        <label for="subject">Email subject</label>
        <input type="text" id="subject" name="subject"
               value="Application for Software Development Opportunity" required>

        <div class="checkbox-row">
            <input type="checkbox" id="resend" name="resend" value="1">
            <label for="resend">Resend to companies already emailed a resume</label>
        </div>
        <div class="hint">Unchecked: companies that already received your resume are skipped automatically.</div>

        <button type="submit">Send Resumes</button>
    </form>

    <div class="note">
        Uses the <code>PERSONAL_MAIL_*</code> credentials in <code>.env</code>. Fill those in
        before using this page — they're separate from the Chromatic Softwares mailer.
    </div>

    <p style="margin-top:30px;">
        <a href="index.php">&larr; Back to HRIS blaster</a>
        &nbsp;|&nbsp;
        <a href="log.php">View sent email log &rarr;</a>
        &nbsp;|&nbsp;
        <a href="recipients.php?campaign=resume">View already-emailed companies &rarr;</a>
    </p>

</body>
</html>
