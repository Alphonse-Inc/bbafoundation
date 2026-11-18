<div class="table-wrap"><table>
    <tr><th>Applicant</th><th>Year</th><th>CGPA</th><th>Status</th><th>Decision</th></tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['reference']) ?> <?= e(trim($row['surname'] . ' ' . $row['other_names'])) ?></td>
            <td><?= e($row['academic_year']) ?></td>
            <td><?= e($row['cgpa']) ?></td>
            <td><?= e($row['status']) ?></td>
            <td>
                <form method="post" action="<?= e(url('admin/renewals/' . $row['id'])) ?>"><?= csrf_field() ?>
                    <select name="status"><option value="approved">Approve</option><option value="deferred">Defer</option><option value="ended">End award</option></select>
                    <input name="review_note" placeholder="Note">
                    <button class="btn btn-small btn-navy" type="submit">Save</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table></div>
<?php $path = '/admin/renewals'; require dirname(__DIR__) . '/partials/pagination.php'; ?>
