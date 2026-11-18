<?php

declare(strict_types=1);

return [
    [
        'title' => 'General & Eligibility',
        'items' => [
            [
                'question' => 'Who is eligible to apply for the BBA Foundation Scholarship?',
                'answer' => 'Eligibility guidelines are published during each application cycle. Generally, applicants must be indigenes or residents of Ekiti State currently enrolled in or admitted to an accredited tertiary institution. Please refer to the current application announcement for specific academic requirements.',
                'flag_note' => 'Flag for the Foundation: the Home and About pages restrict eligibility to indigenes of Ijero-Ekiti or Ewu-Ekiti at government-owned institutions. Reconcile this answer before publishing a cycle.',
            ],
            [
                'question' => 'Is there any application fee?',
                'answer' => 'No. Applying for the BBA Foundation Scholarship is 100% free. The Foundation will never ask for payment or administrative fees at any stage of the application or selection process.',
                'flag_note' => '',
            ],
        ],
    ],
    [
        'title' => 'Application & Documents',
        'items' => [
            [
                'question' => 'How do I apply for the scholarship?',
                'answer' => 'To apply: 1. Download the official application form from our website. 2. Print and complete the form accurately. 3. Gather all required supporting documents (passport photo, official ID, academic records, and recommendation letters). 4. Compress all documents into a single ZIP folder (.zip). 5. Upload the ZIP folder through the upload portal on our website before the closing deadline.',
                'flag_note' => 'Original ZIP route. The online application is an addition and does not replace this wording.',
            ],
            [
                'question' => 'What documents must be included in the ZIP folder?',
                'answer' => "Your compressed ZIP folder must contain: Completed and signed Application Form; Recent passport photograph; Valid means of identification (e.g., Student ID, Voter's Card, NIN slip, or State ID); Official academic transcripts or statement of results; Official recommendation/reference letters.",
                'flag_note' => '',
            ],
            [
                'question' => 'How do I create a ZIP folder for upload?',
                'answer' => 'On Windows, select all your scanned PDF/image files and documents, right-click, select Send to, and click Compressed (zipped) folder. On a Mac, select all files, right-click, and select Compress. If you are applying via mobile phone, you can use built-in file manager tools or free archive apps to create a .zip file.',
                'flag_note' => '',
            ],
            [
                'question' => 'What is the maximum file size for the ZIP folder upload?',
                'answer' => 'To ensure smooth processing, please ensure your uploaded ZIP file does not exceed 20 MB. Make sure scanned images and documents are clear but optimized for file size.',
                'flag_note' => 'The previous upload form also mentioned 25 MB. Both limits are stored in settings. The seeded ZIP answer keeps 20 MB.',
            ],
        ],
    ],
    [
        'title' => 'Review & Selection Process',
        'items' => [
            [
                'question' => 'How will I know if my application was successfully received?',
                'answer' => 'Once you complete the upload on our website, you will see an on-screen confirmation message. If you provided a valid email address on your form, you may also receive an automated email confirming receipt of your file.',
                'flag_note' => '',
            ],
            [
                'question' => 'Can I edit or re-upload my application after submitting?',
                'answer' => 'No. Once a ZIP package is submitted, it goes directly into our review queue and cannot be edited. Please carefully verify that all documents are included and uncorrupted before uploading.',
                'flag_note' => '',
            ],
            [
                'question' => 'How and when will scholarship recipients be notified?',
                'answer' => 'Shortlisted candidates and successful awardees will be contacted directly via email and phone number provided in their application. Final lists may also be announced on our official website.',
                'flag_note' => '',
            ],
        ],
    ],
    [
        'title' => 'Technical Issues & Support',
        'items' => [
            [
                'question' => 'My ZIP file is failing to upload. What should I do?',
                'answer' => 'Check the following: Ensure your file format ends in .zip (other archive formats like .rar or .7z may not be accepted). Check that your file size is within the allowed limit. Ensure you have a stable internet connection. If you continue to experience technical glitches, contact our support team at info@bbafoundation.com before the application deadline.',
                'flag_note' => '',
            ],
            [
                'question' => 'Who can I reach out to for further inquiries?',
                'answer' => 'For any questions not answered here, please reach out to us via our Contact Us page or send an email to info@bbafoundation.com.',
                'flag_note' => '',
            ],
        ],
    ],
    [
        'title' => 'Online application',
        'addition' => true,
        'items' => [
            [
                'question' => 'Can I save my application and finish it later?',
                'answer' => 'Yes. After you create an account, the online form saves a draft on this website. You can sign in again, on a phone or a computer, and continue until you submit. This answer was added for the online application and can be edited by the Foundation.',
                'flag_note' => 'Addition for the online application.',
                'is_addition' => true,
            ],
            [
                'question' => 'Can I upload documents one at a time instead of a ZIP file?',
                'answer' => 'Yes. The online application asks for each required document separately. A single ZIP upload is available only if the Foundation turns that option on for the cycle. This answer was added for the online application and can be edited by the Foundation.',
                'flag_note' => 'Addition for the online application.',
                'is_addition' => true,
            ],
            [
                'question' => 'How do my referees and Head of Department send their letters?',
                'answer' => 'You can upload signed letters yourself. If the cycle allows it, the Foundation can also email your referees and Head of Department a private link so they can submit a letter directly. This answer was added for the online application and can be edited by the Foundation.',
                'flag_note' => 'Addition for the online application.',
                'is_addition' => true,
            ],
        ],
    ],
];
