<?php

declare(strict_types=1);

namespace BBAF\Core;

final class Paginator
{
    public const SIZES = [10, 25, 50, 100];

    public static function perPage(Request $request): int
    {
        $query = $request->query('per_page');
        if ($query !== null && in_array((int) $query, self::SIZES, true)) {
            $size = (int) $query;
            $userId = Auth::id();
            if ($userId !== null) {
                Database::exec('UPDATE users SET page_size = ? WHERE id = ?', [$size, $userId]);
                Cache::forget('user-' . $userId);
            }

            return $size;
        }
        $user = Auth::user();
        if ($user !== null && in_array((int) $user['page_size'], self::SIZES, true)) {
            return (int) $user['page_size'];
        }

        return 25;
    }

    public static function page(Request $request): int
    {
        return max(1, (int) $request->query('page', '1'));
    }

    public static function meta(int $total, int $page, int $perPage): array
    {
        $pages = max(1, (int) ceil($total / max(1, $perPage)));
        if ($page > $pages) {
            $page = $pages;
        }
        $from = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
        $to = min($total, $page * $perPage);

        return [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $pages,
            'from' => $from,
            'to' => $to,
        ];
    }

    public static function order(Request $request, array $allowed, string $fallback): string
    {
        $key = (string) $request->query('sort', '');
        $direction = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'ASC' : 'DESC';
        if ($key === '' || !isset($allowed[$key])) {
            return $fallback;
        }

        return $allowed[$key] . ' ' . $direction;
    }
}
