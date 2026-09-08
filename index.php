<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HRIS Email Blaster</title>
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
        .toggle-row { display: flex; gap: 20px; margin-top: 20px; }
        .toggle-row label { display: flex; align-items: center; gap: 6px; margin: 0; font-weight: normal; }
        .toggle-row input { width: auto; margin: 0; }
        input[type="email"] {
            width: 100%; padding: 8px; margin-top: 6px; box-sizing: border-box;
            border: 1px solid #ccc; border-radius: 4px;
        }
    </style>
</head>
<body>

    <h1>HRIS Company Email Blaster</h1>
    <p>Upload a CSV of companies, or send to a single recipient, to email them about the HRIS system.</p>

    <form action="send.php" method="post" enctype="multipart/form-data">

        <div class="toggle-row">
            <label><input type="radio" name="send_mode" value="csv" checked onchange="setSendMode('csv')"> Upload CSV</label>
            <label><input type="radio" name="send_mode" value="single" onchange="setSendMode('single')"> Single recipient</label>
        </div>

        <div id="csv-fields">
            <label for="csv_file">Companies CSV file</label>
            <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
            <div class="hint">Columns: <code>company_name,email</code> (header row optional).</div>
        </div>

        <div id="single-fields" style="display:none;">
            <label for="recipient_name">Recipient / company name</label>
            <input type="text" id="recipient_name" name="recipient_name" placeholder="e.g. Acme Inc">
            <div class="hint">Optional — used for the greeting.</div>

            <label for="recipient_email">Recipient email</label>
            <input type="email" id="recipient_email" name="recipient_email" placeholder="name@company.com">
        </div>

        <label for="subject">Email subject</label>
        <input type="text" id="subject" name="subject"
               value="Introducing Our HRIS System" required>

        <div class="checkbox-row">
            <input type="checkbox" id="resend" name="resend" value="1">
            <label for="resend">Resend to companies already emailed</label>
        </div>
        <div class="hint">Unchecked: companies that already received an email are skipped automatically.</div>

        <button type="submit">Send Emails</button>
    </form>

    <p style="margin-top:30px;">
        <a href="log.php">View sent email log &rarr;</a>
        &nbsp;|&nbsp;
        <a href="recipients.php">View already-emailed companies &rarr;</a>
        &nbsp;|&nbsp;
        <a href="profile.php">Send company profile &rarr;</a>
        &nbsp;|&nbsp;
        <a href="resume.php">Resume blaster &rarr;</a>
    </p>

    <script>
        function setSendMode(mode) {
            const csvFields = document.getElementById('csv-fields');
            const singleFields = document.getElementById('single-fields');
            const csvFile = document.getElementById('csv_file');
            const recipientEmail = document.getElementById('recipient_email');

            if (mode === 'single') {
                csvFields.style.display = 'none';
                singleFields.style.display = 'block';
                csvFile.required = false;
                recipientEmail.required = true;
            } else {
                csvFields.style.display = 'block';
                singleFields.style.display = 'none';
                csvFile.required = true;
                recipientEmail.required = false;
            }
        }
    </script>

</body>
</html>
