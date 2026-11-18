<?php $cycle = $cycle ?? ['id' => 0, 'title' => '', 'announcement' => setting('call_body'), 'award_count' => 10, 'status' => 'draft', 'min_cgpa' => '3.50', 'poly_min' => 'upper_credit', 'require_indigene' => 1, 'allow_ijero' => 1, 'allow_ewu' => 1, 'allow_other' => 0, 'require_government' => 1, 'hard_block' => 0, 'referee_links' => 1, 'legacy_zip' => 0, 'blind_review' => 0, 'max_file_bytes' => 5242880, 'max_total_bytes' => 20971520, 'opens_at' => '', 'closes_at' => '', 'extension_until' => '']; ?>
<form method="post" action="<?= e(url('admin/cycles')) ?>" class="form-grid card">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) ($cycle['id'] ?? 0) ?>">
    <label>Title<input name="title" required value="<?= e((string) $cycle['title']) ?>"></label>
    <label>Announcement<textarea name="announcement"><?= e((string) $cycle['announcement']) ?></textarea></label>
    <label>Opens<input name="opens_at" value="<?= e(\BBAF\Core\Dates::inputValue($cycle['opens_at'] ?: null)) ?>" placeholder="DD/MM/YYYY HH:MM"></label>
    <label>Closes<input name="closes_at" value="<?= e(\BBAF\Core\Dates::inputValue($cycle['closes_at'] ?: null)) ?>" placeholder="Leave blank until the Foundation sets it"></label>
    <label>Extension until<input name="extension_until" value="<?= e(\BBAF\Core\Dates::inputValue($cycle['extension_until'] ?: null)) ?>"></label>
    <label>Number of awards<input name="award_count" type="number" value="<?= (int) $cycle['award_count'] ?>"></label>
    <label>Status
        <select name="status"><?php foreach (['draft','open','closed','under_review','results_announced','archived'] as $status): ?><option <?= selected($cycle['status'], $status) ?>><?= e($status) ?></option><?php endforeach; ?></select>
    </label>
    <label>Minimum university CGPA<input name="min_cgpa" value="<?= e((string) $cycle['min_cgpa']) ?>"></label>
    <label>Polytechnic minimum<input name="poly_min" value="<?= e((string) $cycle['poly_min']) ?>"></label>
    <label>Max file MB<input name="max_file_mb" value="<?= (int) round(((int) $cycle['max_file_bytes']) / 1048576) ?>"></label>
    <label>Max total MB<input name="max_total_mb" value="<?= (int) round(((int) $cycle['max_total_bytes']) / 1048576) ?>"></label>
    <?php foreach (['require_indigene' => 'Require indigene community', 'allow_ijero' => 'Allow Ijero-Ekiti', 'allow_ewu' => 'Allow Ewu-Ekiti', 'allow_other' => 'Allow other communities', 'require_government' => 'Require government-owned institution', 'hard_block' => 'Hard-block below the academic threshold', 'referee_links' => 'Email referee and HOD links', 'legacy_zip' => 'Allow a legacy ZIP upload', 'blind_review' => 'Hide identity from reviewers'] as $name => $label): ?>
        <label><input type="checkbox" name="<?= e($name) ?>" value="1" <?= checked((int) ($cycle[$name] ?? 0) === 1) ?>> <?= e($label) ?></label>
    <?php endforeach; ?>
    <?php if (!empty($documents)): ?>
        <h2>Required documents</h2>
        <?php foreach ($documents as $document): ?>
            <label><?= e($document['doc_key']) ?><input name="doc_label[<?= (int) $document['id'] ?>]" value="<?= e($document['label']) ?>"></label>
            <label><input type="checkbox" name="doc_required[<?= (int) $document['id'] ?>]" value="1" <?= checked((int) $document['is_required'] === 1) ?>> Required</label>
            <?php if ($document['flag_note'] !== ''): ?><p class="fine"><?= e($document['flag_note']) ?></p><?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    <button class="btn btn-gold" type="submit">Save cycle</button>
</form>
