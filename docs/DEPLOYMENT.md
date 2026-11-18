# Deployment and cutover

This application is a PHP 8.1+ site for cPanel shared hosting. It does not use Node, Docker, Composer, or WebSockets. Upload the repository as ordinary PHP files.

## 1. Upload

Upload the project above the public web root when the host lets you choose a document root. Point the domain’s document root at the `public/` directory.

If the host cannot change the document root, upload the project into the existing web folder. The repository-root `index.php` and `.htaccess` boot the same application and deny direct access to `app/`, `config/`, `storage/`, `lib/`, `cron/`, `database/`, and `docs/`.

Create these folders if the upload tool omitted them, and make them writable by PHP:

- `storage/cache`
- `storage/logs`
- `storage/sessions`
- `storage/backups`
- `storage/uploads/private`
- `storage/uploads/public`
- `public/assets/img/legacy`
- `public/assets/media`

## 2. Database

In cPanel, create a MySQL or MariaDB database and a user with all privileges on that database. Note the host (often `localhost`), database name, user, and password. The installer can create the database only when the database user is allowed to.

## 3. Installer

Open `https://your-domain/install`. The installer checks PHP 8.1+ and the required extensions (`pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `json`), writes `config/config.php`, creates the tables, seeds the content bank, and creates the super admin. It then writes `storage/install.lock` and refuses to run again.

Leave the demo-data switch off for a real site. The switch adds one labelled demo news post and one labelled draft event. It does not create applicants, amounts, or testimonials.

Set the base URL to the public address, including a subdirectory if the site is not at the domain root. Example: `https://bbafoundation.com` or `https://bbafoundation.com/new`.

Copy the cron key shown at the end of installation into the host’s cron configuration. It is also stored in `config/config.php` and `storage/cron-key.txt`. Do not commit either file.

## 4. Cron

Run these with the PHP binary cPanel provides. Replace the path and key.

```
*/5 * * * * php /home/USER/bbafoundation/cron/mail.php
0 * * * * php /home/USER/bbafoundation/cron/reminders.php
15 * * * * php /home/USER/bbafoundation/cron/close.php
30 2 * * * php /home/USER/bbafoundation/cron/backup.php
0 3 * * * php /home/USER/bbafoundation/cron/sitemap.php
0 4 * * 1 php /home/USER/bbafoundation/cron/report.php
0 5 * * * php /home/USER/bbafoundation/cron/retention.php
```

If the host only allows URL cron, call `https://your-domain/cron/mail?key=THE_KEY` (and the other job names) over HTTPS. The key must match `cron_key`. Automatic retention does nothing until Settings → `retention_auto` is `1`.

## 5. SMTP

Email stays in `storage/logs/mail.log` until a super admin sets `smtp_enabled` to `1` and fills the SMTP host, port, user, and password under Settings, then uses “Send a test email”. Use the mailbox that already receives `info@bbafoundation.com`. That mailbox and its DNS are separate from the website files.

## 6. Legacy import

While the previous WordPress site is still online, sign in as super admin and open Admin → Legacy import. Run the download. Do not delete the old site until every row says `imported`, and keep an offline copy of the old files and database even after that. If the server has no cURL and `allow_url_fopen` is off, the page lists each URL and the filename to place in `public/assets/img/legacy/`.

The public site uses monogram and pattern placeholders until those files exist. It does not hot-link the old site.

## 7. First cycle

The installer creates a draft cycle titled “2026 Scholarship Programme” with the published call text and no deadline. In Admin → Cycles, set the open and close dates when the Foundation has decided them, choose Open, and save. Opening a cycle closes any other open cycle. Leave the deadline empty until it is real. Award amounts are never filled in by the software.

## 8. Redirects

The installer seeds:

- `/privacy-policy-2` → `/privacy-policy` (301)
- `/wp-login.php` and `/wp-admin` → `/login` (301)
- `/xmlrpc.php` and `/wp-json` → 410
- each previous `/wp-content/uploads/...` file → a placeholder until import, then the local file (301)

Trailing slashes are normalised by the front controller. `/#apply` is handled in the browser and sends visitors to `/apply`. Add any further paths under Admin → Redirects.

## 9. Go-live checklist

1. Test on a subdomain or subdirectory first.
2. Run Legacy Import and open every public page, a trustee profile, the sitemap, and a sample redirect.
3. Back up the old WordPress files and database offline.
4. Switch the document root or base URL.
5. Send a real SMTP test to an external inbox.
6. Create or open the first cycle only when the dates are known.
7. Confirm `storage/` is not reachable in a browser.
8. Delete the old site only after the import list is complete and the offline backup is stored somewhere else.

## Shared-hosting compromises

- Sessions are files in `storage/sessions`, not a shared cache.
- There is no ClamAV scan. Uploads are checked for MIME type, size, and a few malware patterns, and images are re-encoded to strip EXIF.
- PDF text uses the bundled FPDF library, which is limited to Windows-1252 characters. Other characters are transliterated.
- The contact map calls OpenStreetMap Nominatim once and stores the result. If that call fails, the page shows a Google Maps link and no invented coordinates.
- Cron is a PHP script or a secret URL, not a resident worker. The mail queue pauses one second between messages.
- Until SMTP is enabled, messages are appended to `storage/logs/mail.log`.
- ZIP bundles use PHP’s ZipArchive when it exists, and a stored (uncompressed) ZIP otherwise.
- Brand colour extraction uses GD sampling, not a design tool.
- HTML pages are gzipped in PHP. Static gzip uses Apache `mod_deflate` when the host enables it. Brotli is not configured here because shared hosts rarely allow it.
- CSS and JavaScript are single files with a cache-busting query string. They are not run through a Node bundler.
- The award-letter QR code is drawn in PHP. There is no card payment. Donate opens the Selar link.
