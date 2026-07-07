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
        cp.crop_name AS crop_profile_name
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
    SELECT *
    FROM harvests
    WHERE batch_id = :id
    ORDER BY harvest_date DESC, id DESC
");
$harvests->execute([':id' => $id]);
$harvestRows = $harvests->fetchAll(PDO::FETCH_ASSOC);

$transactions = $db->prepare("
    SELECT *
    FROM inventory_transactions
    WHERE reference_type = 'grow_batch'
      AND reference_id = :id
    ORDER BY transaction_date DESC, id DESC
");
$transactions->execute([':id' => $id]);
$transactionRows = $transactions->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>🔍 <?= htmlspecialchars(__('batch_details')) ?></h1>

    <p>
        <a class="btn" href="grow_batches.php">← <?= htmlspecialchars(__('back_to_batch_management')) ?></a>
        <a class="btn" href="edit_batch.php?id=<?= urlencode($batch['id']) ?>">✏️ <?= htmlspecialchars(__('edit')) ?></a>
        <a class="btn" href="harvest_batch.php?id=<?= urlencode($batch['id']) ?>">🌾 <?= htmlspecialchars(__('harvest')) ?></a>
    </p>

    <div class="card">
        <h2><?= htmlspecialchars(__('batch_information')) ?></h2>
        <table>
            <tr><th>ID</th><td><?= htmlspecialchars($batch['id']) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('crop')) ?></th><td><?= htmlspecialchars($batch['crop']) ?></td></tr>
            <tr>
                <th>Crop Profile</th>
                <td>
                    <?php
                    if (!empty($batch['crop_profile_name'])) {
                        echo htmlspecialchars($batch['crop_profile_name'])
                            . ' (ID '
                            . htmlspecialchars((string)$batch['crop_profile_id'])
                            . ')';
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
            </tr>
            <tr><th><?= htmlspecialchars(__('status')) ?></th><td><?= htmlspecialchars($batch['status']) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('sowing_date')) ?></th><td><?= htmlspecialchars($batch['sow_date']) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('expected_harvest_date')) ?></th><td><?= htmlspecialchars($batch['expected_harvest_date'] ?? '-') ?></td></tr>
            <tr><th><?= htmlspecialchars(__('actual_harvest_date')) ?></th><td><?= htmlspecialchars($batch['harvest_date'] ?? '-') ?></td></tr>
            <tr><th><?= htmlspecialchars(__('tray_count')) ?></th><td><?= htmlspecialchars($batch['tray_count']) ?></td></tr>
            <tr><th><?= htmlspecialchars(__('tray_type')) ?></th><td><?= htmlspecialchars($batch['tray_type']) ?></td></tr>
        </table>
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
                </tr>
            </thead>
            <tbody>
                <?php if (count($harvestRows) === 0): ?>
                    <tr><td colspan="4"><?= htmlspecialchars(__('no_harvests_registered')) ?></td></tr>
                <?php endif; ?>

                <?php foreach ($harvestRows as $harvest): ?>
                    <tr>
                        <td><?= htmlspecialchars($harvest['id']) ?></td>
                        <td><?= htmlspecialchars($harvest['harvest_date']) ?></td>
                        <td><?= number_format((float)$harvest['weight_grams'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($harvest['quality_notes'] ?? '') ?></td>
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
</div>

<?php include 'includes/footer.php'; ?>