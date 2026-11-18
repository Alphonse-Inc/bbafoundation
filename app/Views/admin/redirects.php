<form method="post" action="<?= e(url('admin/redirects')) ?>" class="form-grid card"><?= csrf_field() ?>
    <label>From<input name="source_path" placeholder="/old-path" required></label>
    <label>To<input name="target_path" placeholder="/new-path"></label>
    <label>Code<select name="status_code"><option value="301">301</option><option value="302">302</option><option value="410">410</option></select></label>
    <label>Note<input name="note"></label>
    <button class="btn btn-gold" type="submit">Add redirect</button>
</form>
<div class="table-wrap"><table>
    <tr><th>From</th><th>To</th><th>Code</th><th>Note</th></tr>
    <?php foreach ($rows as $row): ?><tr><td><?= e($row['source_path']) ?></td><td><?= e($row['target_path']) ?></td><td><?= (int) $row['status_code'] ?></td><td><?= e($row['note']) ?></td></tr><?php endforeach; ?>
</table></div>
<?php $path = '/admin/redirects'; require dirname(__DIR__) . '/partials/pagination.php'; ?>
