<?php
/** @var array $checks */
/** @var array $blocking */
/** @var array|null $result */
/** @var string $suggestedBase */
$done = is_array($result) && !empty($result['ok']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install BBAFoundation</title>
    <style>
        body { margin: 0; font-family: Georgia, "Iowan Old Style", serif; background: #FAF8F3; color: #1E293B; }
        main { max-width: 760px; margin: 0 auto; padding: 2rem 1.25rem 4rem; }
        h1 { font-weight: 600; color: #0F2A4A; }
        .card { background: #fff; border: 1px solid #E6E1D6; border-radius: 16px; padding: 1.25rem; margin: 1rem 0; }
        label { display: block; font-size: 0.92rem; margin: 0.8rem 0 0.25rem; }
        input[type="text"], input[type="email"], input[type="password"], input[type="number"], input[type="url"] { width: 100%; box-sizing: border-box; padding: 0.7rem 0.8rem; border: 1px solid #CBD5E1; border-radius: 10px; font: inherit; }
        button { background: #0F2A4A; color: #FAF8F3; border: 0; border-radius: 999px; padding: 0.8rem 1.3rem; font: inherit; cursor: pointer; }
        button:disabled { opacity: 0.5; }
        .ok { color: #166534; } .bad { color: #9F1239; } .warn { color: #92400E; }
        .error { background: #FFE4E6; color: #9F1239; padding: 0.8rem 1rem; border-radius: 10px; }
        .note { background: #FEF3C7; padding: 0.8rem 1rem; border-radius: 10px; }
        code { font-family: ui-monospace, monospace; }
    </style>
</head>
<body>
<main>
    <p>BBAFoundation</p>
    <h1>Install the website</h1>
    <p>This creates the database tables, seeds the Foundation's existing wording, and creates the first Super Admin. It then locks itself.</p>

    <?php if ($done): ?>
        <div class="card">
            <h2>Installation is complete</h2>
            <p>Sign in with the administrator account you just created. Delete nothing on the old website until Legacy Import shows every file as imported.</p>
            <p>The cron key is stored in <code>config/config.php</code> and <code>storage/cron-key.txt</code>. You will need it for the scheduled jobs.</p>
            <p><a href="<?= e(url('/login')) ?>">Go to sign in</a></p>
        </div>
    <?php else: ?>
        <?php if (is_array($result) && empty($result['ok'])): ?>
            <p class="error"><?= e($result['error'] ?? 'Installation failed.') ?></p>
        <?php endif; ?>
        <div class="card">
            <h2>Server check</h2>
            <ul>
                <?php foreach ($checks as $check): ?>
                    <li class="<?= $check['ok'] ? 'ok' : ($check['blocking'] ? 'bad' : 'warn') ?>">
                        <?= e($check['label']) ?> — <?= e($check['detail']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <form method="post" action="<?= e(url('/install')) ?>" class="card">
            <?= csrf_field() ?>
            <h2>Database</h2>
            <p>On cPanel, create the database and user first, then enter them here. If the database does not exist and the user is allowed to create one, the installer will create it.</p>
            <label for="db_host">Host</label>
            <input id="db_host" name="db_host" type="text" value="<?= e(old('db_host', 'localhost')) ?>" required>
            <label for="db_port">Port</label>
            <input id="db_port" name="db_port" type="number" value="<?= e(old('db_port', '3306')) ?>" required>
            <label for="db_name">Database name</label>
            <input id="db_name" name="db_name" type="text" value="<?= e(old('db_name')) ?>" required>
            <label for="db_user">Database user</label>
            <input id="db_user" name="db_user" type="text" value="<?= e(old('db_user')) ?>" required>
            <label for="db_pass">Database password</label>
            <input id="db_pass" name="db_pass" type="password" value="" autocomplete="new-password">
            <h2>Site address</h2>
            <label for="base_url">BASE_URL</label>
            <input id="base_url" name="base_url" type="url" value="<?= e(old('base_url', $suggestedBase)) ?>" required>
            <h2>First Super Admin</h2>
            <label for="admin_surname">Surname</label>
            <input id="admin_surname" name="admin_surname" type="text" value="<?= e(old('admin_surname')) ?>" required>
            <label for="admin_first">First name</label>
            <input id="admin_first" name="admin_first" type="text" value="<?= e(old('admin_first')) ?>" required>
            <label for="admin_email">Email</label>
            <input id="admin_email" name="admin_email" type="email" value="<?= e(old('admin_email')) ?>" required>
            <label for="admin_password">Password</label>
            <input id="admin_password" name="admin_password" type="password" required autocomplete="new-password">
            <p>At least 10 characters, with upper and lower case letters, a number and a symbol.</p>
            <label><input type="checkbox" name="demo" value="1"> Add removable demo content (one labelled news post and one labelled draft event). Leave this off for the live site. It does not create applicants, awardees or amounts.</label>
            <p class="note">Demo content is clearly titled “DEMO — remove before go-live”.</p>
            <p><button type="submit" <?= $blocking !== [] ? 'disabled' : '' ?>>Install BBAFoundation</button></p>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
