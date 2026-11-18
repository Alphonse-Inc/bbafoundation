<p><a href="<?= e(url('admin/applications')) ?>">Back to applications</a></p>
<p>Reference <?= e((string) ($application['reference'] ?: 'not submitted')) ?> · <?= e($application['status']) ?> · <?= e((string) ($cycle['title'] ?? '')) ?></p>
<?php if ($aggregate !== null): ?><p>Combined score: <?= e((string) $aggregate) ?></p><?php endif; ?>
<h2>Eligibility</h2>
<ul>
    <?php foreach ($rules as $rule): ?>
        <li><?= e((string) ($rule['label'] ?? '')) ?>: <?= e((string) ($rule['result'] ?? '')) ?> — <?= e((string) ($rule['detail'] ?? '')) ?></li>
    <?php endforeach; ?>
</ul>
<?php if ($application['eligibility_override']): ?><p>Override: <?= e($application['eligibility_override']) ?> — <?= e($application['eligibility_override_reason']) ?></p><?php endif; ?>
<?php if ($canManage): ?>
    <form method="post" action="<?= e(url('admin/applications/' . $application['id'] . '/eligibility')) ?>"><?= csrf_field() ?>
        <button class="btn btn-line btn-small" name="action" value="rescreen" type="submit">Check eligibility again</button>
    </form>
    <form method="post" action="<?= e(url('admin/applications/' . $application['id'] . '/eligibility')) ?>" class="form-grid"><?= csrf_field() ?>
        <label>Override
            <select name="override"><option value="pass">Pass</option><option value="flag">Flag</option><option value="fail">Fail</option></select>
        </label>
        <label>Reason<input name="reason" required></label>
        <button class="btn btn-navy btn-small" type="submit">Save override</button>
    </form>
<?php endif; ?>
<h2>Applicant</h2>
<?php if ($blind): ?>
    <p>Identity is hidden for this review.</p>
<?php else: ?>
    <p><?= e($application['surname']) ?> <?= e($application['other_names']) ?> · <?= e($application['sex']) ?> · <?= e(display_date((string) $application['date_of_birth'])) ?></p>
    <p><?= e($application['email']) ?> · <?= e($application['phone']) ?></p>
    <p><?= e($application['community']) ?> <?= e($application['community_other']) ?></p>
    <?php if ($nin !== ''): ?><p>NIN ending <?= e($nin) ?></p><?php endif; ?>
<?php endif; ?>
<p><?= e($application['institution_name']) ?> · <?= e($application['current_level']) ?> · CGPA <?= e($application['cgpa']) ?> / <?= e($application['cgpa_scale']) ?> <?= e($application['poly_grade']) ?></p>
<?php if ((int) $application['academic_flag'] === 1): ?><p class="notice-warn notice"><?= e($application['academic_flag_reason']) ?></p><?php endif; ?>
<h2>Statement</h2>
<p><?= e((string) $application['personal_statement']) ?></p>
<h2>Documents</h2>
<ul>
    <?php foreach ($documents as $document): ?>
        <li>
            <?= e($document['doc_key']) ?> · <?= e($document['status']) ?>
            <a href="<?= e(url('admin/applications/' . $application['id'] . '/file/' . $document['id'])) ?>">View</a>
            <?php if ($document['reviewer_note'] !== ''): ?><span class="fine"><?= e($document['reviewer_note']) ?></span><?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
<?php if ($canManage): ?>
    <p><a href="<?= e(url('admin/applications/' . $application['id'] . '/zip')) ?>">Download applicant file (ZIP)</a></p>
    <form method="post" action="<?= e(url('admin/applications/' . $application['id'] . '/document')) ?>" class="form-grid"><?= csrf_field() ?>
        <label>Document
            <select name="document_id"><?php foreach ($documents as $document): ?><option value="<?= (int) $document['id'] ?>"><?= e($document['doc_key']) ?></option><?php endforeach; ?></select>
        </label>
        <label>Decision
            <select name="status"><option value="accepted">Accept</option><option value="rejected">Reject</option><option value="resubmit">Request resubmission</option></select>
        </label>
        <label>Note<input name="note"></label>
        <button class="btn btn-navy btn-small" type="submit">Update document</button>
    </form>
    <form method="post" action="<?= e(url('admin/applications/' . $application['id'] . '/reopen')) ?>" class="form-grid"><?= csrf_field() ?>
        <label>Reopen until<input name="reopened_until" placeholder="DD/MM/YYYY"></label>
        <label>Reason<input name="reason"></label>
        <button class="btn btn-line btn-small" type="submit">Reopen</button>
    </form>
<?php endif; ?>
<h2>Referees</h2>
<ul><?php foreach ($referees as $referee): ?><li><?= $blind ? e($referee['kind']) : e($referee['full_name']) ?> · <?= e($referee['status']) ?></li><?php endforeach; ?></ul>
<?php if ($canManage): ?>
    <h2>Reviewers</h2>
    <form method="post" action="<?= e(url('admin/applications/' . $application['id'] . '/assign')) ?>"><?= csrf_field() ?>
        <select name="reviewer_id"><?php foreach ($reviewers as $reviewer): ?><option value="<?= (int) $reviewer['id'] ?>"><?= e(trim($reviewer['surname'] . ' ' . $reviewer['first_name'])) ?></option><?php endforeach; ?></select>
        <button class="btn btn-small btn-navy" type="submit">Assign</button>
    </form>
    <form method="post" action="<?= e(url('admin/applications/' . $application['id'] . '/auto-assign')) ?>"><?= csrf_field() ?><button class="btn btn-small btn-line" type="submit">Balanced auto-assign</button></form>
<?php endif; ?>
<form method="post" action="<?= e(url('admin/applications/' . $application['id'] . '/conflict')) ?>"><?= csrf_field() ?>
    <label>Conflict of interest<input name="reason" required></label>
    <button class="btn btn-small btn-line" type="submit">Declare conflict</button>
</form>
<h2>Scores</h2>
<?php if ($assignment): ?>
    <form method="post" action="<?= e(url('admin/applications/' . $application['id'] . '/score')) ?>" class="form-grid"><?= csrf_field() ?>
        <?php $have = []; foreach ($scores as $score) { $have[(int) $score['criterion_id']] = $score['score']; } ?>
        <?php foreach ($rubric as $criterion): ?>
            <label><?= e($criterion['name']) ?> (weight <?= e((string) $criterion['weight']) ?>, max <?= (int) $criterion['max_score'] ?>)
                <input type="number" name="score_<?= (int) $criterion['id'] ?>" min="0" max="<?= (int) $criterion['max_score'] ?>" value="<?= (int) ($have[(int) $criterion['id']] ?? 0) ?>">
            </label>
        <?php endforeach; ?>
        <button class="btn btn-gold btn-small" type="submit">Save scores</button>
    </form>
    <form method="post" action="<?= e(url('admin/applications/' . $application['id'] . '/comment')) ?>"><?= csrf_field() ?>
        <label>Comment<textarea name="body"></textarea></label>
        <button class="btn btn-small btn-navy" type="submit">Save comment</button>
    </form>
    <ul><?php foreach ($comments as $comment): ?><li><?= e($comment['body']) ?></li><?php endforeach; ?></ul>
<?php else: ?>
    <p class="fine">Scores and comments are available after this account is assigned.</p>
<?php endif; ?>
<?php if ($canManage): ?>
    <ul><?php foreach ($totals as $total): ?><li>Reviewer <?= (int) $total['reviewer_id'] ?>: <?= e((string) $total['score']) ?><?= $total['complete'] ? '' : ' (incomplete)' ?></li><?php endforeach; ?></ul>
    <h2>Interview</h2>
    <form method="post" action="<?= e(url('admin/applications/' . $application['id'] . '/interview')) ?>" class="form-grid"><?= csrf_field() ?>
        <label>When<input name="scheduled_at" placeholder="DD/MM/YYYY HH:MM"></label>
        <label>Where<input name="location"></label>
        <label>Note<textarea name="notes"></textarea></label>
        <button class="btn btn-navy btn-small" type="submit">Schedule</button>
    </form>
    <?php if ($interviews !== []): ?><p><a href="<?= e(url('admin/applications/' . $application['id'] . '/interview.ics')) ?>">Download calendar file</a></p><?php endif; ?>
    <h2>Decision</h2>
    <form method="post" action="<?= e(url('admin/applications/' . $application['id'] . '/decision')) ?>" class="form-grid"><?= csrf_field() ?>
        <label>Decision
            <select name="decision"><option value="award">Award</option><option value="waitlist">Waitlist</option><option value="not_selected">Not selected</option></select>
        </label>
        <label>Rationale<textarea name="rationale" required></textarea></label>
        <button class="btn btn-gold" type="submit">Record decision</button>
    </form>
    <form method="post" action="<?= e(url('admin/applications/' . $application['id'] . '/release')) ?>"><?= csrf_field() ?><button class="btn btn-line btn-small" type="submit">Release scores for this cycle</button></form>
    <form method="post" action="<?= e(url('admin/applications/message')) ?>"><?= csrf_field() ?>
        <input type="hidden" name="application_id" value="<?= (int) $application['id'] ?>">
        <label>Message to the applicant<textarea name="body"></textarea></label>
        <button class="btn btn-small btn-navy" type="submit">Send</button>
    </form>
<?php endif; ?>
