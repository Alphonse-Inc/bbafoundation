# Assumptions

These choices were made so the build could finish without waiting for answers. An administrator can change the editable ones in the admin screens.

## Content

- The public vision sentence is the grammatically corrected text (“where at least 100 young people”). The original wording (“where in at least 100 young people”) is stored in `vision_original`, and `vision_needs_review` is `1`.
- The footer label is “Terms & Conditions”. The previous “Terms & Condition” is stored as `footer_terms_label_original`.
- The founder story, announcement banner, cycle deadline, and beneficiary stories are empty. Public templates hide those blocks until an administrator saves text or a date.
- Award amounts are never seeded. A disbursement row can be saved with a blank amount.
- “Students helped” counts awardees who have accepted and whose status is active, deferred, or ended. Set `students_helped_mode` to `manual` to type a number instead. Volunteers and sponsors stay at `0+` until edited.
- No news, events, testimonials, or applicants are invented. The installer demo switch, off by default, adds one post and one draft event whose titles start with “DEMO — remove before go-live”.
- Online additions to the FAQs, terms, and privacy text are flagged for the Foundation to approve. The live privacy page does not include the old comment-system clauses. Those clauses remain in page version history. There is no comments module.
- The FAQ that says eligibility is limited to Ijero-Ekiti and Ewu-Ekiti keeps that answer. A flag note records the tension with any wider “Ekiti residents” wording.
- The legacy ZIP size mentioned in the FAQ (20 MB for the whole upload, with a separate 25 MB ZIP cap in `legacy_zip_mb`) is kept as published. Legacy ZIP uploads are off until a cycle turns them on.
- Affiliate names were not on the previous site. The five slots are labelled “To be labelled by the Foundation”.
- The institution list is a practical set of federal and state universities and polytechnics, plus a short private list, all marked as editable suggestions. It is not a complete directory. Lagos State Polytechnic is stored under that name only.
- Ewu-Ekiti is mapped to Ekiti West LGA and Ijero-Ekiti to Ijero LGA. Both notes are editable. The community field still asks the applicant to choose Ijero-Ekiti, Ewu-Ekiti, or Other.
- The admission letter and student ID are separate upload slots. The old checklist named them together. Two referee letters are separate slots. Means of identification is optional because the form lists it as a type of ID the applicant may hold, while the NIN itself is required on the form. These splits are recorded on the cycle document notes.

## Accounts and selection

- One application per person per cycle.
- Only one cycle may be Open. Saving a cycle as Open closes the others.
- A draft cycle with no deadline shows “Opening soon”. An open cycle with no deadline stays open until an administrator closes it.
- A CGPA or polytechnic grade below the cycle threshold is a flag, not a hard block, unless the cycle’s hard-block option is turned on.
- Reviewers only see applications assigned to them. A conflict removes the assignment and hides the application. Blind review hides the name, contact details, and NIN from reviewers. Score release is an in-app notice. There is no separate score-release email in the content bank.
- An award creates an awardee row when the decision is recorded. The person accepts or declines online. The public scholars page lists only people who have accepted and given showcase consent.
- Requesting a document resubmission reopens the application for seven days.
- Referee and HOD links last 21 days and work once.
- Data retention looks at non-awarded applications on closed, results-announced, or archived cycles older than `retention_months` (12). The cron job does nothing until `retention_auto` is `1`. Erasure is refused automatically when the person still has a non-declined award.

## Technical

- NIN values are encrypted with AES-256-GCM. The key is in `config/config.php`, which the installer writes and git ignores. Comparison uses an HMAC. PDFs show a masked NIN. Audit text strips 11-digit numbers.
- Passwords use Argon2id when PHP allows it, otherwise bcrypt.
- Dates are stored in UTC and shown in Africa/Lagos as DD/MM/YYYY.
- Phone numbers accept `0803…` and `+234803…`.
- The contact page asks Nominatim once for the head-office address and stores the coordinates. It does not invent a pin.
- FPDF cannot embed the full Unicode range. PDF text is transliterated to Windows-1252.
- The QR code on the award letter is generated with the vendored Kazuhiko Arase library and drawn with GD. It points at `/verify/{code}`.
- PHPMailer 6.9.3 and FPDF 1.86 are vendored under `lib/`. No Composer autoload is used.
- When SMTP is off, mail is appended to `storage/logs/mail.log` and the queue is still marked sent, because the message was handed to the local log.
- HTML is cached with a private ETag because each session has its own CSRF token. Static assets use a long cache and a file-time query string.
- The home page inlines a short critical style block and then loads `public/assets/css/app.css`. Colours saved in brand settings override the CSS variables when they are six-digit hex values.
- Geocoding, mail, and imports need outbound HTTPS. The app still renders if those calls fail.
- The super admin created during a local test is not part of the repository. Production creates that account in the installer.
