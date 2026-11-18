<p>The institution list is a set of editable suggestions, not a complete directory of Nigerian schools.</p>
<form method="get"><label>Search<input name="q" value="<?= e((string) ($_GET['q'] ?? '')) ?>"></label><button class="btn btn-small" type="submit">Search</button></form>
<form method="post" action="<?= e(url('admin/institutions')) ?>" class="form-grid card"><?= csrf_field() ?>
    <label>Name<input name="name" required></label>
    <label>Type<select name="institution_type"><option value="university">university</option><option value="polytechnic">polytechnic</option></select></label>
    <label>Ownership<select name="ownership"><option value="government">government</option><option value="private">private</option></select></label>
    <label>State<input name="state_name"></label>
    <button class="btn btn-gold btn-small" type="submit">Add institution</button>
</form>
<div class="table-wrap"><table>
    <tr><th>Name</th><th>Type</th><th>Ownership</th><th>State</th></tr>
    <?php foreach ($rows as $row): ?><tr><td><?= e($row['name']) ?></td><td><?= e($row['institution_type']) ?></td><td><?= e($row['ownership']) ?></td><td><?= e($row['state_name']) ?></td></tr><?php endforeach; ?>
</table></div>
<?php $path = '/admin/institutions'; require dirname(__DIR__) . '/partials/pagination.php'; ?>
<h2>Ekiti LGAs</h2>
<p class="fine">Ewu-Ekiti is recorded under Ekiti West, and Ijero-Ekiti under Ijero. Both notes are editable.</p>
<form method="post" action="<?= e(url('admin/lgas')) ?>" class="form-grid"><?= csrf_field() ?>
    <label>Name<input name="name" required></label>
    <label>State<input name="state_name" value="Ekiti"></label>
    <label>Note<input name="note"></label>
    <button class="btn btn-navy btn-small" type="submit">Save LGA</button>
</form>
<ul><?php foreach ($lgas as $lga): ?><li><?= e($lga['name']) ?><?php if ($lga['note'] !== ''): ?> — <?= e($lga['note']) ?><?php endif; ?></li><?php endforeach; ?></ul>
