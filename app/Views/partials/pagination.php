<?php
/** @var array $meta */
/** @var string $path */
$query = $_GET;
unset($query['page']);
$link = static function (int $page) use ($path, $query): string {
    $query['page'] = $page;
    return url(ltrim($path, '/')) . '?' . http_build_query($query);
};
?>
<nav class="pagination" aria-label="Pagination">
    <span>Showing <?= (int) $meta['from'] ?>–<?= (int) $meta['to'] ?> of <?= (int) $meta['total'] ?></span>
    <?php if ($meta['page'] > 1): ?>
        <a href="<?= e($link(1)) ?>">First</a>
        <a href="<?= e($link($meta['page'] - 1)) ?>">Previous</a>
    <?php endif; ?>
    <?php for ($i = max(1, $meta['page'] - 2); $i <= min($meta['pages'], $meta['page'] + 2); $i++): ?>
        <?php if ($i === (int) $meta['page']): ?>
            <span class="current" aria-current="page"><?= $i ?></span>
        <?php else: ?>
            <a href="<?= e($link($i)) ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($meta['page'] < $meta['pages']): ?>
        <a href="<?= e($link($meta['page'] + 1)) ?>">Next</a>
        <a href="<?= e($link($meta['pages'])) ?>">Last</a>
    <?php endif; ?>
    <?php foreach (\BBAF\Core\Paginator::SIZES as $size): ?>
        <?php $query['per_page'] = $size; $query['page'] = 1; ?>
        <a href="<?= e(url(ltrim($path, '/')) . '?' . http_build_query($query)) ?>" <?= (int) $meta['per_page'] === $size ? 'aria-current="true"' : '' ?>><?= $size ?></a>
    <?php endforeach; ?>
</nav>
