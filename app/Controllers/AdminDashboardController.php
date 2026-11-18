<?php

declare(strict_types=1);

namespace BBAF\Controllers;

use BBAF\Core\Auth;
use BBAF\Core\Database;
use BBAF\Core\Request;
use BBAF\Core\View;
use BBAF\Services\Content;
use BBAF\Services\Cycles;

final class AdminDashboardController
{
    public function index(Request $request): void
    {
        $cycle = Cycles::current();
        $today = (int) Database::value("SELECT COUNT(*) FROM applications WHERE DATE(submitted_at) = UTC_DATE()");
        $total = (int) Database::value('SELECT COUNT(*) FROM applications');
        $byStatus = Database::all('SELECT status, COUNT(*) AS total FROM applications GROUP BY status');
        $awards = $cycle ? (int) Database::value("SELECT COUNT(*) FROM awardees WHERE cycle_id = ? AND status <> 'declined'", [(int) $cycle['id']]) : 0;
        $activity = Database::all('SELECT action, summary, created_at FROM audit_logs ORDER BY id DESC LIMIT 8');
        View::render('admin/dashboard', [
            'title' => 'Dashboard',
            'cycle' => $cycle,
            'state' => Cycles::publicState($cycle),
            'today' => $today,
            'total' => $total,
            'byStatus' => $byStatus,
            'awards' => $awards,
            'target' => $cycle ? (int) $cycle['award_count'] : 10,
            'progress' => Content::goalProgress(),
            'activity' => $activity,
            'role' => Auth::role(),
        ], 'admin');
    }
}
