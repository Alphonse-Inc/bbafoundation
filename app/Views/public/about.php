<section class="section">
    <div class="container prose">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><ol><li><a href="<?= e(url('/')) ?>">Home</a></li><li>About Us</li></ol></nav>
        <h1>About Us</h1>
        <p><?= e(setting('about_intro')) ?></p>
        <div class="grid-2">
            <?php foreach ($cards as $card): ?>
                <article class="card"><h2><?= e($card['title']) ?></h2><p><?= e($card['body']) ?></p></article>
            <?php endforeach; ?>
        </div>
        <h2>Required documents</h2>
        <ol class="check-list">
            <?php foreach ($documents as $index => $document): ?>
                <li><?= e($document['body']) ?></li>
            <?php endforeach; ?>
        </ol>
        <h2>Mission Statement</h2>
        <p><?= e(setting('mission')) ?></p>
        <h2>The Vision</h2>
        <p><?= e(setting('vision')) ?></p>
        <h2>Objectives</h2>
        <ol><?php foreach ($objectives as $item): ?><li><?= e($item['body']) ?></li><?php endforeach; ?></ol>
        <?php if (trim(setting('founder_story')) !== ''): ?>
            <h2>Our Founder's Story</h2>
            <div><?= rich(setting('founder_story')) ?></div>
        <?php endif; ?>
        <div class="logo-strip" aria-label="Affiliated organisations">
            <?php foreach ($affiliates as $affiliate): ?>
                <img src="<?= e(is_file(BBAF_PUBLIC . '/assets/img/legacy/' . $affiliate['image_file']) ? legacy_src((string) $affiliate['image_file']) : asset('img/placeholders/affiliate.svg')) ?>" alt="<?= e($affiliate['name']) ?>" width="180" height="48" loading="lazy">
            <?php endforeach; ?>
        </div>
        <div class="band">
            <p><?= e(setting('get_involved_band')) ?></p>
            <a class="btn btn-gold" href="<?= e(url('contact')) ?>">Get involved now</a>
        </div>
    </div>
</section>
