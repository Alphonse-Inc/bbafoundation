<form method="post" action="<?= e(url('admin/settings')) ?>" class="form-grid"><?= csrf_field() ?>
    <?php foreach ($rows as $row): ?>
        <label><?= e($row['skey']) ?>
            <?php if ($row['skey'] === 'smtp_pass'): ?>
                <input type="password" name="settings[smtp_pass]" value="" autocomplete="new-password" placeholder="Leave blank to keep the current password">
            <?php elseif (strlen((string) $row['svalue']) > 180): ?>
                <textarea name="settings[<?= e($row['skey']) ?>]"><?= e((string) $row['svalue']) ?></textarea>
            <?php else: ?>
                <input name="settings[<?= e($row['skey']) ?>]" value="<?= e((string) $row['svalue']) ?>">
            <?php endif; ?>
        </label>
    <?php endforeach; ?>
    <button class="btn btn-gold" type="submit">Save settings</button>
</form>
<form method="post" action="<?= e(url('admin/smtp-test')) ?>"><?= csrf_field() ?><button class="btn btn-line" type="submit">Send a test email to me</button></form>
