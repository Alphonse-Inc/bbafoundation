<p>Automatic deletion is <?= setting('retention_auto') === '1' ? 'on' : 'off' ?>. Change <code>retention_auto</code> in settings if the nightly job should delete. This page always previews first unless you clear the dry-run box.</p>
<form method="post" action="<?= e(url('admin/retention')) ?>"><?= csrf_field() ?>
    <label><input type="checkbox" name="dry_run" value="1" checked> Dry run only</label>
    <?php if ($rows === []): ?><p class="empty">No applications are due for retention.</p><?php endif; ?>
    <ul>
        <?php foreach ($rows as $row): ?>
            <li><label><input type="checkbox" name="ids[]" value="<?= (int) $row['id'] ?>" checked> <?= e((string) $row['reference']) ?> · <?= e($row['status']) ?> · <?= e($row['title']) ?></label></li>
        <?php endforeach; ?>
    </ul>
    <button class="btn btn-navy" type="submit">Run</button>
</form>
