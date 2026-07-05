<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die(t('invalid_batch_id'));
}

$stmt = $db->prepare("
    SELECT
        g.*,
        i.item_name AS seed_name,
        i.category AS seed_category,
        i.unit AS seed_stock_unit
    FROM grow_batches g
    LEFT JOIN inventory i ON i.id = g.inventory_id
    WHERE g.id = :id
");
$stmt->execute([':id' => $id]);
$batch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$batch) {
    die(t('batch_not_found'));
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
    <h1>🔍 <?= htmlspecialchars(t('batch_details')) ?></h1>

    <p>
        <a class="btn" href="grow_batches.php">← <?= htmlspecialchars(t('back_to_batch_management')) ?></a>
        <a class="btn" href="edit_batch.php?id=<?= urlencode($batch['id']) ?>">✏️ <?= htmlspecialchars(t('edit')) ?></a>
        <a class="btn" href="harvest_batch.php?id=<?= urlencode($batch['id']) ?>">🌾 <?= htmlspecialchars(t('harvest')) ?></a>
    </p>

    <div class="card">
        <h2><?= htmlspecialchars(t('batch_information')) ?></h2>
        <table>
            <tr><th>ID</th><td><?= htmlspecialchars($batch['id']) ?></td></tr>
            <tr><th><?= htmlspecialchars(t('crop')) ?></th><td><?= htmlspecialchars($batch['crop']) ?></td></tr>
            <tr><th><?= htmlspecialchars(t('status')) ?></th><td><?= htmlspecialchars($batch['status']) ?></td></tr>
            <tr><th><?= htmlspecialchars(t('sowing_date')) ?></th><td><?= htmlspecialchars($batch['sow_date']) ?></td></tr>
            <tr><th><?= htmlspecialchars(t('expected_harvest_date')) ?></th><td><?= htmlspecialchars($batch['expected_harvest_date'] ?? '-') ?></td></tr>
            <tr><th><?= htmlspecialchars(t('actual_harvest_date')) ?></th><td><?= htmlspecialchars($batch['harvest_date'] ?? '-') ?></td></tr>
            <tr><th><?= htmlspecialchars(t('tray_count')) ?></th><td><?= htmlspecialchars($batch['tray_count']) ?></td></tr>
            <tr><th><?= htmlspecialchars(t('tray_type')) ?></th><td><?= htmlspecialchars($batch['tray_type']) ?></td></tr>
        </table>
    </div>

    <div class="card">
        <h2><?= htmlspecialchars(t('seed_raw_material')) ?></h2>
        <table>
            <tr><th><?= htmlspecialchars(t('seed_item')) ?></th><td><?= htmlspecialchars($batch['seed_name'] ?? t('not_linked')) ?></td></tr>
            <tr><th><?= htmlspecialchars(t('category')) ?></th><td><?= htmlspecialchars($batch['seed_category'] ?? '-') ?></td></tr>
            <tr>
                <th><?= htmlspecialchars(t('used')) ?></th>
                <td>
                    <?= number_format((float)($batch['seed_amount'] ?? 0), 2, ',', '.') ?>
                    <?= htmlspecialchars($batch['seed_unit'] ?? '') ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2><?= htmlspecialchars(t('harvests')) ?></h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= htmlspecialchars(t('date')) ?></th>
                    <th><?= htmlspecialchars(t('weight_grams')) ?></th>
                    <th><?= htmlspecialchars(t('quality_notes')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($harvestRows) === 0): ?>
                    <tr><td colspan="4"><?= htmlspecialchars(t('no_harvests_registered')) ?></td></tr>
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
        <h2><?= htmlspecialchars(t('inventory_transactions_linked_to_batch')) ?></h2>
        <table>
            <thead>
                <tr>
                    <th><?= htmlspecialchars(t('date')) ?></th>
                    <th><?= htmlspecialchars(t('type')) ?></th>
                    <th><?= htmlspecialchars(t('before')) ?></th>
                    <th><?= htmlspecialchars(t('after')) ?></th>
                    <th><?= htmlspecialchars(t('difference')) ?></th>
                    <th><?= htmlspecialchars(t('unit')) ?></th>
                    <th><?= htmlspecialchars(t('note')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($transactionRows) === 0): ?>
                    <tr><td colspan="7"><?= htmlspecialchars(t('no_linked_inventory_transactions')) ?></td></tr>
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