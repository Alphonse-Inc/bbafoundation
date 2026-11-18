<?php if ($cycle): ?>
    <p><?= e($cycle['title']) ?> · <span class="badge badge-info"><?= e($state) ?></span></p>
    <?php if (!empty($cycle['closes_at'])): ?><p data-countdown="<?= e(gmdate('c', strtotime((string) $cycle['closes_at'] . ' UTC'))) ?>"></p><?php else: ?><p class="fine">No deadline has been set.</p><?php endif; ?>
<?php else: ?>
    <p>No cycle has been created.</p>
<?php endif; ?>
<div class="grid-4">
    <article class="card"><p>Today</p><p class="stat"><?= (int) $today ?></p></article>
    <article class="card"><p>Applications</p><p class="stat"><?= (int) $total ?></p></article>
    <article class="card"><p>Awards / target</p><p class="stat"><?= (int) $awards ?>/<?= (int) $target ?></p></article>
    <article class="card"><p>Students helped</p><p class="stat"><?= (int) $progress['helped'] ?></p></article>
</div>
<h2>Completeness</h2>
<ul><?php foreach ($byStatus as $row): ?><li><?= e($row['status']) ?>: <?= (int) $row['total'] ?></li><?php endforeach; ?></ul>
<h2>Recent activity</h2>
<ul><?php foreach ($activity as $row): ?><li><?= e(display_date((string) $row['created_at'], true)) ?> · <?= e($row['summary']) ?></li><?php endforeach; ?></ul>
<p>
    <a class="btn btn-navy btn-small" href="<?= e(url('admin/applications')) ?>">Applications</a>
    <a class="btn btn-line btn-small" href="<?= e(url('admin/cycles')) ?>">Cycles</a>
</p>
