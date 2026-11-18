<?php

declare(strict_types=1);

namespace BBAF\Cron;

use BBAF\Core\Audit;
use BBAF\Core\Database;
use BBAF\Core\Mailer;
use BBAF\Core\Settings;
use BBAF\Services\Cycles;
use BBAF\Services\Notify;
use BBAF\Services\Reports;
use BBAF\Services\Retention;
use DateTimeImmutable;
use DateTimeZone;

final class Kernel
{
    public static function run(string $job): string
    {
        return match ($job) {
            'mail' => self::mail(),
            'reminders' => self::reminders(),
            'backup' => self::backup(),
            'retention' => self::retention(),
            'sitemap' => self::sitemap(),
            'report' => self::report(),
            'close' => self::close(),
            default => 'Unknown job',
        };
    }

    private static function mail(): string
    {
        $result = Mailer::processBatch(25);

        return 'Mail queue processed. Sent ' . (int) ($result['sent'] ?? 0) . ', failed ' . (int) ($result['failed'] ?? 0) . '.';
    }

    private static function reminders(): string
    {
        $sent = 0;
        $sent += self::deadlineReminders();
        $sent += self::draftReminders();
        $sent += self::documentReminders();
        $sent += self::acceptanceReminders();
        $sent += self::renewalReminders();

        return 'Reminders sent: ' . $sent . '.';
    }

    private static function deadlineReminders(): int
    {
        $cycles = Database::all("SELECT * FROM cycles WHERE status = 'open' AND closes_at IS NOT NULL");
        $bands = [
            ['14d', 7 * 86400, 14 * 86400],
            ['7d', 3 * 86400, 7 * 86400],
            ['3d', 86400, 3 * 86400],
            ['1d', 12 * 3600, 86400],
            ['12h', 0, 12 * 3600],
        ];
        $sent = 0;
        foreach ($cycles as $cycle) {
            $left = strtotime((string) $cycle['closes_at'] . ' UTC') - time();
            $band = '';
            foreach ($bands as [$key, $min, $max]) {
                if ($left <= $max && $left > $min) {
                    $band = $key;
                    break;
                }
            }
            if ($band === '') {
                continue;
            }
            $apps = Database::all(
                "SELECT a.id, a.user_id, a.reference FROM applications a WHERE a.cycle_id = ? AND a.status IN ('draft', 'reopened')",
                [(int) $cycle['id']]
            );
            foreach ($apps as $app) {
                $marker = 'deadline:' . $cycle['id'] . ':' . $band . ':' . $app['id'];
                if (self::already($marker)) {
                    continue;
                }
                Notify::user((int) $app['user_id'], 'deadline_reminder', [
                    'cycle' => (string) $cycle['title'],
                    'deadline' => display_date((string) $cycle['closes_at'], true),
                    'url' => url('dashboard'),
                    'note' => 'Deadline reminder (' . $band . ').',
                ], '/dashboard');
                self::mark($marker);
                $sent++;
            }
        }

        return $sent;
    }

    private static function draftReminders(): int
    {
        $apps = Database::all(
            "SELECT a.id, a.user_id, c.title FROM applications a JOIN cycles c ON c.id = a.cycle_id
             WHERE a.status = 'draft' AND c.status = 'open'"
        );
        $sent = 0;
        foreach ($apps as $app) {
            $marker = 'draft:' . $app['id'];
            if (self::already($marker, 3)) {
                continue;
            }
            Notify::user((int) $app['user_id'], 'draft_reminder', [
                'cycle' => (string) $app['title'],
                'url' => url('application'),
                'note' => 'Your application is still a draft.',
            ], '/application');
            self::mark($marker);
            $sent++;
        }

        return $sent;
    }

    private static function documentReminders(): int
    {
        $rows = Database::all(
            "SELECT a.id, a.user_id, a.reference, d.doc_key FROM application_documents d
             JOIN applications a ON a.id = d.application_id
             WHERE d.status IN ('rejected', 'resubmit') AND a.status = 'reopened'"
        );
        $sent = 0;
        foreach ($rows as $row) {
            $marker = 'document:' . $row['id'] . ':' . $row['doc_key'];
            if (self::already($marker, 1)) {
                continue;
            }
            Notify::user((int) $row['user_id'], 'document_issue', [
                'reference' => (string) $row['reference'],
                'document' => (string) $row['doc_key'],
                'note' => 'This document still needs attention.',
                'url' => url('application/step/3'),
            ], '/application/step/3');
            self::mark($marker);
            $sent++;
        }

        return $sent;
    }

    private static function acceptanceReminders(): int
    {
        $rows = Database::all(
            "SELECT w.id, w.user_id, w.accept_deadline, c.title, a.reference
             FROM awardees w JOIN cycles c ON c.id = w.cycle_id JOIN applications a ON a.id = w.application_id
             WHERE w.status = 'pending_acceptance' AND w.accept_deadline IS NOT NULL
               AND w.accept_deadline > UTC_TIMESTAMP() AND w.accept_deadline < DATE_ADD(UTC_TIMESTAMP(), INTERVAL 3 DAY)"
        );
        $sent = 0;
        foreach ($rows as $row) {
            $marker = 'accept:' . $row['id'];
            if (self::already($marker)) {
                continue;
            }
            Notify::user((int) $row['user_id'], 'acceptance_reminder', [
                'cycle' => (string) $row['title'],
                'deadline' => display_date((string) $row['accept_deadline'], true),
                'url' => url('awardee'),
                'note' => 'Please respond to the offer.',
            ], '/awardee');
            self::mark($marker);
            $sent++;
        }

        return $sent;
    }

    private static function renewalReminders(): int
    {
        $year = (new DateTimeImmutable('now', new DateTimeZone('Africa/Lagos')))->format('Y');
        $rows = Database::all(
            "SELECT w.id, w.user_id FROM awardees w
             WHERE w.status = 'active'
               AND w.id NOT IN (SELECT awardee_id FROM renewals WHERE academic_year LIKE ?)",
            [$year . '%']
        );
        $sent = 0;
        foreach ($rows as $row) {
            $marker = 'renewal:' . $row['id'] . ':' . $year;
            if (self::already($marker)) {
                continue;
            }
            Notify::user((int) $row['user_id'], 'renewal_reminder', [
                'url' => url('awardee'),
                'note' => 'Please submit this year\'s renewal.',
            ], '/awardee');
            self::mark($marker);
            $sent++;
        }

        return $sent;
    }

    public static function backup(): string
    {
        $dir = BBAF_ROOT . '/storage/backups';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $stamp = gmdate('Ymd-His');
        $sqlPath = $dir . '/db-' . $stamp . '.sql';
        $handle = fopen($sqlPath, 'wb');
        if ($handle === false) {
            self::record('database', '', 'failed', 'Could not write the backup file.');
            return 'Backup failed.';
        }
        fwrite($handle, "-- BBAFoundation backup " . gmdate('c') . "\nSET NAMES utf8mb4;\n");
        $pdo = Database::pdo();
        $tables = $pdo->query('SHOW TABLES');
        $sensitive = ['smtp_pass'];
        foreach ($tables ?: [] as $tableRow) {
            $table = (string) array_values($tableRow)[0];
            if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                continue;
            }
            $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch(\PDO::FETCH_ASSOC);
            $statement = (string) ($create['Create Table'] ?? '');
            fwrite($handle, "\nDROP TABLE IF EXISTS `{$table}`;\n{$statement};\n");
            $rows = $pdo->query('SELECT * FROM `' . $table . '`');
            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                if ($table === 'settings' && in_array((string) ($row['skey'] ?? ''), $sensitive, true)) {
                    $row['svalue'] = '';
                }
                $columns = array_map(static fn (string $column): string => '`' . $column . '`', array_keys($row));
                $values = [];
                foreach ($row as $value) {
                    $values[] = $value === null ? 'NULL' : $pdo->quote((string) $value);
                }
                fwrite($handle, 'INSERT INTO `' . $table . '` (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ");\n");
            }
        }
        fclose($handle);
        $index = $dir . '/uploads-' . $stamp . '.txt';
        $lines = ["Private upload index " . gmdate('c'), 'Filenames and sizes only.'];
        foreach (glob(BBAF_ROOT . '/storage/uploads/private/*') ?: [] as $file) {
            if (is_file($file)) {
                $lines[] = basename($file) . "\t" . filesize($file);
            }
        }
        file_put_contents($index, implode("\n", $lines));
        self::record('database', $sqlPath, 'ok', 'SQL backup and upload index written.');
        $keep = max(1, (int) setting('backup_keep', '7'));
        $files = glob($dir . '/db-*.sql') ?: [];
        rsort($files);
        foreach (array_slice($files, $keep) as $old) {
            @unlink($old);
            @unlink(str_replace('/db-', '/uploads-', preg_replace('/\.sql$/', '.txt', $old) ?: $old));
        }

        return 'Backup written to storage/backups.';
    }

    private static function retention(): string
    {
        if (setting('retention_auto', '0') !== '1') {
            return 'Automatic retention is off. An administrator can run a dry-run from the admin page.';
        }
        $ids = array_map(static fn (array $row): int => (int) $row['id'], Retention::candidates());
        $result = Retention::purge($ids, 0, false);

        return 'Retention removed personal data from ' . (int) $result['count'] . ' applications.';
    }

    private static function sitemap(): string
    {
        content_cache_clear();
        file_put_contents(BBAF_ROOT . '/storage/cache/sitemap-refreshed.txt', gmdate('c'));

        return 'Sitemap cache cleared. /sitemap.xml is generated on request.';
    }

    private static function report(): string
    {
        $summary = Reports::summary(null);
        $lines = ['Applications by status:'];
        foreach ($summary['byStatus'] as $row) {
            $lines[] = $row['status'] . ': ' . $row['total'];
        }
        $lines[] = 'Awards: ' . $summary['awards'];
        $text = implode("\n", $lines);
        $admins = Database::all("SELECT u.email, u.surname, u.first_name FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'super_admin' AND u.status = 'active'");
        foreach ($admins as $admin) {
            Mailer::sendNow((string) $admin['email'], trim($admin['surname'] . ' ' . $admin['first_name']), 'BBAFoundation scheduled report', '<pre>' . e($text) . '</pre>', $text);
        }

        return 'Report sent to ' . count($admins) . ' super admin account(s).';
    }

    private static function close(): string
    {
        $closed = Cycles::closeExpired();

        return 'Closed ' . $closed . ' cycle(s) whose deadline had passed.';
    }

    private static function already(string $marker, int $withinDays = 0): bool
    {
        $sql = 'SELECT id FROM audit_logs WHERE action = ? AND summary = ?';
        $params = ['reminder_sent', $marker];
        if ($withinDays > 0) {
            $sql .= ' AND created_at > DATE_SUB(UTC_TIMESTAMP(), INTERVAL ' . $withinDays . ' DAY)';
        }

        return Database::one($sql, $params) !== null;
    }

    private static function mark(string $marker): void
    {
        Audit::log(null, 'reminder_sent', $marker);
    }

    private static function record(string $kind, string $path, string $status, string $note): void
    {
        Database::insert(
            'INSERT INTO backup_runs (kind, file_path, status, note, created_at) VALUES (?, ?, ?, ?, UTC_TIMESTAMP())',
            [$kind, $path, $status, $note]
        );
        Settings::set('last_backup_at', gmdate('c'));
    }
}
