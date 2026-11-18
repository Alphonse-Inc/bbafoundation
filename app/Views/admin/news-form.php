<?php $post = $post ?? ['id' => 0, 'title' => '', 'slug' => '', 'excerpt' => '', 'body' => '', 'status' => 'draft', 'meta_title' => '', 'meta_description' => '', 'category_id' => '', 'published_at' => '']; ?>
<form method="post" action="<?= e(url('admin/news/' . (int) ($post['id'] ?? 0))) ?>" class="form-grid"><?= csrf_field() ?>
    <label>Title<input name="title" required value="<?= e((string) $post['title']) ?>"></label>
    <label>Slug<input name="slug" value="<?= e((string) $post['slug']) ?>"></label>
    <label>Category<select name="category_id"><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>"<?= selected($post['category_id'], $category['id']) ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></label>
    <label>Status<select name="status"><option value="draft"<?= selected($post['status'], 'draft') ?>>Draft</option><option value="published"<?= selected($post['status'], 'published') ?>>Published</option></select></label>
    <label>Publish date<input name="published_at" value="<?= e(\BBAF\Core\Dates::inputValue($post['published_at'] ?: null)) ?>" placeholder="DD/MM/YYYY HH:MM"></label>
    <label>Excerpt<textarea name="excerpt"><?= e((string) $post['excerpt']) ?></textarea></label>
    <div data-rich><label>Body<textarea name="body" rows="12"><?= e((string) $post['body']) ?></textarea></label></div>
    <label>Meta title<input name="meta_title" value="<?= e((string) $post['meta_title']) ?>"></label>
    <label>Meta description<input name="meta_description" value="<?= e((string) $post['meta_description']) ?>"></label>
    <button class="btn btn-gold" type="submit">Save</button>
</form>
