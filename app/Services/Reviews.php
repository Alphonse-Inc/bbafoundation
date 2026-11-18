<?php

declare(strict_types=1);

namespace BBAF\Services;

use BBAF\Core\Audit;
use BBAF\Core\Database;

final class Reviews
{
    public static function rubric(int $cycleId): array
    {
        $rows = Database::all('SELECT * FROM rubric_criteria WHERE cycle_id = ? AND is_active = 1 ORDER BY sort_order, id', [$cycleId]);
        if ($rows !== []) {
            return $rows;
        }

        return Database::all('SELECT * FROM rubric_criteria WHERE cycle_id IS NULL AND is_active = 1 ORDER BY sort_order, id');
    }

    public static function assign(int $applicationId, int $reviewerId, int $by): string
    {
        $conflict = Database::one('SELECT id FROM review_conflicts WHERE application_id = ? AND reviewer_id = ?', [$applicationId, $reviewerId]);
        if ($conflict) {
            return 'That reviewer has declared a conflict and cannot be assigned.';
        }
        $reviewer = Database::one("SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? AND r.slug = 'reviewer' AND u.status = 'active'", [$reviewerId]);
        if ($reviewer === null) {
            return 'Choose an active reviewer.';
        }
        Database::exec(
            'INSERT IGNORE INTO reviewer_assignments (application_id, reviewer_id, assigned_by, created_at) VALUES (?, ?, ?, UTC_TIMESTAMP())',
            [$applicationId, $reviewerId, $by]
        );
        Audit::log($by, 'reviewer_assigned', 'Assigned reviewer ' . $reviewerId, 'application', $applicationId);

        return '';
    }

    public static function autoAssign(int $applicationId, int $by): int
    {
        $application = Applications::find($applicationId);
        if ($application === null) {
            return 0;
        }
        $reviewers = Database::all(
            "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id
             WHERE r.slug = 'reviewer' AND u.status = 'active'
             AND u.id NOT IN (SELECT reviewer_id FROM review_conflicts WHERE application_id = ?)
             ORDER BY (SELECT COUNT(*) FROM reviewer_assignments a WHERE a.reviewer_id = u.id) ASC, u.id ASC",
            [$applicationId]
        );
        $made = 0;
        $target = min(2, count($reviewers));
        foreach (array_slice($reviewers, 0, $target) as $reviewer) {
            if (self::assign($applicationId, (int) $reviewer['id'], $by) === '') {
                $made++;
            }
        }

        return $made;
    }

    public static function conflict(int $applicationId, int $reviewerId, string $reason): void
    {
        Database::exec(
            'INSERT INTO review_conflicts (application_id, reviewer_id, reason, created_at) VALUES (?, ?, ?, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE reason = VALUES(reason)',
            [$applicationId, $reviewerId, $reason]
        );
        Database::exec('DELETE FROM reviewer_assignments WHERE application_id = ? AND reviewer_id = ?', [$applicationId, $reviewerId]);
        Audit::log($reviewerId, 'conflict_declared', 'Conflict recorded. The application is hidden from this reviewer.', 'application', $applicationId);
    }

    public static function canSee(int $applicationId, int $userId, string $role): bool
    {
        if (in_array($role, ['super_admin', 'foundation_admin'], true)) {
            return true;
        }
        if ($role !== 'reviewer') {
            return false;
        }
        $conflict = Database::one('SELECT id FROM review_conflicts WHERE application_id = ? AND reviewer_id = ?', [$applicationId, $userId]);
        if ($conflict) {
            return false;
        }
        $assignment = Database::one('SELECT id FROM reviewer_assignments WHERE application_id = ? AND reviewer_id = ?', [$applicationId, $userId]);

        return $assignment !== null;
    }

    public static function assignment(int $applicationId, int $reviewerId): ?array
    {
        return Database::one('SELECT * FROM reviewer_assignments WHERE application_id = ? AND reviewer_id = ?', [$applicationId, $reviewerId]);
    }

    public static function saveScore(int $assignmentId, int $criterionId, int $score, int $max): void
    {
        $score = max(0, min($max, $score));
        Database::exec(
            'INSERT INTO review_scores (assignment_id, criterion_id, score, updated_at) VALUES (?, ?, ?, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE score = VALUES(score), updated_at = UTC_TIMESTAMP()',
            [$assignmentId, $criterionId, $score]
        );
    }

    public static function totals(int $applicationId, bool $includeOthers): array
    {
        $sql = 'SELECT a.reviewer_id, c.id AS criterion_id, c.weight, c.max_score, s.score
                FROM reviewer_assignments a
                JOIN rubric_criteria c ON c.cycle_id = (SELECT cycle_id FROM applications WHERE id = a.application_id)
                LEFT JOIN review_scores s ON s.assignment_id = a.id AND s.criterion_id = c.id
                WHERE a.application_id = ?';
        $rows = Database::all($sql, [$applicationId]);
        $byReviewer = [];
        foreach ($rows as $row) {
            $byReviewer[(int) $row['reviewer_id']][] = $row;
        }
        $out = [];
        foreach ($byReviewer as $reviewerId => $items) {
            $weighted = 0.0;
            $weight = 0.0;
            $complete = true;
            foreach ($items as $item) {
                $weight += (float) $item['weight'];
                if ($item['score'] === null) {
                    $complete = false;
                    continue;
                }
                $max = max(1, (int) $item['max_score']);
                $weighted += ((int) $item['score'] / $max) * (float) $item['weight'];
            }
            $out[] = [
                'reviewer_id' => $reviewerId,
                'score' => $weight > 0 ? round(($weighted / $weight) * 100, 1) : 0,
                'complete' => $complete,
            ];
        }
        if (!$includeOthers) {
            return $out;
        }

        return $out;
    }

    public static function aggregate(int $applicationId): ?float
    {
        $totals = self::totals($applicationId, true);
        $done = array_filter($totals, static fn (array $row): bool => $row['complete']);
        if ($done === []) {
            return null;
        }

        return round(array_sum(array_column($done, 'score')) / count($done), 1);
    }

    public static function decide(array $application, string $decision, string $rationale, int $adminId): string
    {
        if (!in_array($decision, ['award', 'waitlist', 'not_selected'], true)) {
            return 'Choose a decision.';
        }
        if (trim($rationale) === '') {
            return 'Record the reason for the decision.';
        }
        $cycle = Cycles::find((int) $application['cycle_id']);
        if ($cycle === null) {
            return 'The cycle no longer exists.';
        }
        if ($decision === 'award') {
            $count = (int) Database::value("SELECT COUNT(*) FROM awardees WHERE cycle_id = ? AND status <> 'declined'", [(int) $cycle['id']]);
            if ($count >= (int) $cycle['award_count']) {
                return 'This cycle has reached its award count.';
            }
        }
        $status = match ($decision) {
            'award' => 'awarded',
            'waitlist' => 'waitlisted',
            default => 'not_selected',
        };
        Database::transaction(function () use ($application, $status, $decision, $rationale, $adminId, $cycle): void {
            Database::exec('UPDATE applications SET status = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?', [$status, (int) $application['id']]);
            if ($decision === 'award') {
                $code = self::code();
                $days = max(1, (int) setting('award_accept_days', '14'));
                Database::insert(
                    'INSERT INTO awardees (application_id, user_id, cycle_id, verification_code, status, decision_rationale, decided_by, decided_at, accept_deadline, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL ' . $days . ' DAY), UTC_TIMESTAMP(), UTC_TIMESTAMP())',
                    [(int) $application['id'], (int) $application['user_id'], (int) $cycle['id'], $code, 'pending_acceptance', $rationale, $adminId]
                );
            }
        });
        $template = match ($decision) {
            'award' => 'awarded',
            'waitlist' => 'waitlisted',
            default => 'not_selected',
        };
        $deadline = display_date(gmdate('Y-m-d H:i:s', time() + ((int) setting('award_accept_days', '14') * 86400)), true);
        Notify::user((int) $application['user_id'], $template, [
            'reference' => (string) $application['reference'],
            'cycle' => (string) $cycle['title'],
            'deadline' => $deadline,
            'url' => url('awardee'),
            'note' => 'Decision recorded: ' . $status,
        ], $decision === 'award' ? '/awardee' : '/dashboard');
        Audit::log($adminId, 'decision', $status . ' for ' . $application['reference'], 'application', (int) $application['id']);

        return '';
    }

    private static function code(): string
    {
        do {
            $code = strtoupper(bin2hex(random_bytes(4)));
            $exists = Database::one('SELECT id FROM awardees WHERE verification_code = ?', [$code]);
        } while ($exists);

        return $code;
    }
}
