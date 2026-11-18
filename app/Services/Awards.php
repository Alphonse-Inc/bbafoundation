<?php

declare(strict_types=1);

namespace BBAF\Services;

use BBAF\Core\Audit;
use BBAF\Core\Database;

final class Awards
{
    public static function forUser(int $userId): ?array
    {
        return Database::one('SELECT * FROM awardees WHERE user_id = ? ORDER BY id DESC LIMIT 1', [$userId]);
    }

    public static function accept(array $awardee, int $userId): string
    {
        if ((int) $awardee['user_id'] !== $userId) {
            return 'This offer is not yours.';
        }
        if ($awardee['status'] !== 'pending_acceptance') {
            return 'This offer is no longer waiting for a response.';
        }
        if (!empty($awardee['accept_deadline']) && strtotime((string) $awardee['accept_deadline'] . ' UTC') < time()) {
            return 'The time allowed to accept this offer has passed.';
        }
        $role = Database::one("SELECT id FROM roles WHERE slug = 'awardee'");
        Database::transaction(function () use ($awardee, $userId, $role): void {
            Database::exec("UPDATE awardees SET status = 'active', accepted_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE id = ?", [(int) $awardee['id']]);
            if ($role) {
                Database::exec('UPDATE users SET role_id = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?', [(int) $role['id'], $userId]);
            }
        });
        Audit::log($userId, 'award_accepted', 'Award accepted.', 'awardee', (int) $awardee['id']);

        return '';
    }

    public static function decline(array $awardee, int $userId): string
    {
        if ((int) $awardee['user_id'] !== $userId || $awardee['status'] !== 'pending_acceptance') {
            return 'This offer cannot be declined.';
        }
        Database::exec("UPDATE awardees SET status = 'declined', declined_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE id = ?", [(int) $awardee['id']]);
        Database::exec("UPDATE applications SET status = 'not_selected', updated_at = UTC_TIMESTAMP() WHERE id = ?", [(int) $awardee['application_id']]);
        Audit::log($userId, 'award_declined', 'Award declined.', 'awardee', (int) $awardee['id']);

        return '';
    }

    public static function renewals(int $awardeeId): array
    {
        return Database::all('SELECT * FROM renewals WHERE awardee_id = ? ORDER BY id DESC', [$awardeeId]);
    }

    public static function disbursements(int $awardeeId): array
    {
        return Database::all('SELECT * FROM disbursements WHERE awardee_id = ? ORDER BY paid_on IS NULL, paid_on DESC, id DESC', [$awardeeId]);
    }
}
