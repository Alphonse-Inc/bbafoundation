<?php

declare(strict_types=1);

namespace BBAF\Services;

use BBAF\Core\Database;

final class Reports
{
    public static function summary(?int $cycleId): array
    {
        $where = $cycleId ? 'WHERE cycle_id = ?' : '';
        $params = $cycleId ? [$cycleId] : [];
        $byStatus = Database::all("SELECT status, COUNT(*) AS total FROM applications $where GROUP BY status", $params);
        $byCommunity = Database::all("SELECT community, COUNT(*) AS total FROM applications $where GROUP BY community", $params);
        $byGender = Database::all("SELECT sex, COUNT(*) AS total FROM applications $where GROUP BY sex", $params);
        $byLevel = Database::all("SELECT current_level, COUNT(*) AS total FROM applications $where GROUP BY current_level", $params);
        $byInstitution = Database::all(
            "SELECT institution_name, COUNT(*) AS total FROM applications $where GROUP BY institution_name ORDER BY total DESC LIMIT 12",
            $params
        );
        $average = Database::value(
            "SELECT AVG(CAST(cgpa AS DECIMAL(6,2))) FROM applications " . ($where !== '' ? $where . ' AND ' : 'WHERE ') . "cgpa <> '' AND cgpa REGEXP '^[0-9]+(\\.[0-9]+)?$'",
            $params
        );
        $awards = (int) Database::value(
            'SELECT COUNT(*) FROM awardees' . ($cycleId ? ' WHERE cycle_id = ? AND status <> \'declined\'' : " WHERE status <> 'declined'"),
            $cycleId ? [$cycleId] : []
        );
        $reviewers = Database::all(
            "SELECT u.surname, u.first_name,
                    (SELECT COUNT(*) FROM reviewer_assignments a WHERE a.reviewer_id = u.id) AS assigned,
                    (SELECT COUNT(DISTINCT s.assignment_id) FROM review_scores s JOIN reviewer_assignments a2 ON a2.id = s.assignment_id WHERE a2.reviewer_id = u.id) AS scored
             FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'reviewer'"
        );
        $disbursed = Database::one(
            'SELECT COUNT(*) AS rows_count, SUM(amount) AS amount_sum FROM disbursements' . ($cycleId ? ' WHERE awardee_id IN (SELECT id FROM awardees WHERE cycle_id = ?)' : ''),
            $cycleId ? [$cycleId] : []
        );

        return compact('byStatus', 'byCommunity', 'byGender', 'byLevel', 'byInstitution', 'average', 'awards', 'reviewers', 'disbursed');
    }

    public static function csv(string $filename, array $header, array $rows): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'wb');
        if ($out === false) {
            return;
        }
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $header);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
    }
}
