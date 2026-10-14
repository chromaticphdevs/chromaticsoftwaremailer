<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Custom Mailer</title>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; max-width: 720px; margin: 60px auto; color: #222; }
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
        #attachments-list { margin-top: 10px; font-size: 13px; }
        #attachments-list div { padding: 4px 0; }
        .note-editor { margin-top: 6px; }
    </style>
</head>
<body>
<?php $activeNav = 'mailer'; include __DIR__ . '/includes/nav.php'; ?>

    <h1>Custom Mailer</h1>
    <p>Write a one-off email — recipient, subject and rich-text content get dropped into the standard email template.</p>

    <form action="mailer_send.php" method="post" enctype="multipart/form-data" id="mailer-form">

        <label for="recipient_name">Recipient name</label>
        <input type="text" id="recipient_name" name="recipient_name" placeholder="e.g. Juan Dela Cruz">
        <div class="hint">Optional — shown to PHPMailer as the display name only.</div>

        <label for="recipient_email">Recipient email</label>
        <input type="email" id="recipient_email" name="recipient_email" required placeholder="name@company.com">

        <label for="subject">Subject</label>
        <input type="text" id="subject" name="subject" required placeholder="Email subject">

        <label for="content">Message</label>
        <textarea id="content" name="content"></textarea>
        <div class="hint">This gets wrapped inside the Chromatic Softwares email template (header/footer added automatically).</div>

        <label for="attachments">Attachments</label>
        <input type="file" id="attachments" name="attachments[]" multiple>
        <div class="hint">
            Files are sent straight through to the outgoing email and are never written to a folder on the
            server — PHP only holds them in a temporary request file, which it discards automatically as soon
            as the email is sent.
        </div>
        <div id="attachments-list"></div>

        <button type="submit">Send Email</button>
        <button type="button" id="clear-draft-btn" style="background:#6b7280; margin-left:10px;">Clear Draft</button>
    </form>


    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
    <script>
        const DRAFT_KEY = 'mailerDraft';

        $(function () {
            $('#content').summernote({
                height: 260,
                placeholder: 'Write your message…',
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['fontname', 'fontsize', 'color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture', 'table']],
                    ['view', ['codeview']],
                ],
            });

            loadDraft();

            // Autosave text fields as the user types (attachments are never persisted here —
            // browsers can't rehydrate a file input from localStorage, and there's no need to
            // since the file only has to survive a single sitting).
            const draftFields = ['recipient_name', 'recipient_email', 'subject'];
            draftFields.forEach(function (id) {
                document.getElementById(id).addEventListener('input', saveDraft);
            });
            $('#content').on('summernote.change', saveDraft);

            document.getElementById('clear-draft-btn').addEventListener('click', function () {
                localStorage.removeItem(DRAFT_KEY);
                document.getElementById('mailer-form').reset();
                $('#content').summernote('code', '');
            });
        });

        function saveDraft() {
            try {
                localStorage.setItem(DRAFT_KEY, JSON.stringify({
                    recipient_name: document.getElementById('recipient_name').value,
                    recipient_email: document.getElementById('recipient_email').value,
                    subject: document.getElementById('subject').value,
                    content: $('#content').summernote('code'),
                }));
            } catch (e) {
                // localStorage unavailable (private mode, quota, etc.) — draft just won't persist.
            }
        }

        function loadDraft() {
            let raw;
            try {
                raw = localStorage.getItem(DRAFT_KEY);
            } catch (e) {
                return;
            }
            if (!raw) return;

            try {
                const draft = JSON.parse(raw);
                document.getElementById('recipient_name').value = draft.recipient_name || '';
                document.getElementById('recipient_email').value = draft.recipient_email || '';
                document.getElementById('subject').value = draft.subject || '';
                if (draft.content) {
                    $('#content').summernote('code', draft.content);
                }
            } catch (e) {
                // corrupt draft, ignore
            }
        }

        document.getElementById('attachments').addEventListener('change', function () {
            const list = document.getElementById('attachments-list');
            list.innerHTML = '';
            Array.from(this.files).forEach(function (file) {
                const row = document.createElement('div');
                row.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                list.appendChild(row);
            });
        });
    </script>

<?php $previewType = 'mailer'; include __DIR__ . '/includes/preview.php'; ?>

</body>
</html>
