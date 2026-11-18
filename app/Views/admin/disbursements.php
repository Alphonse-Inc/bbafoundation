<p><a href="<?= e(url('admin/disbursements/export')) ?>">Export CSV</a></p>
<p class="fine">Leave the amount blank until the secretariat has a figure. Nothing is invented.</p>
<form method="post" action="<?= e(url('admin/disbursements')) ?>" enctype="multipart/form-data" class="form-grid card"><?= csrf_field() ?>
    <label>Awardee
        <select name="awardee_id"><?php foreach ($awardees as $awardee): ?><option value="<?= (int) $awardee['id'] ?>"><?= e($awardee['reference'] . ' ' . $awardee['surname']) ?></option><?php endforeach; ?></select>
    </label>
    <label>Amount<input name="amount" inputmode="decimal" placeholder="Leave blank if unknown"></label>
    <label>Currency<input name="currency" value="NGN"></label>
    <label>Paid on<input name="paid_on" placeholder="DD/MM/YYYY"></label>
    <label>Method<input name="method"></label>
    <label>Reference<input name="reference_no"></label>
    <label>Payee (institution or student account)<input name="payee"></label>
    <label>Notes<textarea name="notes"></textarea></label>
    <label>Receipt<input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png"></label>
    <button class="btn btn-gold" type="submit">Record</button>
</form>
<div class="table-wrap"><table>
    <tr><th>Reference</th><th>Amount</th><th>Paid</th><th>Method</th><th>Reference no</th><th>Payee</th></tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e((string) $row['reference']) ?></td>
            <td><?= $row['amount'] === null || $row['amount'] === '' ? 'Not entered' : e(money_input((string) $row['amount']) . ' ' . $row['currency']) ?></td>
            <td><?= e(display_date((string) $row['paid_on'])) ?></td>
            <td><?= e($row['method']) ?></td>
            <td><?= e($row['reference_no']) ?></td>
            <td><?= e($row['payee']) ?></td>
        </tr>
    <?php endforeach; ?>
</table></div>
<?php $path = '/admin/disbursements'; require dirname(__DIR__) . '/partials/pagination.php'; ?>
