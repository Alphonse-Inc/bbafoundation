<h1>Sign in</h1>
<form method="post" action="<?= e(url('login')) ?>" class="form-grid">
    <?= csrf_field() ?>
    <label>Email<input type="email" name="email" required value="<?= e(old('email')) ?>"></label>
    <label>Password<input type="password" name="password" required></label>
    <label><input type="checkbox" name="remember" value="1"> Remember me on this device</label>
    <button class="btn btn-gold" type="submit">Sign in</button>
</form>
<p><a href="<?= e(url('forgot-password')) ?>">Forgot password</a></p>
<p><a href="<?= e(url('register')) ?>">Create an applicant account</a></p>
<p class="fine"><?= e(setting('free_notice')) ?></p>
