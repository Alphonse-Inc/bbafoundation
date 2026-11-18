<h1>Choose a new password</h1>
<form method="post" action="<?= e(url('reset-password/' . $token)) ?>" class="form-grid">
    <?= csrf_field() ?>
    <label>New password<input type="password" name="password" required></label>
    <label>Confirm password<input type="password" name="password_confirmation" required></label>
    <button class="btn btn-gold" type="submit">Update password</button>
</form>
