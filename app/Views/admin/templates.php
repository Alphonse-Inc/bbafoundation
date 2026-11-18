<?php foreach ($rows as $row): ?>
    <form method="post" action="<?= e(url('admin/templates/' . $row['id'])) ?>" class="card form-grid"><?= csrf_field() ?>
        <h2><?= e($row['name']) ?></h2>
        <label>Subject<input name="subject" value="<?= e($row['subject']) ?>"></label>
        <label>HTML<textarea name="body_html" rows="6"><?= e($row['body_html']) ?></textarea></label>
        <label>Text<textarea name="body_text" rows="4"><?= e($row['body_text']) ?></textarea></label>
        <button class="btn btn-small btn-navy" type="submit">Save</button>
    </form>
<?php endforeach; ?>
