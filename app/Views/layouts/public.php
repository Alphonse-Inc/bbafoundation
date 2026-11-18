<?php
/** @var string $content */
/** @var string $title */
/** @var string $metaDescription */
$metaDescription = $metaDescription ?? '';
$nonce = (string) ($GLOBALS['bbaf_nonce'] ?? '');
$pageTitle = trim($title . ' — ' . setting('seo_title_suffix', 'BBAFoundation'));
$description = $metaDescription !== '' ? $metaDescription : setting('seo_description');
$canonical = $canonical ?? url(ltrim((string) ($currentPath ?? '/'), '/'));
$image = $shareImage ?? url('assets/img/placeholders/hills.svg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
    <meta property="og:image" content="<?= e($image) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <?php if (setting('gsc_verification') !== ''): ?>
        <meta name="google-site-verification" content="<?= e(setting('gsc_verification')) ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(favicon_src()) ?>">
    <?php if (($currentPath ?? '') === '/'): ?>
        <style>:root{--navy:#0F2A4A;--gold:#D9A520;--ivory:#FAF8F3;--ink:#1E293B;--sans:system-ui,sans-serif}body{margin:0;background:var(--ivory);color:var(--ink);font-family:var(--sans)}.skip-link{position:absolute;left:-999px}.site-header{background:var(--navy);color:#fff}.btn{display:inline-block;padding:.7rem 1rem;border-radius:999px}</style>
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <?php
    $brand = static function (string $key, string $fallback): string {
        $value = setting($key, $fallback);
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? $value : $fallback;
    };
    ?>
    <style>:root{--navy:<?= e($brand('color_primary', '#0F2A4A')) ?>;--gold:<?= e($brand('color_accent', '#D9A520')) ?>;--ivory:<?= e($brand('color_ivory', '#FAF8F3')) ?>;--sand:<?= e($brand('color_sand', '#F1EFE8')) ?>;--ink:<?= e($brand('color_text', '#1E293B')) ?>;--muted:<?= e($brand('color_muted', '#475569')) ?>}</style>
    <?php if (!empty($jsonLd)): ?>
        <script type="application/ld+json" nonce="<?= e($nonce) ?>"><?= $jsonLd ?></script>
    <?php endif; ?>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<?php require __DIR__ . '/../partials/header.php'; ?>
<main id="main">
    <?= $content ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
<?php require __DIR__ . '/../partials/flashes.php'; ?>
<button class="back-top" type="button" data-back-top>Top</button>
<div class="cookie" data-cookie hidden>
    <p><?= e(setting('cookie_text')) ?> <a href="<?= e(url('privacy-policy')) ?>">Privacy Policy</a></p>
    <div>
        <button class="btn btn-navy btn-small" type="button" data-cookie-choice="accept">Accept analytics</button>
        <button class="btn btn-line btn-small" type="button" data-cookie-choice="reject">Reject analytics</button>
    </div>
</div>
<script type="application/json" id="bbaf-config" nonce="<?= e($nonce) ?>"><?= json_encode([
    'csrf' => $csrf ?? '',
    'ga' => setting('ga_id'),
    'apply' => url('apply'),
    'poll' => !empty($authUser) ? url('notifications/poll') : '',
], JSON_UNESCAPED_SLASHES) ?></script>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
