<?php

declare(strict_types=1);

namespace BBAF\Controllers;

use BBAF\Core\Audit;
use BBAF\Core\Auth;
use BBAF\Core\Crypto;
use BBAF\Core\Database;
use BBAF\Core\Dates;
use BBAF\Core\Paginator;
use BBAF\Core\Request;
use BBAF\Core\View;
use BBAF\Core\ZipPack;
use BBAF\Services\Applications;
use BBAF\Services\Cycles;
use BBAF\Services\Notify;
use BBAF\Services\Reports;
use BBAF\Services\Reviews;

final class AdminApplicationController
{
    public function index(Request $request): void
    {
        [$where, $params] = $this->filters($request);
        $page = Paginator::page($request);
        $per = Paginator::perPage($request);
        $order = Paginator::order($request, [
            'submitted' => 'a.submitted_at',
            'reference' => 'a.reference',
            'status' => 'a.status',
            'institution' => 'a.institution_name',
            'cgpa' => 'a.cgpa',
        ], 'a.id DESC');
        $total = (int) Database::value("SELECT COUNT(*) FROM applications a JOIN cycles c ON c.id = a.cycle_id WHERE $where", $params);
        $meta = Paginator::meta($total, $page, $per);
        $rows = Database::all(
            "SELECT a.id, a.reference, a.status, a.surname, a.other_names, a.community, a.institution_name, a.current_level, a.sex, a.cgpa, a.submitted_at, a.eligibility_status, a.duplicate_flag, a.academic_flag, c.title AS cycle_title, c.blind_review
             FROM applications a JOIN cycles c ON c.id = a.cycle_id
             WHERE $where ORDER BY $order LIMIT " . (int) $per . ' OFFSET ' . (int) (($meta['page'] - 1) * $per),
            $params
        );
        View::render('admin/applications', [
            'title' => 'Applications',
            'rows' => $rows,
            'meta' => $meta,
            'reviewers' => $this->reviewers(),
            'blind' => Auth::role() === 'reviewer',
        ], 'admin');
    }

    public function export(Request $request): void
    {
        [$where, $params] = $this->filters($request);
        $rows = Database::all(
            "SELECT a.reference, a.status, a.community, a.institution_name, a.current_level, a.sex, a.cgpa, a.eligibility_status, a.submitted_at
             FROM applications a JOIN cycles c ON c.id = a.cycle_id WHERE $where ORDER BY a.id DESC",
            $params
        );
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                $row['reference'],
                $row['status'],
                $row['community'],
                $row['institution_name'],
                $row['current_level'],
                $row['sex'],
                $row['cgpa'],
                $row['eligibility_status'],
                $row['submitted_at'],
            ];
        }
        Reports::csv('applications.csv', ['Reference', 'Status', 'Community', 'Institution', 'Level', 'Sex', 'CGPA', 'Eligibility', 'Submitted UTC'], $out);
    }

    public function show(Request $request): void
    {
        $application = $this->application($request);
        $cycle = Cycles::find((int) $application['cycle_id']) ?? [];
        $blind = Auth::role() === 'reviewer' && (int) ($cycle['blind_review'] ?? 0) === 1;
        $nin = '';
        if (!$blind && Auth::role() !== 'reviewer' && !empty($application['nin_cipher'])) {
            $nin = Crypto::maskNin(Crypto::decrypt((string) $application['nin_cipher']));
        }
        $assignment = Reviews::assignment((int) $application['id'], (int) Auth::id());
        View::render('admin/application', [
            'title' => $blind ? 'Application' : trim($application['surname'] . ' ' . $application['other_names']),
            'application' => $application,
            'cycle' => $cycle,
            'blind' => $blind,
            'nin' => $nin,
            'documents' => Applications::documents((int) $application['id']),
            'referees' => Applications::referees((int) $application['id']),
            'rules' => json_decode((string) ($application['eligibility_json'] ?? ''), true) ?: [],
            'rubric' => Reviews::rubric((int) $application['cycle_id']),
            'assignment' => $assignment,
            'scores' => $assignment ? Database::all('SELECT criterion_id, score FROM review_scores WHERE assignment_id = ?', [(int) $assignment['id']]) : [],
            'totals' => Auth::role() === 'reviewer' ? [] : Reviews::totals((int) $application['id'], true),
            'aggregate' => Auth::role() === 'reviewer' ? null : Reviews::aggregate((int) $application['id']),
            'comments' => $assignment ? Database::all('SELECT body, created_at FROM review_comments WHERE assignment_id = ? ORDER BY id', [(int) $assignment['id']]) : [],
            'interviews' => Database::all('SELECT * FROM interviews WHERE application_id = ? ORDER BY id DESC', [(int) $application['id']]),
            'reviewers' => $this->reviewers(),
            'canManage' => in_array(Auth::role(), ['super_admin', 'foundation_admin'], true),
        ], 'admin');
    }

    public function eligibility(Request $request): void
    {
        $application = $this->application($request);
        $cycle = Cycles::find((int) $application['cycle_id']);
        if ($cycle === null) {
            flash('error', 'The cycle no longer exists.');
            View::redirect('/admin/applications');
        }
        if ($request->input('action') === 'rescreen') {
            Applications::screen($application, $cycle);
            Audit::log(Auth::id(), 'eligibility_rescreen', 'Eligibility checked again', 'application', (int) $application['id']);
            flash('success', 'Eligibility checked again.');
        } else {
            $override = $request->input('override');
            $reason = $request->input('reason');
            if (!in_array($override, ['pass', 'flag', 'fail'], true) || $reason === '') {
                flash('error', 'Choose a result and record the reason.');
                View::redirect('/admin/applications/' . $application['id']);
            }
            Database::exec(
                'UPDATE applications SET eligibility_override = ?, eligibility_override_reason = ?, eligibility_status = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?',
                [$override, $reason, $override, (int) $application['id']]
            );
            Audit::log(Auth::id(), 'eligibility_override', $override . ': ' . $reason, 'application', (int) $application['id']);
            flash('success', 'Override recorded.');
        }
        View::redirect('/admin/applications/' . $application['id']);
    }

    public function reopen(Request $request): void
    {
        $application = $this->application($request);
        $until = Dates::parseLocal($request->input('reopened_until'), true);
        $reason = $request->input('reason');
        if ($until === null || $reason === '') {
            flash('error', 'Set a date and a reason to reopen the application.');
            View::redirect('/admin/applications/' . $application['id']);
        }
        Database::exec(
            "UPDATE applications SET status = 'reopened', reopened_until = ?, reopen_reason = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?",
            [$until, $reason, (int) $application['id']]
        );
        Notify::user((int) $application['user_id'], 'document_issue', [
            'reference' => (string) $application['reference'],
            'document' => 'Application',
            'note' => 'The secretariat reopened your application until ' . display_date($until, true) . '. ' . $reason,
            'url' => url('dashboard'),
        ], '/dashboard');
        Audit::log(Auth::id(), 'application_reopened', $reason, 'application', (int) $application['id']);
        flash('success', 'Application reopened.');
        View::redirect('/admin/applications/' . $application['id']);
    }

    public function document(Request $request): void
    {
        $application = $this->application($request);
        $doc = Database::one(
            'SELECT * FROM application_documents WHERE id = ? AND application_id = ?',
            [(int) $request->input('document_id'), (int) $application['id']]
        );
        $status = $request->input('status');
        if ($doc === null || !in_array($status, ['accepted', 'rejected', 'resubmit'], true)) {
            flash('error', 'Choose a document and a decision.');
            View::redirect('/admin/applications/' . $application['id']);
        }
        Database::exec(
            'UPDATE application_documents SET status = ?, reviewer_note = ? WHERE id = ?',
            [$status, $request->input('note'), (int) $doc['id']]
        );
        if ($status !== 'accepted') {
            $until = gmdate('Y-m-d H:i:s', time() + (7 * 86400));
            Database::exec(
                "UPDATE applications SET status = 'reopened', reopened_until = ?, reopen_reason = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?",
                [$until, 'Document resubmission requested', (int) $application['id']]
            );
            Notify::user((int) $application['user_id'], 'document_issue', [
                'reference' => (string) $application['reference'],
                'document' => (string) $doc['doc_key'],
                'note' => $request->input('note') !== '' ? $request->input('note') : 'Please replace this document.',
                'url' => url('application/step/3'),
            ], '/application/step/3');
        }
        Audit::log(Auth::id(), 'document_' . $status, (string) $doc['doc_key'], 'application', (int) $application['id']);
        flash('success', 'Document updated.');
        View::redirect('/admin/applications/' . $application['id']);
    }

    public function zip(Request $request): void
    {
        $application = $this->application($request);
        $files = [];
        foreach (Applications::documents((int) $application['id']) as $doc) {
            $path = Applications::privatePath((string) $doc['stored_name']);
            if (!is_file($path)) {
                continue;
            }
            $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $doc['doc_key'] . '-' . $doc['original_name']) ?: 'file';
            $files[$name] = $path;
        }
        if ($files === []) {
            flash('error', 'There are no documents to bundle.');
            View::redirect('/admin/applications/' . $application['id']);
        }
        $dest = BBAF_ROOT . '/storage/uploads/private/bundle-' . bin2hex(random_bytes(8)) . '.zip';
        ZipPack::create($files, $dest);
        Audit::log(Auth::id(), 'applicant_zip', 'Downloaded the document bundle', 'application', (int) $application['id']);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="application-' . (int) $application['id'] . '.zip"');
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($dest);
        @unlink($dest);
    }

    public function file(Request $request): void
    {
        $application = $this->application($request);
        $doc = Database::one(
            'SELECT * FROM application_documents WHERE id = ? AND application_id = ?',
            [(int) $request->param('doc'), (int) $application['id']]
        );
        if ($doc === null) {
            http_response_code(404);
            return;
        }
        Audit::log(Auth::id(), 'document_viewed', (string) $doc['doc_key'], 'application', (int) $application['id']);
        $inline = $request->query('download') !== '1';
        send_stored_file((string) $doc['stored_name'], (string) $doc['original_name'], (string) $doc['mime'], $inline);
    }

    public function assign(Request $request): void
    {
        $application = $this->application($request);
        $error = Reviews::assign((int) $application['id'], (int) $request->input('reviewer_id'), (int) Auth::id());
        flash($error === '' ? 'success' : 'error', $error === '' ? 'Reviewer assigned.' : $error);
        View::redirect('/admin/applications/' . $application['id']);
    }

    public function autoAssign(Request $request): void
    {
        $application = $this->application($request);
        $made = Reviews::autoAssign((int) $application['id'], (int) Auth::id());
        flash('success', $made . ' reviewer' . ($made === 1 ? '' : 's') . ' assigned.');
        View::redirect('/admin/applications/' . $application['id']);
    }

    public function conflict(Request $request): void
    {
        $application = $this->application($request);
        $reviewerId = Auth::role() === 'reviewer' ? (int) Auth::id() : (int) $request->input('reviewer_id', (string) Auth::id());
        $reason = $request->input('reason');
        if ($reason === '') {
            flash('error', 'Describe the conflict.');
            View::redirect('/admin/applications/' . $application['id']);
        }
        Reviews::conflict((int) $application['id'], $reviewerId, $reason);
        flash('success', 'Conflict recorded. That reviewer can no longer open this application.');
        View::redirect(Auth::role() === 'reviewer' ? '/admin/applications' : '/admin/applications/' . $application['id']);
    }

    public function score(Request $request): void
    {
        $application = $this->application($request);
        $assignment = Reviews::assignment((int) $application['id'], (int) Auth::id());
        if ($assignment === null) {
            flash('error', 'Score this application after you are assigned to it.');
            View::redirect('/admin/applications/' . $application['id']);
        }
        foreach (Reviews::rubric((int) $application['cycle_id']) as $criterion) {
            Reviews::saveScore(
                (int) $assignment['id'],
                (int) $criterion['id'],
                (int) $request->input('score_' . $criterion['id'], '0'),
                (int) $criterion['max_score']
            );
        }
        if (in_array($application['status'], ['submitted', 'eligibility'], true)) {
            Database::exec("UPDATE applications SET status = 'under_review', updated_at = UTC_TIMESTAMP() WHERE id = ?", [(int) $application['id']]);
        }
        Audit::log(Auth::id(), 'scores_saved', 'Scores saved', 'application', (int) $application['id']);
        flash('success', 'Scores saved.');
        View::redirect('/admin/applications/' . $application['id']);
    }

    public function comment(Request $request): void
    {
        $application = $this->application($request);
        $assignment = Reviews::assignment((int) $application['id'], (int) Auth::id());
        $body = trim($request->inputRaw('body'));
        if ($assignment === null || $body === '') {
            flash('error', 'Write a comment after you are assigned.');
            View::redirect('/admin/applications/' . $application['id']);
        }
        Database::insert(
            'INSERT INTO review_comments (assignment_id, body, created_at) VALUES (?, ?, UTC_TIMESTAMP())',
            [(int) $assignment['id'], mb_substr(strip_tags($body), 0, 4000)]
        );
        flash('success', 'Comment saved.');
        View::redirect('/admin/applications/' . $application['id']);
    }

    public function interview(Request $request): void
    {
        $application = $this->application($request);
        $when = Dates::parseLocal($request->input('scheduled_at'));
        if ($when === null) {
            flash('error', 'Enter the interview date and time as DD/MM/YYYY HH:MM.');
            View::redirect('/admin/applications/' . $application['id']);
        }
        Database::insert(
            'INSERT INTO interviews (application_id, scheduled_at, location, notes, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
            [(int) $application['id'], $when, $request->input('location'), $request->inputRaw('notes'), 'planned', Auth::id()]
        );
        if (!in_array($application['status'], ['awarded', 'waitlisted', 'not_selected'], true)) {
            Database::exec("UPDATE applications SET status = 'interview', updated_at = UTC_TIMESTAMP() WHERE id = ?", [(int) $application['id']]);
        }
        Notify::user((int) $application['user_id'], 'interview_invite', [
            'reference' => (string) $application['reference'],
            'when' => display_date($when, true),
            'where' => $request->input('location'),
            'note' => $request->input('notes'),
            'url' => url('dashboard'),
        ], '/dashboard');
        Audit::log(Auth::id(), 'interview_scheduled', display_date($when, true), 'application', (int) $application['id']);
        flash('success', 'Interview saved and the applicant has been notified.');
        View::redirect('/admin/applications/' . $application['id']);
    }

    public function ics(Request $request): void
    {
        $application = $this->application($request);
        $interview = Database::one('SELECT * FROM interviews WHERE application_id = ? ORDER BY id DESC LIMIT 1', [(int) $application['id']]);
        if ($interview === null || empty($interview['scheduled_at'])) {
            flash('error', 'No interview has been scheduled.');
            View::redirect('/admin/applications/' . $application['id']);
        }
        $stamp = gmdate('Ymd\THis\Z');
        $start = gmdate('Ymd\THis\Z', strtotime((string) $interview['scheduled_at'] . ' UTC'));
        $body = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//BBAFoundation//EN\r\nBEGIN:VEVENT\r\nUID:interview-" . (int) $interview['id'] . "@bbafoundation\r\nDTSTAMP:$stamp\r\nDTSTART:$start\r\nSUMMARY:BBAFoundation scholarship interview\r\nLOCATION:" . $this->icsText((string) $interview['location']) . "\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        header('Content-Type: text/calendar; charset=UTF-8');
        header('Content-Disposition: attachment; filename="interview.ics"');
        echo $body;
    }

    public function decision(Request $request): void
    {
        $application = $this->application($request);
        $error = Reviews::decide($application, $request->input('decision'), $request->inputRaw('rationale'), (int) Auth::id());
        flash($error === '' ? 'success' : 'error', $error === '' ? 'Decision recorded.' : $error);
        View::redirect('/admin/applications/' . $application['id']);
    }

    public function release(Request $request): void
    {
        $application = $this->application($request);
        Database::exec('UPDATE cycles SET scores_released = 1, updated_at = UTC_TIMESTAMP() WHERE id = ?', [(int) $application['cycle_id']]);
        $people = Database::all(
            "SELECT user_id, reference FROM applications WHERE cycle_id = ? AND status IN ('shortlisted', 'interview', 'awarded', 'waitlisted', 'not_selected')",
            [(int) $application['cycle_id']]
        );
        foreach ($people as $person) {
            Database::insert(
                'INSERT INTO notifications (user_id, title, body, url, created_at) VALUES (?, ?, ?, ?, UTC_TIMESTAMP())',
                [(int) $person['user_id'], 'Review scores released', 'A combined score for ' . $person['reference'] . ' is now on your account when one has been calculated.', '/dashboard']
            );
        }
        Audit::log(Auth::id(), 'scores_released', 'Scores released for the cycle', 'cycle', (int) $application['cycle_id']);
        flash('success', 'Scores for this cycle are now visible to applicants who have a decision or shortlist.');
        View::redirect('/admin/applications/' . $application['id']);
    }

    public function message(Request $request): void
    {
        $ids = $this->ids($request);
        $body = trim($request->inputRaw('body'));
        if ($ids === [] || $body === '') {
            flash('error', 'Choose at least one application and write the message.');
            View::redirect('/admin/applications');
        }
        $this->sendMessages($ids, $body);
        flash('success', 'Message sent.');
        View::redirect('/admin/applications');
    }

    public function bulk(Request $request): void
    {
        $ids = $this->ids($request);
        $action = $request->input('bulk');
        if ($ids === []) {
            flash('error', 'Select at least one application.');
            View::redirect('/admin/applications');
        }
        if ($action === 'shortlist') {
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $rows = Database::all(
                "SELECT id, user_id, reference, cycle_id, status FROM applications WHERE id IN ($marks) AND status IN ('submitted', 'eligibility', 'under_review')",
                $ids
            );
            foreach ($rows as $row) {
                Database::exec("UPDATE applications SET status = 'shortlisted', updated_at = UTC_TIMESTAMP() WHERE id = ?", [(int) $row['id']]);
                $cycle = Cycles::find((int) $row['cycle_id']);
                Notify::user((int) $row['user_id'], 'shortlisted', [
                    'reference' => (string) $row['reference'],
                    'cycle' => (string) ($cycle['title'] ?? ''),
                    'note' => 'Your application has been shortlisted.',
                ], '/dashboard');
            }
            Audit::log(Auth::id(), 'shortlist', count($rows) . ' applications shortlisted');
            flash('success', count($rows) . ' applications shortlisted.');
        } elseif ($action === 'message') {
            $body = trim($request->inputRaw('body'));
            if ($body === '') {
                flash('error', 'Write the message to send.');
                View::redirect('/admin/applications');
            }
            $this->sendMessages($ids, $body);
            flash('success', 'Message sent.');
        } else {
            flash('error', 'Choose a bulk action.');
        }
        View::redirect('/admin/applications');
    }

    private function application(Request $request): array
    {
        $row = Applications::find((int) $request->param('id'));
        if ($row === null || !Reviews::canSee((int) $row['id'], (int) Auth::id(), Auth::role())) {
            http_response_code(404);
            View::render('public/404', [
                'title' => 'Not found',
                'metaDescription' => 'That application is not available.',
            ], 'bare');
            exit;
        }

        return $row;
    }

    private function filters(Request $request): array
    {
        $where = ['1=1'];
        $params = [];
        if (Auth::role() === 'reviewer') {
            $where[] = 'a.id IN (SELECT application_id FROM reviewer_assignments WHERE reviewer_id = ?)';
            $params[] = (int) Auth::id();
            $where[] = 'a.id NOT IN (SELECT application_id FROM review_conflicts WHERE reviewer_id = ?)';
            $params[] = (int) Auth::id();
        }
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $like = '%' . $q . '%';
            if (Auth::role() === 'reviewer') {
                $where[] = '(a.reference LIKE ? OR a.institution_name LIKE ?)';
                array_push($params, $like, $like);
            } else {
                $where[] = '(a.reference LIKE ? OR a.institution_name LIKE ? OR a.surname LIKE ? OR a.email LIKE ?)';
                array_push($params, $like, $like, $like, $like);
            }
        }
        foreach (['status' => 'a.status', 'community' => 'a.community', 'sex' => 'a.sex', 'current_level' => 'a.current_level'] as $key => $column) {
            $value = trim((string) $request->query($key, ''));
            if ($value !== '') {
                $where[] = $column . ' = ?';
                $params[] = $value;
            }
        }
        $institution = trim((string) $request->query('institution', ''));
        if ($institution !== '') {
            $where[] = 'a.institution_name LIKE ?';
            $params[] = '%' . $institution . '%';
        }
        if ($request->query('flag') === '1') {
            $where[] = '(a.duplicate_flag = 1 OR a.academic_flag = 1 OR a.eligibility_status = \'flag\')';
        }
        $min = $request->query('cgpa_min');
        $max = $request->query('cgpa_max');
        if ($min !== null && $min !== '' && is_numeric($min)) {
            $where[] = "a.cgpa REGEXP '^[0-9]+(\\\\.[0-9]+)?$' AND CAST(a.cgpa AS DECIMAL(6,2)) >= ?";
            $params[] = $min;
        }
        if ($max !== null && $max !== '' && is_numeric($max)) {
            $where[] = "a.cgpa REGEXP '^[0-9]+(\\\\.[0-9]+)?$' AND CAST(a.cgpa AS DECIMAL(6,2)) <= ?";
            $params[] = $max;
        }
        $reviewer = (int) $request->query('reviewer', '0');
        if ($reviewer > 0 && Auth::role() !== 'reviewer') {
            $where[] = 'a.id IN (SELECT application_id FROM reviewer_assignments WHERE reviewer_id = ?)';
            $params[] = $reviewer;
        }

        return [implode(' AND ', $where), $params];
    }

    private function reviewers(): array
    {
        return Database::all(
            "SELECT u.id, u.surname, u.first_name FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'reviewer' AND u.status = 'active' ORDER BY u.surname"
        );
    }

    /** @return list<int> */
    private function ids(Request $request): array
    {
        $raw = $_POST['ids'] ?? $request->input('application_id');
        $list = is_array($raw) ? $raw : [$raw];
        $ids = [];
        foreach ($list as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /** @param list<int> $ids */
    private function sendMessages(array $ids, string $body): void
    {
        $body = mb_substr(strip_tags($body), 0, 2000);
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::all("SELECT id, user_id, reference FROM applications WHERE id IN ($marks)", $ids);
        foreach ($rows as $row) {
            Database::insert(
                'INSERT INTO messages (application_id, user_id, sender_id, body, created_at) VALUES (?, ?, ?, ?, UTC_TIMESTAMP())',
                [(int) $row['id'], (int) $row['user_id'], Auth::id(), $body]
            );
            Database::insert(
                'INSERT INTO notifications (user_id, title, body, url, created_at) VALUES (?, ?, ?, ?, UTC_TIMESTAMP())',
                [(int) $row['user_id'], 'Message about ' . $row['reference'], mb_substr($body, 0, 500), '/dashboard']
            );
        }
        Audit::log(Auth::id(), 'applicant_message', count($rows) . ' applicants messaged');
    }

    private function icsText(string $value): string
    {
        return str_replace(["\r", "\n", ',', ';'], ['', ' ', ' ', ' '], $value);
    }
}
