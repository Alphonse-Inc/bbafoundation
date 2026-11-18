<?php

declare(strict_types=1);

namespace BBAF\Install;

use BBAF\Core\Security;

final class Installer
{
    public static function requirements(): array
    {
        $checks = [];
        $checks[] = self::check(
            'PHP 8.1 or newer',
            version_compare(PHP_VERSION, '8.1.0', '>='),
            PHP_VERSION . (version_compare(PHP_VERSION, '8.0.0', '>=') && version_compare(PHP_VERSION, '8.1.0', '<') ? ' (8.0 will run, with fewer password-hash options)' : ''),
            version_compare(PHP_VERSION, '8.0.0', '<')
        );
        foreach (['pdo', 'pdo_mysql', 'mbstring', 'fileinfo', 'openssl', 'json'] as $extension) {
            $checks[] = self::check($extension, extension_loaded($extension), extension_loaded($extension) ? 'loaded' : 'missing', true);
        }
        $checks[] = self::check('curl', extension_loaded('curl'), extension_loaded('curl') ? 'loaded' : 'missing — Legacy Import will show a manual file list', false);
        $checks[] = self::check('zip', extension_loaded('zip') || class_exists(\ZipArchive::class), extension_loaded('zip') ? 'loaded' : 'missing — a store-only ZIP writer is used instead', false);
        $images = extension_loaded('gd') || extension_loaded('imagick');
        $checks[] = self::check('gd or imagick', $images, $images ? 'loaded' : 'missing — photos will not be re-encoded', false);
        foreach ([
            'storage',
            'storage/uploads/private',
            'storage/uploads/public',
            'storage/cache',
            'storage/logs',
            'storage/sessions',
            'storage/backups',
            'config',
        ] as $directory) {
            $path = BBAF_ROOT . '/' . $directory;
            $writable = is_dir($path) && is_writable($path);
            $checks[] = self::check($directory . ' is writable', $writable, $writable ? 'writable' : 'not writable', true);
        }
        $upload = (string) ini_get('upload_max_filesize');
        $post = (string) ini_get('post_max_size');
        $checks[] = self::check(
            'upload_max_filesize and post_max_size',
            self::bytes($upload) >= 20 * 1024 * 1024 && self::bytes($post) >= 20 * 1024 * 1024,
            $upload . ' / ' . $post . ' — 25M and 32M are recommended in public/.user.ini',
            false
        );

        return $checks;
    }

    public static function blocking(array $checks): array
    {
        $problems = [];
        foreach ($checks as $check) {
            if (!$check['ok'] && $check['blocking']) {
                $problems[] = $check['label'];
            }
        }

        return $problems;
    }

    public static function install(array $input): array
    {
        $problems = self::blocking(self::requirements());
        if ($problems !== []) {
            return ['ok' => false, 'error' => 'The server is missing: ' . implode(', ', $problems)];
        }
        if (is_file(BBAF_ROOT . '/storage/install.lock') && is_file(BBAF_ROOT . '/config/config.php')) {
            return ['ok' => false, 'error' => 'The installer is already locked.'];
        }
        $host = trim((string) ($input['db_host'] ?? 'localhost'));
        $port = (int) ($input['db_port'] ?? 3306);
        $name = trim((string) ($input['db_name'] ?? ''));
        $user = trim((string) ($input['db_user'] ?? ''));
        $pass = (string) ($input['db_pass'] ?? '');
        $base = rtrim(trim((string) ($input['base_url'] ?? '')), '/');
        $surname = trim((string) ($input['admin_surname'] ?? ''));
        $first = trim((string) ($input['admin_first'] ?? ''));
        $email = mb_strtolower(trim((string) ($input['admin_email'] ?? '')));
        $password = (string) ($input['admin_password'] ?? '');
        $demo = !empty($input['demo']);
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            return ['ok' => false, 'error' => 'The database name may contain only letters, numbers and underscores.'];
        }
        if ($host === '' || $user === '' || $base === '' || $surname === '' || $first === '') {
            return ['ok' => false, 'error' => 'Fill in the database, site address and administrator name.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Enter a valid administrator email address.'];
        }
        $passwordProblem = Security::passwordProblem($password);
        if ($passwordProblem !== null) {
            return ['ok' => false, 'error' => $passwordProblem];
        }
        if (!preg_match('#^https?://#i', $base)) {
            return ['ok' => false, 'error' => 'The site address must start with http:// or https://.'];
        }

        try {
            $server = new \PDO(
                sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
                $user,
                $pass,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            $server->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $server->exec('USE `' . $name . '`');
            $exists = $server->query("SHOW TABLES LIKE 'users'")->fetch();
            if ($exists) {
                return ['ok' => false, 'error' => 'This database already has a users table. Use an empty database, or remove those tables before installing again.'];
            }
            self::runSql($server, BBAF_ROOT . '/database/schema.sql');
            require_once BBAF_ROOT . '/database/seed.php';
            bbaf_seed($server, $demo);
            $server->prepare('INSERT INTO users (role_id, surname, first_name, email, password_hash, email_verified_at, status, consent_at, consent_version, created_at, updated_at) VALUES (1, ?, ?, ?, ?, UTC_TIMESTAMP(), ?, UTC_TIMESTAMP(), ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())')
                ->execute([$surname, $first, $email, Security::hashPassword($password), 'active', '2026-07-27']);
        } catch (\Throwable $error) {
            return ['ok' => false, 'error' => 'Installation stopped: ' . $error->getMessage()];
        }

        $key = base64_encode(random_bytes(32));
        $cron = bin2hex(random_bytes(24));
        $config = [
            'installed' => true,
            'base_url' => $base,
            'timezone' => 'Africa/Lagos',
            'debug' => false,
            'db' => [
                'host' => $host,
                'port' => $port,
                'name' => $name,
                'user' => $user,
                'pass' => $pass,
                'charset' => 'utf8mb4',
            ],
            'key' => $key,
            'cron_key' => $cron,
            'session_name' => 'BBAFSESSID',
        ];
        $written = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($config, true) . ";\n";
        if (file_put_contents(BBAF_ROOT . '/config/config.php', $written, LOCK_EX) === false) {
            return ['ok' => false, 'error' => 'The tables were created, but config/config.php could not be written.'];
        }
        @chmod(BBAF_ROOT . '/config/config.php', 0640);
        file_put_contents(BBAF_ROOT . '/storage/install.lock', gmdate('c') . ' ' . $email . PHP_EOL);
        file_put_contents(BBAF_ROOT . '/storage/cron-key.txt', $cron . PHP_EOL);
        @chmod(BBAF_ROOT . '/storage/cron-key.txt', 0640);

        return ['ok' => true, 'cron_key' => $cron, 'base_url' => $base];
    }

    private static function runSql(\PDO $pdo, string $file): void
    {
        $sql = (string) file_get_contents($file);
        $buffer = '';
        $inString = false;
        $quote = '';
        $length = strlen($sql);
        for ($i = 0; $i < $length; $i++) {
            $character = $sql[$i];
            if (!$inString && $character === '-' && ($sql[$i + 1] ?? '') === '-') {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }
            if ($inString) {
                $buffer .= $character;
                if ($character === $quote) {
                    $inString = false;
                }
                continue;
            }
            if ($character === "'" || $character === '"') {
                $inString = true;
                $quote = $character;
                $buffer .= $character;
                continue;
            }
            if ($character === ';') {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $pdo->exec($statement);
                }
                $buffer = '';
                continue;
            }
            $buffer .= $character;
        }
        $tail = trim($buffer);
        if ($tail !== '') {
            $pdo->exec($tail);
        }
    }

    private static function check(string $label, bool $ok, string $detail, bool $blocking): array
    {
        return compact('label', 'ok', 'detail', 'blocking');
    }

    private static function bytes(string $value): int
    {
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1073741824,
            'm' => $number * 1048576,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
