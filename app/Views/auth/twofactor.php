<h1>Two-factor code</h1>
<form method="post" action="<?= e(url('two-factor')) ?>" class="form-grid">
    <?= csrf_field() ?>
    <label>6-digit code<input name="code" inputmode="numeric" required autocomplete="one-time-code"></label>
    <button class="btn btn-gold" type="submit">Continue</button>
</form>
