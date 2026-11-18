<?php

declare(strict_types=1);

namespace BBAF\Controllers;

use BBAF\Core\Audit;
use BBAF\Core\Auth;
use BBAF\Core\Database;
use BBAF\Core\Dates;
use BBAF\Core\Paginator;
use BBAF\Core\Purify;
use BBAF\Core\Request;
use BBAF\Core\Settings;
use BBAF\Core\Upload;
use BBAF\Core\View;

final class AdminContentController
{
    public function pages(Request $request): void
    {
        $this->listPage('pages', 'SELECT id, title, slug, status FROM pages ORDER BY title', [], 'Pages', 'admin/pages');
    }

    public function editPage(Request $request): void
    {
        $id = (int) $request->param('id');
        $page = $id === 0 ? null : Database::one('SELECT * FROM pages WHERE id = ?', [$id]);
        if ($id !== 0 && $page === null) {
            flash('error', 'Page not found.');
            View::redirect('/admin/pages');
        }
        View::render('admin/page-form', [
            'title' => $page['title'] ?? 'New page',
            'page' => $page,
            'versions' => $page ? Database::all('SELECT id, effective_label, note, created_at FROM page_versions WHERE page_id = ? ORDER BY id DESC', [(int) $page['id']]) : [],
        ], 'admin');
    }

    public function savePage(Request $request): void
    {
        $id = (int) $request->param('id');
        $title = $request->input('title');
        $slug = $this->slug($request->input('slug', $title), 'pages', $id);
        $body = Purify::clean($request->inputRaw('body'));
        $status = $request->input('status', 'draft') === 'published' ? 'published' : 'draft';
        if ($id === 0) {
            $id = Database::insert(
                'INSERT INTO pages (slug, title, body, meta_title, meta_description, page_type, status, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
                [$slug, $title, $body, $request->input('meta_title'), $request->input('meta_description'), 'page', $status]
            );
        } else {
            Database::exec(
                'UPDATE pages SET slug = ?, title = ?, body = ?, meta_title = ?, meta_description = ?, status = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?',
                [$slug, $title, $body, $request->input('meta_title'), $request->input('meta_description'), $status, $id]
            );
        }
        Database::insert(
            'INSERT INTO page_versions (page_id, title, body, effective_label, note, created_at) VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP())',
            [$id, $title, $body, $request->input('effective_label'), $request->input('note')]
        );
        content_cache_clear();
        Audit::log(Auth::id(), 'page_saved', $slug, 'page', $id);
        flash('success', 'Page saved.');
        View::redirect('/admin/pages/' . $id);
    }

    public function trustees(Request $request): void
    {
        $this->listPage('trustees', 'SELECT id, listing_name, role_label, sort_order, is_active FROM trustees ORDER BY sort_order, id', [], 'Trustees', 'admin/trustees');
    }

    public function editTrustee(Request $request): void
    {
        $id = (int) $request->param('id');
        $trustee = $id === 0 ? null : Database::one('SELECT * FROM trustees WHERE id = ?', [$id]);
        View::render('admin/trustee-form', ['title' => $trustee['listing_name'] ?? 'New trustee', 'trustee' => $trustee], 'admin');
    }

    public function saveTrustee(Request $request): void
    {
        $id = (int) $request->param('id');
        $name = $request->input('listing_name');
        $slug = $this->slug($request->input('slug', $name), 'trustees', $id);
        $photo = $request->input('photo_file');
        $file = $request->file('photo');
        if ($file !== null) {
            try {
                $saved = Upload::storePublicImage($file);
                $photo = 'assets/media/' . $saved['stored_name'];
            } catch (\Throwable $error) {
                flash('error', $error->getMessage());
                View::redirect('/admin/trustees/' . $id);
            }
        }
        $fields = [
            $slug, (int) $request->input('sort_order', '0'), $request->input('role_label'), $name,
            $request->input('profile_name'), $request->input('credential'), $request->inputRaw('summary'),
            Purify::clean($request->inputRaw('full_profile')), $photo,
            $request->input('social_facebook'), $request->input('social_twitter'), $request->input('social_youtube'), $request->input('social_linkedin'),
            $request->has('is_active') ? 1 : 0, $request->input('meta_description'),
        ];
        if ($id === 0) {
            $id = Database::insert(
                'INSERT INTO trustees (slug, sort_order, role_label, listing_name, profile_name, credential, summary, full_profile, photo_file, social_facebook, social_twitter, social_youtube, social_linkedin, is_active, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                $fields
            );
        } else {
            Database::exec(
                'UPDATE trustees SET slug=?, sort_order=?, role_label=?, listing_name=?, profile_name=?, credential=?, summary=?, full_profile=?, photo_file=?, social_facebook=?, social_twitter=?, social_youtube=?, social_linkedin=?, is_active=?, meta_description=? WHERE id=?',
                array_merge($fields, [$id])
            );
        }
        content_cache_clear();
        flash('success', 'Trustee saved.');
        View::redirect('/admin/trustees/' . $id);
    }

    public function faqs(Request $request): void
    {
        View::render('admin/faqs', [
            'title' => 'FAQs',
            'groups' => Database::all('SELECT * FROM faq_groups ORDER BY sort_order, id'),
            'rows' => Database::all('SELECT f.*, g.title AS group_title FROM faqs f JOIN faq_groups g ON g.id = f.group_id ORDER BY g.sort_order, f.sort_order, f.id'),
        ], 'admin');
    }

    public function saveFaq(Request $request): void
    {
        $id = (int) $request->input('id', '0');
        $values = [
            (int) $request->input('group_id'),
            (int) $request->input('sort_order', '0'),
            $request->input('question'),
            Purify::clean($request->inputRaw('answer')),
            $request->has('is_addition') ? 1 : 0,
            $request->input('flag_note'),
            $request->has('is_visible') ? 1 : 0,
        ];
        if ($request->input('question') === '') {
            flash('error', 'Enter the question.');
            View::redirect('/admin/faqs');
        }
        if ($id === 0) {
            Database::insert(
                'INSERT INTO faqs (group_id, sort_order, question, answer, is_addition, flag_note, is_visible) VALUES (?, ?, ?, ?, ?, ?, ?)',
                $values
            );
        } else {
            Database::exec(
                'UPDATE faqs SET group_id=?, sort_order=?, question=?, answer=?, is_addition=?, flag_note=?, is_visible=? WHERE id=?',
                array_merge($values, [$id])
            );
        }
        content_cache_clear();
        flash('success', 'FAQ saved.');
        View::redirect('/admin/faqs');
    }

    public function news(Request $request): void
    {
        $this->listPage('news', 'SELECT id, title, status, is_demo, published_at FROM news_posts ORDER BY id DESC', [], 'News', 'admin/news');
    }

    public function editNews(Request $request): void
    {
        $id = (int) $request->param('id');
        View::render('admin/news-form', [
            'title' => 'News',
            'post' => $id === 0 ? null : Database::one('SELECT * FROM news_posts WHERE id = ?', [$id]),
            'categories' => Database::all('SELECT id, name FROM news_categories ORDER BY name'),
        ], 'admin');
    }

    public function saveNews(Request $request): void
    {
        $this->saveStory('news_posts', (int) $request->param('id'), '/admin/news/');
    }

    public function events(Request $request): void
    {
        $this->listPage('events', 'SELECT id, title, status, is_demo, starts_at FROM events ORDER BY id DESC', [], 'Events', 'admin/events');
    }

    public function editEvent(Request $request): void
    {
        $id = (int) $request->param('id');
        View::render('admin/event-form', [
            'title' => 'Event',
            'event' => $id === 0 ? null : Database::one('SELECT * FROM events WHERE id = ?', [$id]),
        ], 'admin');
    }

    public function saveEvent(Request $request): void
    {
        $id = (int) $request->param('id');
        $title = $request->input('title');
        $slug = $this->slug($request->input('slug', $title), 'events', $id);
        $status = $request->input('status') === 'published' ? 'published' : 'draft';
        $fields = [
            $title, $slug, $request->input('excerpt'), Purify::clean($request->inputRaw('body')),
            $request->input('venue'), Dates::parseLocal($request->input('starts_at')), Dates::parseLocal($request->input('ends_at')),
            $request->input('meta_title'), $request->input('meta_description'), $status,
        ];
        if ($id === 0) {
            $id = Database::insert(
                'INSERT INTO events (title, slug, excerpt, body, venue, starts_at, ends_at, meta_title, meta_description, status, is_demo, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
                $fields
            );
        } else {
            Database::exec(
                'UPDATE events SET title=?, slug=?, excerpt=?, body=?, venue=?, starts_at=?, ends_at=?, meta_title=?, meta_description=?, status=?, updated_at=UTC_TIMESTAMP() WHERE id=?',
                array_merge($fields, [$id])
            );
        }
        flash('success', 'Event saved. Leave the date blank until the Foundation publishes one.');
        View::redirect('/admin/events/' . $id);
    }

    public function media(Request $request): void
    {
        $page = Paginator::page($request);
        $per = Paginator::perPage($request);
        $total = (int) Database::value('SELECT COUNT(*) FROM media');
        $meta = Paginator::meta($total, $page, $per);
        $rows = Database::all('SELECT * FROM media ORDER BY id DESC LIMIT ' . (int) $per . ' OFFSET ' . (int) (($meta['page'] - 1) * $per));
        View::render('admin/media', ['title' => 'Media', 'rows' => $rows, 'meta' => $meta], 'admin');
    }

    public function uploadMedia(Request $request): void
    {
        $file = $request->file('file');
        if ($file === null) {
            flash('error', 'Choose an image.');
            View::redirect('/admin/media');
        }
        try {
            $saved = Upload::storePublicImage($file);
        } catch (\Throwable $error) {
            flash('error', $error->getMessage());
            View::redirect('/admin/media');
        }
        $size = @getimagesize($saved['absolute']);
        Database::insert(
            'INSERT INTO media (disk_path, public_path, mime, original_name, alt_text, byte_size, width, height, checksum, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
            [
                $saved['absolute'],
                $saved['public_path'],
                $saved['mime'],
                $saved['original_name'],
                $request->input('alt_text'),
                $saved['byte_size'],
                is_array($size) ? $size[0] : null,
                is_array($size) ? $size[1] : null,
                hash_file('sha256', $saved['absolute']) ?: '',
                Auth::id(),
            ]
        );
        flash('success', 'Image stored.');
        View::redirect('/admin/media');
    }

    public function homeContent(Request $request): void
    {
        View::render('admin/home', [
            'title' => 'Home content',
            'slides' => Database::all('SELECT * FROM hero_slides ORDER BY sort_order, id'),
            'blocks' => Database::all('SELECT * FROM blocks ORDER BY group_key, sort_order, id'),
            'affiliates' => Database::all('SELECT * FROM affiliates ORDER BY sort_order, id'),
        ], 'admin');
    }

    public function saveHome(Request $request): void
    {
        if (isset($_POST['slide_title']) && is_array($_POST['slide_title'])) {
            foreach ($_POST['slide_title'] as $id => $title) {
                Database::exec(
                    'UPDATE hero_slides SET title = ?, subtitle = ?, kicker = ?, is_visible = ? WHERE id = ?',
                    [trim((string) $title), trim((string) ($_POST['slide_subtitle'][$id] ?? '')), trim((string) ($_POST['slide_kicker'][$id] ?? '')), isset($_POST['slide_visible'][$id]) ? 1 : 0, (int) $id]
                );
            }
        }
        if (isset($_POST['block_title']) && is_array($_POST['block_title'])) {
            foreach ($_POST['block_title'] as $id => $title) {
                Database::exec(
                    'UPDATE blocks SET title = ?, body = ?, is_visible = ? WHERE id = ?',
                    [trim((string) $title), (string) ($_POST['block_body'][$id] ?? ''), isset($_POST['block_visible'][$id]) ? 1 : 0, (int) $id]
                );
            }
        }
        if (isset($_POST['affiliate_name']) && is_array($_POST['affiliate_name'])) {
            foreach ($_POST['affiliate_name'] as $id => $name) {
                Database::exec(
                    'UPDATE affiliates SET name = ?, link_url = ?, is_visible = ? WHERE id = ?',
                    [trim((string) $name), trim((string) ($_POST['affiliate_link'][$id] ?? '')), isset($_POST['affiliate_visible'][$id]) ? 1 : 0, (int) $id]
                );
            }
        }
        Settings::setMany([
            'founder_story' => $request->inputRaw('founder_story'),
            'announcement_banner' => $request->input('announcement_banner'),
            'volunteers_count' => $request->input('volunteers_count', '0'),
            'sponsors_count' => $request->input('sponsors_count', '0'),
            'students_helped_mode' => $request->input('students_helped_mode') === 'manual' ? 'manual' : 'auto',
            'students_helped_manual' => $request->input('students_helped_manual', '0'),
        ]);
        content_cache_clear();
        flash('success', 'Home content saved. The founder story stays hidden on the public site until this field has text.');
        View::redirect('/admin/home');
    }

    public function inbox(Request $request): void
    {
        $page = Paginator::page($request);
        $per = Paginator::perPage($request);
        $total = (int) Database::value('SELECT COUNT(*) FROM contact_messages');
        $meta = Paginator::meta($total, $page, $per);
        $rows = Database::all('SELECT * FROM contact_messages ORDER BY id DESC LIMIT ' . (int) $per . ' OFFSET ' . (int) (($meta['page'] - 1) * $per));
        View::render('admin/inbox', ['title' => 'Inbox', 'rows' => $rows, 'meta' => $meta], 'admin');
    }

    public function inboxStatus(Request $request): void
    {
        $status = $request->input('status');
        if (!in_array($status, ['new', 'read', 'replied', 'archived'], true)) {
            $status = 'read';
        }
        Database::exec('UPDATE contact_messages SET status = ? WHERE id = ?', [$status, (int) $request->param('id')]);
        flash('success', 'Message updated.');
        View::redirect('/admin/inbox');
    }

    public function newsletter(Request $request): void
    {
        $page = Paginator::page($request);
        $per = Paginator::perPage($request);
        $total = (int) Database::value('SELECT COUNT(*) FROM newsletter_subscribers');
        $meta = Paginator::meta($total, $page, $per);
        $rows = Database::all('SELECT id, email, status, confirmed_at, created_at FROM newsletter_subscribers ORDER BY id DESC LIMIT ' . (int) $per . ' OFFSET ' . (int) (($meta['page'] - 1) * $per));
        View::render('admin/newsletter', ['title' => 'Newsletter', 'rows' => $rows, 'meta' => $meta], 'admin');
    }

    public function newsletterCsv(Request $request): void
    {
        $rows = Database::all('SELECT email, status, confirmed_at, created_at FROM newsletter_subscribers ORDER BY id');
        $out = [];
        foreach ($rows as $row) {
            $out[] = array_values($row);
        }
        \BBAF\Services\Reports::csv('newsletter.csv', ['Email', 'Status', 'Confirmed', 'Created'], $out);
    }

    private function listPage(string $key, string $sql, array $params, string $title, string $view): void
    {
        $request = Request::current();
        $page = Paginator::page($request);
        $per = Paginator::perPage($request);
        $countSql = 'SELECT COUNT(*) FROM (' . $sql . ') listed';
        $total = (int) Database::value($countSql, $params);
        $meta = Paginator::meta($total, $page, $per);
        $rows = Database::all($sql . ' LIMIT ' . (int) $per . ' OFFSET ' . (int) (($meta['page'] - 1) * $per), $params);
        View::render($view, ['title' => $title, 'rows' => $rows, 'meta' => $meta, 'listKey' => $key], 'admin');
    }

    private function saveStory(string $table, int $id, string $back): void
    {
        $request = Request::current();
        $title = $request->input('title');
        $slug = $this->slug($request->input('slug', $title), $table, $id);
        $status = $request->input('status') === 'published' ? 'published' : 'draft';
        $published = $status === 'published' ? (Dates::parseLocal($request->input('published_at')) ?? Dates::nowUtc()) : null;
        $fields = [
            (int) $request->input('category_id') ?: null,
            Auth::id(),
            $title,
            $slug,
            $request->input('excerpt'),
            Purify::clean($request->inputRaw('body')),
            $request->input('meta_title'),
            $request->input('meta_description'),
            $status,
            $published,
        ];
        if ($id === 0) {
            $id = Database::insert(
                "INSERT INTO news_posts (category_id, author_id, title, slug, excerpt, body, meta_title, meta_description, status, published_at, is_demo, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, UTC_TIMESTAMP(), UTC_TIMESTAMP())",
                $fields
            );
        } else {
            Database::exec(
                'UPDATE news_posts SET category_id=?, author_id=?, title=?, slug=?, excerpt=?, body=?, meta_title=?, meta_description=?, status=?, published_at=?, updated_at=UTC_TIMESTAMP() WHERE id=?',
                array_merge($fields, [$id])
            );
        }
        flash('success', 'News saved.');
        View::redirect($back . $id);
    }

    private function slug(string $value, string $table, int $ignoreId): string
    {
        $allowed = ['pages', 'trustees', 'news_posts', 'events'];
        if (!in_array($table, $allowed, true)) {
            $table = 'pages';
        }
        $base = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
        if ($base === '') {
            $base = 'item';
        }
        $slug = $base;
        $n = 2;
        while (Database::one("SELECT id FROM {$table} WHERE slug = ? AND id <> ?", [$slug, $ignoreId])) {
            $slug = $base . '-' . $n;
            $n++;
        }

        return $slug;
    }
}
