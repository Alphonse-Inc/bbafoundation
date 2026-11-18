<?php

declare(strict_types=1);

namespace BBAF\Services;

use BBAF\Core\Audit;
use BBAF\Core\Database;

final class Retention
{
    public static function candidates(): array
    {
        $months = max(1, (int) setting('retention_months', '12'));

        return Database::all(
            "SELECT a.id, a.reference, a.status, a.submitted_at, c.title
             FROM applications a JOIN cycles c ON c.id = a.cycle_id
             WHERE a.retained_at IS NULL
               AND a.status IN ('not_selected', 'waitlisted', 'submitted', 'under_review', 'shortlisted', 'interview', 'eligibility')
               AND c.status IN ('results_announced', 'archived', 'closed')
               AND a.submitted_at IS NOT NULL
               AND a.submitted_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL {$months} MONTH)
               AND a.id NOT IN (SELECT application_id FROM awardees WHERE status <> 'declined')"
        );
    }

    public static function purge(array $ids, int $adminId, bool $dryRun): array
    {
        $candidates = self::candidates();
        $allowed = [];
        foreach ($candidates as $row) {
            if (in_array((int) $row['id'], $ids, true)) {
                $allowed[] = $row;
            }
        }
        if ($dryRun) {
            return ['count' => count($allowed), 'rows' => $allowed];
        }
        foreach ($allowed as $row) {
            $docs = Database::all('SELECT stored_name FROM application_documents WHERE application_id = ?', [(int) $row['id']]);
            foreach ($docs as $doc) {
                $path = Applications::privatePath((string) $doc['stored_name']);
                if (is_file($path)) {
                    unlink($path);
                }
            }
            $refs = Database::all('SELECT stored_name FROM application_referees WHERE application_id = ? AND stored_name <> ""', [(int) $row['id']]);
            foreach ($refs as $ref) {
                $path = Applications::privatePath((string) $ref['stored_name']);
                if (is_file($path)) {
                    unlink($path);
                }
            }
            $slip = BBAF_ROOT . '/storage/uploads/private/slip-' . (int) $row['id'] . '.pdf';
            if (is_file($slip)) {
                unlink($slip);
            }
            Database::exec('DELETE FROM application_documents WHERE application_id = ?', [(int) $row['id']]);
            Database::exec(
                "UPDATE applications SET surname = '', other_names = '', phone = '', phone_normalized = '', email = '',
                 nin_cipher = NULL, nin_hash = NULL, residential_address = NULL, parents_hometown = NULL,
                 personal_statement = NULL, signature_name = '', place_of_birth = '', retained_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
                 WHERE id = ?",
                [(int) $row['id']]
            );
            Database::exec('DELETE FROM application_referees WHERE application_id = ?', [(int) $row['id']]);
            Audit::log($adminId, 'retention_purge', 'Personal data removed from application ' . ($row['reference'] ?: $row['id']), 'application', (int) $row['id']);
        }

        return ['count' => count($allowed), 'rows' => $allowed];
    }

    public static function exportUser(int $userId): string
    {
        $user = Database::one('SELECT id, surname, first_name, other_names, email, phone, gender, created_at, consent_at FROM users WHERE id = ?', [$userId]);
        $applications = Database::all('SELECT id, reference, status, surname, other_names, email, phone, community, institution_name, current_level, submitted_at FROM applications WHERE user_id = ?', [$userId]);
        foreach ($applications as &$application) {
            unset($application['nin_cipher'], $application['nin_hash']);
            $application['documents'] = Database::all('SELECT doc_key, original_name, status, uploaded_at FROM application_documents WHERE application_id = ?', [(int) $application['id']]);
        }
        $payload = json_encode(['user' => $user, 'applications' => $applications], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $name = 'export-' . $userId . '-' . gmdate('YmdHis') . '.json';
        $path = BBAF_ROOT . '/storage/uploads/private/' . $name;
        file_put_contents($path, (string) $payload);

        return $name;
    }
}
