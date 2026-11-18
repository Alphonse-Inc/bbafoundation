<form method="post" action="<?= e(url('admin/home')) ?>"><?= csrf_field() ?>
    <h2>Announcement and founder story</h2>
    <label>Announcement banner<input name="announcement_banner" value="<?= e(setting('announcement_banner')) ?>"></label>
    <label>Founder story<textarea name="founder_story"><?= e(setting('founder_story')) ?></textarea></label>
    <p class="fine"><?= e(setting('founder_story_status')) ?>. The public About page hides this section while the story is empty.</p>
    <label>Volunteers count<input name="volunteers_count" value="<?= e(setting('volunteers_count', '0')) ?>"></label>
    <label>Sponsors count<input name="sponsors_count" value="<?= e(setting('sponsors_count', '0')) ?>"></label>
    <label>Students helped
        <select name="students_helped_mode"><option value="auto"<?= selected(setting('students_helped_mode'), 'auto') ?>>Count accepted awards</option><option value="manual"<?= selected(setting('students_helped_mode'), 'manual') ?>>Manual number</option></select>
    </label>
    <label>Manual number<input name="students_helped_manual" value="<?= e(setting('students_helped_manual', '0')) ?>"></label>
    <h2>Hero slides</h2>
    <?php foreach ($slides as $slide): ?>
        <fieldset>
            <label>Kicker<input name="slide_kicker[<?= (int) $slide['id'] ?>]" value="<?= e($slide['kicker']) ?>"></label>
            <label>Title<input name="slide_title[<?= (int) $slide['id'] ?>]" value="<?= e($slide['title']) ?>"></label>
            <label>Subtitle<textarea name="slide_subtitle[<?= (int) $slide['id'] ?>]"><?= e($slide['subtitle']) ?></textarea></label>
            <label><input type="checkbox" name="slide_visible[<?= (int) $slide['id'] ?>]" value="1" <?= checked((int) $slide['is_visible'] === 1) ?>> Visible</label>
        </fieldset>
    <?php endforeach; ?>
    <h2>Blocks</h2>
    <?php foreach ($blocks as $block): ?>
        <fieldset>
            <p class="fine"><?= e($block['group_key']) ?></p>
            <label>Title<input name="block_title[<?= (int) $block['id'] ?>]" value="<?= e($block['title']) ?>"></label>
            <label>Body<textarea name="block_body[<?= (int) $block['id'] ?>]"><?= e((string) $block['body']) ?></textarea></label>
            <label><input type="checkbox" name="block_visible[<?= (int) $block['id'] ?>]" value="1" <?= checked((int) $block['is_visible'] === 1) ?>> Visible</label>
        </fieldset>
    <?php endforeach; ?>
    <h2>Affiliates</h2>
    <p class="fine">Names were not on the previous website. Label them here.</p>
    <?php foreach ($affiliates as $affiliate): ?>
        <fieldset>
            <label>Name<input name="affiliate_name[<?= (int) $affiliate['id'] ?>]" value="<?= e($affiliate['name']) ?>"></label>
            <label>Link<input name="affiliate_link[<?= (int) $affiliate['id'] ?>]" value="<?= e($affiliate['link_url']) ?>"></label>
            <label><input type="checkbox" name="affiliate_visible[<?= (int) $affiliate['id'] ?>]" value="1" <?= checked((int) $affiliate['is_visible'] === 1) ?>> Visible</label>
        </fieldset>
    <?php endforeach; ?>
    <button class="btn btn-gold" type="submit">Save home content</button>
</form>
