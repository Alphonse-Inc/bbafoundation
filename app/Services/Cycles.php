<?php

declare(strict_types=1);

namespace BBAF\Services;

use BBAF\Core\Cache;
use BBAF\Core\Database;
use DateTimeImmutable;
use DateTimeZone;

final class Cycles
{
    public static function current(): ?array
    {
        return Cache::remember('public-cycle', 60, static function (): ?array {
            $open = Database::one("SELECT * FROM cycles WHERE status = 'open' ORDER BY id DESC LIMIT 1");
            if ($open) {
                return $open;
            }

            return Database::one("SELECT * FROM cycles WHERE status <> 'archived' ORDER BY id DESC LIMIT 1");
        });
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM cycles WHERE id = ?', [$id]);
    }

    public static function publicState(?array $cycle): string
    {
        if ($cycle === null) {
            return 'closed';
        }
        $status = (string) $cycle['status'];
        if (!in_array($status, ['open'], true)) {
            return $status === 'draft' ? 'opening_soon' : 'closed';
        }
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if (!empty($cycle['opens_at'])) {
            $opens = new DateTimeImmutable((string) $cycle['opens_at'], new DateTimeZone('UTC'));
            if ($opens > $now) {
                return 'opening_soon';
            }
        }
        if (!self::beforeDeadline($cycle, $now)) {
            return 'closed';
        }

        return 'open';
    }

    public static function isAccepting(?array $cycle): bool
    {
        return $cycle !== null && self::publicState($cycle) === 'open';
    }

    public static function beforeDeadline(array $cycle, ?DateTimeImmutable $now = null): bool
    {
        $now = $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $deadline = $cycle['closes_at'] ?? null;
        $extension = $cycle['extension_until'] ?? null;
        $effective = $deadline;
        if ($extension && (!$deadline || $extension > $deadline)) {
            $effective = $extension;
        }
        if ($effective === null || $effective === '') {
            return true;
        }
        $end = new DateTimeImmutable((string) $effective, new DateTimeZone('UTC'));

        return $now <= $end;
    }

    public static function stateLabel(string $state): string
    {
        return match ($state) {
            'open' => 'Open',
            'opening_soon' => 'Opening soon',
            default => 'Closed',
        };
    }

    public static function documents(int $cycleId): array
    {
        return Database::all('SELECT * FROM cycle_documents WHERE cycle_id = ? ORDER BY sort_order, id', [$cycleId]);
    }

    public static function closeExpired(): int
    {
        $rows = Database::all("SELECT * FROM cycles WHERE status = 'open'");
        $count = 0;
        foreach ($rows as $cycle) {
            if (!self::beforeDeadline($cycle)) {
                Database::exec("UPDATE cycles SET status = 'closed', updated_at = UTC_TIMESTAMP() WHERE id = ?", [(int) $cycle['id']]);
                $count++;
            }
        }
        if ($count > 0) {
            Cache::forget('public-cycle');
        }

        return $count;
    }
}
