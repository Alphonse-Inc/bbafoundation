<?php $page = $page ?? ['id' => 0, 'title' => '', 'slug' => '', 'body' => '', 'meta_title' => '', 'meta_description' => '', 'status' => 'draft']; ?>
<form method="post" action="<?= e(url('admin/pages/' . (int) ($page['id'] ?? 0))) ?>" class="form-grid"><?= csrf_field() ?>
    <label>Title<input name="title" required value="<?= e((string) $page['title']) ?>"></label>
    <label>Slug<input name="slug" value="<?= e((string) $page['slug']) ?>"></label>
    <label>Status<select name="status"><option value="published"<?= selected($page['status'], 'published') ?>>Published</option><option value="draft"<?= selected($page['status'], 'draft') ?>>Draft</option></select></label>
    <label>Meta title<input name="meta_title" value="<?= e((string) $page['meta_title']) ?>"></label>
    <label>Meta description<input name="meta_description" value="<?= e((string) $page['meta_description']) ?>"></label>
    <div data-rich>
        <p><button type="button" data-cmd="p">Paragraph</button> <button type="button" data-cmd="strong">Bold</button> <button type="button" data-cmd="h2">Heading</button> <button type="button" data-cmd="link">Link</button> <button type="button" data-cmd="li">List</button></p>
        <label>Body<textarea name="body" rows="16"><?= e((string) $page['body']) ?></textarea></label>
    </div>
    <label>Version label<input name="effective_label" placeholder="For example: 2026 revision"></label>
    <label>Version note<input name="note"></label>
    <button class="btn btn-gold" type="submit">Save</button>
</form>
<?php if (!empty($versions)): ?>
    <h2>Version history</h2>
    <ul><?php foreach ($versions as $version): ?><li><?= e(display_date((string) $version['created_at'], true)) ?> · <?= e($version['effective_label']) ?> · <?= e($version['note']) ?></li><?php endforeach; ?></ul>
<?php endif; ?>
