<form method="post" action="<?= e(url('admin/faqs')) ?>" class="form-grid card"><?= csrf_field() ?>
    <input type="hidden" name="id" value="0">
    <label>Group<select name="group_id"><?php foreach ($groups as $group): ?><option value="<?= (int) $group['id'] ?>"><?= e($group['title']) ?></option><?php endforeach; ?></select></label>
    <label>Order<input name="sort_order" value="0"></label>
    <label>Question<input name="question" required></label>
    <label>Answer<textarea name="answer"></textarea></label>
    <label>Flag note for administrators<input name="flag_note"></label>
    <label><input type="checkbox" name="is_visible" value="1" checked> Visible</label>
    <label><input type="checkbox" name="is_addition" value="1"> Mark as an addition that still needs Foundation approval</label>
    <button class="btn btn-gold" type="submit">Add FAQ</button>
</form>
<?php foreach ($rows as $row): ?>
    <form method="post" action="<?= e(url('admin/faqs')) ?>" class="card form-grid"><?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
        <p class="fine"><?= e($row['group_title']) ?><?php if ((int) $row['is_addition'] === 1): ?> · addition<?php endif; ?></p>
        <label>Group<select name="group_id"><?php foreach ($groups as $group): ?><option value="<?= (int) $group['id'] ?>"<?= selected($row['group_id'], $group['id']) ?>><?= e($group['title']) ?></option><?php endforeach; ?></select></label>
        <label>Order<input name="sort_order" value="<?= (int) $row['sort_order'] ?>"></label>
        <label>Question<input name="question" value="<?= e($row['question']) ?>"></label>
        <label>Answer<textarea name="answer"><?= e($row['answer']) ?></textarea></label>
        <label>Flag note<input name="flag_note" value="<?= e($row['flag_note']) ?>"></label>
        <label><input type="checkbox" name="is_visible" value="1" <?= checked((int) $row['is_visible'] === 1) ?>> Visible</label>
        <label><input type="checkbox" name="is_addition" value="1" <?= checked((int) $row['is_addition'] === 1) ?>> Addition awaiting approval</label>
        <button class="btn btn-small btn-navy" type="submit">Save</button>
    </form>
<?php endforeach; ?>
