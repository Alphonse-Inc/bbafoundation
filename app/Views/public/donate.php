<section class="section">
    <div class="container prose">
        <h1>Donate</h1>
        <p><?= e(setting('campaign_text')) ?></p>
        <p><?= e(setting('get_involved_band')) ?></p>
        <p class="notice"><?= e(setting('donate_trust_note')) ?></p>
        <p><a class="btn btn-gold" href="<?= e(setting('donate_url')) ?>" target="_blank" rel="noopener noreferrer">Donate to this cause</a></p>
        <?php if (trim(setting('bank_transfer_details')) !== ''): ?>
            <h2>Bank transfer</h2>
            <div><?= rich(setting('bank_transfer_details')) ?></div>
        <?php endif; ?>
    </div>
</section>
