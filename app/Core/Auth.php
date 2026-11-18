<?php

declare(strict_types=1);

namespace BBAF\Core;

final class Auth
{
    private static ?array $user = null;

    public static function id(): ?int
    {
        $id = $_SESSION['user_id'] ?? null;

        return is_int($id) || (is_string($id) && ctype_digit($id)) ? (int) $id : null;
    }

    public static function user(): ?array
    {
        $id = self::id();
        if ($id === null) {
            return null;
        }
        if (self::$user !== null && (int) self::$user['id'] === $id) {
            return self::$user;
        }
        self::$user = Database::one(
            'SELECT u.*, r.slug AS role, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?',
            [$id]
        );

        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null && (self::user()['status'] ?? '') === 'active';
    }

    public static function role(): string
    {
        return (string) (self::user()['role'] ?? '');
    }

    public static function attempt(string $email, string $password, bool $remember): array
    {
        $email = mb_strtolower(trim($email));
        $user = Database::one(
            'SELECT u.*, r.slug AS role FROM users u JOIN roles r ON r.id = u.role_id WHERE email = ?',
            [$email]
        );
        $max = (int) Settings::get('lockout_attempts', '5');
        $lockMinutes = (int) Settings::get('lockout_minutes', '15');
        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            if ($user !== null) {
                $failed = (int) $user['failed_logins'] + 1;
                $locked = null;
                if ($failed >= $max) {
                    $locked = gmdate('Y-m-d H:i:s', time() + ($lockMinutes * 60));
                    $failed = 0;
                }
                Database::exec(
                    'UPDATE users SET failed_logins = ?, locked_until = ? WHERE id = ?',
                    [$failed, $locked, (int) $user['id']]
                );
            }
            Audit::log($user ? (int) $user['id'] : null, 'login_failed', 'Failed login for ' . $email);
            return ['ok' => false, 'error' => 'Those details do not match an account.'];
        }
        if (($user['status'] ?? '') !== 'active') {
            return ['ok' => false, 'error' => 'This account is disabled. Contact the Foundation.'];
        }
        if (!empty($user['locked_until']) && strtotime((string) $user['locked_until'] . ' UTC') > time()) {
            Audit::log((int) $user['id'], 'login_locked', 'Login blocked during lockout.');
            return ['ok' => false, 'error' => 'This account is temporarily locked. Try again later.'];
        }
        if (empty($user['email_verified_at']) && !self::isStaff($user['role'])) {
            return ['ok' => false, 'error' => 'Confirm your email address before signing in. Check your inbox for the verification link.'];
        }
        if ((int) $user['totp_enabled'] === 1 && (string) $user['totp_secret'] !== '') {
            $_SESSION['pending_2fa'] = (int) $user['id'];
            $_SESSION['pending_remember'] = $remember;
            return ['ok' => true, 'two_factor' => true];
        }
        self::loginUser($user, $remember);

        return ['ok' => true, 'two_factor' => false];
    }

    public static function confirmTwoFactor(string $code): bool
    {
        $id = (int) ($_SESSION['pending_2fa'] ?? 0);
        if ($id < 1) {
            return false;
        }
        $user = Database::one(
            'SELECT u.*, r.slug AS role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?',
            [$id]
        );
        if ($user === null || !Totp::verify((string) $user['totp_secret'], $code)) {
            Audit::log($id, 'login_failed', 'Two-factor code rejected.');
            return false;
        }
        $remember = (bool) ($_SESSION['pending_remember'] ?? false);
        unset($_SESSION['pending_2fa'], $_SESSION['pending_remember']);
        self::loginUser($user, $remember);

        return true;
    }

    public static function loginUser(array $user, bool $remember): void
    {
        Session::regenerate();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['_last_activity'] = time();
        self::$user = null;
        Database::exec(
            'UPDATE users SET failed_logins = 0, locked_until = NULL, last_login_at = UTC_TIMESTAMP() WHERE id = ?',
            [(int) $user['id']]
        );
        if ($remember) {
            self::remember((int) $user['id']);
        }
        Audit::log((int) $user['id'], 'login', 'Signed in.');
    }

    public static function logout(): void
    {
        $id = self::id();
        if ($id !== null) {
            Database::exec('UPDATE users SET remember_hash = NULL WHERE id = ?', [$id]);
            Audit::log($id, 'logout', 'Signed out.');
        }
        self::forgetRemember();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], true);
        }
        session_destroy();
        self::$user = null;
    }

    public static function restoreRemember(): void
    {
        if (self::id() !== null || empty($_COOKIE['BBAFREM'])) {
            return;
        }
        $parts = explode('|', (string) $_COOKIE['BBAFREM']);
        if (count($parts) !== 3) {
            self::forgetRemember();
            return;
        }
        [$id, $expiry, $sig] = $parts;
        if (!ctype_digit($id) || !ctype_digit($expiry) || (int) $expiry < time()) {
            self::forgetRemember();
            return;
        }
        $payload = $id . '|' . $expiry;
        $expected = hash_hmac('sha256', $payload, Config::key());
        if (!hash_equals($expected, $sig)) {
            self::forgetRemember();
            return;
        }
        $user = Database::one(
            'SELECT u.*, r.slug AS role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? AND status = ?',
            [(int) $id, 'active']
        );
        if ($user === null || !hash_equals((string) $user['remember_hash'], hash('sha256', $sig))) {
            self::forgetRemember();
            return;
        }
        Session::regenerate();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['_last_activity'] = time();
        Audit::log((int) $user['id'], 'login', 'Signed in with a remember-me cookie.');
    }

    public static function touchIdle(): bool
    {
        if (self::id() === null) {
            return true;
        }
        $idle = max(5, (int) Settings::get('session_idle_minutes', '30')) * 60;
        $last = (int) ($_SESSION['_last_activity'] ?? time());
        if ((time() - $last) > $idle) {
            $id = self::id();
            Database::exec('UPDATE users SET remember_hash = NULL WHERE id = ?', [$id]);
            Audit::log($id, 'logout', 'Signed out after inactivity.');
            self::forgetRemember();
            unset($_SESSION['user_id']);
            self::$user = null;
            flash('warning', 'You were signed out after a period of inactivity.');
            return false;
        }
        $_SESSION['_last_activity'] = time();

        return true;
    }

    public static function isStaff(string $role): bool
    {
        return in_array($role, ['super_admin', 'foundation_admin', 'reviewer', 'content_editor'], true);
    }

    public static function can(array $roles): bool
    {
        $role = self::role();
        if ($role === 'super_admin') {
            return true;
        }
        if ($role === 'awardee' && in_array('applicant', $roles, true)) {
            return true;
        }

        return in_array($role, $roles, true);
    }

    private static function remember(int $userId): void
    {
        $days = max(1, (int) Settings::get('remember_days', '14'));
        $expiry = time() + ($days * 86400);
        $payload = $userId . '|' . $expiry;
        $sig = hash_hmac('sha256', $payload, Config::key());
        Database::exec('UPDATE users SET remember_hash = ? WHERE id = ?', [hash('sha256', $sig), $userId]);
        $path = (string) (parse_url((string) Config::get('base_url', ''), PHP_URL_PATH) ?: '/');
        if ($path === '') {
            $path = '/';
        }
        setcookie('BBAFREM', $payload . '|' . $sig, [
            'expires' => $expiry,
            'path' => $path,
            'secure' => Session::https() || str_starts_with((string) Config::get('base_url', ''), 'https://'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function forgetRemember(): void
    {
        $path = '/';
        if (Config::loaded()) {
            $path = (string) (parse_url((string) Config::get('base_url', ''), PHP_URL_PATH) ?: '/');
            if ($path === '') {
                $path = '/';
            }
        }
        setcookie('BBAFREM', '', [
            'expires' => time() - 3600,
            'path' => $path,
            'secure' => Session::https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
