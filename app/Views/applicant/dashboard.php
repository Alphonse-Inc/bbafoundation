<section class="section"><div class="container">
    <h1>Your account</h1>
    <p class="notice"><?= e(setting('free_notice')) ?></p>
    <?php if ($cycle): ?>
        <p><?= e($cycle['title']) ?> · <span class="badge badge-info"><?= e($cycleState) ?></span></p>
    <?php endif; ?>
    <?php if ($application): ?>
        <article class="card">
            <h2>Application <?= e((string) ($application['reference'] ?: 'draft')) ?></h2>
            <p>Status: <?= e((string) $application['status']) ?></p>
            <ol class="timeline">
                <?php foreach (['draft' => 'Draft', 'submitted' => 'Submitted', 'eligibility' => 'Under eligibility check', 'under_review' => 'Under review', 'shortlisted' => 'Shortlisted', 'interview' => 'Interview', 'awarded' => 'Awarded', 'waitlisted' => 'Waitlisted', 'not_selected' => 'Not selected'] as $key => $label): ?>
                    <li class="<?= $application['status'] === $key ? 'done' : '' ?>"><?= e($label) ?></li>
                <?php endforeach; ?>
            </ol>
            <?php if (in_array($application['status'], ['draft', 'reopened'], true)): ?>
                <a class="btn btn-gold" href="<?= e(url('application/step/' . max(1, (int) $application['current_step']))) ?>">Continue application</a>
            <?php else: ?>
                <a class="btn btn-navy" href="<?= e(url('application/acknowledgement.pdf')) ?>">Download acknowledgement</a>
            <?php endif; ?>
        </article>
    <?php elseif ($cycleState === 'open'): ?>
        <a class="btn btn-gold" href="<?= e(url('application')) ?>">Start application</a>
    <?php endif; ?>
    <?php if ($award): ?>
        <p><a href="<?= e(url('awardee')) ?>">Open the awardee page</a></p>
    <?php endif; ?>
    <?php if ($releasedScore !== null): ?>
        <p>Combined review score: <?= e((string) $releasedScore) ?></p>
    <?php elseif ($application && $cycle && (int) $cycle['scores_released'] === 1): ?>
        <p class="fine">Scores have been released. A combined score is not available yet.</p>
    <?php endif; ?>
    <h2>Messages from the secretariat</h2>
    <?php if ($messages === []): ?><p class="empty">No secretariat messages yet.</p><?php endif; ?>
    <ul><?php foreach ($messages as $message): ?><li><?= e($message['body']) ?> <span class="fine"><?= e(display_date((string) $message['created_at'], true)) ?></span></li><?php endforeach; ?></ul>
    <h2>Notices</h2>
    <?php if ($notes === []): ?><p class="empty">No notices yet.</p><?php endif; ?>
    <ul><?php foreach ($notes as $note): ?><li><?= e($note['title']) ?> <span class="fine"><?= e(display_date((string) $note['created_at'], true)) ?></span></li><?php endforeach; ?></ul>
    <form method="post" action="<?= e(url('notifications/read')) ?>"><?= csrf_field() ?><input type="hidden" name="back" value="/dashboard"><button class="btn btn-line btn-small" type="submit">Mark all as read</button></form>
    <h2>Your data</h2>
    <form method="post" action="<?= e(url('account/preferences')) ?>"><?= csrf_field() ?>
        <label><input type="checkbox" name="notify_email" value="1" <?= checked((int) $user['notify_email'] === 1) ?>> Email me as well as showing notices here</label>
        <button class="btn btn-small btn-navy" type="submit">Save</button>
    </form>
    <form method="post" action="<?= e(url('application/data-request')) ?>" class="form-grid"><?= csrf_field() ?>
        <label>Request
            <select name="request_type"><option value="export">Export my data</option><option value="correction">Correct my data</option><option value="erasure">Erase my data</option></select>
        </label>
        <label>Details<textarea name="details"></textarea></label>
        <button class="btn btn-line" type="submit">Send request</button>
    </form>
</div></section>
