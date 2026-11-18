<?php

declare(strict_types=1);

namespace BBAF\Controllers;

use BBAF\Core\Audit;
use BBAF\Core\Database;
use BBAF\Core\Dates;
use BBAF\Core\Paginator;
use BBAF\Core\Request;
use BBAF\Core\Security;
use BBAF\Core\View;
use BBAF\Services\Content;
use BBAF\Services\Cycles;
use BBAF\Services\Documents;
use BBAF\Services\Notify;

final class PublicController
{
    public function home(Request $request): void
    {
        $cycle = Cycles::current();
        $state = Cycles::publicState($cycle);
        View::render('public/home', [
            'title' => setting('site_title'),
            'metaDescription' => setting('seo_description'),
            'slides' => Content::slides(),
            'affiliates' => Content::affiliates(),
            'involved' => Content::blocks('involved'),
            'objectives' => Content::blocks('objective'),
            'steps' => Content::blocks('step_online'),
            'legacySteps' => Content::blocks('step_legacy'),
            'helped' => Content::studentsHelped(),
            'cycle' => $cycle,
            'cycleState' => $state,
            'cycleLabel' => Cycles::stateLabel($state),
            'events' => Content::upcomingEvents(3),
            'news' => Content::publishedNews(3),
            'jsonLd' => json_encode($this->organization(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'ogType' => 'website',
        ]);
    }

    public function about(Request $request): void
    {
        View::render('public/about', [
            'title' => 'About Us',
            'metaDescription' => setting('about_intro'),
            'cards' => Content::blocks('about_card'),
            'documents' => Content::blocks('required_document'),
            'objectives' => Content::blocks('objective'),
            'affiliates' => Content::affiliates(),
        ]);
    }

    public function trustees(Request $request): void
    {
        View::render('public/trustees', [
            'title' => 'Board of Trustees',
            'metaDescription' => 'The Board of Trustees of the Biodun and Bolanle Arokodare Foundation.',
            'trustees' => Content::trustees(),
        ]);
    }

    public function trustee(Request $request): void
    {
        $trustee = Content::trustee($request->param('slug', trim($request->path(), '/')));
        if ($trustee === null) {
            $this->notFound($request);
            return;
        }
        $person = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $trustee['listing_name'],
            'jobTitle' => $trustee['credential'],
            'description' => $trustee['summary'],
            'url' => url($trustee['slug']),
        ];
        View::render('public/trustee', [
            'title' => $trustee['listing_name'],
            'metaDescription' => (string) ($trustee['meta_description'] ?: $trustee['summary']),
            'trustee' => $trustee,
            'jsonLd' => json_encode($person, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function apply(Request $request): void
    {
        $cycle = Cycles::current();
        $state = Cycles::publicState($cycle);
        View::render('public/apply', [
            'title' => 'Apply',
            'metaDescription' => setting('call_body'),
            'cycle' => $cycle,
            'cycleState' => $state,
            'cycleLabel' => Cycles::stateLabel($state),
            'involved' => Content::blocks('involved'),
            'documents' => Content::blocks('required_document'),
            'steps' => Content::blocks('step_online'),
        ]);
    }

    public function formPdf(Request $request): void
    {
        Documents::sendDownload(Documents::blankForm(), 'BBAFoundation-application-form.pdf');
    }

    public function contact(Request $request): void
    {
        View::render('public/contact', $this->contactData());
    }

    public function contactSubmit(Request $request): void
    {
        if (!Security::rateAllow('contact', 5, 3600)) {
            flash('error', 'Please wait before sending another message.');
            View::redirect('/contact');
        }
        if (trim($request->input('company_website')) !== '') {
            flash('success', 'Thank you. If a reply is needed, the secretariat will write to you.');
            View::redirect('/contact');
        }
        $rendered = (int) $request->input('rendered_at', '0');
        if ($rendered < 1 || (time() - $rendered) < 3) {
            flash('error', 'The form was submitted too quickly. Please try again.');
            View::redirect('/contact');
        }
        $name = $request->input('full_name');
        $phone = $request->input('phone');
        $email = mb_strtolower($request->input('email'));
        $subject = $request->input('subject');
        $message = mb_substr($request->inputRaw('message'), 0, 180);
        $errors = [];
        if ($name === '') {
            $errors[] = 'Enter your full name.';
        }
        if (!Dates::phoneValid($phone)) {
            $errors[] = 'Enter a Nigerian phone number.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if (trim($message) === '') {
            $errors[] = 'Enter a message.';
        }
        if ($errors !== []) {
            remember_input($_POST);
            flash('error', implode(' ', $errors));
            View::redirect('/contact');
        }
        Database::insert(
            'INSERT INTO contact_messages (full_name, phone, email, subject, message, ip, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
            [$name, $phone, $email, $subject, trim($message), $request->ip(), 'new']
        );
        Notify::email($email, $name, 'contact_autoreply', ['name' => $name, 'subject' => $subject]);
        Notify::admins('contact_admin', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => trim($message),
        ], '/admin/inbox');
        Audit::log(null, 'contact_message', 'Contact form from ' . $email);
        flash('success', 'Thank you. If a reply is needed, the secretariat will write to you.');
        View::redirect('/contact');
    }

    public function faqs(Request $request): void
    {
        $groups = Content::faqs();
        $entities = [];
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $entities[] = [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
                ];
            }
        }
        View::render('public/faqs', [
            'title' => 'Frequently Asked Questions (FAQs)',
            'metaDescription' => 'Questions about eligibility, documents, selection and technical support.',
            'groups' => $groups,
            'jsonLd' => json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $entities], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function legal(Request $request): void
    {
        $slug = trim($request->path(), '/');
        $page = Content::page($slug);
        if ($page === null) {
            $this->notFound($request);
            return;
        }
        View::render('public/legal', [
            'title' => $page['title'],
            'metaDescription' => $page['meta_description'],
            'page' => $page,
        ]);
    }

    public function news(Request $request): void
    {
        [$page, $per, $offset] = $this->paging($request);
        $total = (int) Database::value("SELECT COUNT(*) FROM news_posts WHERE status = 'published' AND is_demo = 0");
        $posts = Database::all(
            "SELECT id, title, slug, excerpt, published_at FROM news_posts WHERE status = 'published' AND is_demo = 0 ORDER BY published_at DESC LIMIT {$per} OFFSET {$offset}"
        );
        View::render('public/news-index', [
            'title' => 'News',
            'metaDescription' => setting('news_sub'),
            'posts' => $posts,
            'meta' => Paginator::meta($total, $page, $per),
        ]);
    }

    public function newsShow(Request $request): void
    {
        $post = Database::one("SELECT n.*, CONCAT(u.surname, ' ', u.first_name) AS author_name FROM news_posts n LEFT JOIN users u ON u.id = n.author_id WHERE n.slug = ? AND n.status = 'published'", [$request->param('slug')]);
        if ($post === null) {
            $this->notFound($request);
            return;
        }
        $article = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post['title'],
            'datePublished' => $post['published_at'],
            'author' => ['@type' => 'Organization', 'name' => setting('org_short')],
        ];
        View::render('public/news-show', [
            'title' => $post['meta_title'] ?: $post['title'],
            'metaDescription' => $post['meta_description'] ?: $post['excerpt'],
            'post' => $post,
            'author' => (string) ($post['author_name'] ?? ''),
            'jsonLd' => json_encode($article, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ogType' => 'article',
        ]);
    }

    public function events(Request $request): void
    {
        $events = Database::all("SELECT * FROM events WHERE status = 'published' AND is_demo = 0 ORDER BY starts_at IS NULL, starts_at ASC");
        View::render('public/events-index', [
            'title' => 'Events',
            'metaDescription' => setting('events_sub'),
            'events' => $events,
        ]);
    }

    public function eventShow(Request $request): void
    {
        $event = Database::one("SELECT * FROM events WHERE slug = ? AND status = 'published'", [$request->param('slug')]);
        if ($event === null) {
            $this->notFound($request);
            return;
        }
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event['title'],
            'description' => $event['excerpt'],
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'eventStatus' => 'https://schema.org/EventScheduled',
            'location' => ['@type' => 'Place', 'name' => $event['venue'] !== '' ? $event['venue'] : setting('head_office')],
        ];
        if ($event['starts_at']) {
            $data['startDate'] = $event['starts_at'];
        }
        View::render('public/event-show', [
            'title' => $event['meta_title'] ?: $event['title'],
            'metaDescription' => $event['meta_description'] ?: $event['excerpt'],
            'event' => $event,
            'jsonLd' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function donate(Request $request): void
    {
        View::render('public/donate', [
            'title' => 'Donate',
            'metaDescription' => setting('campaign_text'),
        ]);
    }

    public function scholars(Request $request): void
    {
        View::render('public/scholars', [
            'title' => 'Scholars',
            'metaDescription' => setting('goal_caption'),
            'scholars' => Content::scholars(),
            'progress' => Content::goalProgress(),
        ]);
    }

    public function verify(Request $request): void
    {
        $code = strtoupper($request->param('code'));
        $row = Database::one(
            'SELECT a.status, c.title FROM awardees a JOIN cycles c ON c.id = a.cycle_id WHERE a.verification_code = ?',
            [$code]
        );
        View::render('public/verify', [
            'title' => 'Verify an award',
            'metaDescription' => 'Confirm a BBAFoundation award letter.',
            'valid' => $row !== null,
            'cycleTitle' => (string) ($row['title'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
        ]);
    }

    public function newsletter(Request $request): void
    {
        if (!Security::rateAllow('newsletter', 5, 3600)) {
            flash('error', 'Please wait before trying again.');
            View::redirect('/');
        }
        $email = mb_strtolower($request->input('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Enter a valid email address.');
            View::redirect('/');
        }
        $token = bin2hex(random_bytes(16));
        Database::exec(
            "INSERT INTO newsletter_subscribers (email, status, token_hash, created_at) VALUES (?, 'pending', ?, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), status = IF(status = 'confirmed', status, 'pending')",
            [$email, hash('sha256', $token)]
        );
        Notify::email($email, $email, 'newsletter_confirm', ['url' => url('newsletter/confirm/' . $token), 'name' => $email]);
        flash('success', 'Check your email to confirm the subscription.');
        View::redirect('/');
    }

    public function newsletterConfirm(Request $request): void
    {
        $hash = hash('sha256', $request->param('token'));
        $row = Database::one('SELECT id FROM newsletter_subscribers WHERE token_hash = ?', [$hash]);
        if ($row) {
            Database::exec("UPDATE newsletter_subscribers SET status = 'confirmed', confirmed_at = UTC_TIMESTAMP(), token_hash = NULL WHERE id = ?", [(int) $row['id']]);
            flash('success', 'Your subscription is confirmed.');
        } else {
            flash('error', 'That confirmation link is no longer valid.');
        }
        View::redirect('/');
    }

    public function sitemap(Request $request): void
    {
        $paths = ['/', '/about-us', '/bot', '/apply', '/contact', '/faqs', '/terms-and-conditions', '/privacy-policy', '/donate', '/scholars', '/news', '/events'];
        foreach (Content::trustees() as $trustee) {
            $paths[] = '/' . $trustee['slug'];
        }
        foreach (Database::all("SELECT slug, updated_at FROM news_posts WHERE status = 'published'") as $post) {
            $paths[] = '/news/' . $post['slug'];
        }
        foreach (Database::all("SELECT slug FROM events WHERE status = 'published'") as $event) {
            $paths[] = '/events/' . $event['slug'];
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($paths as $path) {
            $xml .= '<url><loc>' . htmlspecialchars(url(ltrim($path, '/')), ENT_XML1) . '</loc></url>';
        }
        $xml .= '</urlset>';
        header('Content-Type: application/xml; charset=UTF-8');
        echo $xml;
    }

    public function robots(Request $request): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        echo "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /dashboard\nDisallow: /install\nSitemap: " . url('sitemap.xml') . "\n";
    }

    public function rss(Request $request): void
    {
        $kind = $request->param('kind');
        $items = $kind === 'events'
            ? Database::all("SELECT title, slug, excerpt, created_at FROM events WHERE status = 'published' ORDER BY id DESC LIMIT 20")
            : Database::all("SELECT title, slug, excerpt, published_at AS created_at FROM news_posts WHERE status = 'published' ORDER BY published_at DESC LIMIT 20");
        $prefix = $kind === 'events' ? 'events/' : 'news/';
        $xml = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>' . htmlspecialchars(setting('org_short'), ENT_XML1) . '</title><link>' . htmlspecialchars(url('/'), ENT_XML1) . '</link><description>' . htmlspecialchars(setting('tagline'), ENT_XML1) . '</description>';
        foreach ($items as $item) {
            $xml .= '<item><title>' . htmlspecialchars((string) $item['title'], ENT_XML1) . '</title><link>' . htmlspecialchars(url($prefix . $item['slug']), ENT_XML1) . '</link><description>' . htmlspecialchars((string) $item['excerpt'], ENT_XML1) . '</description></item>';
        }
        $xml .= '</channel></rss>';
        header('Content-Type: application/rss+xml; charset=UTF-8');
        echo $xml;
    }

    public function notFound(Request $request): void
    {
        http_response_code(404);
        View::render('public/404', [
            'title' => 'Page not found',
            'metaDescription' => 'That page is not on the BBAFoundation website.',
        ]);
    }

    private function contactData(): array
    {
        $query = setting('map_query');
        $lat = setting('map_lat');
        $lng = setting('map_lng');
        if (($lat === '' || $lng === '') && $query !== '' && function_exists('curl_init')) {
            $coords = $this->geocode($query);
            if ($coords !== null) {
                \BBAF\Core\Settings::set('map_lat', (string) $coords['lat']);
                \BBAF\Core\Settings::set('map_lng', (string) $coords['lng']);
                $lat = (string) $coords['lat'];
                $lng = (string) $coords['lng'];
            }
        }
        $map = '';
        if ($lat !== '' && $lng !== '') {
            $delta = 0.02;
            $bbox = ($lng - $delta) . ',' . ($lat - $delta) . ',' . ($lng + $delta) . ',' . ($lat + $delta);
            $map = 'https://www.openstreetmap.org/export/embed.html?bbox=' . rawurlencode($bbox) . '&layer=mapnik&marker=' . rawurlencode($lat . ',' . $lng);
        }

        return [
            'title' => 'Contact',
            'metaDescription' => setting('contact_intro'),
            'map' => $map,
            'mapsLink' => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($query),
        ];
    }

    private function geocode(string $query): ?array
    {
        $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' . rawurlencode($query);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => ['User-Agent: BBAFoundation/1.0 (info@bbafoundation.com)', 'Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        if (!is_string($body)) {
            return null;
        }
        $rows = json_decode($body, true);
        if (!is_array($rows) || !isset($rows[0]['lat'], $rows[0]['lon'])) {
            return null;
        }

        return ['lat' => (float) $rows[0]['lat'], 'lng' => (float) $rows[0]['lon']];
    }

    private function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'NGO',
            'name' => setting('legal_name'),
            'alternateName' => setting('org_short'),
            'url' => url('/'),
            'email' => setting('email'),
            'telephone' => setting('phone_e164'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => setting('head_office'),
                'addressCountry' => 'NG',
            ],
        ];
    }

    private function paging(Request $request): array
    {
        $per = Paginator::perPage($request);
        $page = Paginator::page($request);

        return [$page, $per, ($page - 1) * $per];
    }
}
