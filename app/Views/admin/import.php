<?php if ($pending > 0): ?>
    <p class="notice-warn notice">Do not delete the previous website until every file below shows Imported. <?= (int) $pending ?> file(s) are still outstanding. Keep an offline backup of the old site as well.</p>
<?php else: ?>
    <p class="notice">Every file in the manifest is imported. Keep an offline backup before the old site is removed. The mailbox and DNS for info@bbafoundation.com are separate from the website.</p>
<?php endif; ?>
<?php if (!$canDownload): ?>
    <p>This server cannot download files. Save each file below into <code>public/assets/img/legacy/</code> using the file name at the end of the URL, then refresh this page.</p>
<?php else: ?>
    <form method="post" action="<?= e(url('admin/import')) ?>"><?= csrf_field() ?><button class="btn btn-gold" type="submit">Download the manifest</button></form>
<?php endif; ?>
<div class="table-wrap"><table>
    <tr><th>Status</th><th>URL</th><th>Local</th><th>Error</th></tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['status']) ?></td>
            <td><?= e($row['source_url']) ?></td>
            <td><?= e($row['local_path']) ?></td>
            <td><?= e($row['error_text']) ?></td>
        </tr>
    <?php endforeach; ?>
</table></div>
