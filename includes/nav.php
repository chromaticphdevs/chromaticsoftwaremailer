<?php
/**
 * Shared top navigation. Set $activeNav before including:
 * 'hris' | 'profile' | 'resume' | 'sent' | 'mailer'
 */
$activeNav = $activeNav ?? '';
$navItems = [
    'hris'    => ['index.php',   'HRIS Blaster'],
    'profile' => ['profile.php', 'Company Profile Blaster'],
    'resume'  => ['resume.php',  'Resume Blaster'],
    'sent'    => ['log.php',     'Sent Items'],
    'mailer'  => ['mailer.php',  'Custom Mailer'],
];
?>
<style>
    .app-nav { display: flex; flex-wrap: wrap; gap: 4px; margin: 0 0 28px 0; padding: 6px;
               background: #f1f5f9; border-radius: 8px; font-family: Arial, Helvetica, sans-serif; }
    .app-nav a { padding: 8px 14px; border-radius: 6px; color: #334155; text-decoration: none;
                 font-size: 14px; font-weight: bold; }
    .app-nav a:hover { background: #e2e8f0; }
    .app-nav a.active { background: #2563eb; color: #fff; }
</style>
<nav class="app-nav">
    <?php foreach ($navItems as $key => [$href, $label]): ?>
        <a href="<?= $href ?>"<?= $key === $activeNav ? ' class="active" aria-current="page"' : '' ?>><?= $label ?></a>
    <?php endforeach; ?>
</nav>
