<?php

declare(strict_types=1);

namespace BBAF\Controllers;

use BBAF\Core\Audit;
use BBAF\Core\Auth;
use BBAF\Core\Database;
use BBAF\Core\Dates;
use BBAF\Core\Paginator;
use BBAF\Core\Request;
use BBAF\Core\Upload;
use BBAF\Core\View;
use BBAF\Services\Content;
use BBAF\Services\Notify;
use BBAF\Services\Reports;

final class AdminAwardController
{
    public function index(Request $request): void
    {
        $page = Paginator::page($request);
        $per = Paginator::perPage($request);
        $total = (int) Database::value('SELECT COUNT(*) FROM awardees');
        $meta = Paginator::meta($total, $page, $per);
        $rows = Database::all(
            'SELECT w.*, a.surname, a.other_names, a.institution_name, a.reference, c.title AS cycle_title
             FROM awardees w
             JOIN applications a ON a.id = w.application_id
             JOIN cycles c ON c.id = w.cycle_id
             ORDER BY w.id DESC LIMIT ' . (int) $per . ' OFFSET ' . (int) (($meta['page'] - 1) * $per)
        );
        View::render('admin/awardees', [
            'title' => 'Awardees',
            'rows' => $rows,
            'meta' => $meta,
            'progress' => Content::goalProgress(),
        ], 'admin');
    }

    public function update(Request $request): void
    {
        $status = $request->input('status');
        if (!in_array($status, ['pending_acceptance', 'active', 'deferred', 'ended', 'declined'], true)) {
            flash('error', 'Choose a status.');
            View::redirect('/admin/awardees');
        }
        Database::exec(
            'UPDATE awardees SET status = ?, expected_graduation = ?, ended_reason = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?',
            [$status, $request->input('expected_graduation'), $request->input('ended_reason'), (int) $request->param('id')]
        );
        Audit::log(Auth::id(), 'awardee_updated', $status, 'awardee', (int) $request->param('id'));
        flash('success', 'Awardee updated.');
        View::redirect('/admin/awardees');
    }

    public function disbursements(Request $request): void
    {
        $page = Paginator::page($request);
        $per = Paginator::perPage($request);
        $total = (int) Database::value('SELECT COUNT(*) FROM disbursements');
        $meta = Paginator::meta($total, $page, $per);
        $rows = Database::all(
            'SELECT d.*, a.reference FROM disbursements d JOIN awardees w ON w.id = d.awardee_id JOIN applications a ON a.id = w.application_id ORDER BY d.id DESC LIMIT ' . (int) $per . ' OFFSET ' . (int) (($meta['page'] - 1) * $per)
        );
        $awardees = Database::all(
            "SELECT w.id, a.reference, a.surname, a.other_names FROM awardees w JOIN applications a ON a.id = w.application_id WHERE w.status <> 'declined' ORDER BY w.id DESC"
        );
        View::render('admin/disbursements', [
            'title' => 'Disbursements',
            'rows' => $rows,
            'meta' => $meta,
            'awardees' => $awardees,
        ], 'admin');
    }

    public function saveDisbursement(Request $request): void
    {
        $amount = str_replace(',', '', $request->input('amount'));
        $storedAmount = null;
        if ($amount !== '') {
            if (!is_numeric($amount)) {
                flash('error', 'Enter an amount only when you have one. Leave it blank otherwise.');
                View::redirect('/admin/disbursements');
            }
            $storedAmount = $amount;
        }
        $awardee = Database::one('SELECT id FROM awardees WHERE id = ?', [(int) $request->input('awardee_id')]);
        if ($awardee === null) {
            flash('error', 'Choose an awardee.');
            View::redirect('/admin/disbursements');
        }
        $stored = '';
        $original = '';
        $file = $request->file('receipt');
        if ($file !== null) {
            try {
                $saved = Upload::storePrivate($file, Upload::documentMimes(), 5 * 1048576);
                $stored = $saved['stored_name'];
                $original = $saved['original_name'];
            } catch (\Throwable $error) {
                flash('error', $error->getMessage());
                View::redirect('/admin/disbursements');
            }
        }
        Database::insert(
            'INSERT INTO disbursements (awardee_id, amount, currency, paid_on, method, reference_no, payee, notes, stored_name, original_name, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
            [
                (int) $awardee['id'],
                $storedAmount,
                $request->input('currency', 'NGN') ?: 'NGN',
                Dates::parseLocal($request->input('paid_on')),
                $request->input('method'),
                $request->input('reference_no'),
                $request->input('payee'),
                $request->inputRaw('notes'),
                $stored,
                $original,
                Auth::id(),
            ]
        );
        Audit::log(Auth::id(), 'disbursement_recorded', 'Disbursement recorded', 'awardee', (int) $awardee['id']);
        flash('success', 'Disbursement recorded. No amount is assumed when the field is left blank.');
        View::redirect('/admin/disbursements');
    }

    public function exportDisbursements(Request $request): void
    {
        $rows = Database::all(
            'SELECT a.reference, d.amount, d.currency, d.paid_on, d.method, d.reference_no, d.payee FROM disbursements d JOIN awardees w ON w.id = d.awardee_id JOIN applications a ON a.id = w.application_id ORDER BY d.id'
        );
        $out = [];
        foreach ($rows as $row) {
            $out[] = array_values($row);
        }
        Reports::csv('disbursements.csv', ['Reference', 'Amount', 'Currency', 'Paid on', 'Method', 'Reference no', 'Payee'], $out);
    }

    public function renewals(Request $request): void
    {
        $page = Paginator::page($request);
        $per = Paginator::perPage($request);
        $total = (int) Database::value('SELECT COUNT(*) FROM renewals');
        $meta = Paginator::meta($total, $page, $per);
        $rows = Database::all(
            'SELECT n.*, a.reference, a.surname, a.other_names, w.user_id
             FROM renewals n JOIN awardees w ON w.id = n.awardee_id JOIN applications a ON a.id = w.application_id
             ORDER BY n.id DESC LIMIT ' . (int) $per . ' OFFSET ' . (int) (($meta['page'] - 1) * $per)
        );
        View::render('admin/renewals', ['title' => 'Renewals', 'rows' => $rows, 'meta' => $meta], 'admin');
    }

    public function reviewRenewal(Request $request): void
    {
        $status = $request->input('status');
        if (!in_array($status, ['approved', 'deferred', 'ended'], true)) {
            flash('error', 'Choose a decision.');
            View::redirect('/admin/renewals');
        }
        $renewal = Database::one(
            'SELECT n.*, w.user_id FROM renewals n JOIN awardees w ON w.id = n.awardee_id WHERE n.id = ?',
            [(int) $request->param('id')]
        );
        if ($renewal === null) {
            flash('error', 'Renewal not found.');
            View::redirect('/admin/renewals');
        }
        Database::exec(
            'UPDATE renewals SET status = ?, review_note = ?, reviewed_by = ?, reviewed_at = UTC_TIMESTAMP() WHERE id = ?',
            [$status, $request->input('review_note'), Auth::id(), (int) $renewal['id']]
        );
        $awardStatus = $status === 'approved' ? 'active' : ($status === 'deferred' ? 'deferred' : 'ended');
        Database::exec(
            'UPDATE awardees SET status = ?, ended_reason = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?',
            [$awardStatus, $status === 'ended' ? $request->input('review_note') : '', (int) $renewal['awardee_id']]
        );
        Notify::user((int) $renewal['user_id'], 'renewal_decision', [
            'decision' => $status,
            'note' => $request->input('review_note'),
        ], '/awardee');
        Audit::log(Auth::id(), 'renewal_' . $status, 'Renewal ' . $status, 'awardee', (int) $renewal['awardee_id']);
        flash('success', 'Renewal updated.');
        View::redirect('/admin/renewals');
    }

    public function reports(Request $request): void
    {
        $cycleId = (int) $request->query('cycle', '0');
        View::render('admin/reports', [
            'title' => 'Reports',
            'cycles' => Database::all('SELECT id, title FROM cycles ORDER BY id DESC'),
            'cycleId' => $cycleId,
            'summary' => Reports::summary($cycleId > 0 ? $cycleId : null),
            'progress' => Content::goalProgress(),
        ], 'admin');
    }

    public function exportReport(Request $request): void
    {
        $cycleId = (int) $request->query('cycle', '0');
        $summary = Reports::summary($cycleId > 0 ? $cycleId : null);
        $rows = [];
        foreach ($summary['byStatus'] as $row) {
            $rows[] = ['status', $row['status'], $row['total']];
        }
        foreach ($summary['byCommunity'] as $row) {
            $rows[] = ['community', $row['community'], $row['total']];
        }
        foreach ($summary['byGender'] as $row) {
            $rows[] = ['sex', $row['sex'], $row['total']];
        }
        Reports::csv('report.csv', ['Group', 'Label', 'Total'], $rows);
    }
}
