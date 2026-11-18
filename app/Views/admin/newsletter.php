<p><a href="<?= e(url('admin/newsletter.csv')) ?>">Export CSV</a></p>
<div class="table-wrap"><table>
    <tr><th>Email</th><th>Status</th><th>Confirmed</th></tr>
    <?php foreach ($rows as $row): ?><tr><td><?= e($row['email']) ?></td><td><?= e($row['status']) ?></td><td><?= e(display_date((string) $row['confirmed_at'], true)) ?></td></tr><?php endforeach; ?>
</table></div>
<?php $path = '/admin/newsletter'; require dirname(__DIR__) . '/partials/pagination.php'; ?>
