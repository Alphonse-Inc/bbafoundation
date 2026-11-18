<?php

declare(strict_types=1);

namespace BBAF\Controllers;

use BBAF\Core\Audit;
use BBAF\Core\Auth;
use BBAF\Core\Cache;
use BBAF\Core\Database;
use BBAF\Core\Dates;
use BBAF\Core\Paginator;
use BBAF\Core\Request;
use BBAF\Core\View;

final class AdminCycleController
{
    public function index(Request $request): void
    {
        $page = Paginator::page($request);
        $per = Paginator::perPage($request);
        $total = (int) Database::value('SELECT COUNT(*) FROM cycles');
        $rows = Database::all('SELECT * FROM cycles ORDER BY id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per));
        View::render('admin/cycles', [
            'title' => 'Cycles',
            'rows' => $rows,
            'meta' => Paginator::meta($total, $page, $per),
        ], 'admin');
    }

    public function edit(Request $request): void
    {
        $id = (int) $request->param('id');
        if ($id === 0) {
            View::render('admin/cycle-form', [
                'title' => 'New cycle',
                'cycle' => null,
                'documents' => [],
            ], 'admin');
            return;
        }
        $cycle = Database::one('SELECT * FROM cycles WHERE id = ?', [$id]);
        if ($cycle === null) {
            flash('error', 'Cycle not found.');
            View::redirect('/admin/cycles');
        }
        View::render('admin/cycle-form', [
            'title' => $cycle['title'],
            'cycle' => $cycle,
            'documents' => Database::all('SELECT * FROM cycle_documents WHERE cycle_id = ? ORDER BY sort_order', [(int) $cycle['id']]),
        ], 'admin');
    }

    public function save(Request $request): void
    {
        $id = (int) $request->input('id', '0');
        $status = $request->input('status', 'draft');
        $allowed = ['draft', 'open', 'closed', 'under_review', 'results_announced', 'archived'];
        if (!in_array($status, $allowed, true)) {
            $status = 'draft';
        }
        if ($status === 'open') {
            Database::exec("UPDATE cycles SET status = 'closed', updated_at = UTC_TIMESTAMP() WHERE status = 'open' AND id <> ?", [$id]);
        }
        $fields = [
            $request->input('title'),
            $request->inputRaw('announcement'),
            Dates::parseLocal($request->input('opens_at')),
            Dates::parseLocal($request->input('closes_at')),
            Dates::parseLocal($request->input('extension_until')),
            max(1, (int) $request->input('award_count', '10')),
            $status,
            $request->has('require_indigene') ? 1 : 0,
            $request->has('allow_ijero') ? 1 : 0,
            $request->has('allow_ewu') ? 1 : 0,
            $request->has('allow_other') ? 1 : 0,
            $request->has('require_government') ? 1 : 0,
            $request->input('min_cgpa', '3.50'),
            $request->input('poly_min', 'upper_credit'),
            $request->has('hard_block') ? 1 : 0,
            $request->has('referee_links') ? 1 : 0,
            $request->has('legacy_zip') ? 1 : 0,
            max(1, (int) $request->input('max_file_mb', '5')) * 1048576,
            max(1, (int) $request->input('max_total_mb', '20')) * 1048576,
            $request->has('blind_review') ? 1 : 0,
        ];
        if ($id > 0) {
            Database::exec(
                'UPDATE cycles SET title=?, announcement=?, opens_at=?, closes_at=?, extension_until=?, award_count=?, status=?, require_indigene=?, allow_ijero=?, allow_ewu=?, allow_other=?, require_government=?, min_cgpa=?, poly_min=?, hard_block=?, referee_links=?, legacy_zip=?, max_file_bytes=?, max_total_bytes=?, blind_review=?, updated_at=UTC_TIMESTAMP() WHERE id=?',
                array_merge($fields, [$id])
            );
        } else {
            $id = Database::insert(
                'INSERT INTO cycles (title, announcement, opens_at, closes_at, extension_until, award_count, status, require_indigene, allow_ijero, allow_ewu, allow_other, require_government, min_cgpa, poly_min, hard_block, referee_links, legacy_zip, max_file_bytes, max_total_bytes, blind_review, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
                $fields
            );
            require_once BBAF_ROOT . '/database/seed.php';
            bbaf_seed_cycle_children(Database::pdo(), $id);
        }
        if (isset($_POST['doc_label']) && is_array($_POST['doc_label'])) {
            foreach ($_POST['doc_label'] as $docId => $label) {
                Database::exec(
                    'UPDATE cycle_documents SET label = ?, is_required = ? WHERE id = ? AND cycle_id = ?',
                    [trim((string) $label), isset($_POST['doc_required'][$docId]) ? 1 : 0, (int) $docId, $id]
                );
            }
        }
        Cache::forget('public-cycle');
        Audit::log(Auth::id(), 'cycle_saved', 'Cycle saved: ' . $request->input('title'), 'cycle', $id);
        flash('success', $status === 'open' ? 'Cycle saved. Any other open cycle was closed.' : 'Cycle saved.');
        View::redirect('/admin/cycles/' . $id);
    }
}
