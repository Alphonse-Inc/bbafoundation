<p><a class="btn btn-gold btn-small" href="<?= e(url('admin/events/0')) ?>">New event</a></p>
<div class="table-wrap"><table>
    <tr><th>Title</th><th>Status</th><th>Starts</th></tr>
    <?php foreach ($rows as $row): ?><tr><td><a href="<?= e(url('admin/events/' . $row['id'])) ?>"><?= e($row['title']) ?></a><?php if ((int) $row['is_demo'] === 1): ?> <span class="badge">Demo</span><?php endif; ?></td><td><?= e($row['status']) ?></td><td><?= e(display_date((string) $row['starts_at'], true)) ?></td></tr><?php endforeach; ?>
</table></div>
<?php $path = '/admin/events'; require dirname(__DIR__) . '/partials/pagination.php'; ?>
