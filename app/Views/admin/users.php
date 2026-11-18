<form method="post" action="<?= e(url('admin/users')) ?>" class="form-grid card"><?= csrf_field() ?>
    <input type="hidden" name="id" value="0">
    <label>Surname<input name="surname" required></label>
    <label>First name<input name="first_name"></label>
    <label>Email<input type="email" name="email" required></label>
    <label>Password<input type="password" name="password" required autocomplete="new-password"></label>
    <label>Role<select name="role"><?php foreach ($roles as $role): ?><option value="<?= e($role['slug']) ?>"><?= e($role['name']) ?></option><?php endforeach; ?></select></label>
    <label>Status<select name="status"><option value="active">active</option><option value="disabled">disabled</option></select></label>
    <button class="btn btn-gold" type="submit">Create user</button>
</form>
<div class="table-wrap"><table>
    <tr><th>Name</th><th>Email</th><th>Role</th><th>Update</th></tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e(trim($row['surname'] . ' ' . $row['first_name'])) ?></td>
            <td><?= e($row['email']) ?></td>
            <td><?= e($row['role']) ?></td>
            <td>
                <form method="post" action="<?= e(url('admin/users')) ?>"><?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <input type="hidden" name="email" value="<?= e($row['email']) ?>">
                    <input name="surname" value="<?= e($row['surname']) ?>">
                    <input name="first_name" value="<?= e($row['first_name']) ?>">
                    <select name="role"><?php foreach ($roles as $role): ?><option value="<?= e($role['slug']) ?>"<?= selected($row['role'], $role['slug']) ?>><?= e($role['name']) ?></option><?php endforeach; ?></select>
                    <select name="status"><option value="active"<?= selected($row['status'], 'active') ?>>active</option><option value="disabled"<?= selected($row['status'], 'disabled') ?>>disabled</option></select>
                    <input type="password" name="password" placeholder="New password" autocomplete="new-password">
                    <button class="btn btn-small btn-navy" type="submit">Save</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table></div>
<?php $path = '/admin/users'; require dirname(__DIR__) . '/partials/pagination.php'; ?>
