<section class="section"><div class="container card" style="max-width:640px">
    <h1>Two-factor authentication</h1>
    <p>Add this secret to an authenticator app, then enter the 6-digit code.</p>
    <p><code><?= e($secret) ?></code></p>
    <p class="fine"><?= e($uri) ?></p>
    <form method="post" class="form-grid">
        <?= csrf_field() ?>
        <label>Code<input name="code" inputmode="numeric" required></label>
        <button class="btn btn-gold" type="submit">Turn on</button>
    </form>
</div></section>
