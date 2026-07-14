<?php
include 'includes/header.php';
include 'includes/language.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$inventoryItems = $db->query("
    SELECT
        id,
        item_name,
        quantity,
        unit
    FROM inventory
    ORDER BY item_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crop = trim($_POST['crop'] ?? '');
    $sowDate = trim($_POST['sow_date'] ?? '');
    $expectedHarvestDate = trim($_POST['expected_harvest_date'] ?? '');
    $trayCount = (int)($_POST['tray_count'] ?? 1);
    $trayType = trim($_POST['tray_type'] ?? '');
    $status = trim($_POST['status'] ?? 'Groeiend');
    $inventoryId = (int)($_POST['inventory_id'] ?? 0);
    $seedAmount = (float)($_POST['seed_amount'] ?? 0);

    if ($crop === '' || $sowDate === '' || $expectedHarvestDate === '' || $trayCount <= 0 || $inventoryId <= 0 || $seedAmount <= 0) {
        die(__('invalid_batch_input'));
    }

    $stmt = $db->prepare("
        SELECT
            id,
            quantity,
            unit
        FROM inventory
        WHERE id = :id
    ");
    $stmt->execute([':id' => $inventoryId]);
    $seedItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$seedItem) {
        die(__('seed_inventory_not_found'));
    }

    $quantityBefore = (float)$seedItem['quantity'];
    $quantityAfter = $quantityBefore - $seedAmount;

    if ($quantityAfter < 0) {
        die(__('insufficient_seed_inventory'));
    }

    $insert = $db->prepare("
        INSERT INTO grow_batches
            (crop, sow_date, expected_harvest_date, tray_count, tray_type, status, inventory_id, seed_amount, seed_unit)
        VALUES
            (:crop, :sow_date, :expected_harvest_date, :tray_count, :tray_type, :status, :inventory_id, :seed_amount, :seed_unit)
    ");

    $insert->execute([
        ':crop' => $crop,
        ':sow_date' => $sowDate,
        ':expected_harvest_date' => $expectedHarvestDate,
        ':tray_count' => $trayCount,
        ':tray_type' => $trayType,
        ':status' => $status,
        ':inventory_id' => $inventoryId,
        ':seed_amount' => $seedAmount,
        ':seed_unit' => $seedItem['unit']
    ]);

    $batchId = $db->lastInsertId();

    $updateInventory = $db->prepare("
        UPDATE inventory
        SET quantity = :quantity
        WHERE id = :id
    ");

    $updateInventory->execute([
        ':quantity' => $quantityAfter,
        ':id' => $inventoryId
    ]);

    $log = $db->prepare("
        INSERT INTO inventory_transactions
            (inventory_id, type, quantity_change, quantity_before, quantity_after, unit, note, reference_type, reference_id)
        VALUES
            (:inventory_id, :type, :quantity_change, :quantity_before, :quantity_after, :unit, :note, :reference_type, :reference_id)
    ");

    $log->execute([
        ':inventory_id' => $inventoryId,
        ':type' => 'VERBRUIK',
        ':quantity_change' => -$seedAmount,
        ':quantity_before' => $quantityBefore,
        ':quantity_after' => $quantityAfter,
        ':unit' => $seedItem['unit'],
        ':note' => __('seed_used_for_batch') . ': ' . $crop,
        ':reference_type' => 'grow_batch',
        ':reference_id' => $batchId
    ]);

    header('Location: grow_batches.php');
    exit;
}
?>

<div class="main">
    <h1>🌱 <?= htmlspecialchars(__('new_batch')) ?></h1>

    <div class="card">
        <form method="post">
            <p>
                <?= htmlspecialchars(__('crop')) ?><br>
                <input type="text" name="crop" required>
            </p>

            <p>
                <?= htmlspecialchars(__('sowing_date')) ?><br>
                <input type="date" name="sow_date" required>
            </p>

            <p>
                <?= htmlspecialchars(__('expected_harvest_date')) ?><br>
                <input type="date" name="expected_harvest_date" required>
            </p>

            <p>
                <?= htmlspecialchars(__('tray_count')) ?><br>
                <input type="number" name="tray_count" value="1" min="1" required>
            </p>

            <p>
                <?= htmlspecialchars(__('tray_type')) ?><br>
                <input type="text" name="tray_type" value="1020">
            </p>

            <p>
                <?= htmlspecialchars(__('seed_inventory')) ?><br>
                <select name="inventory_id" required>
                    <option value=""><?= htmlspecialchars(__('choose_seed_inventory')) ?></option>
                    <?php foreach ($inventoryItems as $item): ?>
                        <option value="<?= htmlspecialchars((string)$item['id']) ?>">
                            <?= htmlspecialchars($item['item_name']) ?>
                            (<?= number_format((float)$item['quantity'], 2, ',', '.') ?>
                            <?= htmlspecialchars($item['unit']) ?> <?= htmlspecialchars(__('available')) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <?= htmlspecialchars(__('seed_usage')) ?><br>
                <input type="number" step="0.01" name="seed_amount" required>
            </p>

            <p>
                <?= htmlspecialchars(__('status')) ?><br>
                <select name="status">
                    <option value="Gepland"><?= htmlspecialchars(__('planned')) ?></option>
                    <option value="Groeiend" selected><?= htmlspecialchars(__('growing')) ?></option>
                    <option value="Oogstklaar"><?= htmlspecialchars(__('ready_to_harvest')) ?></option>
                    <option value="Geoogst"><?= htmlspecialchars(__('harvested')) ?></option>
                </select>
            </p>

            <p>
                <button class="btn" type="submit">💾 <?= htmlspecialchars(__('save_batch')) ?></button>
                <a class="btn" href="grow_batches.php"><?= htmlspecialchars(__('back')) ?></a>
            </p>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>