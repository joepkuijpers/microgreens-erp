<?php
include 'includes/header.php';
include 'includes/language.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die(__('invalid_batch_id'));
}

$stmt = $db->prepare("
    SELECT
        g.*,
        i.item_name AS seed_name,
        i.category AS seed_category,
        i.unit AS seed_stock_unit,
        cp.crop_name AS crop_profile_name,
        cp.expected_yield_grams_per_tray,
        cp.grow_days_min,
        cp.grow_days_max
    FROM grow_batches g
    LEFT JOIN inventory i ON i.id = g.inventory_id
    LEFT JOIN crop_profiles cp ON cp.id = g.crop_profile_id
    WHERE g.id = :id
");
$stmt->execute([':id' => $id]);
$batch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$batch) {
    die(__('batch_not_found'));
}

$harvests = $db->prepare("
    SELECT
        id,
        harvest_date,
        weight_grams,
        quality_notes
    FROM harvests
    WHERE batch_id = :id
    ORDER BY harvest_date DESC, id DESC
");
$harvests->execute([':id' => $id]);
$harvestRows = $harvests->fetchAll(PDO::FETCH_ASSOC);

$transactions = $db->prepare("
    SELECT
        transaction_date,
        type,
        quantity_before,
        quantity_after,
        quantity_change,
        unit,
        note
    FROM inventory_transactions
    WHERE reference_type = 'grow_batch'
      AND reference_id = :id
    ORDER BY transaction_date DESC, id DESC
");
$transactions->execute([':id' => $id]);
$transactionRows = $transactions->fetchAll(PDO::FETCH_ASSOC);

$laborSummaryStmt = $db->prepare("
    SELECT
        COUNT(*) AS labor_entry_count,
        COALESCE(SUM(hours_worked), 0) AS total_hours,
        COALESCE(
            SUM(hours_worked * gross_hourly_rate),
            0
        ) AS total_gross_labor_value
    FROM labor_entries
    WHERE batch_id = :id
");
$laborSummaryStmt->execute([':id' => $id]);
$laborSummary = $laborSummaryStmt->fetch(PDO::FETCH_ASSOC);

$laborEntryCount = (int)($laborSummary['labor_entry_count'] ?? 0);
$totalLaborHours = (float)($laborSummary['total_hours'] ?? 0);
$totalGrossLaborValue = (float)($laborSummary['total_gross_labor_value'] ?? 0);

$totalHarvestedGrams = 0;

foreach ($harvestRows as $harvest) {
    $totalHarvestedGrams += (float)($harvest['weight_grams'] ?? 0);
}

$trayCount = (int)($batch['tray_count'] ?? 0);
$expectedYieldPerTray = (float)($batch['expected_yield_grams_per_tray'] ?? 0);
$expectedTotalYield = $trayCount * $expectedYieldPerTray;

$actualYieldPerTray = $trayCount > 0
    ? $totalHarvestedGrams / $trayCount
    : 0;

$yieldDifference = $totalHarvestedGrams - $expectedTotalYield;

$yieldDifferencePercent = $expectedTotalYield > 0
    ? ($yieldDifference / $expectedTotalYield) * 100
    : 0;

$actualGrowDays = null;

if (!empty($batch['sow_date']) && !empty($batch['harvest_date'])) {
    $sowTimestamp = strtotime($batch['sow_date']);
    $harvestTimestamp = strtotime($batch['harvest_date']);

    if ($sowTimestamp !== false && $harvestTimestamp !== false) {
        $actualGrowDays = (int)round(($harvestTimestamp - $sowTimestamp) / 86400);
    }
}

$expectedGrowDaysMin = (int)($batch['grow_days_min'] ?? 0);
$expectedGrowDaysMax = (int)($batch['grow_days_max'] ?? 0);
$growDaysStatus = __('none');

if ($actualGrowDays !== null && $expectedGrowDaysMin > 0 && $expectedGrowDaysMax > 0) {
    if ($actualGrowDays < $expectedGrowDaysMin) {
        $growDaysStatus = __('shorter_than_expected');
    } elseif ($actualGrowDays > $expectedGrowDaysMax) {
        $growDaysStatus = __('longer_than_expected');
    } else {
        $growDaysStatus = __('within_expected_range');
    }
}

$performanceStatus = __('no_expected_yield_available');

if ($expectedTotalYield > 0) {
    if ($yieldDifferencePercent >= 10) {
        $performanceStatus = __('above_expectation');
    } elseif ($yieldDifferencePercent <= -10) {
        $performanceStatus = __('below_expectation');
    } else {
        $performanceStatus = __('within_expectation');
    }
}
?>

<div class="main">
    <h1>🔍 <?= htmlspecialchars(__('batch_details')) ?></h1>

    <p>
        <a class="btn" href="grow_batches.php">← <?= htmlspecialchars(__('back_to_batch_management')) ?></a>
        <a class="btn" href="edit_batch.php?id=<?= urlencode((string)$batch['id']) ?>">✏️ <?= htmlspecialchars(__('edit')) ?></a>
        <a class="btn" href="harvest_batch.php?id=<?= urlencode((string)$batch['id']) ?>">🌾 <?= htmlspecialchars(__('harvest')) ?></a>
    </p>

    <div class="card">
        <h2><?= htmlspecialchars(__('batch_information')) ?></h2>

        <table>
            <tr><th>ID</th><td><?= htmlspecialchars((string)$batch['id']) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('crop')) ?></th><td><?= htmlspecialchars($batch['crop']) ?></td></tr>
            <tr>
                <th>Crop Profile</th>
                <td>
                    <?php if (!empty($batch['crop_profile_name'])): ?>
                        <a href="crop_profile_details.php?id=<?= urlencode((string)$batch['crop_profile_id']) ?>">
                            <?= htmlspecialchars($batch['crop_profile_name']) ?>
                        </a>
                        (ID <?= htmlspecialchars((string)$batch['crop_profile_id']) ?>)
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <tr><th><?= htmlspecialchars(__('status')) ?></th><td><?= htmlspecialchars($batch['status']) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('sowing_date')) ?></th><td><?= htmlspecialchars($batch['sow_date']) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('expected_harvest_date')) ?></th><td><?= htmlspecialchars($batch['expected_harvest_date'] ?? '-') ?></td></tr>
            <tr><th><?= htmlspecialchars(__('actual_harvest_date')) ?></th><td><?= htmlspecialchars($batch['harvest_date'] ?? '-') ?></td></tr>
            <tr><th><?= htmlspecialchars(__('tray_count')) ?></th><td><?= htmlspecialchars((string)$batch['tray_count']) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('tray_type')) ?></th><td><?= htmlspecialchars($batch['tray_type']) ?></td></tr>
        </table>
    </div>

    <div class="card">
        <h2><?= htmlspecialchars(__('batch_kpis')) ?></h2>

        <table>
            <tr><th><?= htmlspecialchars(__('total_harvested')) ?></th><td><?= number_format($totalHarvestedGrams, 2, ',', '.') ?> g</td></tr>
            <tr><th><?= htmlspecialchars(__('expected_yield_per_tray')) ?></th><td><?= number_format($expectedYieldPerTray, 2, ',', '.') ?> g</td></tr>
            <tr><th><?= htmlspecialchars(__('expected_total_yield')) ?></th><td><?= number_format($expectedTotalYield, 2, ',', '.') ?> g</td></tr>
            <tr><th><?= htmlspecialchars(__('actual_yield_per_tray')) ?></th><td><?= number_format($actualYieldPerTray, 2, ',', '.') ?> g</td></tr>
            <tr><th><?= htmlspecialchars(__('yield_difference')) ?></th><td><?= number_format($yieldDifference, 2, ',', '.') ?> g</td></tr>
            <tr><th><?= htmlspecialchars(__('yield_difference_percent')) ?></th><td><?= number_format($yieldDifferencePercent, 2, ',', '.') ?>%</td></tr>
        </table>
    </div>

    <div class="card">
        <h2><?= htmlspecialchars(__('batch_performance')) ?></h2>

        <table>
            <tr><th><?= htmlspecialchars(__('performance_status')) ?></th><td><?= htmlspecialchars($performanceStatus) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('actual_grow_days')) ?></th><td><?= htmlspecialchars($actualGrowDays !== null ? (string)$actualGrowDays : '-') ?></td></tr>
            <tr><th><?= htmlspecialchars(__('expected_grow_days')) ?></th><td><?= htmlspecialchars((string)$expectedGrowDaysMin) ?> - <?= htmlspecialchars((string)$expectedGrowDaysMax) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('grow_days_status')) ?></th><td><?= htmlspecialchars($growDaysStatus) ?></td></tr>
        </table>
    </div>

    <div class="card">
        <h2>⏱ Labor Summary</h2>

        <?php if ($laborEntryCount === 0): ?>
            <p>No labor registrations are linked to this batch.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Labor Entries</th>
                    <td><?= htmlspecialchars((string)$laborEntryCount) ?></td>
                </tr>
                <tr>
                    <th>Total Hours</th>
                    <td><?= number_format($totalLaborHours, 2, ',', '.') ?> h</td>
                </tr>
                <tr>
                    <th>Gross Labor Value</th>
                    <td>€ <?= number_format($totalGrossLaborValue, 2, ',', '.') ?></td>
                </tr>
            </table>
        <?php endif; ?>

        <p>
            <a class="btn" href="list_labor.php">
                View Labor Registrations
            </a>
        </p>
    </div>

    <div class="card">
        <h2><?= htmlspecialchars(__('seed_raw_material')) ?></h2>

        <table>
            <tr><th><?= htmlspecialchars(__('seed_item')) ?></th><td><?= htmlspecialchars($batch['seed_name'] ?? __('not_linked')) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('category')) ?></th><td><?= htmlspecialchars($batch['seed_category'] ?? '-') ?></td></tr>
            <tr>
                <th><?= htmlspecialchars(__('used')) ?></th>
                <td>
                    <?= number_format((float)($batch['seed_amount'] ?? 0), 2, ',', '.') ?>
                    <?= htmlspecialchars($batch['seed_unit'] ?? '') ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2><?= htmlspecialchars(__('harvests')) ?></h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= htmlspecialchars(__('date')) ?></th>
                    <th><?= htmlspecialchars(__('weight_grams')) ?></th>
                    <th><?= htmlspecialchars(__('quality_notes')) ?></th>
                    <th><?= htmlspecialchars(__('details')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($harvestRows) === 0): ?>
                    <tr><td colspan="5"><?= htmlspecialchars(__('no_harvests_registered')) ?></td></tr>
                <?php endif; ?>

                <?php foreach ($harvestRows as $harvest): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$harvest['id']) ?></td>
                        <td><?= htmlspecialchars($harvest['harvest_date']) ?></td>
                        <td><?= number_format((float)$harvest['weight_grams'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($harvest['quality_notes'] ?? '') ?></td>
                        <td>
                            <a href="harvest_details.php?id=<?= urlencode((string)$harvest['id']) ?>">
                                🔍 <?= htmlspecialchars(__('details')) ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2><?= htmlspecialchars(__('inventory_transactions_linked_to_batch')) ?></h2>

        <table>
            <thead>
                <tr>
                    <th><?= htmlspecialchars(__('date')) ?></th>
                    <th><?= htmlspecialchars(__('type')) ?></th>
                    <th><?= htmlspecialchars(__('before')) ?></th>
                    <th><?= htmlspecialchars(__('after')) ?></th>
                    <th><?= htmlspecialchars(__('difference')) ?></th>
                    <th><?= htmlspecialchars(__('unit')) ?></th>
                    <th><?= htmlspecialchars(__('note')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($transactionRows) === 0): ?>
                    <tr><td colspan="7"><?= htmlspecialchars(__('no_linked_inventory_transactions')) ?></td></tr>
                <?php endif; ?>

                <?php foreach ($transactionRows as $transaction): ?>
                    <tr>
                        <td><?= htmlspecialchars($transaction['transaction_date']) ?></td>
                        <td><?= htmlspecialchars($transaction['type']) ?></td>
                        <td><?= number_format((float)$transaction['quantity_before'], 2, ',', '.') ?></td>
                        <td><?= number_format((float)$transaction['quantity_after'], 2, ',', '.') ?></td>
                        <td><?= number_format((float)$transaction['quantity_change'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($transaction['unit'] ?? '') ?></td>
                        <td><?= htmlspecialchars($transaction['note'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p>
        <a class="btn" href="grow_batches.php">← <?= htmlspecialchars(__('back_to_batch_management')) ?></a>
        <a class="btn" href="edit_batch.php?id=<?= urlencode((string)$batch['id']) ?>">✏️ <?= htmlspecialchars(__('edit')) ?></a>
        <a class="btn" href="harvest_batch.php?id=<?= urlencode((string)$batch['id']) ?>">🌾 <?= htmlspecialchars(__('harvest')) ?></a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>