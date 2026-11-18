# Testing checklist

Run these after installation, before the domain is switched. Use a draft or open cycle with a deadline you control. Do not invent public news, events, or applicant stories while testing. Remove any installer demo rows before go-live.

## Accounts and permissions

- Register an applicant. The verification message is in `storage/logs/mail.log` until SMTP works. Unverified login is refused.
- An applicant opening `/admin` receives “Not available”.
- Applicant A cannot open applicant B’s document URL (`/application/document/{id}`).
- A reviewer who is not assigned gets a not-found page for that application, including by guessing the id.
- After a conflict is declared, that reviewer loses the application and it disappears from their list.
- A content editor can edit a page and cannot open Settings, Users, or Applications.
- A foundation admin can decide an application and cannot create a super admin.
- A super admin can. Signing out, waiting past the idle timeout, and using a remembered browser are all covered by the session cookie.
- CSRF: submit any form with the token removed. The request is rejected.

## Application

- Save each step, refresh, and confirm the draft is still there. Leave the personal-statement tab idle and confirm the autosave note.
- Upload a PDF and a JPEG. Confirm a PHP file and an oversized file are refused. Confirm a photograph no longer contains GPS data (re-download it and inspect the file).
- Choose the passport slot and confirm the browser offers a square crop. Other slots must not be cropped.
- Submit with a missing document, a statement over 400 words, a mismatched typed name, and a second account using the same NIN. Each case is refused. A successful submit returns an application number `BBAF-{year}-{sequence}` and an acknowledgement PDF.
- After the deadline, a new submit is refused. Reopen one application and confirm only that one can be edited.
- Two referee links: open each once, submit, and confirm the second visit fails.

## Review and awards

- Auto-assign two reviewers and enter scores. The combined score appears for an admin and not for the applicant until “Release scores”.
- Award, waitlist, and not-selected each require a rationale. Awarding past the cycle’s award count is refused.
- The awardee accepts, downloads a letter, and the QR code opens `/verify/{code}` without showing documents. Decline is available before acceptance.
- Record a disbursement with the amount blank, then one with a real test amount you will delete. The blank one displays “Not entered”. Export CSV.
- Submit a renewal and approve, defer, and end it on separate test rows.

## Retention, mail, redirects

- Retention dry-run lists only non-awarded applications on a closed cycle older than the retention window. Execute on a test row and confirm the document file is gone and the name fields are blank.
- Request a data export and open the emailed link. Request erasure for an account with an award and confirm it is held for review.
- Deadline reminders: set a close time inside each band (14 days, 7, 3, 1, and 12 hours) and run `cron/reminders.php` once per band. The same band does not send twice.
- With SMTP off, `cron/mail.php` appends to `storage/logs/mail.log`. With SMTP on, a test message arrives.
- `/privacy-policy-2` returns 301 to `/privacy-policy`. `/wp-login.php` returns 301 to `/login`. `/xmlrpc.php` returns 410. A `/wp-content/uploads/...` path from the manifest redirects.
- `/install` after installation shows the locked page.

## Mobile

- At about 390px wide, open the home page, a trustee profile, the application wizard, and the admin application list. The navigation opens, the tables scroll inside their frame, and the primary buttons remain tappable. Confirm the cookie notice, theme switch, and FAQ search.
