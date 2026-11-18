<?php
$banner = setting('announcement_banner');
$path = $currentPath ?? '/';
?>
<?php if ($banner !== ''): ?>
    <div class="notice" style="border-radius:0;text-align:center"><?= e($banner) ?></div>
<?php endif; ?>
<div class="topbar">
    <div class="container topbar-inner">
        <div class="topbar-meta">
            <a href="tel:<?= e(setting('phone_e164')) ?>"><?= e(setting('phone_display')) ?></a>
            <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a>
            <span><?= e(setting('topbar_address')) ?></span>
        </div>
        <div class="socials">
            <?php foreach (['facebook' => 'Facebook', 'twitter' => 'Twitter', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn'] as $key => $label): ?>
                <?php $href = setting('social_' . $key); if ($href === '') continue; ?>
                <a href="<?= e($href) ?>" aria-label="<?= e($label) ?>" target="_blank" rel="noopener noreferrer"><?= e(strtoupper(substr($label, 0, 1))) ?></a>
            <?php endforeach; ?>
            <button class="btn btn-line btn-small" type="button" data-theme-toggle style="color:#fff;border-color:rgba(255,255,255,.4)">Theme</button>
        </div>
    </div>
</div>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e(url('/')) ?>">
            <img src="<?= e(logo_src()) ?>" alt="<?= e(setting('org_short', 'BBAFoundation')) ?>" width="160" height="48">
        </a>
        <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="site-nav">Menu</button>
        <nav class="nav" id="site-nav" data-nav>
            <a href="<?= e(url('/')) ?>" <?= $path === '/' ? 'aria-current="page"' : '' ?>>Home</a>
            <a href="<?= e(url('about-us')) ?>" <?= $path === '/about-us' ? 'aria-current="page"' : '' ?>>About Us</a>
            <a href="<?= e(url('bot')) ?>" <?= $path === '/bot' ? 'aria-current="page"' : '' ?>>Board of Trustees</a>
            <a href="<?= e(url('contact')) ?>" <?= $path === '/contact' ? 'aria-current="page"' : '' ?>>Contact</a>
            <a class="btn btn-gold btn-small" href="<?= e(url('apply')) ?>">Apply Now</a>
            <a class="btn btn-navy btn-small" href="<?= e(url('donate')) ?>">Donate Funds</a>
            <?php if (!empty($authUser)): ?>
                <a href="<?= e(url(\BBAF\Core\Middleware::homeFor((string) $authUser['role']))) ?>" data-poll>Account <span data-note-count hidden>0</span></a>
            <?php else: ?>
                <a href="<?= e(url('login')) ?>">Sign in</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
