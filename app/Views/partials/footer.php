<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <img src="<?= e(logo_src()) ?>" alt="" width="140" height="42">
            <p><?= e(setting('tagline')) ?></p>
        </div>
        <nav aria-label="Footer">
            <a href="<?= e(url('privacy-policy')) ?>">Privacy Policy</a><br>
            <a href="<?= e(url('terms-and-conditions')) ?>"><?= e(setting('footer_terms_label', 'Terms & Conditions')) ?></a><br>
            <a href="<?= e(url('faqs')) ?>">FAQ</a>
        </nav>
        <p>© <?= e((new DateTimeImmutable('now', new DateTimeZone('Africa/Lagos')))->format('Y')) ?> <?= e(setting('footer_copyright_name', 'BBAFoundation')) ?></p>
    </div>
</footer>
