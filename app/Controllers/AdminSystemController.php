<?php

declare(strict_types=1);

namespace BBAF\Controllers;

use BBAF\Core\Audit;
use BBAF\Core\Auth;
use BBAF\Core\Database;
use BBAF\Core\Dates;
use BBAF\Core\Mailer;
use BBAF\Core\Paginator;
use BBAF\Core\Request;
use BBAF\Core\Security;
use BBAF\Core\Settings;
use BBAF\Core\Upload;
use BBAF\Core\View;
use BBAF\Cron\Kernel;
use BBAF\Services\Importer;
use BBAF\Services\Notify;
use BBAF\Services\Retention;

final class AdminSystemController
{
    public function settings(Request $request): void
    {
        $rows = Database::all('SELECT skey, svalue FROM settings ORDER BY skey');
        View::render('admin/settings', ['title' => 'Settings', 'rows' => $rows], 'admin');
    }

    public function saveSettings(Request $request): void
    {
        $posted = $_POST['settings'] ?? [];
        if (!is_array($posted)) {
            flash('error', 'Nothing to save.');
            View::redirect('/admin/settings');
        }
        foreach ($posted as $key => $value) {
            if (!is_string($key) || preg_match('/^[a-z0-9_]+$/', $key) !== 1 || !is_string($value)) {
                continue;
            }
            if ($key === 'smtp_pass' && $value === '') {
                continue;
            }
            Settings::set($key, $value);
        }
        content_cache_clear();
        Audit::log(Auth::id(), 'settings_saved', 'Site settings updated');
        flash('success', 'Settings saved.');
        View::redirect('/admin/settings');
    }

    public function brand(Request $request): void
    {
        View::render('admin/brand', ['title' => 'Brand'], 'admin');
    }

    public function saveBrand(Request $request): void
    {
        foreach (['color_primary', 'color_accent', 'color_ivory', 'color_sand', 'color_text', 'color_muted'] as $key) {
            $value = $request->input($key);
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1) {
                Settings::set($key, $value);
            }
        }
        $logo = $request->file('logo');
        if ($logo !== null) {
            $saved = $this->storeBrand($logo, 'logo_path');
            if ($request->has('extract_color') && $saved !== '') {
                $hex = $this->dominant(BBAF_PUBLIC . '/' . $saved);
                if ($hex !== '') {
                    Settings::set('color_primary', $hex);
                }
            }
        }
        $favicon = $request->file('favicon');
        if ($favicon !== null) {
            $this->storeBrand($favicon, 'favicon_path');
        }
        content_cache_clear();
        flash('success', 'Brand settings saved.');
        View::redirect('/admin/brand');
    }

    public function smtpTest(Request $request): void
    {
        $user = Auth::user();
        Mailer::sendNow((string) $user['email'], trim($user['surname'] . ' ' . $user['first_name']), 'BBAFoundation SMTP test', '<p>This is a test from the secretariat.</p>', 'This is a test from the secretariat.');
        flash('success', 'Test message handed to the mailer. If SMTP is off, it is in storage/logs/mail.log.');
        View::redirect('/admin/settings');
    }

    public function users(Request $request): void
    {
        $page = Paginator::page($request);
        $per = Paginator::perPage($request);
        $total = (int) Database::value('SELECT COUNT(*) FROM users');
        $meta = Paginator::meta($total, $page, $per);
        $rows = Database::all(
            'SELECT u.id, u.surname, u.first_name, u.email, u.status, r.slug AS role FROM users u JOIN roles r ON r.id = u.role_id ORDER BY u.id DESC LIMIT ' . (int) $per . ' OFFSET ' . (int) (($meta['page'] - 1) * $per)
        );
        View::render('admin/users', [
            'title' => 'Users',
            'rows' => $rows,
            'meta' => $meta,
            'roles' => Database::all('SELECT id, slug, name FROM roles ORDER BY id'),
        ], 'admin');
    }

    public function saveUser(Request $request): void
    {
        $role = Database::one('SELECT id FROM roles WHERE slug = ?', [$request->input('role')]);
        if ($role === null) {
            flash('error', 'Choose a role.');
            View::redirect('/admin/users');
        }
        $email = mb_strtolower($request->input('email'));
        $status = in_array($request->input('status'), ['active', 'disabled'], true) ? $request->input('status') : 'active';
        $id = (int) $request->input('id', '0');
        $password = $request->input('password');
        if ($id === 0) {
            $problem = Security::passwordProblem($password);
            if ($problem !== null || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('error', $problem ?? 'Enter a valid email.');
                View::redirect('/admin/users');
            }
            Database::insert(
                'INSERT INTO users (role_id, surname, first_name, email, password_hash, email_verified_at, status, consent_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP(), ?, UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP())',
                [(int) $role['id'], $request->input('surname'), $request->input('first_name'), $email, Security::hashPassword($password), $status]
            );
        } else {
            if ($password !== '') {
                $problem = Security::passwordProblem($password);
                if ($problem !== null) {
                    flash('error', $problem);
                    View::redirect('/admin/users');
                }
                Database::exec('UPDATE users SET password_hash = ? WHERE id = ?', [Security::hashPassword($password), $id]);
            }
            Database::exec(
                'UPDATE users SET role_id = ?, surname = ?, first_name = ?, status = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?',
                [(int) $role['id'], $request->input('surname'), $request->input('first_name'), $status, $id]
            );
        }
        Audit::log(Auth::id(), 'user_saved', $email);
        flash('success', 'User saved.');
        View::redirect('/admin/users');
    }

    public function templates(Request $request): void
    {
        View::render('admin/templates', [
            'title' => 'Email templates',
            'rows' => Database::all('SELECT * FROM email_templates ORDER BY name'),
        ], 'admin');
    }

    public function saveTemplate(Request $request): void
    {
        Database::exec(
            'UPDATE email_templates SET subject = ?, body_html = ?, body_text = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?',
            [$request->input('subject'), $request->inputRaw('body_html'), $request->inputRaw('body_text'), (int) $request->param('id')]
        );
        flash('success', 'Template saved.');
        View::redirect('/admin/templates');
    }

    public function redirects(Request $request): void
    {
        $page = Paginator::page($request);
        $per = Paginator::perPage($request);
        $total = (int) Database::value('SELECT COUNT(*) FROM redirects');
        $meta = Paginator::meta($total, $page, $per);
        $rows = Database::all('SELECT * FROM redirects ORDER BY source_path LIMIT ' . (int) $per . ' OFFSET ' . (int) (($meta['page'] - 1) * $per));
        View::render('admin/redirects', ['title' => 'Redirects', 'rows' => $rows, 'meta' => $meta], 'admin');
    }

    public function saveRedirect(Request $request): void
    {
        $source = '/' . trim($request->input('source_path'), '/');
        $code = (int) $request->input('status_code', '301');
        if (!in_array($code, [301, 302, 410], true)) {
            $code = 301;
        }
        $id = (int) $request->input('id', '0');
        if ($id > 0) {
            Database::exec(
                'UPDATE redirects SET source_path = ?, target_path = ?, status_code = ?, is_active = ?, note = ? WHERE id = ?',
                [$source, $request->input('target_path'), $code, $request->has('is_active') ? 1 : 0, $request->input('note'), $id]
            );
        } else {
            Database::exec(
                'INSERT INTO redirects (source_path, target_path, status_code, is_active, note) VALUES (?, ?, ?, 1, ?) ON DUPLICATE KEY UPDATE target_path = VALUES(target_path), status_code = VALUES(status_code), note = VALUES(note)',
                [$source, $request->input('target_path'), $code, $request->input('note')]
            );
        }
        flash('success', 'Redirect saved.');
        View::redirect('/admin/redirects');
    }

    public function audit(Request $request): void
    {
        $page = Paginator::page($request);
        $per = Paginator::perPage($request);
        $q = trim((string) $request->query('q', ''));
        $where = '1=1';
        $params = [];
        if ($q !== '') {
            $where = '(action LIKE ? OR summary LIKE ?)';
            $params = ['%' . $q . '%', '%' . $q . '%'];
        }
        $total = (int) Database::value("SELECT COUNT(*) FROM audit_logs WHERE $where", $params);
        $meta = Paginator::meta($total, $page, $per);
        $rows = Database::all(
            "SELECT id, user_id, action, summary, created_at FROM audit_logs WHERE $where ORDER BY id DESC LIMIT " . (int) $per . ' OFFSET ' . (int) (($meta['page'] - 1) * $per),
            $params
        );
        View::render('admin/audit', ['title' => 'Audit log', 'rows' => $rows, 'meta' => $meta], 'admin');
    }

    public function backups(Request $request): void
    {
        View::render('admin/backups', [
            'title' => 'Backups',
            'rows' => Database::all('SELECT * FROM backup_runs ORDER BY id DESC LIMIT 20'),
        ], 'admin');
    }

    public function backupNow(Request $request): void
    {
        flash('success', Kernel::backup());
        View::redirect('/admin/backups');
    }

    public function import(Request $request): void
    {
        View::render('admin/import', [
            'title' => 'Legacy import',
            'rows' => Importer::rows(),
            'canDownload' => Importer::canDownload(),
            'pending' => Importer::pendingCount(),
        ], 'admin');
    }

    public function runImport(Request $request): void
    {
        if (!Importer::canDownload()) {
            flash('error', 'This server cannot download remote files. Use the manual list on the import page.');
            View::redirect('/admin/import');
        }
        $results = Importer::run();
        $failed = count(array_filter($results, static fn (array $row): bool => $row['status'] !== 'imported'));
        Audit::log(Auth::id(), 'legacy_import', count($results) . ' files checked, ' . $failed . ' failed');
        flash($failed === 0 ? 'success' : 'warning', $failed === 0 ? 'Every manifest file is imported. The previous site can be removed only after you have also kept an offline backup.' : $failed . ' files still need attention. Do not delete the previous site yet.');
        View::redirect('/admin/import');
    }

    public function retention(Request $request): void
    {
        View::render('admin/retention', [
            'title' => 'Retention',
            'rows' => Retention::candidates(),
        ], 'admin');
    }

    public function runRetention(Request $request): void
    {
        $ids = [];
        foreach ((array) ($_POST['ids'] ?? []) as $id) {
            $ids[] = (int) $id;
        }
        $dry = $request->has('dry_run');
        $result = Retention::purge($ids, (int) Auth::id(), $dry);
        flash('success', ($dry ? 'Dry run: ' : 'Removed personal data from ') . (int) $result['count'] . ' applications.');
        View::redirect('/admin/retention');
    }

    public function requests(Request $request): void
    {
        View::render('admin/requests', [
            'title' => 'Data requests',
            'rows' => Database::all('SELECT d.*, u.email FROM data_requests d JOIN users u ON u.id = d.user_id ORDER BY d.id DESC LIMIT 100'),
        ], 'admin');
    }

    public function handleRequest(Request $request): void
    {
        $row = Database::one('SELECT * FROM data_requests WHERE id = ?', [(int) $request->param('id')]);
        if ($row === null) {
            flash('error', 'Request not found.');
            View::redirect('/admin/requests');
        }
        $note = $request->input('admin_note');
        if ($row['request_type'] === 'export' && $request->input('action') === 'fulfil') {
            $dir = BBAF_ROOT . '/storage/uploads/private/exports';
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $written = basename(Retention::exportUser((int) $row['user_id']));
            $source = BBAF_ROOT . '/storage/uploads/private/' . $written;
            $name = $written;
            if (!is_file($source) || !copy($source, $dir . '/' . $name)) {
                flash('error', 'The export file could not be prepared.');
                View::redirect('/admin/requests');
            }
            $token = Security::sign(['file' => $name], 3 * 86400);
            Database::exec(
                "UPDATE data_requests SET status = 'done', admin_note = ?, handled_by = ?, handled_at = UTC_TIMESTAMP(), export_path = ? WHERE id = ?",
                [$note, Auth::id(), $name, (int) $row['id']]
            );
            Notify::user((int) $row['user_id'], 'data_export', [
                'url' => url('account/export/' . $token),
                'note' => 'Your data export is ready.',
            ], '/dashboard');
            flash('success', 'Export prepared and the person has been notified.');
        } elseif ($row['request_type'] === 'erasure' && $request->input('action') === 'fulfil') {
            $award = Database::one("SELECT id FROM awardees WHERE user_id = ? AND status <> 'declined'", [(int) $row['user_id']]);
            if ($award) {
                Database::exec(
                    "UPDATE data_requests SET status = 'needs_review', admin_note = ?, handled_by = ?, handled_at = UTC_TIMESTAMP() WHERE id = ?",
                    ['An award record exists, so the account was not erased. ' . $note, Auth::id(), (int) $row['id']]
                );
                flash('warning', 'This person has an award record. The account was not erased.');
            } else {
                $this->eraseUser((int) $row['user_id']);
                Database::exec(
                    "UPDATE data_requests SET status = 'done', admin_note = ?, handled_by = ?, handled_at = UTC_TIMESTAMP() WHERE id = ?",
                    [$note, Auth::id(), (int) $row['id']]
                );
                Audit::log(Auth::id(), 'erasure', 'Account anonymised', 'user', (int) $row['user_id']);
                flash('success', 'Account anonymised.');
            }
        } else {
            Database::exec(
                "UPDATE data_requests SET status = 'noted', admin_note = ?, handled_by = ?, handled_at = UTC_TIMESTAMP() WHERE id = ?",
                [$note, Auth::id(), (int) $row['id']]
            );
            flash('success', 'Note saved. Correct the record in the relevant admin screen.');
        }
        View::redirect('/admin/requests');
    }

    public function requestFile(Request $request): void
    {
        $row = Database::one('SELECT export_path FROM data_requests WHERE id = ?', [(int) $request->param('id')]);
        $name = basename((string) ($row['export_path'] ?? ''));
        $path = BBAF_ROOT . '/storage/uploads/private/exports/' . $name;
        if ($name === '' || !is_file($path)) {
            http_response_code(404);
            echo 'Export not found';
            return;
        }
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="data-export.json"');
        header('Cache-Control: private, no-store');
        readfile($path);
    }

    public function breach(Request $request): void
    {
        View::render('admin/breach', [
            'title' => 'Breach procedure',
            'rows' => Database::all('SELECT * FROM breach_incidents ORDER BY id DESC'),
        ], 'admin');
    }

    public function saveBreach(Request $request): void
    {
        Database::insert(
            'INSERT INTO breach_incidents (title, description, discovered_at, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP())',
            [$request->input('title'), $request->inputRaw('description'), Dates::parseLocal($request->input('discovered_at')), 'open', Auth::id()]
        );
        Audit::log(Auth::id(), 'breach_logged', $request->input('title'));
        flash('success', 'Incident logged.');
        View::redirect('/admin/breach');
    }

    public function institutions(Request $request): void
    {
        $page = Paginator::page($request);
        $per = Paginator::perPage($request);
        $q = trim((string) $request->query('q', ''));
        $where = '1=1';
        $params = [];
        if ($q !== '') {
            $where = 'name LIKE ?';
            $params[] = '%' . $q . '%';
        }
        $total = (int) Database::value("SELECT COUNT(*) FROM institutions WHERE $where", $params);
        $meta = Paginator::meta($total, $page, $per);
        $rows = Database::all(
            "SELECT * FROM institutions WHERE $where ORDER BY name LIMIT " . (int) $per . ' OFFSET ' . (int) (($meta['page'] - 1) * $per),
            $params
        );
        View::render('admin/institutions', [
            'title' => 'Institutions and LGAs',
            'rows' => $rows,
            'meta' => $meta,
            'lgas' => Database::all('SELECT * FROM lgas ORDER BY sort_order, name'),
        ], 'admin');
    }

    public function saveInstitution(Request $request): void
    {
        Database::insert(
            'INSERT INTO institutions (name, institution_type, ownership, state_name, is_suggestion, is_active) VALUES (?, ?, ?, ?, 1, 1)',
            [$request->input('name'), $request->input('institution_type', 'university'), $request->input('ownership', 'government'), $request->input('state_name')]
        );
        flash('success', 'Institution added to the editable suggestions.');
        View::redirect('/admin/institutions');
    }

    public function saveLga(Request $request): void
    {
        $name = $request->input('name');
        if ($name === '') {
            flash('error', 'Enter the LGA name.');
            View::redirect('/admin/institutions');
        }
        Database::exec(
            'INSERT INTO lgas (name, state_name, note, sort_order) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE note = VALUES(note)',
            [$name, $request->input('state_name', 'Ekiti'), $request->input('note'), (int) $request->input('sort_order', '0')]
        );
        flash('success', 'LGA saved.');
        View::redirect('/admin/institutions');
    }

    public function installLocked(Request $request): void
    {
        http_response_code(403);
        View::render('admin/install-locked', [
            'title' => 'Already installed',
            'metaDescription' => 'The installer is closed.',
        ], 'bare');
    }

    private function storeBrand(array $file, string $setting): string
    {
        $saved = Upload::storePublicImage($file);
        Settings::set($setting, $saved['public_path']);

        return $saved['public_path'];
    }

    private function dominant(string $path): string
    {
        $binary = @file_get_contents($path);
        if (!is_string($binary) || !function_exists('imagecreatefromstring')) {
            return '';
        }
        $image = @imagecreatefromstring($binary);
        if (!$image) {
            return '';
        }
        $width = imagesx($image);
        $height = imagesy($image);
        $buckets = [];
        $stepX = max(1, (int) ($width / 24));
        $stepY = max(1, (int) ($height / 24));
        for ($y = 0; $y < $height; $y += $stepY) {
            for ($x = 0; $x < $width; $x += $stepX) {
                $rgb = imagecolorat($image, $x, $y);
                $key = sprintf('%02X%02X%02X', ($rgb >> 16) & 0xF0, ($rgb >> 8) & 0xF0, $rgb & 0xF0);
                $buckets[$key] = ($buckets[$key] ?? 0) + 1;
            }
        }
        imagedestroy($image);
        if ($buckets === []) {
            return '';
        }
        arsort($buckets);

        return '#' . (string) array_key_first($buckets);
    }

    private function eraseUser(int $userId): void
    {
        $apps = Database::all('SELECT id FROM applications WHERE user_id = ?', [$userId]);
        foreach ($apps as $app) {
            $docs = Database::all('SELECT stored_name FROM application_documents WHERE application_id = ?', [(int) $app['id']]);
            foreach ($docs as $doc) {
                $path = \BBAF\Services\Applications::privatePath((string) $doc['stored_name']);
                if (is_file($path)) {
                    unlink($path);
                }
            }
            Database::exec('DELETE FROM application_documents WHERE application_id = ?', [(int) $app['id']]);
            Database::exec(
                "UPDATE applications SET surname = '', other_names = '', email = '', phone = '', phone_normalized = '', nin_cipher = NULL, nin_hash = NULL, residential_address = NULL, personal_statement = NULL, retained_at = UTC_TIMESTAMP() WHERE id = ?",
                [(int) $app['id']]
            );
        }
        Database::exec(
            "UPDATE users SET surname = '', first_name = '', other_names = '', phone = '', phone_normalized = '', email = ?, status = 'disabled', password_hash = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?",
            ['deleted-' . $userId . '@invalid.example', Security::hashPassword(bin2hex(random_bytes(16))), $userId]
        );
    }
}
