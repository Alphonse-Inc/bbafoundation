<section class="section">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><ol><li><a href="<?= e(url('/')) ?>">Home</a></li><li>Apply</li></ol></nav>
        <p class="kicker"><?= e(setting('call_kicker')) ?></p>
        <h1><?= e($cycle['title'] ?? 'Scholarship') ?></h1>
        <p><?= e($cycle['announcement'] ?? setting('call_body')) ?></p>
        <p><span class="badge badge-info"><?= e($cycleLabel) ?></span></p>
        <?php if (!empty($cycle['closes_at']) && $cycleState === 'open'): ?>
            <p data-countdown="<?= e(gmdate('c', strtotime((string) $cycle['closes_at'] . ' UTC'))) ?>"></p>
        <?php endif; ?>
        <p class="notice"><?= e(setting('free_notice')) ?></p>
        <div class="grid-3">
            <?php foreach ($involved as $card): ?>
                <article class="card"><h2><?= e($card['title']) ?></h2><p><?= e($card['body']) ?></p></article>
            <?php endforeach; ?>
        </div>
        <h2>Required documents</h2>
        <ol><?php foreach ($documents as $document): ?><li><?= e($document['body']) ?></li><?php endforeach; ?></ol>
        <h2><?= e(setting('steps_heading')) ?></h2>
        <div class="steps"><?php foreach ($steps as $step): ?><div class="step"><div><strong><?= e($step['title']) ?></strong><p><?= e($step['body']) ?></p></div></div><?php endforeach; ?></div>
        <form class="card" data-eligibility>
            <h2>Am I eligible?</h2>
            <p>This check is information only. It does not submit an application.</p>
            <label>Are you an indigene of Ijero-Ekiti or Ewu-Ekiti?
                <select name="indigene"><option value="yes">Yes</option><option value="no">No</option></select>
            </label>
            <label>Are you currently enrolled in a government-owned university or polytechnic in Nigeria?
                <select name="enrolled"><option value="yes">Yes</option><option value="no">No</option></select>
            </label>
            <label>Do you have a CGPA of at least 3.50, or Upper Credit for a polytechnic?
                <select name="grades"><option value="yes">Yes</option><option value="no">No</option></select>
            </label>
            <p><button class="btn btn-navy" type="submit">Check</button></p>
            <p data-eligibility-result></p>
        </form>
        <p>
            <a class="btn btn-navy" href="<?= e(url('apply/form.pdf')) ?>">Download application form</a>
            <?php if ($cycleState === 'open'): ?>
                <a class="btn btn-gold" href="<?= e(url('register')) ?>">Start the online application</a>
            <?php else: ?>
                <span class="btn btn-line">Online applications are not open</span>
            <?php endif; ?>
        </p>
        <p><a href="<?= e(url('faqs')) ?>">Read the FAQs</a></p>
    </div>
</section>
