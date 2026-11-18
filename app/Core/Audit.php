<?php

declare(strict_types=1);

namespace BBAF\Core;

final class Audit
{
    public static function log(?int $userId, string $action, string $summary, string $entityType = '', ?int $entityId = null): void
    {
        $summary = self::scrub($summary);
        Database::insert(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, summary, ip, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
            [$userId, $action, $entityType, $entityId, mb_substr($summary, 0, 500), client_ip(), client_agent()]
        );
    }

    private static function scrub(string $summary): string
    {
        $summary = preg_replace('/\b\d{11}\b/', '[redacted]', $summary) ?? $summary;
        $summary = preg_replace('/(?i)(password|nin|secret|token)\s*[:=]\s*\S+/', '$1=[redacted]', $summary) ?? $summary;

        return $summary;
    }
}
