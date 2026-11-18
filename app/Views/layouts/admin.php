<?php
/** @var string $content */
/** @var string $title */
$nonce = (string) ($GLOBALS['bbaf_nonce'] ?? '');
$role = (string) ($authUser['role'] ?? '');
$item = static function (string $href, string $label) use ($currentPath): string {
    $path = (string) ($currentPath ?? '');
    $current = $href === '/admin' ? $path === '/admin' : ($path === $href || str_starts_with($path, $href . '/'));
    return '<a href="' . e(url(ltrim($href, '/'))) . '"' . ($current ? ' aria-current="page"' : '') . '>' . e($label) . '</a>';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — Admin</title>
    <link rel="icon" href="<?= e(favicon_src()) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="shell">
    <aside class="side">
        <a class="brand" href="<?= e(url('admin')) ?>" style="color:#fff">BBAF admin</a>
        <p><?= e(trim(($authUser['surname'] ?? '') . ' ' . ($authUser['first_name'] ?? ''))) ?></p>
        <?= $item('/admin', 'Dashboard') ?>
        <?php if (in_array($role, ['super_admin', 'foundation_admin', 'reviewer'], true)): ?>
            <?= $item('/admin/applications', 'Applications') ?>
        <?php endif; ?>
        <?php if (in_array($role, ['super_admin', 'foundation_admin'], true)): ?>
            <?= $item('/admin/cycles', 'Cycles') ?>
            <?= $item('/admin/awardees', 'Awardees') ?>
            <?= $item('/admin/disbursements', 'Disbursements') ?>
            <?= $item('/admin/reports', 'Reports') ?>
            <?= $item('/admin/renewals', 'Renewals') ?>
        <?php endif; ?>
        <?php if (in_array($role, ['super_admin', 'foundation_admin', 'content_editor'], true)): ?>
            <?= $item('/admin/pages', 'Pages') ?>
            <?= $item('/admin/trustees', 'Trustees') ?>
            <?= $item('/admin/faqs', 'FAQs') ?>
            <?= $item('/admin/news', 'News') ?>
            <?= $item('/admin/events', 'Events') ?>
            <?= $item('/admin/media', 'Media') ?>
            <?= $item('/admin/home', 'Home content') ?>
        <?php endif; ?>
        <?php if ($role === 'super_admin'): ?>
            <?= $item('/admin/users', 'Users') ?>
            <?= $item('/admin/settings', 'Settings') ?>
            <?= $item('/admin/brand', 'Brand') ?>
            <?= $item('/admin/templates', 'Email templates') ?>
            <?= $item('/admin/redirects', 'Redirects') ?>
            <?= $item('/admin/audit', 'Audit log') ?>
            <?= $item('/admin/backups', 'Backups') ?>
            <?= $item('/admin/import', 'Legacy import') ?>
            <?= $item('/admin/retention', 'Retention') ?>
            <?= $item('/admin/requests', 'Data requests') ?>
            <?= $item('/admin/breach', 'Breach procedure') ?>
            <?= $item('/admin/institutions', 'Institutions') ?>
        <?php endif; ?>
        <?php if (in_array($role, ['super_admin', 'foundation_admin', 'content_editor'], true)): ?>
            <?= $item('/admin/inbox', 'Inbox') ?>
            <?= $item('/admin/newsletter', 'Newsletter') ?>
        <?php endif; ?>
        <a href="<?= e(url('/')) ?>">View site</a>
        <form method="post" action="<?= e(url('logout')) ?>"><?= csrf_field() ?><button class="btn btn-line btn-small" type="submit">Sign out</button></form>
    </aside>
    <div class="main">
        <h1><?= e($title) ?></h1>
        <?= $content ?>
    </div>
</div>
<?php require __DIR__ . '/../partials/flashes.php'; ?>
<script type="application/json" id="bbaf-config" nonce="<?= e($nonce) ?>"><?= json_encode(['csrf' => $csrf ?? ''], JSON_UNESCAPED_SLASHES) ?></script>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
