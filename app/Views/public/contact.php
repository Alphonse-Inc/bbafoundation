<section class="section">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><ol><li><a href="<?= e(url('/')) ?>">Home</a></li><li>Contact</li></ol></nav>
        <h1><?= e(setting('contact_heading')) ?></h1>
        <p><?= e(setting('contact_intro')) ?></p>
        <div class="two-col">
            <form method="post" action="<?= e(url('contact')) ?>" class="card form-grid">
                <?= csrf_field() ?>
                <input type="hidden" name="rendered_at" value="<?= e((string) time()) ?>">
                <label class="honeypot">Please do not fill in this field<input name="company_website" tabindex="-1" autocomplete="off"></label>
                <label>Full Name*<input name="full_name" required value="<?= e(old('full_name')) ?>"></label>
                <label>Phone Number*<input name="phone" required value="<?= e(old('phone')) ?>" inputmode="tel"></label>
                <label>Email Address*<input name="email" type="email" required value="<?= e(old('email')) ?>"></label>
                <label>Subject<input name="subject" value="<?= e(old('subject')) ?>"></label>
                <label>Message*<textarea name="message" maxlength="180" required data-chars><?= e(old('message')) ?></textarea></label>
                <p data-charcount>0/180</p>
                <button class="btn btn-gold" type="submit">Send message</button>
            </form>
            <div>
                <h2>Head office</h2>
                <p><?= e(setting('head_office')) ?></p>
                <h2>Operational office</h2>
                <p><?= e(setting('operational_office')) ?></p>
                <p>Phone: <a href="tel:<?= e(setting('phone_e164')) ?>"><?= e(setting('phone_contact')) ?></a></p>
                <p>Email: <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p>
                <p>Website: <?= e(setting('website_label')) ?></p>
                <?php if ($map !== ''): ?>
                    <iframe class="map-frame" title="Map showing the BBAFoundation head office" loading="lazy" src="<?= e($map) ?>"></iframe>
                <?php endif; ?>
                <p><a href="<?= e($mapsLink) ?>" target="_blank" rel="noopener noreferrer">Open in Google Maps</a></p>
            </div>
        </div>
    </div>
</section>
