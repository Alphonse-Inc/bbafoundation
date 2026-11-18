<?php

declare(strict_types=1);

namespace BBAF\Controllers;

use BBAF\Core\Auth;
use BBAF\Core\Database;
use BBAF\Core\Request;
use BBAF\Core\Security;
use BBAF\Core\View;
use BBAF\Services\Applications;
use BBAF\Services\Awards;
use BBAF\Services\Content;
use BBAF\Services\Cycles;
use BBAF\Services\Documents;
use BBAF\Services\Notify;

final class ApplicantController
{
    public function dashboard(Request $request): void
    {
        $user = Auth::user();
        $cycle = Cycles::current();
        $application = $cycle ? Applications::forUser((int) $user['id'], (int) $cycle['id']) : null;
        $award = Awards::forUser((int) $user['id']);
        $messages = Database::all(
            'SELECT body, created_at FROM messages WHERE user_id = ? ORDER BY id DESC LIMIT 10',
            [(int) $user['id']]
        );
        $releasedScore = null;
        if ($application && $cycle && (int) $cycle['scores_released'] === 1) {
            $releasedScore = \BBAF\Services\Reviews::aggregate((int) $application['id']);
        }
        View::render('applicant/dashboard', [
            'title' => 'Your account',
            'metaDescription' => 'Track your BBAFoundation application.',
            'user' => $user,
            'cycle' => $cycle,
            'application' => $application,
            'award' => $award,
            'notes' => Notify::recent((int) $user['id']),
            'messages' => $messages,
            'releasedScore' => $releasedScore,
            'cycleState' => Cycles::publicState($cycle),
        ]);
    }

    public function wizard(Request $request): void
    {
        $context = $this->context();
        if ($context === null) {
            flash('warning', 'There is no scholarship cycle to apply for yet.');
            View::redirect('/apply');
        }
        [$user, $cycle, $application] = $context;
        if (!Cycles::isAccepting($cycle) && ($application === null || !Applications::editable($application))) {
            flash('warning', 'Applications are not open.');
            View::redirect('/apply');
        }
        if ($application === null) {
            $application = Applications::ensure((int) $user['id'], $cycle, $user);
        }
        $step = max(1, min(6, (int) ($request->param('step', '1'))));
        $this->renderWizard($application, $cycle, $step, []);
    }

    public function save(Request $request): void
    {
        $context = $this->context(true);
        if ($context === null) {
            $this->fail($request, 'Applications are not open.');
        }
        [$user, $cycle, $application] = $context;
        $step = max(1, min(6, (int) $request->input('step', '1')));
        $result = Applications::save($application, $step, $_POST);
        if ($step === 5 && ($result['ok'] ?? false)) {
            Applications::saveReferees($application, $_POST);
        }
        if (!($result['ok'] ?? false)) {
            $this->fail($request, $result['error'] ?? 'Check the highlighted fields.', $result['errors'] ?? [], $step);
        }
        if ($request->wantsJson()) {
            View::json(['ok' => true]);
            return;
        }
        $next = min(6, $step + 1);
        flash('success', 'Draft saved.');
        View::redirect('/application/step/' . $next);
    }

    public function upload(Request $request): void
    {
        $context = $this->context(true);
        if ($context === null) {
            $this->fail($request, 'Applications are not open.');
        }
        [$user, $cycle, $application] = $context;
        $file = $request->file('document');
        if ($file === null) {
            $this->fail($request, 'Choose a file.', [], 3);
        }
        $result = Applications::storeDocument($application, $cycle, $request->input('doc_key'), $file);
        if (!($result['ok'] ?? false)) {
            $this->fail($request, $result['error'] ?? 'Upload failed.', [], 3);
        }
        flash('success', 'Document stored.');
        View::redirect('/application/step/3');
    }

    public function submit(Request $request): void
    {
        $context = $this->context(true);
        if ($context === null) {
            $this->fail($request, 'Applications are not open.');
        }
        [$user, $cycle, $application] = $context;
        $result = Applications::submit($application, $cycle, $_POST);
        if (!($result['ok'] ?? false)) {
            $this->fail($request, $result['error'] ?? 'The application is not complete.', $result['errors'] ?? [], 6);
        }
        flash('success', 'Application ' . $result['reference'] . ' has been received. Applying is 100% free.');
        View::redirect('/dashboard');
    }

    public function filledPdf(Request $request): void
    {
        $context = $this->context(true);
        if ($context === null) {
            View::redirect('/dashboard');
        }
        Documents::sendDownload(Documents::filledForm($context[2]), 'application.pdf');
    }

    public function acknowledgement(Request $request): void
    {
        $context = $this->context(true);
        if ($context === null || empty($context[2]['reference'])) {
            flash('error', 'An acknowledgement is available after you submit.');
            View::redirect('/dashboard');
        }
        $path = BBAF_ROOT . '/storage/uploads/private/slip-' . (int) $context[2]['id'] . '.pdf';
        if (!is_file($path)) {
            file_put_contents($path, Documents::acknowledgement($context[2]));
        }
        Documents::sendDownload((string) file_get_contents($path), $context[2]['reference'] . '-acknowledgement.pdf');
    }

    public function download(Request $request): void
    {
        $context = $this->context(true);
        if ($context === null) {
            http_response_code(404);
            return;
        }
        $doc = Database::one('SELECT * FROM application_documents WHERE id = ? AND application_id = ?', [(int) $request->param('id'), (int) $context[2]['id']]);
        if ($doc === null) {
            http_response_code(404);
            return;
        }
        $this->sendFile($doc['stored_name'], $doc['original_name'], $doc['mime']);
    }

    public function dataRequest(Request $request): void
    {
        $type = $request->input('request_type');
        if (!in_array($type, ['export', 'erasure', 'correction'], true)) {
            flash('error', 'Choose a request type.');
            View::redirect('/dashboard');
        }
        Database::insert(
            'INSERT INTO data_requests (user_id, request_type, details, status, created_at) VALUES (?, ?, ?, ?, UTC_TIMESTAMP())',
            [(int) Auth::id(), $type, $request->input('details'), 'pending']
        );
        flash('success', 'Your request has been sent to the secretariat.');
        View::redirect('/dashboard');
    }

    public function exportDownload(Request $request): void
    {
        $payload = Security::unsign($request->param('token'));
        $file = is_array($payload) ? basename((string) ($payload['file'] ?? '')) : '';
        $path = BBAF_ROOT . '/storage/uploads/private/exports/' . $file;
        if ($file === '' || !is_file($path)) {
            http_response_code(404);
            echo 'This export link is invalid or has expired.';
            return;
        }
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="bbaf-data-export.json"');
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
    }

    private function context(bool $existingOnly = false): ?array
    {
        $user = Auth::user();
        $cycle = Cycles::current();
        if ($user === null || $cycle === null) {
            return null;
        }
        $application = Applications::forUser((int) $user['id'], (int) $cycle['id']);
        if ($existingOnly && $application === null) {
            return null;
        }

        return [$user, $cycle, $application];
    }

    private function renderWizard(array $application, array $cycle, int $step, array $errors): void
    {
        View::render('applicant/wizard', [
            'title' => 'Application',
            'metaDescription' => 'BBAFoundation scholarship application.',
            'application' => Applications::find((int) $application['id']) ?? $application,
            'cycle' => $cycle,
            'step' => $step,
            'steps' => Applications::STEPS,
            'errors' => $errors,
            'lgas' => Content::lgas(),
            'institutions' => Content::institutions(),
            'documents' => Cycles::documents((int) $cycle['id']),
            'uploaded' => Applications::documents((int) $application['id']),
            'referees' => Applications::referees((int) $application['id']),
            'editable' => Applications::editable($application),
        ]);
    }

    private function fail(Request $request, string $message, array $errors = [], int $step = 1): void
    {
        if ($request->wantsJson()) {
            View::json(['ok' => false, 'error' => $message, 'errors' => $errors], 422);
            exit;
        }
        flash('error', $message);
        $context = $this->context(true);
        if ($context === null) {
            View::redirect('/dashboard');
        }
        $this->renderWizard($context[2], $context[1], $step, $errors);
        exit;
    }

    private function sendFile(string $stored, string $name, string $mime): void
    {
        $path = \BBAF\Services\Applications::privatePath($stored);
        if (!is_file($path)) {
            http_response_code(404);
            return;
        }
        header('Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . rawurlencode($name) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($path);
    }
}
