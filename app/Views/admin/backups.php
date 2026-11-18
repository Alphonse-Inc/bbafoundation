<form method="post" action="<?= e(url('admin/backups')) ?>"><?= csrf_field() ?><button class="btn btn-gold" type="submit">Back up now</button></form>
<p class="fine">Backups stay in storage/backups, outside the public site. SMTP passwords are left blank in the SQL file.</p>
<ul><?php foreach ($rows as $row): ?><li><?= e(display_date((string) $row['created_at'], true)) ?> · <?= e($row['status']) ?> · <?= e($row['note']) ?></li><?php endforeach; ?></ul>
