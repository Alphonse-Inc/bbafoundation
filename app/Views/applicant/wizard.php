<section class="section"><div class="container">
    <h1>Scholarship application</h1>
    <p class="notice"><?= e(setting('free_notice')) ?></p>
    <p class="fine" data-save-status></p>
    <nav class="stepper" aria-label="Application steps">
        <?php foreach ($steps as $number => $label): ?>
            <a class="<?= $number === $step ? 'now' : '' ?>" href="<?= e(url('application/step/' . $number)) ?>"><?= $number ?>. <?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php if (!$editable): ?><p class="notice-warn notice">This application is read only.</p><?php endif; ?>
    <form method="post" action="<?= e(url('application/save')) ?>" class="card form-grid" <?= $editable ? 'data-autosave="' . e(url('application/autosave')) . '"' : '' ?> enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="step" value="<?= (int) $step ?>">
        <?php if ($step === 1): ?>
            <label>Surname in capitals<input name="surname" value="<?= e(old('surname', (string) $application['surname'])) ?>" <?= $editable ? '' : 'readonly' ?>></label>
            <?php if (!empty($errors['surname'])): ?><p class="field-error"><?= e($errors['surname']) ?></p><?php endif; ?>
            <label>Other names in capitals<input name="other_names" value="<?= e(old('other_names', (string) $application['other_names'])) ?>"></label>
            <label>Sex
                <select name="sex"><option value="male"<?= selected($application['sex'], 'male') ?>>Male</option><option value="female"<?= selected($application['sex'], 'female') ?>>Female</option></select>
            </label>
            <label>Date of birth<input name="date_of_birth" placeholder="DD/MM/YYYY" value="<?= e(display_date((string) $application['date_of_birth'])) ?>"></label>
            <label>Place of birth<input name="place_of_birth" value="<?= e((string) $application['place_of_birth']) ?>"></label>
            <label>Local government area
                <select name="lga_id"><option value="">Choose</option><?php foreach ($lgas as $lga): ?><option value="<?= (int) $lga['id'] ?>"<?= selected($application['lga_id'], $lga['id']) ?>><?= e($lga['name']) ?></option><?php endforeach; ?></select>
            </label>
            <label>Community
                <select name="community">
                    <option value="ijero"<?= selected($application['community'], 'ijero') ?>>Ijero-Ekiti</option>
                    <option value="ewu"<?= selected($application['community'], 'ewu') ?>>Ewu-Ekiti</option>
                    <option value="other"<?= selected($application['community'], 'other') ?>>Other</option>
                </select>
            </label>
            <label>If other, which community?<input name="community_other" value="<?= e((string) $application['community_other']) ?>"></label>
            <label>Parents' hometown address<textarea name="parents_hometown"><?= e((string) $application['parents_hometown']) ?></textarea></label>
            <label>Phone<input name="phone" value="<?= e((string) $application['phone']) ?>"></label>
            <label>Email<input name="email" type="email" value="<?= e((string) $application['email']) ?>"></label>
            <label>Valid means of identification
                <select name="id_type">
                    <?php foreach (['Student ID', "Voter's Card", 'NIN slip', 'State ID', 'International passport'] as $type): ?>
                        <option <?= selected($application['id_type'], $type) ?>><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>NIN number<input name="nin" inputmode="numeric" autocomplete="off" placeholder="Enter only when updating"></label>
            <p class="fine">The NIN is encrypted before it is stored. It is not shown again on this form.</p>
            <label>Residential address<textarea name="residential_address"><?= e((string) $application['residential_address']) ?></textarea></label>
        <?php elseif ($step === 2): ?>
            <label>Secondary school<input name="secondary_school_name" value="<?= e((string) $application['secondary_school_name']) ?>"></label>
            <label>School address<textarea name="secondary_school_address"><?= e((string) $application['secondary_school_address']) ?></textarea></label>
            <label>Dates of attendance<input name="secondary_dates" value="<?= e((string) $application['secondary_dates']) ?>"></label>
            <label>Name of principal<input name="principal_name" value="<?= e((string) $application['principal_name']) ?>"></label>
            <label>Institution suggestion
                <select name="institution_id"><option value="">Other / type below</option><?php foreach ($institutions as $school): ?><option value="<?= (int) $school['id'] ?>"<?= selected($application['institution_id'], $school['id']) ?>><?= e($school['name']) ?> (<?= e($school['ownership']) ?>)</option><?php endforeach; ?></select>
            </label>
            <p class="fine">The list is an editable set of suggestions, not a complete directory.</p>
            <label>Tertiary institution<input name="institution_name" value="<?= e((string) $application['institution_name']) ?>"></label>
            <label>Institution address<textarea name="institution_address"><?= e((string) $application['institution_address']) ?></textarea></label>
            <label>Department<input name="department" value="<?= e((string) $application['department']) ?>"></label>
            <label>Faculty<input name="faculty" value="<?= e((string) $application['faculty']) ?>"></label>
            <label>Type
                <select name="institution_type"><option value="university"<?= selected($application['institution_type'], 'university') ?>>University</option><option value="polytechnic"<?= selected($application['institution_type'], 'polytechnic') ?>>Polytechnic</option></select>
            </label>
            <label>Ownership
                <select name="ownership"><option value="government"<?= selected($application['ownership'], 'government') ?>>Government-owned</option><option value="private"<?= selected($application['ownership'], 'private') ?>>Private</option><option value="other"<?= selected($application['ownership'], 'other') ?>>Other</option></select>
            </label>
            <label><input type="checkbox" name="government_confirmed" value="1" <?= checked((int) $application['government_confirmed'] === 1) ?>> I confirm the institution is government-owned</label>
            <label>Current level<input name="current_level" placeholder="100L, 200L, ND1, HND2" value="<?= e((string) $application['current_level']) ?>"></label>
            <label>Admission year<input name="admission_year" value="<?= e((string) $application['admission_year']) ?>"></label>
            <label>Course duration<input name="course_duration" value="<?= e((string) $application['course_duration']) ?>"></label>
            <label>CGPA for the previous year<input name="cgpa" value="<?= e((string) $application['cgpa']) ?>"></label>
            <label>CGPA scale<input name="cgpa_scale" value="<?= e((string) ($application['cgpa_scale'] ?: '5.00')) ?>"></label>
            <label>Polytechnic grade
                <select name="poly_grade">
                    <option value="">Not a polytechnic</option>
                    <?php foreach (['distinction' => 'Distinction', 'upper_credit' => 'Upper Credit', 'lower_credit' => 'Lower Credit', 'pass' => 'Pass'] as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= selected($application['poly_grade'], $value) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php if ((int) $application['academic_flag'] === 1): ?><p class="notice-warn notice">The academic record is below the published guideline. You can still continue. The secretariat will see the flag.</p><?php endif; ?>
        <?php elseif ($step === 3): ?>
            <p>Each file may be PDF, JPG or PNG. Passport photographs are JPG or PNG. The server removes location data from photographs.</p>
            <ul>
                <?php $have = []; foreach ($uploaded as $file) { $have[$file['doc_key']] = $file; } ?>
                <?php foreach ($documents as $document): ?>
                    <li>
                        <?= e($document['label']) ?>
                        <?php if ((int) $document['is_required'] === 1): ?><span class="badge">Required</span><?php endif; ?>
                        <?php if (isset($have[$document['doc_key']])): ?><span class="badge badge-ok"><?= e($have[$document['doc_key']]['status']) ?></span><?php endif; ?>
                        <?php if ($document['flag_note'] !== ''): ?><span class="fine"><?= e($document['flag_note']) ?></span><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($editable): ?>
                <p class="fine">Save the other steps with the button below. Upload a file with the separate button so the browser sends the file.</p>
            <?php endif; ?>
        <?php elseif ($step === 4): ?>
            <label>Personal statement, not more than 400 words
                <textarea name="personal_statement" data-words="400"><?= e((string) $application['personal_statement']) ?></textarea>
            </label>
            <p data-wordcount></p>
            <p class="fine">In not more than 400 words, explain why you should be awarded this scholarship and how it will impact your education.</p>
        <?php elseif ($step === 5): ?>
            <?php
            $slots = ['referee_1' => 'First referee', 'referee_2' => 'Second referee', 'hod' => 'Head of Department'];
            $existing = ['referee_1' => null, 'referee_2' => null, 'hod' => null];
            $refereeIndex = 0;
            foreach ($referees as $referee) {
                if ($referee['kind'] === 'hod') {
                    $existing['hod'] = $referee;
                } elseif ($refereeIndex === 0) {
                    $existing['referee_1'] = $referee;
                    $refereeIndex++;
                } else {
                    $existing['referee_2'] = $referee;
                }
            }
            ?>
            <?php foreach ($slots as $prefix => $label): $row = $existing[$prefix] ?? []; ?>
                <h2><?= e($label) ?></h2>
                <label>Full name<input name="<?= e($prefix) ?>_name" value="<?= e((string) ($row['full_name'] ?? '')) ?>"></label>
                <label>Role<input name="<?= e($prefix) ?>_role" value="<?= e((string) ($row['role_title'] ?? '')) ?>"></label>
                <label>Email<input name="<?= e($prefix) ?>_email" value="<?= e((string) ($row['email'] ?? '')) ?>"></label>
                <label>Phone<input name="<?= e($prefix) ?>_phone" value="<?= e((string) ($row['phone'] ?? '')) ?>"></label>
                <label>Address<textarea name="<?= e($prefix) ?>_address"><?= e((string) ($row['address'] ?? '')) ?></textarea></label>
                <?php if (!empty($row['status'])): ?><p class="fine">Status: <?= e((string) $row['status']) ?></p><?php endif; ?>
            <?php endforeach; ?>
            <p class="fine">You can also upload signed letters on the documents step. If this cycle allows it, the Foundation emails a private link after you submit.</p>
        <?php else: ?>
            <h2>Review</h2>
            <p><?= e($application['surname']) ?> <?= e($application['other_names']) ?></p>
            <p><?= e($application['institution_name']) ?> · <?= e($application['current_level']) ?></p>
            <p><a href="<?= e(url('application/form.pdf')) ?>">Download the form filled from these answers</a></p>
            <p><?= e(setting('declaration')) ?></p>
            <label><input type="checkbox" name="declaration" value="1"> I agree to the declaration</label>
            <label>Type your full name as it appears above<input name="signature_name" value=""></label>
            <?php if ($editable): ?>
                <button class="btn btn-gold" formaction="<?= e(url('application/submit')) ?>" formmethod="post">Submit application</button>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($editable && $step < 6): ?><button class="btn btn-navy" type="submit">Save and continue</button><?php endif; ?>
    </form>
    <?php if ($step === 3 && $editable): ?>
        <form method="post" action="<?= e(url('application/upload')) ?>" enctype="multipart/form-data" class="card form-grid">
            <?= csrf_field() ?>
            <label>Document
                <select name="doc_key" data-passport-kind><?php foreach ($documents as $document): ?><option value="<?= e($document['doc_key']) ?>"><?= e($document['label']) ?></option><?php endforeach; ?></select>
            </label>
            <label>File<input type="file" name="document" data-passport-input accept=".pdf,.jpg,.jpeg,.png,.zip"></label>
            <img data-preview alt="Passport preview" hidden width="160" height="160">
            <button class="btn btn-gold" type="submit">Upload</button>
        </form>
    <?php endif; ?>
</div></section>
