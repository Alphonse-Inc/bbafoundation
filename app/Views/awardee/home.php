<section class="section"><div class="container">
    <h1>Award</h1>
    <?php if (!$award): ?>
        <p class="empty">There is no award on this account.</p>
    <?php else: ?>
        <p>Status: <span class="badge"><?= e($award['status']) ?></span></p>
        <p>Verification code: <?= e($award['verification_code']) ?></p>
        <?php if ($award['accept_deadline']): ?><p>Respond by <?= e(display_date((string) $award['accept_deadline'], true)) ?></p><?php endif; ?>
        <?php if ($award['status'] === 'pending_acceptance'): ?>
            <form method="post" action="<?= e(url('awardee/accept')) ?>"><?= csrf_field() ?><button class="btn btn-gold" type="submit">Accept the award</button></form>
            <form method="post" action="<?= e(url('awardee/decline')) ?>"><?= csrf_field() ?><button class="btn btn-line" type="submit">Decline</button></form>
        <?php else: ?>
            <p><a class="btn btn-navy" href="<?= e(url('awardee/letter.pdf')) ?>">Download award letter</a></p>
        <?php endif; ?>
        <h2>Renewal</h2>
        <form method="post" action="<?= e(url('awardee/renewal')) ?>" enctype="multipart/form-data" class="form-grid card">
            <?= csrf_field() ?>
            <label>Academic year<input name="academic_year" placeholder="2026/2027"></label>
            <label>Updated CGPA or grade<input name="cgpa"></label>
            <label>Notes<textarea name="notes"></textarea></label>
            <label>Enrolment evidence<input type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png"></label>
            <button class="btn btn-gold" type="submit">Submit renewal</button>
        </form>
        <ul><?php foreach ($renewals as $renewal): ?><li><?= e($renewal['academic_year']) ?> · <?= e($renewal['status']) ?></li><?php endforeach; ?></ul>
        <h2>Disbursements</h2>
        <?php if ($disbursements === []): ?><p class="empty">No disbursements have been recorded.</p><?php endif; ?>
        <ul><?php foreach ($disbursements as $row): ?><li><?= e((string) ($row['amount'] ?? '')) ?> <?= e($row['currency']) ?> · <?= e((string) $row['reference_no']) ?> · <?= e(display_date((string) $row['paid_on'])) ?></li><?php endforeach; ?></ul>
        <h2>Public profile</h2>
        <form method="post" action="<?= e(url('awardee/profile')) ?>" class="form-grid">
            <?= csrf_field() ?>
            <label><input type="checkbox" name="showcase_consent" value="1" <?= checked((int) $award['showcase_consent'] === 1) ?>> Show my name on the public scholars page</label>
            <label>Short public note<textarea name="public_note"><?= e((string) $award['public_note']) ?></textarea></label>
            <label>Expected graduation<input name="expected_graduation" value="<?= e((string) $award['expected_graduation']) ?>" placeholder="MM/YYYY"></label>
            <button class="btn btn-navy" type="submit">Save</button>
        </form>
    <?php endif; ?>
</div></section>
