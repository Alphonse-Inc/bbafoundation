<ol>
    <li>Contain the incident: disable the affected account, rotate the application key only with a planned re-encryption, and take the site into maintenance if documents may be exposed.</li>
    <li>Record what was accessed, when it was discovered, and who is handling it.</li>
    <li>Preserve logs in storage/logs and the audit log. Do not put NIN or document contents into the incident note.</li>
    <li>Notify the Foundation’s data protection contact and, where the Nigeria Data Protection Act requires it, the Commission and the affected people.</li>
    <li>Record the dates of containment, notification and any report below by editing this log with a follow-up note in the description.</li>
</ol>
<form method="post" action="<?= e(url('admin/breach')) ?>" class="form-grid"><?= csrf_field() ?>
    <label>Title<input name="title" required></label>
    <label>Discovered<input name="discovered_at" placeholder="DD/MM/YYYY HH:MM"></label>
    <label>What happened<textarea name="description" required></textarea></label>
    <button class="btn btn-gold" type="submit">Log incident</button>
</form>
<ul><?php foreach ($rows as $row): ?><li><?= e(display_date((string) $row['created_at'], true)) ?> · <?= e($row['title']) ?> · <?= e($row['status']) ?></li><?php endforeach; ?></ul>
