<form method="post" action="<?= e(url('admin/brand')) ?>" enctype="multipart/form-data" class="form-grid"><?= csrf_field() ?>
    <?php foreach (['color_primary' => 'Navy', 'color_accent' => 'Gold', 'color_ivory' => 'Ivory', 'color_sand' => 'Sand', 'color_text' => 'Text', 'color_muted' => 'Muted'] as $key => $label): ?>
        <label><?= e($label) ?><input name="<?= e($key) ?>" value="<?= e(setting($key)) ?>"></label>
    <?php endforeach; ?>
    <label>Logo<input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp"></label>
    <label><input type="checkbox" name="extract_color" value="1"> Use the most common colour in the logo as the navy colour</label>
    <label>Favicon<input type="file" name="favicon" accept=".jpg,.jpeg,.png,.webp"></label>
    <button class="btn btn-gold" type="submit">Save brand</button>
</form>
<p>Current logo: <img src="<?= e(logo_src()) ?>" alt="Logo" width="160"></p>
