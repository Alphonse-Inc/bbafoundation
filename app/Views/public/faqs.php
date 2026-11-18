<section class="section">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><ol><li><a href="<?= e(url('/')) ?>">Home</a></li><li>FAQs</li></ol></nav>
        <h1>Frequently Asked Questions (FAQs)</h1>
        <label>Search<input type="search" data-faq-search placeholder="Search the questions"></label>
        <?php foreach ($groups as $group): ?>
            <h2><?= e($group['title']) ?></h2>
            <div class="accordion">
                <?php foreach ($group['items'] as $item): ?>
                    <details data-faq>
                        <summary><?= e($item['question']) ?></summary>
                        <p><?= e($item['answer']) ?></p>
                        <?php if ($item['is_addition']): ?><p class="fine">Added for the online application. The Foundation can edit this answer.</p><?php endif; ?>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
