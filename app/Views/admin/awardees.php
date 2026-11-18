<p>Students helped (accepted awards): <?= (int) $progress['helped'] ?> · Target <?= (int) $progress['target'] ?></p>
<div class="table-wrap"><table>
    <tr><th>Reference</th><th>Name</th><th>Status</th><th>Code</th><th>Update</th></tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e((string) $row['reference']) ?></td>
            <td><?= e(trim($row['surname'] . ' ' . $row['other_names'])) ?></td>
            <td><?= e($row['status']) ?></td>
            <td><?= e($row['verification_code']) ?></td>
            <td>
                <form method="post" action="<?= e(url('admin/awardees/' . $row['id'])) ?>"><?= csrf_field() ?>
                    <select name="status"><?php foreach (['pending_acceptance', 'active', 'deferred', 'ended', 'declined'] as $status): ?><option <?= selected($row['status'], $status) ?>><?= e($status) ?></option><?php endforeach; ?></select>
                    <input name="expected_graduation" value="<?= e((string) $row['expected_graduation']) ?>" placeholder="Graduation">
                    <input name="ended_reason" value="<?= e((string) $row['ended_reason']) ?>" placeholder="Reason if ended">
                    <button class="btn btn-small btn-navy" type="submit">Save</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table></div>
<?php $path = '/admin/awardees'; require dirname(__DIR__) . '/partials/pagination.php'; ?>
