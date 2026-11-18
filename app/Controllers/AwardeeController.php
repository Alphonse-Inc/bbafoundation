<?php

declare(strict_types=1);

namespace BBAF\Controllers;

use BBAF\Core\Auth;
use BBAF\Core\Database;
use BBAF\Core\Request;
use BBAF\Core\Upload;
use BBAF\Core\View;
use BBAF\Services\Applications;
use BBAF\Services\Awards;
use BBAF\Services\Cycles;
use BBAF\Services\Documents;

final class AwardeeController
{
    public function home(Request $request): void
    {
        $award = Awards::forUser((int) Auth::id());
        $application = $award ? Applications::find((int) $award['application_id']) : null;
        View::render('awardee/home', [
            'title' => 'Award',
            'metaDescription' => 'Your BBAFoundation award.',
            'award' => $award,
            'application' => $application,
            'renewals' => $award ? Awards::renewals((int) $award['id']) : [],
            'disbursements' => $award ? Awards::disbursements((int) $award['id']) : [],
        ]);
    }

    public function accept(Request $request): void
    {
        $award = $this->award();
        $error = Awards::accept($award, (int) Auth::id());
        flash($error === '' ? 'success' : 'error', $error === '' ? 'You have accepted the award.' : $error);
        View::redirect('/awardee');
    }

    public function decline(Request $request): void
    {
        $award = $this->award();
        $error = Awards::decline($award, (int) Auth::id());
        flash($error === '' ? 'success' : 'error', $error === '' ? 'You have declined the award.' : $error);
        View::redirect('/dashboard');
    }

    public function renewal(Request $request): void
    {
        $award = $this->award();
        if ($award['status'] !== 'active' && $award['status'] !== 'deferred') {
            flash('error', 'A renewal can be sent while the award is active.');
            View::redirect('/awardee');
        }
        $stored = '';
        $original = '';
        $file = $request->file('evidence');
        if ($file !== null) {
            try {
                $saved = Upload::storePrivate($file, Upload::documentMimes(), 5 * 1048576);
                $stored = $saved['stored_name'];
                $original = $saved['original_name'];
            } catch (\Throwable $error) {
                flash('error', $error->getMessage());
                View::redirect('/awardee');
            }
        }
        Database::insert(
            'INSERT INTO renewals (awardee_id, academic_year, cgpa, notes, stored_name, original_name, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
            [(int) $award['id'], $request->input('academic_year'), $request->input('cgpa'), $request->inputRaw('notes'), $stored, $original, 'submitted']
        );
        flash('success', 'Renewal submitted.');
        View::redirect('/awardee');
    }

    public function profile(Request $request): void
    {
        $award = $this->award();
        Database::exec(
            'UPDATE awardees SET showcase_consent = ?, public_note = ?, expected_graduation = ?, updated_at = UTC_TIMESTAMP() WHERE id = ? AND user_id = ?',
            [
                $request->has('showcase_consent') ? 1 : 0,
                mb_substr($request->input('public_note'), 0, 500),
                $request->input('expected_graduation'),
                (int) $award['id'],
                (int) Auth::id(),
            ]
        );
        flash('success', 'Profile updated. The public scholars page shows you only if you give consent.');
        View::redirect('/awardee');
    }

    public function letter(Request $request): void
    {
        $award = $this->award();
        if ($award['status'] === 'pending_acceptance' || $award['status'] === 'declined') {
            flash('error', 'The award letter is available after you accept.');
            View::redirect('/awardee');
        }
        $application = Applications::find((int) $award['application_id']);
        $cycle = Cycles::find((int) $award['cycle_id']);
        Documents::sendDownload(Documents::awardLetter($award, $application, $cycle), 'BBAFoundation-award-letter.pdf');
    }

    private function award(): array
    {
        $award = Awards::forUser((int) Auth::id());
        if ($award === null) {
            flash('error', 'There is no award on this account.');
            View::redirect('/dashboard');
        }

        return $award;
    }
}
