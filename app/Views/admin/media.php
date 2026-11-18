<form method="post" action="<?= e(url('admin/media')) ?>" enctype="multipart/form-data" class="form-grid"><?= csrf_field() ?>
    <label>Image<input type="file" name="file" accept=".jpg,.jpeg,.png,.webp" required></label>
    <label>Alt text<input name="alt_text"></label>
    <button class="btn btn-gold" type="submit">Upload</button>
</form>
<div class="table-wrap"><table>
    <tr><th>File</th><th>Alt</th><th>Size</th></tr>
    <?php foreach ($rows as $row): ?><tr><td><a href="<?= e(url($row['public_path'])) ?>"><?= e($row['original_name']) ?></a></td><td><?= e($row['alt_text']) ?></td><td><?= (int) $row['byte_size'] ?></td></tr><?php endforeach; ?>
</table></div>
<?php $path = '/admin/media'; require dirname(__DIR__) . '/partials/pagination.php'; ?>
