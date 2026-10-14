<?php
/**
 * Pre-send preview. Include at the bottom of a send page, after setting $previewType
 * ('hris' | 'profile' | 'resume' | 'mailer'). Clicking the form's send button opens a
 * modal with the rendered email body; "Confirm & send" then submits the form as normal.
 *
 * To turn the preview off, set $previewEnabled = false.
 */
$previewEnabled = true;
if (!$previewEnabled) {
    return;
}
?>
<style>
    #preview-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.6); z-index:9999;
                       align-items:center; justify-content:center; font-family:Arial,Helvetica,sans-serif; }
    #preview-overlay.open { display:flex; }
    #preview-box { background:#fff; width:min(720px,94vw); height:90vh; border-radius:10px; display:flex;
                   flex-direction:column; overflow:hidden; }
    #preview-head { padding:14px 18px; border-bottom:1px solid #e5e7eb; }
    #preview-head strong { font-size:16px; }
    #preview-meta { font-size:13px; color:#475569; margin-top:4px; }
    #preview-frame { flex:1; border:0; width:100%; background:#eef2f9; }
    #preview-foot { padding:12px 18px; border-top:1px solid #e5e7eb; display:flex; gap:10px; justify-content:flex-end; }
    #preview-foot button { margin:0; }
    #preview-cancel { background:#6b7280; }
</style>
<div id="preview-overlay">
    <div id="preview-box">
        <div id="preview-head">
            <strong>Preview — nothing has been sent yet</strong>
            <div id="preview-meta"></div>
        </div>
        <iframe id="preview-frame" sandbox></iframe>
        <div id="preview-foot">
            <button type="button" id="preview-cancel">Cancel</button>
            <button type="button" id="preview-confirm">Confirm &amp; send</button>
        </div>
    </div>
</div>
<script>
(function () {
    const TYPE = <?= json_encode($previewType ?? '') ?>;
    const form = document.querySelector('form');
    const overlay = document.getElementById('preview-overlay');
    const frame = document.getElementById('preview-frame');
    const meta = document.getElementById('preview-meta');
    let confirmed = false;

    function field(id) { const el = document.getElementById(id); return el ? el.value.trim() : ''; }

    // First company name in the uploaded CSV, so the preview greeting looks real.
    function firstCsvName(file) {
        return new Promise(function (resolve) {
            if (!file) { return resolve(''); }
            const reader = new FileReader();
            reader.onload = function () {
                const lines = String(reader.result).split(/\r?\n/).filter(function (l) { return l.trim() !== ''; });
                let row = lines[0] ? lines[0].split(',') : [];
                const c0 = (row[0] || '').trim().toLowerCase();
                if (c0 === 'company_name' || c0 === 'company' || (row[1] || '').trim().toLowerCase() === 'email') {
                    row = lines[1] ? lines[1].split(',') : [];
                }
                resolve((row[0] || '').replace(/^"|"$/g, '').trim());
            };
            reader.onerror = function () { resolve(''); };
            reader.readAsText(file.slice(0, 20000));
        });
    }

    async function collect() {
        const data = new URLSearchParams();
        data.set('type', TYPE);
        let who = '';
        if (TYPE === 'mailer') {
            const content = (window.jQuery && jQuery('#content').summernote)
                ? jQuery('#content').summernote('code') : field('content');
            data.set('content', content);
            who = field('recipient_email');
        } else {
            const mode = form.querySelector('input[name="send_mode"]:checked');
            let name = field('recipient_name');
            if (mode && mode.value === 'csv') {
                const f = document.getElementById('csv_file');
                name = await firstCsvName(f && f.files[0]);
                who = name ? 'first CSV row: ' + name : 'CSV (no name found)';
            } else {
                who = field('recipient_email') || name;
            }
            data.set('name', name);
        }
        return { data: data, who: who };
    }

    form.addEventListener('submit', async function (e) {
        if (confirmed) { return; }
        e.preventDefault();
        const c = await collect();
        const res = await fetch('preview.php', { method: 'POST', body: c.data });
        frame.srcdoc = await res.text();
        meta.textContent = 'To: ' + (c.who || '—') + '   |   Subject: ' + field('subject');
        overlay.classList.add('open');
    });

    document.getElementById('preview-cancel').addEventListener('click', function () {
        overlay.classList.remove('open');
    });
    document.getElementById('preview-confirm').addEventListener('click', function () {
        confirmed = true;
        form.submit();
    });
})();
</script>
