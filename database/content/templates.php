<?php

declare(strict_types=1);

$p = static function (string $html, string $text): array {
    return ['html' => $html, 'text' => $text];
};

$rows = [
    'verify_email' => ['Confirm your BBAFoundation account', $p(
        '<p>Dear {{name}},</p><p>Confirm your email address to finish creating your account. Applying is 100% free. The Foundation will never ask for payment.</p><p><a href="{{url}}">Confirm my email</a></p><p>This link expires in 48 hours. If you did not create an account, you can ignore this message.</p>',
        "Dear {{name}},\n\nConfirm your email: {{url}}\n\nThis link expires in 48 hours.\n\nApplying is 100% free. The Foundation will never ask for payment."
    )],
    'password_reset' => ['Reset your BBAFoundation password', $p(
        '<p>Dear {{name}},</p><p>We received a request to reset your password. The request came from IP {{ip}}.</p><p><a href="{{url}}">Choose a new password</a></p><p>This link expires in one hour. If you did not ask for a reset, you can ignore this message.</p>',
        "Dear {{name}},\n\nReset your password: {{url}}\n\nRequest IP: {{ip}}\nThis link expires in one hour."
    )],
    'draft_reminder' => ['Your scholarship application is still a draft', $p(
        '<p>Dear {{name}},</p><p>Your application for {{cycle}} is saved as a draft. Sign in to finish it before the deadline.</p><p><a href="{{url}}">Continue the application</a></p><p>Applying is 100% free. The Foundation will never ask for payment.</p>',
        "Dear {{name}},\n\nYour application for {{cycle}} is still a draft.\nContinue: {{url}}"
    )],
    'deadline_reminder' => ['Scholarship application deadline reminder', $p(
        '<p>Dear {{name}},</p><p>The deadline for {{cycle}} is {{deadline}}. If you have not submitted, sign in and submit before then.</p><p><a href="{{url}}">Open my application</a></p>',
        "Dear {{name}},\n\nThe deadline for {{cycle}} is {{deadline}}.\n{{url}}"
    )],
    'submission_ack' => ['We have received your scholarship application', $p(
        '<p>Dear {{name}},</p><p>Your application for {{cycle}} has been received. Your application number is <strong>{{reference}}</strong>.</p><p>You can sign in to download your acknowledgement slip and to read messages from the Foundation. The application cannot be edited after submission unless the secretariat reopens it.</p><p>Applying is 100% free. The Foundation will never ask for payment.</p>',
        "Dear {{name}},\n\nApplication number: {{reference}}\nCycle: {{cycle}}\n\nSign in to download your acknowledgement slip."
    )],
    'document_issue' => ['A document on your application needs attention', $p(
        '<p>Dear {{name}},</p><p>A document on application {{reference}} needs attention: {{document}}.</p><p>{{note}}</p><p>Sign in to replace it if the secretariat has reopened the application.</p><p><a href="{{url}}">View my application</a></p>',
        "Dear {{name}},\n\n{{document}} on {{reference}} needs attention.\n{{note}}\n{{url}}"
    )],
    'referee_request' => ['A scholarship applicant named you as a referee', $p(
        '<p>Dear {{name}},</p><p>{{applicant}} has named you as a referee for the BBAFoundation scholarship. You can submit a short recommendation or upload a signed letter. This link can be used once and it expires on {{expires}}.</p><p><a href="{{url}}">Submit a recommendation</a></p>',
        "Dear {{name}},\n\n{{applicant}} named you as a referee.\nSubmit here: {{url}}\nExpires: {{expires}}"
    )],
    'hod_request' => ['Attestation request from a scholarship applicant', $p(
        '<p>Dear {{name}},</p><p>{{applicant}} has named you as Head of Department for an attestation. This link can be used once and it expires on {{expires}}.</p><p><a href="{{url}}">Submit the attestation</a></p>',
        "Dear {{name}},\n\n{{applicant}} requested an attestation.\n{{url}}\nExpires: {{expires}}"
    )],
    'shortlisted' => ['Your scholarship application has been shortlisted', $p(
        '<p>Dear {{name}},</p><p>Your application {{reference}} for {{cycle}} has been shortlisted. The Foundation will contact you on the email address and phone number in your application if anything further is required.</p>',
        "Dear {{name}},\n\nApplication {{reference}} for {{cycle}} has been shortlisted."
    )],
    'interview_invite' => ['Scholarship interview invitation', $p(
        '<p>Dear {{name}},</p><p>You are invited to an interview for application {{reference}}.</p><p>When: {{when}}<br>Where: {{where}}</p><p>{{note}}</p><p>A calendar file is attached when email delivery includes it.</p>',
        "Dear {{name}},\n\nInterview for {{reference}}\nWhen: {{when}}\nWhere: {{where}}\n{{note}}"
    )],
    'awarded' => ['Scholarship award offer', $p(
        '<p>Dear {{name}},</p><p>The Foundation has offered you a scholarship under {{cycle}}. Your application number is {{reference}}.</p><p>Please sign in and accept or decline the offer by {{deadline}}. If you accept, you can download your award letter from your account.</p><p><a href="{{url}}">Respond to the offer</a></p>',
        "Dear {{name}},\n\nYou have a scholarship offer for {{cycle}} ({{reference}}).\nRespond by {{deadline}}: {{url}}"
    )],
    'waitlisted' => ['Your scholarship application has been waitlisted', $p(
        '<p>Dear {{name}},</p><p>Your application {{reference}} for {{cycle}} has been placed on the waiting list. This is not an award. The Foundation will contact you if a place becomes available.</p>',
        "Dear {{name}},\n\nApplication {{reference}} for {{cycle}} is on the waiting list."
    )],
    'not_selected' => ['Outcome of your scholarship application', $p(
        '<p>Dear {{name}},</p><p>Thank you for applying to {{cycle}}. Application {{reference}} was not selected.</p><p>Decisions of the Scholarship Board are final. The Foundation is not obliged to give individual feedback.</p>',
        "Dear {{name}},\n\nApplication {{reference}} for {{cycle}} was not selected. Decisions are final."
    )],
    'acceptance_reminder' => ['Please respond to your scholarship offer', $p(
        '<p>Dear {{name}},</p><p>Your scholarship offer for {{cycle}} is still waiting for a response. Please accept or decline by {{deadline}}.</p><p><a href="{{url}}">Respond to the offer</a></p>',
        "Dear {{name}},\n\nPlease respond to your offer by {{deadline}}: {{url}}"
    )],
    'renewal_reminder' => ['Annual scholarship renewal', $p(
        '<p>Dear {{name}},</p><p>Please submit your updated results and proof of enrolment so the Foundation can consider continuing your scholarship.</p><p><a href="{{url}}">Submit a renewal</a></p>',
        "Dear {{name}},\n\nSubmit your renewal: {{url}}"
    )],
    'renewal_decision' => ['Decision on your scholarship renewal', $p(
        '<p>Dear {{name}},</p><p>The decision on your renewal is: {{decision}}.</p><p>{{note}}</p>',
        "Dear {{name}},\n\nRenewal decision: {{decision}}\n{{note}}"
    )],
    'contact_autoreply' => ['We received your message', $p(
        '<p>Dear {{name}},</p><p>Thank you for writing to BBAFoundation. We have received your message about “{{subject}}” and the secretariat will reply if a response is needed.</p>',
        "Dear {{name}},\n\nWe received your message about {{subject}}."
    )],
    'contact_admin' => ['New message from the website', $p(
        '<p>A new contact-form message has arrived.</p><p>From: {{name}} ({{email}}, {{phone}})<br>Subject: {{subject}}</p><p>{{message}}</p>',
        "From: {{name}} {{email}} {{phone}}\nSubject: {{subject}}\n\n{{message}}"
    )],
    'new_application_admin' => ['A scholarship application was submitted', $p(
        '<p>Application {{reference}} was submitted for {{cycle}} by {{name}}.</p><p><a href="{{url}}">Open the application</a></p>',
        "Application {{reference}} was submitted for {{cycle}} by {{name}}.\n{{url}}"
    )],
    'newsletter_confirm' => ['Confirm your BBAFoundation newsletter subscription', $p(
        '<p>Confirm that you want to receive news from BBAFoundation.</p><p><a href="{{url}}">Confirm my subscription</a></p><p>If you did not ask for this, ignore the message.</p>',
        "Confirm your subscription: {{url}}"
    )],
    'data_export' => ['Your personal data export is ready', $p(
        '<p>Dear {{name}},</p><p>The export you requested is ready. The download link expires after a short time.</p><p><a href="{{url}}">Download my data export</a></p>',
        "Dear {{name}},\n\nDownload your data export: {{url}}"
    )],
];

$out = [];
foreach ($rows as $key => [$subject, $bodies]) {
    $out[] = [
        'tkey' => $key,
        'name' => ucwords(str_replace('_', ' ', $key)),
        'subject' => $subject,
        'body_html' => $bodies['html'],
        'body_text' => $bodies['text'],
    ];
}

return $out;
