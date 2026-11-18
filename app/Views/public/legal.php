<section class="section">
    <div class="container prose">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><ol><li><a href="<?= e(url('/')) ?>">Home</a></li><li><?= e($page['title']) ?></li></ol></nav>
        <h1><?= e($page['title']) ?></h1>
        <div><?= rich((string) $page['body']) ?></div>
    </div>
</section>
