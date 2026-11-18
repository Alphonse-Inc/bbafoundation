<?php

declare(strict_types=1);

namespace BBAF\Core;

use DateTimeImmutable;
use DateTimeZone;

final class Dates
{
    public static function zone(): DateTimeZone
    {
        $name = Config::loaded() ? (string) Config::get('timezone', 'Africa/Lagos') : 'Africa/Lagos';
        try {
            return new DateTimeZone($name);
        } catch (\Throwable) {
            return new DateTimeZone('Africa/Lagos');
        }
    }

    public static function nowUtc(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }

    public static function display(?string $utc, bool $withTime = false): string
    {
        if ($utc === null || $utc === '' || $utc === '0000-00-00 00:00:00' || $utc === '0000-00-00') {
            return '';
        }
        try {
            $date = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
        } catch (\Throwable) {
            return '';
        }
        $local = $date->setTimezone(self::zone());
        $format = $withTime ? 'd/m/Y H:i' : 'd/m/Y';
        if (strlen($utc) <= 10) {
            return $local->format('d/m/Y');
        }

        return $local->format($format);
    }

    public static function parseLocal(string $value, bool $endOfDay = false): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $formats = ['d/m/Y H:i', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d\TH:i', 'Y-m-d'];
        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value, self::zone());
            if ($date instanceof DateTimeImmutable) {
                if ($format === 'd/m/Y' || $format === 'Y-m-d') {
                    $date = $date->setTime($endOfDay ? 23 : 0, $endOfDay ? 59 : 0, $endOfDay ? 59 : 0);
                }

                return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    public static function inputValue(?string $utc): string
    {
        if ($utc === null || $utc === '') {
            return '';
        }
        try {
            $date = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
        } catch (\Throwable) {
            return '';
        }

        return $date->setTimezone(self::zone())->format('Y-m-d\TH:i');
    }

    public static function phone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '234') && strlen($digits) === 13) {
            return '+' . $digits;
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '+234' . substr($digits, 1);
        }
        if (strlen($digits) === 10) {
            return '+234' . $digits;
        }

        return $digits === '' ? '' : '+' . $digits;
    }

    public static function phoneValid(string $phone): bool
    {
        return (bool) preg_match('/^\+234[1-9]\d{9}$/', self::phone($phone));
    }
}
