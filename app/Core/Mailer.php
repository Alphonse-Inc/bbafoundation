<?php

declare(strict_types=1);

namespace BBAF\Core;

final class Mailer
{
    public static function enqueue(string $toEmail, string $toName, string $templateKey, array $vars = [], ?string $scheduledAt = null): void
    {
        $template = Database::one('SELECT * FROM email_templates WHERE tkey = ?', [$templateKey]);
        $subject = $template['subject'] ?? ($vars['subject'] ?? 'BBAFoundation');
        $html = $template['body_html'] ?? ('<p>' . e((string) ($vars['body'] ?? '')) . '</p>');
        $text = $template['body_text'] ?? strip_tags($html);
        $subject = self::fill($subject, $vars);
        $html = self::wrap(self::fill($html, $vars));
        $text = self::fill($text, $vars);
        Database::insert(
            'INSERT INTO email_queue (to_email, to_name, subject, body_html, body_text, template_key, status, scheduled_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
            [
                $toEmail,
                $toName,
                mb_substr($subject, 0, 200),
                $html,
                $text,
                $templateKey,
                'queued',
                $scheduledAt ?? Dates::nowUtc(),
            ]
        );
    }

    public static function sendNow(string $toEmail, string $toName, string $subject, string $html, string $text): void
    {
        self::deliver($toEmail, $toName, $subject, $html, $text);
    }

    public static function processBatch(int $limit = 15): array
    {
        $rows = Database::all(
            "SELECT * FROM email_queue
             WHERE status IN ('queued', 'retry') AND scheduled_at <= UTC_TIMESTAMP()
             ORDER BY id ASC LIMIT " . (int) $limit
        );
        $sent = 0;
        $failed = 0;
        foreach ($rows as $row) {
            try {
                self::deliver($row['to_email'], $row['to_name'], $row['subject'], $row['body_html'], $row['body_text']);
                Database::exec(
                    "UPDATE email_queue SET status = 'sent', sent_at = UTC_TIMESTAMP(), attempts = attempts + 1, last_error = '' WHERE id = ?",
                    [(int) $row['id']]
                );
                $sent++;
            } catch (\Throwable $error) {
                $attempts = (int) $row['attempts'] + 1;
                $status = $attempts >= (int) $row['max_attempts'] ? 'failed' : 'retry';
                $delay = $attempts * 15;
                Database::exec(
                    'UPDATE email_queue SET status = ?, attempts = ?, last_error = ?, scheduled_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? MINUTE) WHERE id = ?',
                    [$status, $attempts, mb_substr($error->getMessage(), 0, 500), $delay, (int) $row['id']]
                );
                $failed++;
            }
            if ($sent + $failed < count($rows)) {
                sleep(1);
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    public static function deliver(string $toEmail, string $toName, string $subject, string $html, string $text): void
    {
        $enabled = Settings::get('smtp_enabled', '0') === '1';
        $host = Settings::get('smtp_host', '');
        if (!$enabled || $host === '') {
            $dir = BBAF_ROOT . '/storage/logs';
            $record = gmdate('c') . " TO {$toEmail} SUBJECT {$subject}\n{$text}\n----\n";
            file_put_contents($dir . '/mail.log', $record, FILE_APPEND);
            return;
        }
        require_once BBAF_ROOT . '/lib/phpmailer/Exception.php';
        require_once BBAF_ROOT . '/lib/phpmailer/SMTP.php';
        require_once BBAF_ROOT . '/lib/phpmailer/PHPMailer.php';
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = (int) Settings::get('smtp_port', '587');
        $user = Settings::get('smtp_user', '');
        $mail->SMTPAuth = $user !== '';
        $mail->Username = $user;
        $mail->Password = Settings::get('smtp_pass', '');
        $encryption = Settings::get('smtp_encryption', 'tls');
        if ($encryption === 'tls') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }
        $from = Settings::get('mail_from_email', 'info@bbafoundation.com');
        $fromName = Settings::get('mail_from_name', 'BBAFoundation');
        $mail->setFrom($from, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = $text;
        $mail->send();
    }

    public static function fill(string $template, array $vars): string
    {
        $replace = [];
        foreach ($vars as $key => $value) {
            $replace['{{' . $key . '}}'] = (string) $value;
        }
        $replace['{{foundation}}'] = Settings::get('org_short', 'BBAFoundation');
        $replace['{{site_url}}'] = url('/');
        $replace['{{year}}'] = Dates::display(gmdate('Y-m-d H:i:s'));

        return strtr($template, $replace);
    }

    public static function wrap(string $inner): string
    {
        $primary = Settings::get('color_primary', '#0F2A4A');
        $accent = Settings::get('color_accent', '#D9A520');
        $name = Settings::get('org_short', 'BBAFoundation');
        $logo = Settings::get('logo_path', '');
        $logoHtml = $logo !== ''
            ? '<img src="' . e(url($logo)) . '" alt="' . e($name) . '" height="48">'
            : '<strong style="color:' . e($accent) . '">' . e($name) . '</strong>';

        return '<!DOCTYPE html><html><body style="margin:0;background:#F6F3EA;font-family:Georgia,serif;color:#1E293B">'
            . '<div style="max-width:640px;margin:0 auto;padding:24px">'
            . '<div style="background:' . e($primary) . ';padding:20px 24px;border-radius:12px 12px 0 0">' . $logoHtml . '</div>'
            . '<div style="background:#fff;padding:24px;border:1px solid #E6E1D6">' . $inner . '</div>'
            . '<p style="font-size:12px;color:#64748B">Applying is 100% free. The Foundation will never ask for payment. '
            . e(Settings::get('email', 'info@bbafoundation.com')) . '</p></div></body></html>';
    }
}
