<?php $event = $event ?? ['id' => 0, 'title' => '', 'slug' => '', 'excerpt' => '', 'body' => '', 'venue' => '', 'starts_at' => '', 'ends_at' => '', 'status' => 'draft', 'meta_title' => '', 'meta_description' => '']; ?>
<form method="post" action="<?= e(url('admin/events/' . (int) ($event['id'] ?? 0))) ?>" class="form-grid"><?= csrf_field() ?>
    <label>Title<input name="title" required value="<?= e((string) $event['title']) ?>"></label>
    <label>Slug<input name="slug" value="<?= e((string) $event['slug']) ?>"></label>
    <label>Status<select name="status"><option value="draft"<?= selected($event['status'], 'draft') ?>>Draft</option><option value="published"<?= selected($event['status'], 'published') ?>>Published</option></select></label>
    <label>Venue<input name="venue" value="<?= e((string) $event['venue']) ?>"></label>
    <label>Starts<input name="starts_at" value="<?= e(\BBAF\Core\Dates::inputValue($event['starts_at'] ?: null)) ?>" placeholder="Leave blank until a date is published"></label>
    <label>Ends<input name="ends_at" value="<?= e(\BBAF\Core\Dates::inputValue($event['ends_at'] ?: null)) ?>"></label>
    <label>Excerpt<textarea name="excerpt"><?= e((string) $event['excerpt']) ?></textarea></label>
    <div data-rich><label>Body<textarea name="body" rows="10"><?= e((string) $event['body']) ?></textarea></label></div>
    <label>Meta title<input name="meta_title" value="<?= e((string) $event['meta_title']) ?>"></label>
    <label>Meta description<input name="meta_description" value="<?= e((string) $event['meta_description']) ?>"></label>
    <button class="btn btn-gold" type="submit">Save</button>
</form>
