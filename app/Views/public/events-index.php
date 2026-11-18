<section class="section">
    <div class="container">
        <h1><?= e(setting('events_heading')) ?></h1>
        <p><?= e(setting('events_sub')) ?></p>
        <?php if ($events === []): ?><p class="empty"><?= e(setting('events_empty')) ?></p><?php endif; ?>
        <?php foreach ($events as $event): ?>
            <article class="card">
                <h2><a href="<?= e(url('events/' . $event['slug'])) ?>"><?= e($event['title']) ?></a></h2>
                <?php if ($event['starts_at']): ?><p><?= e(display_date((string) $event['starts_at'], true)) ?></p><?php endif; ?>
                <p><?= e($event['excerpt']) ?></p>
            </article>
        <?php endforeach; ?>
        <p><a href="<?= e(url('rss/events.xml')) ?>">RSS</a></p>
    </div>
</section>
