<section class="section">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><ol><li><a href="<?= e(url('/')) ?>">Home</a></li><li>Board of Trustees</li></ol></nav>
        <h1>Board of Trustees</h1>
        <div class="portrait-grid">
            <?php foreach ($trustees as $trustee): ?>
                <a class="card panel-link" href="<?= e(url($trustee['slug'])) ?>">
                    <div class="portrait">
                        <?php if (is_file(BBAF_PUBLIC . '/assets/img/legacy/' . $trustee['photo_file'])): ?>
                            <img src="<?= e(legacy_src((string) $trustee['photo_file'])) ?>" alt="" width="320" height="320" loading="lazy">
                        <?php else: ?>
                            <span><?= e(initials((string) $trustee['listing_name'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($trustee['role_label'] !== ''): ?><p class="kicker"><?= e($trustee['role_label']) ?></p><?php endif; ?>
                    <h2><?= e($trustee['listing_name']) ?></h2>
                    <?php if ($trustee['credential'] !== ''): ?><p><?= e($trustee['credential']) ?></p><?php endif; ?>
                    <?php if ($trustee['summary'] !== ''): ?><p><?= e($trustee['summary']) ?></p><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
