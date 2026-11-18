<?php

declare(strict_types=1);

namespace BBAF\Services;

use BBAF\Core\Cache;
use BBAF\Core\Database;

final class Content
{
    public static function slides(): array
    {
        return Cache::remember('slides', 300, static function (): array {
            return Database::all('SELECT * FROM hero_slides WHERE is_visible = 1 ORDER BY sort_order, id');
        });
    }

    public static function blocks(string $group): array
    {
        $all = Cache::remember('blocks', 300, static function (): array {
            $rows = Database::all('SELECT * FROM blocks WHERE is_visible = 1 ORDER BY group_key, sort_order, id');
            $grouped = [];
            foreach ($rows as $row) {
                $grouped[$row['group_key']][] = $row;
            }

            return $grouped;
        });

        return $all[$group] ?? [];
    }

    public static function trustees(): array
    {
        return Cache::remember('trustees', 300, static function (): array {
            return Database::all('SELECT * FROM trustees WHERE is_active = 1 ORDER BY sort_order, id');
        });
    }

    public static function trustee(string $slug): ?array
    {
        foreach (self::trustees() as $trustee) {
            if ($trustee['slug'] === $slug) {
                return $trustee;
            }
        }

        return Database::one('SELECT * FROM trustees WHERE slug = ? AND is_active = 1', [$slug]);
    }

    public static function affiliates(): array
    {
        return Cache::remember('affiliates', 300, static function (): array {
            return Database::all('SELECT * FROM affiliates WHERE is_visible = 1 ORDER BY sort_order, id');
        });
    }

    public static function faqs(): array
    {
        return Cache::remember('faqs', 300, static function (): array {
            $groups = Database::all('SELECT * FROM faq_groups ORDER BY sort_order, id');
            $items = Database::all('SELECT * FROM faqs WHERE is_visible = 1 ORDER BY sort_order, id');
            foreach ($groups as &$group) {
                $group['items'] = [];
                foreach ($items as $item) {
                    if ((int) $item['group_id'] === (int) $group['id']) {
                        $group['items'][] = $item;
                    }
                }
            }

            return $groups;
        });
    }

    public static function page(string $slug): ?array
    {
        $pages = Cache::remember('pages', 300, static function (): array {
            $rows = Database::all("SELECT * FROM pages WHERE status = 'published'");
            $indexed = [];
            foreach ($rows as $row) {
                $indexed[$row['slug']] = $row;
            }

            return $indexed;
        });

        return $pages[$slug] ?? null;
    }

    public static function lgas(): array
    {
        return Cache::remember('lgas', 600, static function (): array {
            return Database::all('SELECT * FROM lgas ORDER BY sort_order, name');
        });
    }

    public static function institutions(string $type = ''): array
    {
        $sql = 'SELECT id, name, institution_type, ownership FROM institutions WHERE is_active = 1';
        $params = [];
        if ($type !== '') {
            $sql .= ' AND institution_type = ?';
            $params[] = $type;
        }
        $sql .= ' ORDER BY name';

        return Database::all($sql, $params);
    }

    public static function studentsHelped(): int
    {
        if (setting('students_helped_mode', 'auto') === 'manual') {
            return (int) setting('students_helped_manual', '0');
        }
        return (int) Database::value(
            "SELECT COUNT(*) FROM awardees WHERE accepted_at IS NOT NULL AND status IN ('active', 'deferred', 'ended')"
        );
    }

    public static function goalProgress(): array
    {
        $target = 100;
        $helped = self::studentsHelped();

        return [
            'target' => $target,
            'helped' => $helped,
            'percent' => min(100, (int) round(($helped / $target) * 100)),
        ];
    }

    public static function publishedNews(int $limit = 3): array
    {
        return Database::all(
            "SELECT id, title, slug, excerpt, featured_path, featured_alt, published_at
             FROM news_posts WHERE status = 'published' AND is_demo = 0
             ORDER BY published_at DESC LIMIT " . (int) $limit
        );
    }

    public static function upcomingEvents(int $limit = 3): array
    {
        return Database::all(
            "SELECT id, title, slug, excerpt, venue, starts_at, featured_path, featured_alt
             FROM events
             WHERE status = 'published' AND is_demo = 0 AND (starts_at IS NULL OR starts_at >= UTC_TIMESTAMP())
             ORDER BY starts_at IS NULL, starts_at ASC LIMIT " . (int) $limit
        );
    }

    public static function scholars(): array
    {
        return Database::all(
            "SELECT a.public_note, a.expected_graduation, c.title AS cycle_title,
                    ap.institution_name, ap.current_level, ap.surname, ap.other_names
             FROM awardees a
             JOIN applications ap ON ap.id = a.application_id
             JOIN cycles c ON c.id = a.cycle_id
             WHERE a.showcase_consent = 1 AND a.status IN ('active', 'deferred', 'ended')
             ORDER BY a.accepted_at DESC"
        );
    }
}
