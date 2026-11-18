<?php

declare(strict_types=1);

namespace BBAF\Services;

use BBAF\Core\Audit;
use BBAF\Core\Crypto;
use BBAF\Core\Database;
use BBAF\Core\Dates;
use BBAF\Core\Upload;

final class Applications
{
    public const STEPS = [
        1 => 'Personal',
        2 => 'Academic',
        3 => 'Documents',
        4 => 'Statement',
        5 => 'Referees',
        6 => 'Declaration',
    ];

    public static function forUser(int $userId, int $cycleId): ?array
    {
        return Database::one('SELECT * FROM applications WHERE user_id = ? AND cycle_id = ?', [$userId, $cycleId]);
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM applications WHERE id = ?', [$id]);
    }

    public static function ensure(int $userId, array $cycle, array $user): array
    {
        $existing = self::forUser($userId, (int) $cycle['id']);
        if ($existing) {
            return $existing;
        }
        $id = Database::insert(
            'INSERT INTO applications (cycle_id, user_id, surname, other_names, sex, phone, phone_normalized, email, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
            [
                (int) $cycle['id'],
                $userId,
                (string) $user['surname'],
                trim((string) $user['first_name'] . ' ' . (string) $user['other_names']),
                (string) $user['gender'],
                (string) $user['phone'],
                (string) $user['phone_normalized'],
                (string) $user['email'],
                'draft',
            ]
        );
        Audit::log($userId, 'application_started', 'Draft started for cycle ' . $cycle['title'], 'application', $id);

        return self::find($id) ?? [];
    }

    public static function editable(array $application): bool
    {
        if (($application['status'] ?? '') === 'draft') {
            return true;
        }
        if (($application['status'] ?? '') === 'reopened' && !empty($application['reopened_until'])) {
            return strtotime((string) $application['reopened_until'] . ' UTC') >= time();
        }

        return false;
    }

    public static function save(array $application, int $step, array $input): array
    {
        if (!self::editable($application)) {
            return ['ok' => false, 'error' => 'This application can no longer be edited.'];
        }
        $data = ['current_step' => max((int) $application['current_step'], $step)];
        $errors = [];
        if ($step === 1) {
            $data['surname'] = mb_strtoupper(trim((string) ($input['surname'] ?? '')));
            $data['other_names'] = mb_strtoupper(trim((string) ($input['other_names'] ?? '')));
            $data['sex'] = in_array(($input['sex'] ?? ''), ['male', 'female'], true) ? $input['sex'] : '';
            $dob = Dates::parseLocal((string) ($input['date_of_birth'] ?? ''));
            $data['date_of_birth'] = $dob ? substr($dob, 0, 10) : null;
            $data['place_of_birth'] = trim((string) ($input['place_of_birth'] ?? ''));
            $data['lga_id'] = ctype_digit((string) ($input['lga_id'] ?? '')) ? (int) $input['lga_id'] : null;
            $community = (string) ($input['community'] ?? '');
            $data['community'] = in_array($community, ['ijero', 'ewu', 'other'], true) ? $community : '';
            $data['community_other'] = trim((string) ($input['community_other'] ?? ''));
            $data['parents_hometown'] = trim((string) ($input['parents_hometown'] ?? ''));
            $phone = trim((string) ($input['phone'] ?? ''));
            $data['phone'] = $phone;
            $data['phone_normalized'] = Dates::phone($phone);
            $data['email'] = mb_strtolower(trim((string) ($input['email'] ?? '')));
            $data['id_type'] = trim((string) ($input['id_type'] ?? ''));
            $data['residential_address'] = trim((string) ($input['residential_address'] ?? ''));
            $nin = preg_replace('/\D+/', '', (string) ($input['nin'] ?? '')) ?? '';
            if ($nin !== '') {
                if (strlen($nin) !== 11) {
                    $errors['nin'] = 'Enter the 11-digit NIN, or leave it blank until you have it.';
                } else {
                    $data['nin_cipher'] = Crypto::encrypt($nin);
                    $data['nin_hash'] = Crypto::hashNin($nin);
                }
            }
        }
        if ($step === 2) {
            foreach ([
                'secondary_school_name', 'secondary_school_address', 'secondary_dates', 'principal_name',
                'institution_name', 'institution_address', 'department', 'faculty', 'current_level',
                'admission_year', 'course_duration', 'cgpa', 'cgpa_scale', 'poly_grade',
            ] as $field) {
                $data[$field] = trim((string) ($input[$field] ?? ''));
            }
            $data['institution_id'] = ctype_digit((string) ($input['institution_id'] ?? '')) ? (int) $input['institution_id'] : null;
            $data['institution_type'] = in_array(($input['institution_type'] ?? ''), ['university', 'polytechnic'], true) ? $input['institution_type'] : '';
            $data['ownership'] = in_array(($input['ownership'] ?? ''), ['government', 'private', 'other'], true) ? $input['ownership'] : '';
            $data['government_confirmed'] = !empty($input['government_confirmed']) ? 1 : 0;
            if ($data['institution_id']) {
                $institution = Database::one('SELECT name, institution_type, ownership FROM institutions WHERE id = ?', [$data['institution_id']]);
                if ($institution && $data['institution_name'] === '') {
                    $data['institution_name'] = $institution['name'];
                    $data['institution_type'] = $institution['institution_type'];
                    $data['ownership'] = $institution['ownership'] === 'government' ? 'government' : 'private';
                }
            }
        }
        if ($step === 4) {
            $statement = trim((string) ($input['personal_statement'] ?? ''));
            if (word_count($statement) > 400) {
                $errors['personal_statement'] = 'The personal statement must not be more than 400 words.';
            } else {
                $data['personal_statement'] = $statement;
            }
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }
        self::write($application['id'], $data);
        if ($step === 2) {
            self::flagAcademics((int) $application['id']);
        }
        self::flagDuplicates((int) $application['id']);

        return ['ok' => true];
    }

    public static function saveReferees(array $application, array $input): void
    {
        if (!self::editable($application)) {
            return;
        }
        $kinds = [
            ['kind' => 'referee', 'prefix' => 'referee_1'],
            ['kind' => 'referee', 'prefix' => 'referee_2'],
            ['kind' => 'hod', 'prefix' => 'hod'],
        ];
        Database::exec('DELETE FROM application_referees WHERE application_id = ? AND stored_name = "" AND status = "pending"', [(int) $application['id']]);
        $insert = 'INSERT INTO application_referees (application_id, kind, full_name, role_title, email, phone, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        foreach ($kinds as $item) {
            $prefix = $item['prefix'];
            $name = trim((string) ($input[$prefix . '_name'] ?? ''));
            if ($name === '' && trim((string) ($input[$prefix . '_email'] ?? '')) === '') {
                continue;
            }
            Database::insert($insert, [
                (int) $application['id'],
                $item['kind'],
                $name,
                trim((string) ($input[$prefix . '_role'] ?? '')),
                mb_strtolower(trim((string) ($input[$prefix . '_email'] ?? ''))),
                trim((string) ($input[$prefix . '_phone'] ?? '')),
                trim((string) ($input[$prefix . '_address'] ?? '')),
                'pending',
            ]);
        }
    }

    public static function referees(int $applicationId): array
    {
        return Database::all('SELECT * FROM application_referees WHERE application_id = ? ORDER BY id', [$applicationId]);
    }

    public static function documents(int $applicationId): array
    {
        return Database::all('SELECT * FROM application_documents WHERE application_id = ? ORDER BY id', [$applicationId]);
    }

    public static function storeDocument(array $application, array $cycle, string $key, array $file): array
    {
        if (!self::editable($application)) {
            return ['ok' => false, 'error' => 'This application can no longer be edited.'];
        }
        $allowedKeys = array_column(Cycles::documents((int) $cycle['id']), 'doc_key');
        if (!in_array($key, $allowedKeys, true)) {
            return ['ok' => false, 'error' => 'That document is not requested for this cycle.'];
        }
        $max = (int) $cycle['max_file_bytes'];
        if ($key === 'legacy_zip') {
            $max = (int) setting('legacy_zip_mb', '25') * 1048576;
        }
        $mimes = $key === 'legacy_zip'
            ? ['application/zip' => 'zip', 'application/x-zip-compressed' => 'zip']
            : ($key === 'passport' ? Upload::imageMimes() : Upload::documentMimes());
        try {
            $stored = Upload::storePrivate($file, $mimes, $max);
        } catch (\Throwable $error) {
            return ['ok' => false, 'error' => $error->getMessage()];
        }
        if ($key === 'legacy_zip') {
            $problem = Upload::inspectZip($stored['absolute']);
            if ($problem !== null) {
                @unlink($stored['absolute']);
                return ['ok' => false, 'error' => $problem];
            }
        }
        $used = (int) Database::value('SELECT COALESCE(SUM(byte_size), 0) FROM application_documents WHERE application_id = ? AND doc_key <> ?', [(int) $application['id'], $key]);
        if ($key !== 'legacy_zip' && ($used + $stored['byte_size']) > (int) $cycle['max_total_bytes']) {
            @unlink($stored['absolute']);
            return ['ok' => false, 'error' => 'The documents together exceed the total size for this cycle.'];
        }
        $previous = Database::all('SELECT stored_name FROM application_documents WHERE application_id = ? AND doc_key = ?', [(int) $application['id'], $key]);
        foreach ($previous as $old) {
            $path = BBAF_ROOT . '/storage/uploads/private/' . $old['stored_name'];
            if (is_file($path)) {
                unlink($path);
            }
        }
        Database::exec('DELETE FROM application_documents WHERE application_id = ? AND doc_key = ?', [(int) $application['id'], $key]);
        Database::insert(
            'INSERT INTO application_documents (application_id, doc_key, original_name, stored_name, mime, byte_size, status, uploaded_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
            [(int) $application['id'], $key, $stored['original_name'], $stored['stored_name'], $stored['mime'], $stored['byte_size'], 'pending']
        );

        return ['ok' => true];
    }

    public static function submit(array $application, array $cycle, array $input): array
    {
        if (!self::editable($application)) {
            return ['ok' => false, 'error' => 'This application has already been submitted.'];
        }
        if (!Cycles::isAccepting($cycle) && ($application['status'] ?? '') !== 'reopened') {
            return ['ok' => false, 'error' => 'Applications are not being accepted at the moment.'];
        }
        $fresh = self::find((int) $application['id']) ?? $application;
        $errors = self::completeness($fresh, $cycle);
        if (empty($input['declaration'])) {
            $errors['declaration'] = 'Tick the declaration to submit.';
        }
        $signature = trim((string) ($input['signature_name'] ?? ''));
        $expected = trim($fresh['surname'] . ' ' . $fresh['other_names']);
        if ($signature === '' || mb_strtolower($signature) !== mb_strtolower($expected)) {
            $errors['signature_name'] = 'Type your full name exactly as it appears on the form: surname, then other names.';
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }
        $duplicate = self::blockingDuplicate($fresh);
        if ($duplicate !== null) {
            return ['ok' => false, 'error' => $duplicate];
        }
        $reference = $fresh['reference'] ?: self::nextReference();
        Database::transaction(function () use ($fresh, $signature, $reference): void {
            Database::exec(
                "UPDATE applications SET status = 'submitted', reference = ?, declaration_accepted = 1, signature_name = ?, declared_at = UTC_TIMESTAMP(), submitted_at = UTC_TIMESTAMP(), reopened_until = NULL, updated_at = UTC_TIMESTAMP() WHERE id = ?",
                [$reference, $signature, (int) $fresh['id']]
            );
        });
        $saved = self::find((int) $fresh['id']) ?? $fresh;
        Documents::acknowledgement($saved);
        self::screen($saved, $cycle);
        self::maybeInviteReferees($saved, $cycle);
        Audit::log((int) $saved['user_id'], 'application_submitted', 'Submitted ' . $reference, 'application', (int) $saved['id']);
        Notify::user((int) $saved['user_id'], 'submission_ack', [
            'reference' => $reference,
            'cycle' => $cycle['title'],
            'note' => 'Application ' . $reference . ' has been received.',
        ], '/dashboard');
        Notify::admins('new_application_admin', [
            'reference' => $reference,
            'cycle' => $cycle['title'],
            'name' => trim($saved['surname'] . ' ' . $saved['other_names']),
            'url' => url('admin/applications/' . $saved['id']),
        ], '/admin/applications/' . $saved['id']);

        return ['ok' => true, 'reference' => $reference];
    }

    public static function completeness(array $application, array $cycle): array
    {
        $errors = [];
        foreach ([
            'surname' => 'Enter your surname in capitals.',
            'other_names' => 'Enter your other names in capitals.',
            'sex' => 'Select sex.',
            'place_of_birth' => 'Enter the place of birth.',
            'community' => 'Choose a community.',
            'parents_hometown' => 'Enter your parents\' hometown address.',
            'phone' => 'Enter your phone number.',
            'email' => 'Enter your email address.',
            'id_type' => 'Choose a means of identification.',
            'residential_address' => 'Enter your residential address.',
            'secondary_school_name' => 'Enter the secondary school.',
            'institution_name' => 'Enter the tertiary institution.',
            'department' => 'Enter the department or faculty.',
            'current_level' => 'Enter the current level.',
            'admission_year' => 'Enter the admission year.',
            'course_duration' => 'Enter the course duration.',
        ] as $field => $message) {
            if (trim((string) ($application[$field] ?? '')) === '') {
                $errors[$field] = $message;
            }
        }
        if (empty($application['date_of_birth'])) {
            $errors['date_of_birth'] = 'Enter the date of birth as DD/MM/YYYY.';
        }
        if (!Dates::phoneValid((string) $application['phone'])) {
            $errors['phone'] = 'Use a Nigerian phone number, such as 0803… or +234803….';
        }
        if (!filter_var((string) $application['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (empty($application['nin_hash'])) {
            $errors['nin'] = 'Enter the 11-digit NIN.';
        }
        if ((int) $cycle['require_government'] === 1 && (int) $application['government_confirmed'] !== 1) {
            $errors['government_confirmed'] = 'Confirm that the institution is government-owned, or explain the institution in the form.';
        }
        if (trim((string) ($application['personal_statement'] ?? '')) === '') {
            $errors['personal_statement'] = 'Write the personal statement.';
        } elseif (word_count((string) $application['personal_statement']) > 400) {
            $errors['personal_statement'] = 'The personal statement must not be more than 400 words.';
        }
        $docs = self::documents((int) $application['id']);
        $have = [];
        foreach ($docs as $doc) {
            $have[$doc['doc_key']] = true;
        }
        foreach (Cycles::documents((int) $cycle['id']) as $required) {
            if ((int) $required['is_required'] === 1 && empty($have[$required['doc_key']])) {
                $errors['doc_' . $required['doc_key']] = 'Upload: ' . $required['label'];
            }
        }
        $referees = array_filter(self::referees((int) $application['id']), static fn (array $row): bool => $row['kind'] === 'referee' && $row['full_name'] !== '');
        if (count($referees) < 2) {
            $errors['referees'] = 'Enter two referees.';
        }

        return $errors;
    }

    public static function screen(array $application, array $cycle): array
    {
        $rules = [];
        $communityOk = ($application['community'] === 'ijero' && (int) $cycle['allow_ijero'] === 1)
            || ($application['community'] === 'ewu' && (int) $cycle['allow_ewu'] === 1)
            || ($application['community'] === 'other' && (int) $cycle['allow_other'] === 1);
        if ((int) $cycle['require_indigene'] !== 1) {
            $communityOk = true;
        }
        $rules[] = ['key' => 'community', 'label' => 'Indigene community', 'result' => $communityOk ? 'pass' : 'fail', 'detail' => (string) $application['community']];
        $gov = (string) $application['ownership'] === 'government' && (int) $application['government_confirmed'] === 1;
        if ((int) $cycle['require_government'] !== 1) {
            $gov = true;
        }
        $rules[] = ['key' => 'institution', 'label' => 'Government-owned institution', 'result' => $gov ? 'pass' : 'fail', 'detail' => (string) $application['institution_name']];
        $academic = self::academicResult($application, $cycle);
        $rules[] = $academic;
        $missing = self::completeness($application, $cycle);
        $docFails = array_filter(array_keys($missing), static fn (string $key): bool => str_starts_with($key, 'doc_'));
        $rules[] = ['key' => 'documents', 'label' => 'Required documents', 'result' => $docFails === [] ? 'pass' : 'fail', 'detail' => $docFails === [] ? 'Present' : implode(', ', $docFails)];
        $rules[] = ['key' => 'deadline', 'label' => 'Deadline', 'result' => Cycles::beforeDeadline($cycle) || $application['status'] === 'reopened' ? 'pass' : 'fail', 'detail' => $cycle['closes_at'] ? display_date((string) $cycle['closes_at'], true) : 'No deadline set'];
        $rules[] = ['key' => 'duplicate', 'label' => 'Duplicate check', 'result' => (int) $application['duplicate_flag'] === 1 ? 'flag' : 'pass', 'detail' => (string) $application['duplicate_note']];
        $status = 'pass';
        foreach ($rules as $rule) {
            if ($rule['result'] === 'fail') {
                $status = 'fail';
                break;
            }
            if ($rule['result'] === 'flag') {
                $status = 'flag';
            }
        }
        if ($status === 'flag' && (int) $cycle['hard_block'] === 1 && $academic['result'] !== 'pass') {
            $status = 'fail';
        }
        Database::exec(
            'UPDATE applications SET eligibility_json = ?, eligibility_status = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?',
            [json_encode($rules), $status, (int) $application['id']]
        );

        return ['status' => $status, 'rules' => $rules];
    }

    public static function privatePath(string $stored): string
    {
        return BBAF_ROOT . '/storage/uploads/private/' . basename($stored);
    }

    private static function academicResult(array $application, array $cycle): array
    {
        if ($application['institution_type'] === 'polytechnic') {
            $okGrades = ['distinction', 'upper_credit'];
            $min = (string) $cycle['poly_min'];
            if ($min === 'distinction') {
                $okGrades = ['distinction'];
            }
            $ok = in_array((string) $application['poly_grade'], $okGrades, true);
            return ['key' => 'academics', 'label' => 'Polytechnic grade', 'result' => $ok ? 'pass' : 'flag', 'detail' => (string) $application['poly_grade']];
        }
        $cgpa = (float) $application['cgpa'];
        $scale = (float) ($application['cgpa_scale'] ?: 5);
        $normalized = $scale > 0 ? ($cgpa / $scale) * 5 : $cgpa;
        $ok = $normalized + 0.0001 >= (float) $cycle['min_cgpa'];

        return ['key' => 'academics', 'label' => 'CGPA threshold', 'result' => $ok ? 'pass' : 'flag', 'detail' => (string) $application['cgpa'] . ' / ' . (string) $application['cgpa_scale']];
    }

    private static function flagAcademics(int $id): void
    {
        $application = self::find($id);
        if ($application === null) {
            return;
        }
        $cycle = Cycles::find((int) $application['cycle_id']);
        if ($cycle === null) {
            return;
        }
        $result = self::academicResult($application, $cycle);
        Database::exec(
            'UPDATE applications SET academic_flag = ?, academic_flag_reason = ? WHERE id = ?',
            [$result['result'] === 'pass' ? 0 : 1, $result['result'] === 'pass' ? '' : $result['detail'], $id]
        );
    }

    private static function flagDuplicates(int $id): void
    {
        $application = self::find($id);
        if ($application === null) {
            return;
        }
        $notes = [];
        if ($application['email'] !== '') {
            $count = (int) Database::value('SELECT COUNT(*) FROM applications WHERE cycle_id = ? AND email = ? AND id <> ?', [$application['cycle_id'], $application['email'], $id]);
            if ($count > 0) {
                $notes[] = 'Another application in this cycle uses the same email.';
            }
        }
        if ($application['phone_normalized'] !== '') {
            $count = (int) Database::value('SELECT COUNT(*) FROM applications WHERE cycle_id = ? AND phone_normalized = ? AND id <> ?', [$application['cycle_id'], $application['phone_normalized'], $id]);
            if ($count > 0) {
                $notes[] = 'Another application in this cycle uses the same phone number.';
            }
        }
        if (!empty($application['nin_hash'])) {
            $count = (int) Database::value('SELECT COUNT(*) FROM applications WHERE cycle_id = ? AND nin_hash = ? AND id <> ?', [$application['cycle_id'], $application['nin_hash'], $id]);
            if ($count > 0) {
                $notes[] = 'Another application in this cycle has the same identification number.';
            }
        }
        Database::exec(
            'UPDATE applications SET duplicate_flag = ?, duplicate_note = ? WHERE id = ?',
            [$notes === [] ? 0 : 1, implode(' ', $notes), $id]
        );
    }

    private static function blockingDuplicate(array $application): ?string
    {
        $params = [$application['cycle_id'], $application['id']];
        $clauses = [];
        if ($application['email'] !== '') {
            $clauses[] = 'email = ?';
            $params[] = $application['email'];
        }
        if ($application['phone_normalized'] !== '') {
            $clauses[] = 'phone_normalized = ?';
            $params[] = $application['phone_normalized'];
        }
        if (!empty($application['nin_hash'])) {
            $clauses[] = 'nin_hash = ?';
            $params[] = $application['nin_hash'];
        }
        if ($clauses === []) {
            return null;
        }
        $sql = "SELECT id FROM applications WHERE cycle_id = ? AND id <> ? AND status NOT IN ('draft') AND (" . implode(' OR ', $clauses) . ') LIMIT 1';
        $row = Database::one($sql, $params);

        return $row ? 'An application for this person is already in this cycle. The secretariat has been asked to review the duplicate.' : null;
    }

    private static function nextReference(): string
    {
        $year = (new \DateTimeImmutable('now', Dates::zone()))->format('Y');
        Database::exec(
            "INSERT INTO settings (skey, svalue, updated_at) VALUES ('application_sequence', '0', UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE skey = skey"
        );
        Database::exec("UPDATE settings SET svalue = LAST_INSERT_ID(CAST(svalue AS UNSIGNED) + 1) WHERE skey = 'application_sequence'");
        $number = (int) Database::pdo()->lastInsertId();

        return sprintf('BBAF-%s-%05d', $year, max(1, $number));
    }

    private static function maybeInviteReferees(array $application, array $cycle): void
    {
        if ((int) $cycle['referee_links'] !== 1) {
            return;
        }
        foreach (self::referees((int) $application['id']) as $referee) {
            if ($referee['email'] === '' || $referee['status'] === 'submitted') {
                continue;
            }
            $token = bin2hex(random_bytes(24));
            Database::exec(
                'UPDATE application_referees SET token_hash = ?, token_expires = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 21 DAY), status = ? WHERE id = ?',
                [hash('sha256', $token), 'invited', (int) $referee['id']]
            );
            $template = $referee['kind'] === 'hod' ? 'hod_request' : 'referee_request';
            Notify::email((string) $referee['email'], (string) $referee['full_name'], $template, [
                'name' => $referee['full_name'],
                'applicant' => trim($application['surname'] . ' ' . $application['other_names']),
                'url' => url('r/' . $token),
                'expires' => display_date(gmdate('Y-m-d H:i:s', time() + 21 * 86400), true),
            ]);
        }
    }

    private static function write(int $id, array $data): void
    {
        if ($data === []) {
            return;
        }
        $sets = [];
        $params = [];
        foreach ($data as $column => $value) {
            $sets[] = $column . ' = ?';
            $params[] = $value;
        }
        $sets[] = 'updated_at = UTC_TIMESTAMP()';
        $params[] = $id;
        Database::exec('UPDATE applications SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }
}
