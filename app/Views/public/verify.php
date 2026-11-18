<section class="section">
    <div class="container">
        <h1>Verify an award letter</h1>
        <?php if ($valid): ?>
            <p class="notice notice-ok">This code matches a BBAFoundation scholarship award.</p>
            <p>Programme: <?= e($cycleTitle) ?></p>
            <p>Status: <?= e($status) ?></p>
        <?php else: ?>
            <p class="notice">This code does not match an award letter.</p>
        <?php endif; ?>
    </div>
</section>
