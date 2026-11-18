<?php

declare(strict_types=1);

namespace BBAF\Services;

use BBAF\Core\Crypto;
use BBAF\Core\Database;
use BBAF\Core\PdfBuilder;

final class Documents
{
    public static function blankForm(): string
    {
        $pdf = new PdfBuilder('Application form for education scholarship');
        $pdf->p('Form no: ________    Date: ________');
        $pdf->h2('Section A — Personal');
        foreach ([
            'Full name (surname in capitals / other names in capitals)',
            'Sex',
            'Date and place of birth',
            'Local government area',
            "Parents' hometown address",
            'Applicant phone number',
            'Applicant email',
            'Valid means of identification',
            'NIN number',
            'Residential address',
            'Passport picture attached',
        ] as $line) {
            $pdf->field($line, '');
        }
        $pdf->h2('Section B — Academic records');
        foreach ([
            'Name and address of secondary school, dates of attendance, name of principal',
            'Name and address of tertiary institution, department / faculty',
            'Current level',
            'Admission year and course duration',
            'CGPA for the previous year',
            'Attestation from HOD: name, address, signature, date and stamp',
        ] as $line) {
            $pdf->field($line, '');
        }
        $pdf->h2('Section C — Supporting documents');
        $pdf->p('Scanned copy of the following supporting documents should accompany the application form, insert into a zip folder and attach on the website: a) Completed application form b) Admission Letter and Student ID Card c) Academic Transcript d) State of Origin Certificate e) Proof of indigene of Ijero-Ekiti or Ewu-Ekiti f) Two referees\' letters (letter from community leader, clergy, religious head) g) Passport photograph h) Birth Certificate');
        $pdf->h2('Section D — Personal statement');
        $pdf->p('In not more than 400 words, explain why you should be awarded this scholarship and how it will impact your education (to be attached to the application form).');
        $pdf->h2('Declaration');
        $pdf->p(setting('declaration'));
        $pdf->p('Date ________    Signature ________');
        $pdf->p(setting('free_notice'));

        return $pdf->output();
    }

    public static function filledForm(array $application): string
    {
        $pdf = new PdfBuilder('Application form for education scholarship');
        $pdf->p('Form no: ' . ($application['reference'] ?: 'Draft') . '    Date: ' . display_date((string) ($application['submitted_at'] ?: $application['updated_at'])));
        $pdf->h2('Section A — Personal');
        $pdf->field('Surname', (string) $application['surname']);
        $pdf->field('Other names', (string) $application['other_names']);
        $pdf->field('Sex', (string) $application['sex']);
        $pdf->field('Date of birth', display_date((string) $application['date_of_birth']));
        $pdf->field('Place of birth', (string) $application['place_of_birth']);
        $lga = $application['lga_id'] ? Database::one('SELECT name FROM lgas WHERE id = ?', [(int) $application['lga_id']]) : null;
        $pdf->field('Local government area', (string) ($lga['name'] ?? ''));
        $pdf->field('Community', (string) $application['community']);
        $pdf->field("Parents' hometown", (string) $application['parents_hometown']);
        $pdf->field('Phone', (string) $application['phone']);
        $pdf->field('Email', (string) $application['email']);
        $pdf->field('Means of identification', (string) $application['id_type']);
        $nin = $application['nin_cipher'] ? Crypto::maskNin(Crypto::decrypt((string) $application['nin_cipher'])) : '';
        $pdf->field('NIN', $nin);
        $pdf->field('Residential address', (string) $application['residential_address']);
        $pdf->h2('Section B — Academic records');
        $pdf->field('Secondary school', (string) $application['secondary_school_name']);
        $pdf->field('School address', (string) $application['secondary_school_address']);
        $pdf->field('Dates of attendance', (string) $application['secondary_dates']);
        $pdf->field('Principal', (string) $application['principal_name']);
        $pdf->field('Tertiary institution', (string) $application['institution_name']);
        $pdf->field('Institution address', (string) $application['institution_address']);
        $pdf->field('Department / faculty', trim($application['department'] . ' / ' . $application['faculty']));
        $pdf->field('Type and ownership', trim($application['institution_type'] . ' / ' . $application['ownership']));
        $pdf->field('Current level', (string) $application['current_level']);
        $pdf->field('Admission year', (string) $application['admission_year']);
        $pdf->field('Course duration', (string) $application['course_duration']);
        $pdf->field('CGPA', trim($application['cgpa'] . ' / ' . $application['cgpa_scale']));
        $pdf->field('Polytechnic grade', (string) $application['poly_grade']);
        $pdf->h2('Section D — Personal statement');
        $pdf->p((string) $application['personal_statement']);
        $pdf->h2('Declaration');
        $pdf->p(setting('declaration'));
        $pdf->field('Typed name', (string) $application['signature_name']);
        $pdf->field('Date', display_date((string) $application['declared_at'], true));

        return $pdf->output();
    }

    public static function acknowledgement(array $application): string
    {
        $pdf = new PdfBuilder('Acknowledgement slip');
        $pdf->p('This slip confirms that the application below was received. It is not an award.');
        $pdf->field('Application number', (string) $application['reference']);
        $pdf->field('Applicant', trim($application['surname'] . ' ' . $application['other_names']));
        $pdf->field('Received', display_date((string) $application['submitted_at'], true));
        $pdf->p(setting('free_notice'));
        $binary = $pdf->output();
        $name = 'slip-' . (int) $application['id'] . '.pdf';
        file_put_contents(BBAF_ROOT . '/storage/uploads/private/' . $name, $binary);

        return $binary;
    }

    public static function awardLetter(array $awardee, array $application, array $cycle): string
    {
        $pdf = new PdfBuilder('Scholarship award letter');
        $pdf->p('This letter confirms a scholarship award. It does not state an amount. Amounts are recorded only when the secretariat enters a disbursement.');
        $pdf->field('Verification code', (string) $awardee['verification_code']);
        $pdf->field('Programme', (string) $cycle['title']);
        $pdf->field('Awardee', trim($application['surname'] . ' ' . $application['other_names']));
        $pdf->field('Institution', (string) $application['institution_name']);
        $pdf->field('Decision date', display_date((string) $awardee['decided_at'], true));
        $pdf->p('Anyone can confirm this code at the address encoded below. The public page does not show private documents.');
        $pdf->qr(url('verify/' . $awardee['verification_code']));
        $pdf->p('Decisions of the Scholarship Board are final.');

        return $pdf->output();
    }

    public static function sendDownload(string $binary, string $filename): void
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Content-Length: ' . strlen($binary));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        echo $binary;
    }
}
