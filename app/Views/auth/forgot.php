<h1>Reset your password</h1>
<form method="post" action="<?= e(url('forgot-password')) ?>" class="form-grid">
    <?= csrf_field() ?>
    <label>Email<input type="email" name="email" required></label>
    <button class="btn btn-gold" type="submit">Send a reset link</button>
</form>
