<?php
require_once __DIR__ . '/app/includes/auth.php';
auth_require_login();
include 'app/db_connect.php';
include 'app/includes/language.php';
include 'app/includes/header.php';
$activityCodes = ['SOWING','WATERING','BLACKOUT','LIGHTING','CROP_CARE','HARVESTING','PACKAGING','DELIVERY','CLEANING','MAINTENANCE','ADMINISTRATION','RESEARCH_DEVELOPMENT','PURCHASING','MARKETING','TRAINING','OTHER'];
$typeCodes = ['medewerker','stage','vrijwilliger'];

$typeFilter = $_GET['type'] ?? '';
$where = '';
$params = [];
if (in_array($typeFilter, $typeCodes, true)) {
    $where = ' WHERE l.worker_type = :wt ';
    $params[':wt'] = $typeFilter;
}

$laborEntries = $db->prepare("
    SELECT
        l.id,
        l.work_date,
        l.hours_worked,
        l.activity,
        l.batch_id,
        l.gross_hourly_rate,
        l.notes,
        l.created_at,
        l.worker_type,
        g.crop AS batch_crop
    FROM labor_entries l
    LEFT JOIN grow_batches g
        ON g.id = l.batch_id
    $where
    ORDER BY
        l.work_date DESC,
        l.id DESC
");
$laborEntries->execute($params);
$laborEntries = $laborEntries->fetchAll(PDO::FETCH_ASSOC);

$laborTotals = $db->prepare("
    SELECT
        COALESCE(SUM(l.hours_worked), 0) AS total_hours,
        COALESCE(SUM(l.hours_worked * l.gross_hourly_rate), 0) AS total_gross_labor_value
    FROM labor_entries l
    $where
");
$laborTotals->execute($params);
$laborTotals = $laborTotals->fetch(PDO::FETCH_ASSOC);

$totalHours = (float)($laborTotals['total_hours'] ?? 0);
$totalGrossLaborValue = (float)($laborTotals['total_gross_labor_value'] ?? 0);
?>

<div class="main">

    <h1>⏱ <?= htmlspecialchars(__('labor_title')) ?></h1>

    <p>
        <a class="btn" href="add_labor_form.php">
            ➕ <?= htmlspecialchars(__('labor_register')) ?>
        </a>

        <a class="btn" href="dashboard.php">
            ← <?= htmlspecialchars(__('back')) ?>
        </a>
    </p>

    <div class="card">
        <p>
            <strong><?= htmlspecialchars(__('labor_filter_type')) ?></strong>
            <a class="btn" href="list_labor.php"><?= htmlspecialchars(__('labor_all')) ?></a>
            <?php foreach ($typeCodes as $tc): ?>
                <a class="btn" href="list_labor.php?type=<?= htmlspecialchars($tc) ?>"><?= htmlspecialchars(__('type_' . $tc)) ?></a>
            <?php endforeach; ?>
            <?php if ($typeFilter !== ''): ?>
                <em>(<?= htmlspecialchars(__('labor_filter')) ?>: <?= htmlspecialchars(__('type_' . $typeFilter)) ?>)</em>
            <?php endif; ?>
        </p>
    </div>

    <div class="grid">

        <div class="tile">
            <h2>⏱ <?= htmlspecialchars(__('labor_total_hours')) ?></h2>
            <p>
                <?= number_format($totalHours, 2, ',', '.') ?> <?= htmlspecialchars(__('labor_hours_unit')) ?>
            </p>
        </div>

        <div class="tile">
            <h2>💶 <?= htmlspecialchars(__('labor_total_value')) ?></h2>
            <p>
                € <?= number_format($totalGrossLaborValue, 2, ',', '.') ?>
            </p>
        </div>

    </div>

    <div class="card">
        <p>
            <strong><?= htmlspecialchars(__('labor_financial_explanation')) ?></strong>
            <?= htmlspecialchars(__('labor_financial_body')) ?>
            <br><br>
            <strong><?= htmlspecialchars(__('labor_formula')) ?></strong>
            <br><br>
            <?= htmlspecialchars(__('labor_disclaimer')) ?>
            <?= htmlspecialchars(__('labor_volunteer_note')) ?>
        </p>
    </div>

    <div class="card labor-table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?= htmlspecialchars(__('labor_work_date')) ?></th>
                        <th><?= htmlspecialchars(__('labor_activity')) ?></th>
                        <th><?= htmlspecialchars(__('labor_type')) ?></th>
                        <th><?= htmlspecialchars(__('labor_batch')) ?></th>
                        <th><?= htmlspecialchars(__('labor_hours_worked')) ?></th>
                        <th><?= htmlspecialchars(__('labor_rate')) ?></th>
                        <th><?= htmlspecialchars(__('labor_value')) ?></th>
                        <th><?= htmlspecialchars(__('notes')) ?></th>
                    </tr>
                </thead>
                <tbody>
<?php if (empty($laborEntries)): ?>
    <tr>
        <td colspan="9"><?= htmlspecialchars(__('labor_no_entries')) ?></td>
    </tr>
<?php endif; ?>

<?php foreach ($laborEntries as $entry): ?>
<?php
$hoursWorked = (float)$entry['hours_worked'];
$grossHourlyRate = (float)$entry['gross_hourly_rate'];
$grossLaborValue = $hoursWorked * $grossHourlyRate;
$activityCode = (string)$entry['activity'];
$activityLabel = __('act_' . $activityCode);
if (strpos($activityLabel, '[[') === 0) { $activityLabel = $activityCode; }
$wtCode = (string)($entry['worker_type'] ?? 'medewerker');
?>
<tr>
    <td><?= htmlspecialchars((string)$entry['id']) ?></td>
    <td><?= htmlspecialchars((string)$entry['work_date']) ?></td>
    <td><?= htmlspecialchars($activityLabel) ?></td>
    <td><?= htmlspecialchars(__('type_' . $wtCode)) ?></td>
    <td>
        <?php if (!empty($entry['batch_id'])): ?>
            <a href="batch_details.php?id=<?= urlencode((string)$entry['batch_id']) ?>">
                #<?= htmlspecialchars((string)$entry['batch_id']) ?>
                <?php if (!empty($entry['batch_crop'])): ?>
                    — <?= htmlspecialchars((string)$entry['batch_crop']) ?>
                <?php endif; ?>
            </a>
        <?php else: ?>
            -
        <?php endif; ?>
    </td>
    <td><?= number_format($hoursWorked, 2, ',', '.') ?> <?= htmlspecialchars(__('labor_hours_unit')) ?></td>
    <td>€ <?= number_format($grossHourlyRate, 2, ',', '.') ?></td>
    <td><strong>€ <?= number_format($grossLaborValue, 2, ',', '.') ?></strong></td>
    <td><?= htmlspecialchars((string)($entry['notes'] ?? '-')) ?></td>
</tr>
<?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include 'app/includes/footer.php'; ?>