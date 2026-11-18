<?php /** @var string $content */ /** @var string $title */ $nonce = (string) ($GLOBALS['bbaf_nonce'] ?? ''); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — <?= e(setting('org_short')) ?></title>
    <link rel="icon" href="<?= e(favicon_src()) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <a href="<?= e(url('/')) ?>"><img src="<?= e(logo_src(false)) ?>" alt="<?= e(setting('org_short')) ?>" width="160" height="48"></a>
        <?= $content ?>
    </div>
</div>
<?php require __DIR__ . '/../partials/flashes.php'; ?>
<script type="application/json" id="bbaf-config" nonce="<?= e($nonce) ?>"><?= json_encode(['csrf' => $csrf ?? ''], JSON_UNESCAPED_SLASHES) ?></script>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
