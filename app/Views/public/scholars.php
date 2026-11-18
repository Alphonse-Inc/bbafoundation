<section class="section">
    <div class="container">
        <h1>Scholars</h1>
        <p><?= e(setting('goal_caption')) ?></p>
        <div class="progress" aria-label="Progress toward 100 students"><span style="width:<?= (int) $progress['percent'] ?>%"></span></div>
        <p><?= (int) $progress['helped'] ?> of <?= (int) $progress['target'] ?></p>
        <?php if ($scholars === []): ?><p class="empty"><?= e(setting('scholars_empty')) ?></p><?php endif; ?>
        <div class="grid-3">
            <?php foreach ($scholars as $scholar): ?>
                <article class="card">
                    <h2><?= e($scholar['surname'] . ' ' . $scholar['other_names']) ?></h2>
                    <p><?= e($scholar['institution_name']) ?></p>
                    <p><?= e($scholar['cycle_title']) ?></p>
                    <?php if ($scholar['public_note'] !== ''): ?><p><?= e($scholar['public_note']) ?></p><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
