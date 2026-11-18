<?php
/** @var array $slides */
/** @var array $cycle */
/** @var string $cycleState */
$heroImage = is_file(BBAF_PUBLIC . '/assets/img/legacy/home-upper-section.jpg')
    ? asset('img/legacy/home-upper-section.jpg')
    : asset('img/placeholders/hills.svg');
?>
<section class="hero" style="background-image:linear-gradient(90deg,rgba(15,42,74,.82),rgba(15,42,74,.35)),url('<?= e($heroImage) ?>')">
    <div class="container">
        <?php foreach ($slides as $index => $slide): ?>
            <article class="hero-slide<?= $index === 0 ? ' is-active' : '' ?>" data-slide <?= $index === 0 ? '' : 'hidden' ?>>
                <p class="kicker"><?= e($slide['kicker']) ?></p>
                <h1><?= e($slide['title']) ?></h1>
                <p><?= e($slide['subtitle']) ?></p>
                <div class="hero-actions">
                    <a class="btn btn-gold" href="<?= e(url(ltrim((string) $slide['primary_url'], '/'))) ?>"><?= e($slide['primary_label']) ?></a>
                    <a class="btn btn-line" href="<?= e(url(ltrim((string) $slide['secondary_url'], '/'))) ?>"><?= e($slide['secondary_label']) ?></a>
                </div>
            </article>
        <?php endforeach; ?>
        <div class="hero-dots" role="tablist" aria-label="Hero slides">
            <?php foreach ($slides as $index => $slide): ?>
                <button type="button" data-slide-dot aria-label="Show slide <?= $index + 1 ?>" aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section">
    <div class="container">
        <div class="logo-strip" aria-label="Affiliated organisations">
            <?php foreach ($affiliates as $affiliate): ?>
                <?php $src = legacy_src((string) $affiliate['image_file']); if (!is_file(BBAF_PUBLIC . '/assets/img/legacy/' . $affiliate['image_file'])) { $src = asset('img/placeholders/affiliate.svg'); } ?>
                <?php if ($affiliate['link_url'] !== ''): ?><a href="<?= e($affiliate['link_url']) ?>" target="_blank" rel="noopener noreferrer"><?php endif; ?>
                <img src="<?= e($src) ?>" alt="<?= e($affiliate['name']) ?>" width="180" height="48" loading="lazy">
                <?php if ($affiliate['link_url'] !== ''): ?></a><?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-head"><h2>Get Involved</h2></div>
        <div class="grid-3">
            <?php foreach ($involved as $card): ?>
                <article class="card">
                    <img src="<?= e(asset('img/placeholders/books.svg')) ?>" alt="" width="80" height="60">
                    <h3><?= e($card['title']) ?></h3>
                    <p><?= e($card['body']) ?></p>
                    <a href="<?= e(url('about-us')) ?>">Learn more</a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section" style="padding-top:0">
    <div class="container grid-3">
        <article class="card"><p class="kicker">Mission Statement</p><p><?= e(setting('mission')) ?></p></article>
        <article class="card"><p class="kicker">The Vision</p><p><?= e(setting('vision')) ?></p></article>
        <article class="card">
            <p class="kicker">Objectives</p>
            <ol><?php foreach ($objectives as $item): ?><li><?= e($item['body']) ?></li><?php endforeach; ?></ol>
        </article>
    </div>
</section>
<section class="section" style="padding-top:0">
    <div class="container split">
        <div class="photo-frame" style="background-image:url('<?= e($heroImage) ?>')" role="img" aria-label="Foundation photograph"></div>
        <div class="counters">
            <div class="counter"><strong data-count="<?= (int) setting('volunteers_count', '0') ?>">0</strong><?= e(setting('volunteers_suffix')) ?> Volunteers</div>
            <div class="counter"><strong data-count="<?= (int) setting('sponsors_count', '0') ?>">0</strong><?= e(setting('sponsors_suffix')) ?> Sponsors</div>
            <div class="counter"><strong data-count="<?= (int) $helped ?>">0</strong> Students Helped</div>
        </div>
    </div>
</section>
<section class="section" style="padding-top:0">
    <div class="container split">
        <div>
            <h2><?= e(setting('about_home_heading')) ?></h2>
            <p><?= e(setting('about_home')) ?></p>
        </div>
        <div>
            <h3><?= e(setting('steps_heading')) ?></h3>
            <div class="steps">
                <?php foreach ($steps as $step): ?>
                    <div class="step"><div><strong><?= e($step['title']) ?></strong><p><?= e($step['body']) ?></p></div></div>
                <?php endforeach; ?>
            </div>
            <details>
                <summary>Paper and ZIP route</summary>
                <div class="steps">
                    <?php foreach ($legacySteps as $step): ?>
                        <div class="step"><div><p><?= e($step['body']) ?></p></div></div>
                    <?php endforeach; ?>
                </div>
            </details>
        </div>
    </div>
</section>
<section class="section" id="apply" style="padding-top:0">
    <div class="container card">
        <p class="kicker"><?= e(setting('call_kicker')) ?></p>
        <h2><?= e($cycle['title'] ?? 'Scholarship Programme') ?></h2>
        <p><?= e($cycle['announcement'] ?? setting('call_body')) ?></p>
        <p><span class="badge badge-info"><?= e($cycleLabel) ?></span></p>
        <?php if (!empty($cycle['closes_at']) && $cycleState === 'open'): ?>
            <p>Deadline: <strong data-countdown="<?= e(gmdate('c', strtotime((string) $cycle['closes_at'] . ' UTC'))) ?>"><?= e(display_date((string) $cycle['closes_at'], true)) ?></strong></p>
        <?php endif; ?>
        <p class="notice"><?= e(setting('free_notice')) ?></p>
        <p>
            <a class="btn btn-gold" href="<?= e(url('apply')) ?>">Apply Now</a>
            <a class="btn btn-navy" href="<?= e(url('apply/form.pdf')) ?>">Download application form</a>
        </p>
        <p class="fine"><?= e(setting('call_zip_note')) ?></p>
    </div>
</section>
<section class="section" style="padding-top:0">
    <div class="container grid-3">
        <article class="card">
            <h2><?= e(setting('campaign_text')) ?></h2>
            <a class="btn btn-gold" href="<?= e(url('donate')) ?>">Donate to this cause</a>
        </article>
        <article class="card">
            <h2><?= e(setting('events_heading')) ?></h2>
            <p><?= e(setting('events_sub')) ?></p>
            <?php if ($events === []): ?><p class="empty"><?= e(setting('events_empty')) ?></p><?php endif; ?>
            <?php foreach ($events as $event): ?>
                <p><a href="<?= e(url('events/' . $event['slug'])) ?>"><?= e($event['title']) ?></a></p>
            <?php endforeach; ?>
        </article>
        <article class="card">
            <h2><?= e(setting('news_heading')) ?></h2>
            <p><?= e(setting('news_sub')) ?></p>
            <?php if ($news === []): ?><p class="empty"><?= e(setting('news_empty')) ?></p><?php endif; ?>
            <?php foreach ($news as $post): ?>
                <p><a href="<?= e(url('news/' . $post['slug'])) ?>"><?= e($post['title']) ?></a></p>
            <?php endforeach; ?>
        </article>
    </div>
</section>
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="band">
            <div>
                <h2>Get involved</h2>
                <p><?= e(setting('get_involved_band')) ?></p>
            </div>
            <a class="btn btn-gold" href="<?= e(url('contact')) ?>">Get involved now</a>
        </div>
    </div>
</section>
