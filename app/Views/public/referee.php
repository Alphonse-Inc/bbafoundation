<h1><?= e($referee['kind'] === 'hod' ? 'Head of Department attestation' : 'Referee recommendation') ?></h1>
<p>This link works once.</p>
<form method="post" action="<?= e(url('r/' . $token)) ?>" enctype="multipart/form-data" class="form-grid">
    <?= csrf_field() ?>
    <label>Recommendation or attestation<textarea name="response"></textarea></label>
    <label>Or upload a signed PDF or image<input type="file" name="letter" accept=".pdf,.jpg,.jpeg,.png"></label>
    <button class="btn btn-gold" type="submit">Submit</button>
</form>
