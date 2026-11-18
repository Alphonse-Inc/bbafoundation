<p><a class="btn btn-gold btn-small" href="<?= e(url('admin/pages/0')) ?>">New page</a></p>
<div class="table-wrap"><table>
    <tr><th>Title</th><th>Slug</th><th>Status</th></tr>
    <?php foreach ($rows as $row): ?><tr><td><a href="<?= e(url('admin/pages/' . $row['id'])) ?>"><?= e($row['title']) ?></a></td><td><?= e($row['slug']) ?></td><td><?= e($row['status']) ?></td></tr><?php endforeach; ?>
</table></div>
<?php $path = '/admin/pages'; require dirname(__DIR__) . '/partials/pagination.php'; ?>
