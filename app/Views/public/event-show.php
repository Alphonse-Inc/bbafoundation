<article class="section">
    <div class="container prose">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><ol><li><a href="<?= e(url('/')) ?>">Home</a></li><li><a href="<?= e(url('events')) ?>">Events</a></li><li><?= e($event['title']) ?></li></ol></nav>
        <h1><?= e($event['title']) ?></h1>
        <?php if ($event['starts_at']): ?><p><?= e(display_date((string) $event['starts_at'], true)) ?><?php if ($event['venue'] !== ''): ?> · <?= e($event['venue']) ?><?php endif; ?></p><?php endif; ?>
        <div><?= rich((string) $event['body']) ?></div>
    </div>
</article>
