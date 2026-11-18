<article class="section">
    <div class="container prose">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><ol><li><a href="<?= e(url('/')) ?>">Home</a></li><li><a href="<?= e(url('news')) ?>">News</a></li><li><?= e($post['title']) ?></li></ol></nav>
        <h1><?= e($post['title']) ?></h1>
        <p class="fine"><?= e(display_date((string) $post['published_at'], true)) ?><?php if ($author !== ''): ?> · <?= e($author) ?><?php endif; ?></p>
        <?php if ($post['featured_path'] !== ''): ?>
            <img src="<?= e(url(ltrim((string) $post['featured_path'], '/'))) ?>" alt="<?= e($post['featured_alt']) ?>" width="960" height="540" loading="lazy">
        <?php endif; ?>
        <div><?= rich((string) $post['body']) ?></div>
        <p>Share: <a href="https://twitter.com/intent/tweet?url=<?= e(rawurlencode(url('news/' . $post['slug']))) ?>">Twitter</a></p>
    </div>
</article>
