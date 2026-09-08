<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Company Profile</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; max-width: 560px; margin: 60px auto; color: #222; }
        h1 { font-size: 22px; }
        label { display: block; margin-top: 16px; font-weight: bold; }
        input[type="text"], input[type="email"] {
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

    <h1>Send Company Profile</h1>
    <p>Email a recipient a short introduction to Chromatic Softwares with our company profile PDF attached.</p>

    <form action="profile_send.php" method="post">

        <label for="recipient_name">Recipient / company name</label>
        <input type="text" id="recipient_name" name="recipient_name" placeholder="e.g. Acme Inc">
        <div class="hint">Optional — used for the greeting. Leave blank for a generic "Hello there,".</div>

        <label for="recipient_email">Recipient email</label>
        <input type="email" id="recipient_email" name="recipient_email" required placeholder="name@company.com">

        <label for="subject">Email subject</label>
        <input type="text" id="subject" name="subject"
               value="Chromatic Softwares — Company Profile" required>

        <div class="checkbox-row">
            <input type="checkbox" id="resend" name="resend" value="1">
            <label for="resend">Resend even if we already sent this recipient a company profile</label>
        </div>

        <button type="submit">Send Company Profile</button>
    </form>

    <p style="margin-top:30px;"><a href="index.php">&larr; Back to HRIS blaster</a></p>

</body>
</html>
