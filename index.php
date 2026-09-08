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
    </style>
</head>
<body>

    <h1>HRIS Company Email Blaster</h1>
    <p>Upload a CSV of companies to email them about the HRIS system.</p>

    <form action="send.php" method="post" enctype="multipart/form-data">

        <label for="csv_file">Companies CSV file</label>
        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
        <div class="hint">Columns: <code>company_name,email</code> (header row optional).</div>

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
    </p>

</body>
</html>
