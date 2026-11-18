<form method="get"><label>Search<input name="q" value="<?= e((string) ($_GET['q'] ?? '')) ?>"></label><button class="btn btn-small btn-navy" type="submit">Search</button></form>
<div class="table-wrap"><table>
    <tr><th>When</th><th>User</th><th>Action</th><th>Summary</th></tr>
    <?php foreach ($rows as $row): ?><tr><td><?= e(display_date((string) $row['created_at'], true)) ?></td><td><?= e((string) $row['user_id']) ?></td><td><?= e($row['action']) ?></td><td><?= e($row['summary']) ?></td></tr><?php endforeach; ?>
</table></div>
<?php $path = '/admin/audit'; require dirname(__DIR__) . '/partials/pagination.php'; ?>
