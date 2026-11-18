<?php $trustee = $trustee ?? ['id' => 0, 'slug' => '', 'sort_order' => 0, 'role_label' => '', 'listing_name' => '', 'profile_name' => '', 'credential' => '', 'summary' => '', 'full_profile' => '', 'photo_file' => '', 'social_facebook' => '', 'social_twitter' => '', 'social_youtube' => '', 'social_linkedin' => '', 'is_active' => 1, 'meta_description' => '']; ?>
<form method="post" action="<?= e(url('admin/trustees/' . (int) ($trustee['id'] ?? 0))) ?>" enctype="multipart/form-data" class="form-grid"><?= csrf_field() ?>
    <label>Listing name<input name="listing_name" required value="<?= e((string) $trustee['listing_name']) ?>"></label>
    <label>Profile name<input name="profile_name" value="<?= e((string) $trustee['profile_name']) ?>"></label>
    <label>Slug<input name="slug" value="<?= e((string) $trustee['slug']) ?>"></label>
    <label>Order<input name="sort_order" value="<?= (int) $trustee['sort_order'] ?>"></label>
    <label>Role<input name="role_label" value="<?= e((string) $trustee['role_label']) ?>"></label>
    <label>Credential<input name="credential" value="<?= e((string) $trustee['credential']) ?>"></label>
    <label>Summary<textarea name="summary"><?= e((string) $trustee['summary']) ?></textarea></label>
    <div data-rich><label>Full profile<textarea name="full_profile" rows="12"><?= e((string) $trustee['full_profile']) ?></textarea></label></div>
    <label>Photo filename or path<input name="photo_file" value="<?= e((string) $trustee['photo_file']) ?>"></label>
    <label>Replace photo<input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp"></label>
    <label>Facebook<input name="social_facebook" value="<?= e((string) $trustee['social_facebook']) ?>"></label>
    <label>Twitter<input name="social_twitter" value="<?= e((string) $trustee['social_twitter']) ?>"></label>
    <label>YouTube<input name="social_youtube" value="<?= e((string) $trustee['social_youtube']) ?>"></label>
    <label>LinkedIn<input name="social_linkedin" value="<?= e((string) $trustee['social_linkedin']) ?>"></label>
    <label>Meta description<input name="meta_description" value="<?= e((string) $trustee['meta_description']) ?>"></label>
    <label><input type="checkbox" name="is_active" value="1" <?= checked((int) $trustee['is_active'] === 1) ?>> Active</label>
    <button class="btn btn-gold" type="submit">Save</button>
</form>
