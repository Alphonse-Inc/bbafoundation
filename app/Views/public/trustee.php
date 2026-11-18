<section class="section">
    <div class="container prose">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><ol>
            <li><a href="<?= e(url('/')) ?>">Home</a></li>
            <li><a href="<?= e(url('bot')) ?>">Board of Trustees</a></li>
            <li><?= e($trustee['listing_name']) ?></li>
        </ol></nav>
        <div class="split">
            <div class="portrait" style="max-width:360px">
                <?php if (is_file(BBAF_PUBLIC . '/assets/img/legacy/' . $trustee['photo_file'])): ?>
                    <img src="<?= e(legacy_src((string) $trustee['photo_file'])) ?>" alt="<?= e($trustee['listing_name']) ?>" width="360" height="360">
                <?php else: ?>
                    <span><?= e(initials((string) $trustee['listing_name'])) ?></span>
                <?php endif; ?>
            </div>
            <div>
                <?php if ($trustee['role_label'] !== ''): ?><p class="kicker"><?= e($trustee['role_label']) ?></p><?php endif; ?>
                <h1><?= e($trustee['listing_name']) ?></h1>
                <?php if ($trustee['profile_name'] !== '' && $trustee['profile_name'] !== $trustee['listing_name']): ?>
                    <p><?= e($trustee['profile_name']) ?></p>
                <?php endif; ?>
                <?php if ($trustee['credential'] !== ''): ?><p><?= e($trustee['credential']) ?></p><?php endif; ?>
                <div class="socials">
                    <?php foreach (['facebook' => 'Facebook', 'twitter' => 'Twitter', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn'] as $key => $label): ?>
                        <?php if ($trustee['social_' . $key] === '') continue; ?>
                        <a href="<?= e($trustee['social_' . $key]) ?>" aria-label="<?= e($label) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php $structured = json_decode((string) ($trustee['structured_json'] ?? ''), true); ?>
        <?php if (is_array($structured) && $structured !== []): ?>
            <?php foreach ($structured as $heading => $body): ?>
                <h2><?= e((string) $heading) ?></h2>
                <p><?= e((string) $body) ?></p>
            <?php endforeach; ?>
        <?php else: ?>
            <?php foreach (preg_split("/\n{2,}/", trim((string) $trustee['full_profile'])) ?: [] as $paragraph): ?>
                <p><?= e($paragraph) ?></p>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
