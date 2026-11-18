<p><a class="btn btn-gold btn-small" href="<?= e(url('admin/cycles/0')) ?>">New cycle</a></p>
<div class="table-wrap"><table>
    <tr><th>Title</th><th>Status</th><th>Opens</th><th>Closes</th></tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><a href="<?= e(url('admin/cycles/' . $row['id'])) ?>"><?= e($row['title']) ?></a></td>
            <td><?= e($row['status']) ?></td>
            <td><?= e(display_date((string) $row['opens_at'], true)) ?></td>
            <td><?= e(display_date((string) $row['closes_at'], true)) ?></td>
        </tr>
    <?php endforeach; ?>
</table></div>
<?php $path = '/admin/cycles'; require dirname(__DIR__) . '/partials/pagination.php'; ?>
