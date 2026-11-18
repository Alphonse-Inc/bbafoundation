<section class="section">
    <div class="container">
        <h1><?= e(setting('news_heading')) ?></h1>
        <p><?= e(setting('news_sub')) ?></p>
        <?php if ($posts === []): ?><p class="empty"><?= e(setting('news_empty')) ?></p><?php endif; ?>
        <div class="grid-3">
            <?php foreach ($posts as $post): ?>
                <article class="card">
                    <h2><a href="<?= e(url('news/' . $post['slug'])) ?>"><?= e($post['title']) ?></a></h2>
                    <p><?= e($post['excerpt']) ?></p>
                    <p class="fine"><?= e(display_date((string) $post['published_at'])) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($meta) && $meta['pages'] > 1) { $path = '/news'; require dirname(__DIR__) . '/partials/pagination.php'; } ?>
        <p><a href="<?= e(url('rss/news.xml')) ?>">RSS</a></p>
    </div>
</section>
