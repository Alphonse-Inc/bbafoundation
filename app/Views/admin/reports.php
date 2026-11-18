<form method="get">
    <label>Cycle
        <select name="cycle" onchange="this.form.submit()">
            <option value="0">All cycles</option>
            <?php foreach ($cycles as $cycle): ?><option value="<?= (int) $cycle['id'] ?>"<?= selected($cycleId, $cycle['id']) ?>><?= e($cycle['title']) ?></option><?php endforeach; ?>
        </select>
    </label>
</form>
<p><a href="<?= e(url('admin/reports.csv') . '?cycle=' . (int) $cycleId) ?>">Export CSV</a></p>
<p>Awards recorded: <?= (int) $summary['awards'] ?> · Students helped: <?= (int) $progress['helped'] ?> of <?= (int) $progress['target'] ?></p>
<p>Average numeric CGPA: <?= $summary['average'] === null ? 'No numeric CGPA yet' : e((string) round((float) $summary['average'], 2)) ?></p>
<p>Disbursement rows: <?= (int) ($summary['disbursed']['rows_count'] ?? 0) ?> · Sum of entered amounts: <?= $summary['disbursed']['amount_sum'] === null ? 'none entered' : e(money_input((string) $summary['disbursed']['amount_sum'])) ?></p>
<?php
$chart = static function (array $rows, string $labelKey): void {
    $max = 1;
    foreach ($rows as $row) {
        $max = max($max, (int) $row['total']);
    }
    echo '<svg viewBox="0 0 640 20" width="100%" height="' . (count($rows) * 28) . '" role="img">';
    $y = 4;
    foreach ($rows as $row) {
        $width = (int) round(((int) $row['total'] / $max) * 420);
        $label = (string) ($row[$labelKey] ?? '');
        echo '<text x="0" y="' . ($y + 12) . '" font-size="12">' . e($label !== '' ? $label : 'Blank') . '</text>';
        echo '<rect x="180" y="' . $y . '" width="' . $width . '" height="16" fill="#0F2A4A"></rect>';
        echo '<text x="' . (188 + $width) . '" y="' . ($y + 12) . '" font-size="12">' . (int) $row['total'] . '</text>';
        $y += 24;
    }
    echo '</svg>';
};
?>
<h2>By status</h2>
<?php $chart($summary['byStatus'], 'status'); ?>
<h2>By community</h2>
<?php $chart($summary['byCommunity'], 'community'); ?>
<h2>By sex</h2>
<?php $chart($summary['byGender'], 'sex'); ?>
<h2>By level</h2>
<?php $chart($summary['byLevel'], 'current_level'); ?>
<h2>By institution</h2>
<ul><?php foreach ($summary['byInstitution'] as $row): ?><li><?= e((string) $row['institution_name']) ?>: <?= (int) $row['total'] ?></li><?php endforeach; ?></ul>
<h2>Reviewer progress</h2>
<ul><?php foreach ($summary['reviewers'] as $row): ?><li><?= e(trim($row['surname'] . ' ' . $row['first_name'])) ?> · assigned <?= (int) $row['assigned'] ?> · scored <?= (int) $row['scored'] ?></li><?php endforeach; ?></ul>
