<h1>Create an account</h1>
<p>Enter your surname first, then your first name, as the official form asks.</p>
<p class="notice"><?= e(setting('free_notice')) ?></p>
<form method="post" action="<?= e(url('register')) ?>" class="form-grid">
    <?= csrf_field() ?>
    <label>Surname<input name="surname" required value="<?= e(old('surname')) ?>"></label>
    <label>First name<input name="first_name" required value="<?= e(old('first_name')) ?>"></label>
    <label>Email<input type="email" name="email" required value="<?= e(old('email')) ?>"></label>
    <label>Phone number<input name="phone" required value="<?= e(old('phone')) ?>" inputmode="tel"></label>
    <label>Gender
        <select name="gender" required>
            <option value="">Choose</option>
            <option value="male"<?= selected(old('gender'), 'male') ?>>Male</option>
            <option value="female"<?= selected(old('gender'), 'female') ?>>Female</option>
        </select>
    </label>
    <label>Password<input type="password" name="password" required></label>
    <label>Confirm password<input type="password" name="password_confirmation" required></label>
    <label><input type="checkbox" name="consent" value="1" required> I consent to BBAFoundation processing my personal data to administer a scholarship, as described in the <a href="<?= e(url('privacy-policy')) ?>">Privacy Policy</a>.</label>
    <button class="btn btn-gold" type="submit">Create account</button>
</form>
