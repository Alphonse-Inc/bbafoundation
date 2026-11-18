<p><a class="btn btn-gold btn-small" href="<?= e(url('admin/trustees/0')) ?>">New trustee</a></p>
<div class="table-wrap"><table>
    <tr><th>Order</th><th>Name</th><th>Role</th><th>Active</th></tr>
    <?php foreach ($rows as $row): ?><tr><td><?= (int) $row['sort_order'] ?></td><td><a href="<?= e(url('admin/trustees/' . $row['id'])) ?>"><?= e($row['listing_name']) ?></a></td><td><?= e($row['role_label']) ?></td><td><?= (int) $row['is_active'] === 1 ? 'Yes' : 'No' ?></td></tr><?php endforeach; ?>
</table></div>
<?php $path = '/admin/trustees'; require dirname(__DIR__) . '/partials/pagination.php'; ?>
