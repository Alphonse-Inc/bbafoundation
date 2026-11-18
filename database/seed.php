<?php

declare(strict_types=1);

/**
 * Seeds the content bank. Safe to call only on an empty schema.
 */
function bbaf_seed(PDO $pdo, bool $demo = false): void
{
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $now = gmdate('Y-m-d H:i:s');

    $roles = [
        [1, 'super_admin', 'Super Admin', 'Settings, users, SMTP, backups, audit, import and retention.'],
        [2, 'foundation_admin', 'Foundation Admin', 'Cycles, applications, awards, content and communications.'],
        [3, 'reviewer', 'Reviewer', 'Scores only the applications assigned to them.'],
        [4, 'content_editor', 'Content Editor', 'Pages, news, events, media, FAQs and trustees.'],
        [5, 'applicant', 'Applicant', 'Owns their registration and application.'],
        [6, 'awardee', 'Awardee', 'An applicant who accepted an award.'],
    ];
    $roleStmt = $pdo->prepare('INSERT INTO roles (id, slug, name, description) VALUES (?, ?, ?, ?)');
    foreach ($roles as $role) {
        $roleStmt->execute($role);
    }

    $site = require __DIR__ . '/content/site.php';
    $setting = $pdo->prepare('INSERT INTO settings (skey, svalue, updated_at) VALUES (?, ?, ?)');
    foreach ($site['settings'] as $key => $value) {
        $setting->execute([$key, $value, $now]);
    }

    $slide = $pdo->prepare('INSERT INTO hero_slides (sort_order, kicker, title, subtitle, primary_label, primary_url, secondary_label, secondary_url, is_visible) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)');
    foreach ($site['slides'] as $index => $row) {
        $slide->execute([$index + 1, $row['kicker'], $row['title'], $row['subtitle'], $row['primary_label'], $row['primary_url'], $row['secondary_label'], $row['secondary_url']]);
    }

    $block = $pdo->prepare('INSERT INTO blocks (group_key, sort_order, title, body, link_url, is_visible, is_original) VALUES (?, ?, ?, ?, ?, 1, 1)');
    $groupCount = [];
    foreach ($site['blocks'] as $row) {
        $group = $row['group_key'];
        $groupCount[$group] = ($groupCount[$group] ?? 0) + 1;
        $block->execute([$group, $groupCount[$group], $row['title'], $row['body'], $row['link_url']]);
    }

    $affiliate = $pdo->prepare('INSERT INTO affiliates (sort_order, code, name, link_url, image_file, is_visible) VALUES (?, ?, ?, ?, ?, 1)');
    foreach ($site['affiliates'] as $index => $row) {
        $affiliate->execute([$index + 1, $row['code'], 'To be labelled by the Foundation', '', $row['image_file']]);
    }

    $lga = $pdo->prepare('INSERT INTO lgas (name, state_name, note, sort_order) VALUES (?, ?, ?, ?)');
    foreach ($site['lgas'] as $index => $row) {
        $lga->execute([$row['name'], 'Ekiti', $row['note'], $index + 1]);
    }

    $institution = $pdo->prepare('INSERT INTO institutions (name, institution_type, ownership, state_name, is_suggestion, is_active) VALUES (?, ?, ?, ?, 1, 1)');
    foreach (require __DIR__ . '/content/institutions.php' as $row) {
        $institution->execute([$row['name'], $row['institution_type'], $row['ownership'], '']);
    }

    $legal = require __DIR__ . '/content/legal.php';
    $page = $pdo->prepare('INSERT INTO pages (slug, title, body, meta_title, meta_description, page_type, status, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $version = $pdo->prepare('INSERT INTO page_versions (page_id, title, body, effective_label, note, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $pages = [
        ['terms-and-conditions', 'Terms and Conditions', $legal['terms_live'], 'Terms and Conditions', 'Terms governing the BBAFoundation website and scholarship applications.', 'legal', $legal['terms_original'], 'Seeded from the previous website. The live page adds an online-application section marked for Foundation approval.'],
        ['privacy-policy', 'Privacy Policy', $legal['privacy_live'], 'Privacy Policy', 'How BBAFoundation collects, uses and keeps personal data.', 'legal', $legal['privacy_legacy'], 'Legacy WordPress text, including the comments clauses. Comments are not enabled. The live policy removes that section and adds an online-accounts section marked for Foundation approval.'],
        ['selection-process', 'Selection process', $legal['selection'], 'Selection process', 'How scholarship decisions are made.', 'legal', null, ''],
        ['breach-procedure', 'Breach response', $legal['breach'], 'Breach response', 'Administrator procedure for a personal-data breach.', 'admin', null, ''],
    ];
    foreach ($pages as $row) {
        $page->execute([$row[0], $row[1], $row[2], $row[3], $row[4], $row[5], 'published', $now]);
        $pageId = (int) $pdo->lastInsertId();
        if ($row[6] !== null) {
            $version->execute([$pageId, $row[1], $row[6], 'July 27, 2026', $row[7], $now]);
        }
    }

    $groupStmt = $pdo->prepare('INSERT INTO faq_groups (title, sort_order) VALUES (?, ?)');
    $faqStmt = $pdo->prepare('INSERT INTO faqs (group_id, sort_order, question, answer, is_addition, flag_note, is_visible) VALUES (?, ?, ?, ?, ?, ?, 1)');
    foreach (require __DIR__ . '/content/faqs.php' as $gIndex => $group) {
        $groupStmt->execute([$group['title'], $gIndex + 1]);
        $groupId = (int) $pdo->lastInsertId();
        foreach ($group['items'] as $index => $item) {
            $faqStmt->execute([
                $groupId,
                $index + 1,
                $item['question'],
                $item['answer'],
                !empty($item['is_addition']) || !empty($group['addition']) ? 1 : 0,
                $item['flag_note'] ?? '',
            ]);
        }
    }

    $trusteeStmt = $pdo->prepare('INSERT INTO trustees (slug, sort_order, role_label, listing_name, profile_name, credential, summary, full_profile, structured_json, photo_file, is_active, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)');
    foreach (require __DIR__ . '/content/trustees.php' as $index => $trustee) {
        $profile = trim((string) $trustee['full_profile']);
        $structured = $trustee['structured'];
        if ($profile === '' && $structured !== []) {
            $parts = [];
            foreach ($structured as $heading => $body) {
                $parts[] = $heading . "\n" . $body;
            }
            $profile = implode("\n\n", $parts);
        }
        $trusteeStmt->execute([
            $trustee['slug'],
            $index + 1,
            $trustee['role_label'],
            $trustee['listing_name'],
            $trustee['profile_name'],
            $trustee['credential'],
            $trustee['summary'],
            $profile,
            $structured === [] ? null : json_encode($structured, JSON_UNESCAPED_UNICODE),
            $trustee['photo_file'],
            mb_substr(trim((string) $trustee['summary'] . ' ' . $trustee['credential']), 0, 300),
        ]);
    }

    $template = $pdo->prepare('INSERT INTO email_templates (tkey, name, subject, body_html, body_text, updated_at) VALUES (?, ?, ?, ?, ?, ?)');
    foreach (require __DIR__ . '/content/templates.php' as $row) {
        $template->execute([$row['tkey'], $row['name'], $row['subject'], $row['body_html'], $row['body_text'], $now]);
    }

    $cycle = $pdo->prepare('INSERT INTO cycles (title, announcement, opens_at, closes_at, award_count, status, created_at, updated_at) VALUES (?, ?, NULL, NULL, 10, ?, ?, ?)');
    $cycle->execute([
        '2026 Scholarship Programme',
        $site['settings']['call_body'],
        'draft',
        $now,
        $now,
    ]);
    $cycleId = (int) $pdo->lastInsertId();
    bbaf_seed_cycle_children($pdo, $cycleId);

    $redirect = $pdo->prepare('INSERT INTO redirects (source_path, target_path, status_code, is_active, note) VALUES (?, ?, ?, 1, ?)');
    $redirects = [
        ['/privacy-policy-2', '/privacy-policy', 301, 'Previous website privacy URL'],
        ['/wp-login.php', '/login', 301, 'WordPress login'],
        ['/wp-admin', '/login', 301, 'WordPress admin'],
        ['/xmlrpc.php', '', 410, 'WordPress XML-RPC is not used'],
        ['/wp-json', '', 410, 'WordPress REST API is not used'],
    ];
    $manifest = require __DIR__ . '/content/manifest.php';
    foreach ($manifest['files'] as $file) {
        $redirects[] = [
            '/wp-content/uploads/' . $file['path'],
            '/assets/img/placeholders/pending.svg',
            302,
            'Temporary until Legacy Import replaces this file',
        ];
    }
    foreach ($redirects as $row) {
        $redirect->execute($row);
    }

    $legacy = $pdo->prepare('INSERT INTO legacy_imports (source_url, local_path, alt_text, status, updated_at) VALUES (?, ?, ?, ?, ?)');
    foreach ($manifest['files'] as $file) {
        $legacy->execute([$manifest['base'] . $file['path'], '', $file['alt'], 'pending', $now]);
    }

    $category = $pdo->prepare('INSERT INTO news_categories (name, slug) VALUES (?, ?)');
    $category->execute(['Updates', 'updates']);

    if ($demo) {
        $categoryId = (int) $pdo->lastInsertId();
        $news = $pdo->prepare('INSERT INTO news_posts (category_id, title, slug, excerpt, body, status, is_demo, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?)');
        $news->execute([
            $categoryId,
            'DEMO — remove before go-live',
            'demo-remove-before-go-live',
            'Installer demo content. This is not a Foundation announcement.',
            '<p>This post was created because the installer demo switch was turned on. Delete it before the site goes live. It is not a Foundation announcement, event or testimonial.</p>',
            'published',
            $now,
            $now,
            $now,
        ]);
        $event = $pdo->prepare('INSERT INTO events (title, slug, excerpt, body, venue, status, is_demo, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)');
        $event->execute([
            'DEMO — remove before go-live',
            'demo-event-remove-before-go-live',
            'Installer demo content. This is not a Foundation event.',
            '<p>This event was created because the installer demo switch was turned on. Delete it before the site goes live. No date is set because the Foundation has not published one.</p>',
            '',
            'draft',
            $now,
            $now,
        ]);
    }
}

function bbaf_seed_cycle_children(PDO $pdo, int $cycleId): void
{
    $doc = $pdo->prepare('INSERT INTO cycle_documents (cycle_id, doc_key, label, is_required, sort_order, flag_note) VALUES (?, ?, ?, ?, ?, ?)');
    $documents = [
        ['admission_letter', 'Admission letter', 1, ''],
        ['student_id', 'Student ID card', 1, 'The public checklist lists this together with the admission letter. They are separate uploads so each can be reviewed.'],
        ['transcript', 'Academic transcript', 1, ''],
        ['state_of_origin', 'State of Origin Certificate', 1, ''],
        ['indigene_proof', 'Proof of indigene of Ijero-Ekiti or Ewu-Ekiti (letter from the Kabiyesi)', 1, ''],
        ['referee_letter_1', "First referee's letter", 1, ''],
        ['referee_letter_2', "Second referee's letter", 1, ''],
        ['passport', 'Passport photograph', 1, 'The same file satisfies Section A and Section C.'],
        ['birth_certificate', 'Birth Certificate', 1, ''],
        ['means_of_id', 'Valid means of identification', 0, 'The FAQ ZIP list mentions an ID copy. The official form records the type and NIN. Optional unless the Foundation requires it.'],
        ['signed_form', 'Signed copy of the application form', 0, 'The online form generates a PDF. Upload a signed copy only if the Foundation asks for one.'],
        ['legacy_zip', 'Legacy ZIP of all documents', 0, 'Off unless this cycle enables the legacy ZIP route. The previous form mentioned 25 MB.'],
    ];
    foreach ($documents as $index => $row) {
        $doc->execute([$cycleId, $row[0], $row[1], $row[2], $index + 1, $row[3]]);
    }

    $rubric = $pdo->prepare('INSERT INTO rubric_criteria (cycle_id, sort_order, name, description, weight, max_score, is_active) VALUES (?, ?, ?, ?, ?, 10, 1)');
    $criteria = [
        ['Academic performance', 'Results, CGPA or polytechnic grade, and the transcript.', 30],
        ['Financial need', 'What the statement and documents show about the risk of discontinuing.', 20],
        ['Personal statement', 'Clarity, sincerity and the stated use of the scholarship. Maximum 400 words.', 20],
        ['Referees and community standing', 'Two referees and the indigene letter.', 15],
        ['Character', 'Consistency of the declaration and the referee comments.', 15],
    ];
    foreach ($criteria as $index => $row) {
        $rubric->execute([$cycleId, $index + 1, $row[0], $row[1], $row[2]]);
    }
}
