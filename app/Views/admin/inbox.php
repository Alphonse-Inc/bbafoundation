<?php foreach ($rows as $row): ?>
    <article class="card">
        <p><strong><?= e($row['subject']) ?></strong> · <?= e($row['status']) ?> · <?= e(display_date((string) $row['created_at'], true)) ?></p>
        <p><?= e($row['full_name']) ?> · <?= e($row['email']) ?> · <?= e($row['phone']) ?></p>
        <p><?= e($row['message']) ?></p>
        <form method="post" action="<?= e(url('admin/inbox/' . $row['id'])) ?>"><?= csrf_field() ?>
            <select name="status"><?php foreach (['new', 'read', 'replied', 'archived'] as $status): ?><option <?= selected($row['status'], $status) ?>><?= e($status) ?></option><?php endforeach; ?></select>
            <button class="btn btn-small btn-navy" type="submit">Update</button>
        </form>
    </article>
<?php endforeach; ?>
<?php if ($rows === []): ?><p class="empty">No messages.</p><?php endif; ?>
<?php $path = '/admin/inbox'; require dirname(__DIR__) . '/partials/pagination.php'; ?>
