<form method="get" class="filters">
    <label>Search<input name="q" value="<?= e((string) ($_GET['q'] ?? '')) ?>"></label>
    <label>Status<input name="status" value="<?= e((string) ($_GET['status'] ?? '')) ?>"></label>
    <label>Community<input name="community" value="<?= e((string) ($_GET['community'] ?? '')) ?>"></label>
    <label>Institution<input name="institution" value="<?= e((string) ($_GET['institution'] ?? '')) ?>"></label>
    <label>Level<input name="current_level" value="<?= e((string) ($_GET['current_level'] ?? '')) ?>"></label>
    <label>Sex<input name="sex" value="<?= e((string) ($_GET['sex'] ?? '')) ?>"></label>
    <label>CGPA from<input name="cgpa_min" value="<?= e((string) ($_GET['cgpa_min'] ?? '')) ?>"></label>
    <label>CGPA to<input name="cgpa_max" value="<?= e((string) ($_GET['cgpa_max'] ?? '')) ?>"></label>
    <label><input type="checkbox" name="flag" value="1" <?= checked(($_GET['flag'] ?? '') === '1') ?>> Flags only</label>
    <?php if (!$blind): ?>
        <label>Reviewer
            <select name="reviewer"><option value="">Any</option><?php foreach ($reviewers as $reviewer): ?><option value="<?= (int) $reviewer['id'] ?>"<?= selected($_GET['reviewer'] ?? '', $reviewer['id']) ?>><?= e(trim($reviewer['surname'] . ' ' . $reviewer['first_name'])) ?></option><?php endforeach; ?></select>
        </label>
    <?php endif; ?>
    <button class="btn btn-navy btn-small" type="submit">Filter</button>
    <?php if (!$blind): ?><a class="btn btn-line btn-small" href="<?= e(url('admin/applications/export') . '?' . http_build_query($_GET)) ?>">Export CSV</a><?php endif; ?>
</form>
<form method="post" action="<?= e(url('admin/applications/bulk')) ?>">
    <?= csrf_field() ?>
    <div class="table-wrap"><table>
        <tr><th></th><th>Reference</th><th>Name</th><th>Status</th><th>Community</th><th>Institution</th><th>Level</th><th>CGPA</th><th>Eligibility</th></tr>
        <?php foreach ($rows as $row): $hide = $blind && (int) $row['blind_review'] === 1; ?>
            <tr>
                <td><?php if (!$blind): ?><input type="checkbox" name="ids[]" value="<?= (int) $row['id'] ?>"><?php endif; ?></td>
                <td><a href="<?= e(url('admin/applications/' . $row['id'])) ?>"><?= e((string) ($row['reference'] ?: 'Draft ' . $row['id'])) ?></a></td>
                <td><?= $hide ? 'Hidden' : e(trim($row['surname'] . ' ' . $row['other_names'])) ?></td>
                <td><?= e($row['status']) ?></td>
                <td><?= e($row['community']) ?></td>
                <td><?= e($row['institution_name']) ?></td>
                <td><?= e($row['current_level']) ?></td>
                <td><?= e($row['cgpa']) ?></td>
                <td><?= e($row['eligibility_status']) ?><?php if ((int) $row['duplicate_flag'] === 1 || (int) $row['academic_flag'] === 1): ?> <span class="badge">Flag</span><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
    </table></div>
    <?php if (!$blind): ?>
        <label>Bulk action
            <select name="bulk"><option value="shortlist">Shortlist selected</option><option value="message">Message selected</option></select>
        </label>
        <label>Message<textarea name="body"></textarea></label>
        <button class="btn btn-gold btn-small" type="submit">Apply</button>
    <?php endif; ?>
</form>
<?php $path = '/admin/applications'; require dirname(__DIR__) . '/partials/pagination.php'; ?>
