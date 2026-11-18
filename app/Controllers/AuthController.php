<?php

declare(strict_types=1);

namespace BBAF\Controllers;

use BBAF\Core\Audit;
use BBAF\Core\Auth;
use BBAF\Core\Database;
use BBAF\Core\Dates;
use BBAF\Core\Middleware;
use BBAF\Core\Request;
use BBAF\Core\Security;
use BBAF\Core\Session;
use BBAF\Core\Totp;
use BBAF\Core\View;
use BBAF\Services\Notify;

final class AuthController
{
    public function loginForm(Request $request): void
    {
        View::render('auth/login', ['title' => 'Sign in', 'metaDescription' => 'Sign in to BBAFoundation.'], 'auth');
    }

    public function login(Request $request): void
    {
        if (!Security::rateAllow('login', 10, 900)) {
            flash('error', 'Too many sign-in attempts from this network. Wait and try again.');
            View::redirect('/login');
        }
        $result = Auth::attempt($request->input('email'), $request->inputRaw('password'), $request->has('remember'));
        if (!$result['ok']) {
            remember_input(['email' => $request->input('email')]);
            flash('error', $result['error']);
            View::redirect('/login');
        }
        if (!empty($result['two_factor'])) {
            View::redirect('/two-factor');
        }
        $this->afterLogin();
    }

    public function twoFactorForm(Request $request): void
    {
        View::render('auth/twofactor', ['title' => 'Two-factor code', 'metaDescription' => 'Enter your authentication code.'], 'auth');
    }

    public function twoFactor(Request $request): void
    {
        if (!Auth::confirmTwoFactor($request->input('code'))) {
            flash('error', 'That code was not accepted.');
            View::redirect('/two-factor');
        }
        $this->afterLogin();
    }

    public function registerForm(Request $request): void
    {
        View::render('auth/register', ['title' => 'Create an account', 'metaDescription' => 'Create a free BBAFoundation applicant account.'], 'auth');
    }

    public function register(Request $request): void
    {
        if (!Security::rateAllow('register', 8, 3600)) {
            flash('error', 'Too many accounts were started from this network. Try again later.');
            View::redirect('/register');
        }
        $surname = trim($request->input('surname'));
        $first = trim($request->input('first_name'));
        $email = mb_strtolower($request->input('email'));
        $phone = $request->input('phone');
        $gender = $request->input('gender');
        $password = $request->inputRaw('password');
        $errors = [];
        if ($surname === '' || $first === '') {
            $errors[] = 'Enter your surname and first name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if (!Dates::phoneValid($phone)) {
            $errors[] = 'Enter a Nigerian phone number.';
        }
        if (!in_array($gender, ['male', 'female'], true)) {
            $errors[] = 'Select Male or Female.';
        }
        $problem = Security::passwordProblem($password);
        if ($problem !== null) {
            $errors[] = $problem;
        }
        if ($password !== $request->inputRaw('password_confirmation')) {
            $errors[] = 'The two passwords do not match.';
        }
        if (!$request->has('consent')) {
            $errors[] = 'Consent is required before an account can be created.';
        }
        if (Database::one('SELECT id FROM users WHERE email = ?', [$email])) {
            $errors[] = 'An account with that email already exists.';
        }
        if ($errors !== []) {
            remember_input($_POST);
            flash('error', implode(' ', $errors));
            View::redirect('/register');
        }
        $role = Database::one("SELECT id FROM roles WHERE slug = 'applicant'");
        $token = bin2hex(random_bytes(24));
        $id = Database::insert(
            'INSERT INTO users (role_id, surname, first_name, email, phone, phone_normalized, gender, password_hash, verification_hash, verification_expires, consent_at, consent_version, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 48 HOUR), UTC_TIMESTAMP(), ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
            [
                (int) $role['id'],
                $surname,
                $first,
                $email,
                $phone,
                Dates::phone($phone),
                $gender,
                Security::hashPassword($password),
                hash('sha256', $token),
                setting('privacy_version', '2026-07-27'),
            ]
        );
        Notify::email($email, $surname . ' ' . $first, 'verify_email', [
            'name' => $surname . ' ' . $first,
            'url' => url('verify-email/' . $token),
        ]);
        Audit::log($id, 'register', 'Applicant account created.');
        flash('success', 'Account created. Confirm your email address before signing in. Applying is 100% free.');
        View::redirect('/login');
    }

    public function verify(Request $request): void
    {
        $hash = hash('sha256', $request->param('token'));
        $user = Database::one('SELECT id FROM users WHERE verification_hash = ? AND verification_expires > UTC_TIMESTAMP()', [$hash]);
        if ($user === null) {
            flash('error', 'That confirmation link is invalid or has expired.');
            View::redirect('/login');
        }
        Database::exec('UPDATE users SET email_verified_at = UTC_TIMESTAMP(), verification_hash = NULL, verification_expires = NULL WHERE id = ?', [(int) $user['id']]);
        Audit::log((int) $user['id'], 'email_verified', 'Email address confirmed.');
        flash('success', 'Email confirmed. You can sign in.');
        View::redirect('/login');
    }

    public function forgotForm(Request $request): void
    {
        View::render('auth/forgot', ['title' => 'Reset password', 'metaDescription' => 'Reset your BBAFoundation password.'], 'auth');
    }

    public function forgot(Request $request): void
    {
        if (!Security::rateAllow('reset', 5, 3600)) {
            flash('error', 'Please wait before requesting another reset.');
            View::redirect('/forgot-password');
        }
        $email = mb_strtolower($request->input('email'));
        $user = Database::one('SELECT id, surname, first_name, email FROM users WHERE email = ?', [$email]);
        if ($user) {
            $token = bin2hex(random_bytes(24));
            Database::insert(
                'INSERT INTO password_resets (user_id, token_hash, expires_at, request_ip, created_at) VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR), ?, UTC_TIMESTAMP())',
                [(int) $user['id'], hash('sha256', $token), $request->ip()]
            );
            Notify::email($user['email'], $user['surname'] . ' ' . $user['first_name'], 'password_reset', [
                'name' => $user['surname'] . ' ' . $user['first_name'],
                'url' => url('reset-password/' . $token),
                'ip' => $request->ip(),
            ]);
            Audit::log((int) $user['id'], 'password_reset_requested', 'Password reset requested.');
        }
        flash('success', 'If that email belongs to an account, a reset link is on its way.');
        View::redirect('/login');
    }

    public function resetForm(Request $request): void
    {
        View::render('auth/reset', [
            'title' => 'New password',
            'metaDescription' => 'Choose a new password.',
            'token' => $request->param('token'),
        ], 'auth');
    }

    public function reset(Request $request): void
    {
        $hash = hash('sha256', $request->param('token'));
        $row = Database::one('SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > UTC_TIMESTAMP()', [$hash]);
        $password = $request->inputRaw('password');
        $problem = Security::passwordProblem($password);
        if ($row === null || $problem !== null || $password !== $request->inputRaw('password_confirmation')) {
            flash('error', $row === null ? 'That reset link is no longer valid.' : ($problem ?? 'The two passwords do not match.'));
            View::redirect('/forgot-password');
        }
        Database::exec('UPDATE users SET password_hash = ?, failed_logins = 0, locked_until = NULL, remember_hash = NULL WHERE id = ?', [Security::hashPassword($password), (int) $row['user_id']]);
        Database::exec('UPDATE password_resets SET used_at = UTC_TIMESTAMP() WHERE id = ?', [(int) $row['id']]);
        Audit::log((int) $row['user_id'], 'password_reset', 'Password was reset.');
        flash('success', 'Password updated. Sign in with the new password.');
        View::redirect('/login');
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        Session::start();
        flash('success', 'You have signed out.');
        View::redirect('/');
    }

    public function poll(Request $request): void
    {
        $user = Auth::user();
        if ($user === null) {
            View::json(['count' => 0, 'items' => []]);
            return;
        }
        View::json([
            'count' => Notify::unread((int) $user['id']),
            'items' => Notify::recent((int) $user['id']),
        ]);
    }

    public function readNotifications(Request $request): void
    {
        $user = Auth::user();
        if ($user) {
            Database::exec('UPDATE notifications SET read_at = UTC_TIMESTAMP() WHERE user_id = ? AND read_at IS NULL', [(int) $user['id']]);
        }
        View::redirect($request->input('back', '/dashboard'));
    }

    public function preferences(Request $request): void
    {
        $user = Auth::user();
        Database::exec('UPDATE users SET notify_email = ? WHERE id = ?', [$request->has('notify_email') ? 1 : 0, (int) $user['id']]);
        flash('success', 'Notification preferences saved.');
        View::redirect('/dashboard');
    }

    public function enableTotp(Request $request): void
    {
        $user = Auth::user();
        if ($request->method() === 'POST' && $request->input('code') !== '') {
            $secret = (string) ($_SESSION['totp_pending'] ?? '');
            if ($secret !== '' && Totp::verify($secret, $request->input('code'))) {
                Database::exec('UPDATE users SET totp_secret = ?, totp_enabled = 1 WHERE id = ?', [$secret, (int) $user['id']]);
                unset($_SESSION['totp_pending']);
                Audit::log((int) $user['id'], 'totp_enabled', 'Two-factor authentication enabled.');
                flash('success', 'Two-factor authentication is on.');
                View::redirect('/admin');
            }
            flash('error', 'That code did not match. Try again.');
        }
        $secret = (string) ($_SESSION['totp_pending'] ?? Totp::secret());
        $_SESSION['totp_pending'] = $secret;
        $uri = Totp::provisioningUri($secret, (string) $user['email'], 'BBAFoundation');
        View::render('auth/totp-setup', [
            'title' => 'Two-factor authentication',
            'metaDescription' => 'Turn on two-factor authentication.',
            'secret' => $secret,
            'uri' => $uri,
        ], 'portal');
    }

    private function afterLogin(): void
    {
        $target = (string) ($_SESSION['intended'] ?? '');
        unset($_SESSION['intended']);
        if ($target !== '' && str_starts_with($target, url('/'))) {
            header('Location: ' . $target, true, 302);
            exit;
        }
        View::redirect(Middleware::homeFor(Auth::role()));
    }
}
