<?php foreach ($rows as $row): ?>
    <article class="card">
        <p><?= e($row['request_type']) ?> · <?= e($row['email']) ?> · <?= e($row['status']) ?></p>
        <p><?= e((string) $row['details']) ?></p>
        <?php if ($row['admin_note'] !== ''): ?><p class="fine"><?= e($row['admin_note']) ?></p><?php endif; ?>
        <?php if ($row['export_path'] !== ''): ?><p><a href="<?= e(url('admin/requests/' . $row['id'] . '/file')) ?>">Download export</a></p><?php endif; ?>
        <form method="post" action="<?= e(url('admin/requests/' . $row['id'])) ?>"><?= csrf_field() ?>
            <label>Note<input name="admin_note" value="<?= e($row['admin_note']) ?>"></label>
            <button class="btn btn-gold btn-small" name="action" value="fulfil" type="submit">Fulfil</button>
            <button class="btn btn-line btn-small" name="action" value="note" type="submit">Save note</button>
        </form>
    </article>
<?php endforeach; ?>
<?php if ($rows === []): ?><p class="empty">No requests.</p><?php endif; ?>
