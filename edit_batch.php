<?php
include 'includes/language.php';
include 'db_connect.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die(__('invalid_batch_id'));
}

$stmt = $db->prepare("
    SELECT
        id,
        crop,
        sow_date,
        expected_harvest_date,
        tray_count,
        tray_type,
        status,
        inventory_id,
        seed_amount,
        seed_unit
    FROM grow_batches
    WHERE id = :id
");
$stmt->execute([':id' => $id]);
$batch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$batch) {
    die(__('batch_not_found'));
}

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
    $newInventoryId = (int)($_POST['inventory_id'] ?? 0);
    $newSeedAmount = (float)($_POST['seed_amount'] ?? 0);

    if ($crop === '' || $sowDate === '' || $trayCount <= 0 || $newInventoryId <= 0 || $newSeedAmount < 0) {
        die(__('invalid_batch_input'));
    }

    $oldInventoryId = (int)($batch['inventory_id'] ?? 0);
    $oldSeedAmount = (float)($batch['seed_amount'] ?? 0);

    $db->beginTransaction();

    try {
        if ($oldInventoryId > 0 && $oldSeedAmount > 0) {
            $stmt = $db->prepare("
                SELECT
                    id,
                    quantity,
                    unit
                FROM inventory
                WHERE id = :id
            ");
            $stmt->execute([':id' => $oldInventoryId]);
            $oldItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($oldItem) {
                $before = (float)$oldItem['quantity'];
                $after = $before + $oldSeedAmount;

                $db->prepare("
                    UPDATE inventory
                    SET quantity = :quantity
                    WHERE id = :id
                ")->execute([
                    ':quantity' => $after,
                    ':id' => $oldInventoryId
                ]);

                $db->prepare("
                    INSERT INTO inventory_transactions
                        (inventory_id, type, quantity_change, quantity_before, quantity_after, unit, note, reference_type, reference_id)
                    VALUES
                        (:inventory_id, :type, :quantity_change, :quantity_before, :quantity_after, :unit, :note, :reference_type, :reference_id)
                ")->execute([
                    ':inventory_id' => $oldInventoryId,
                    ':type' => 'BATCH_EDIT_RETOUR',
                    ':quantity_change' => $oldSeedAmount,
                    ':quantity_before' => $before,
                    ':quantity_after' => $after,
                    ':unit' => $oldItem['unit'],
                    ':note' => 'Oude zaadverbruik teruggezet bij batchbewerking',
                    ':reference_type' => 'grow_batch',
                    ':reference_id' => $id
                ]);
            }
        }

        $stmt = $db->prepare("
            SELECT
                id,
                quantity,
                unit
            FROM inventory
            WHERE id = :id
        ");
        $stmt->execute([':id' => $newInventoryId]);
        $newItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$newItem) {
            throw new Exception('Nieuwe zaadvoorraad niet gevonden.');
        }

        $before = (float)$newItem['quantity'];
        $after = $before - $newSeedAmount;

        if ($after < 0) {
            throw new Exception('Onvoldoende voorraad voor deze wijziging.');
        }

        $db->prepare("
            UPDATE inventory
            SET quantity = :quantity
            WHERE id = :id
        ")->execute([
            ':quantity' => $after,
            ':id' => $newInventoryId
        ]);

        $db->prepare("
            INSERT INTO inventory_transactions
                (inventory_id, type, quantity_change, quantity_before, quantity_after, unit, note, reference_type, reference_id)
            VALUES
                (:inventory_id, :type, :quantity_change, :quantity_before, :quantity_after, :unit, :note, :reference_type, :reference_id)
        ")->execute([
            ':inventory_id' => $newInventoryId,
            ':type' => 'BATCH_EDIT_VERBRUIK',
            ':quantity_change' => -$newSeedAmount,
            ':quantity_before' => $before,
            ':quantity_after' => $after,
            ':unit' => $newItem['unit'],
            ':note' => 'Nieuw zaadverbruik ingesteld bij batchbewerking',
            ':reference_type' => 'grow_batch',
            ':reference_id' => $id
        ]);

        $db->prepare("
            UPDATE grow_batches
            SET crop = :crop,
                sow_date = :sow_date,
                expected_harvest_date = :expected_harvest_date,
                tray_count = :tray_count,
                tray_type = :tray_type,
                status = :status,
                inventory_id = :inventory_id,
                seed_amount = :seed_amount,
                seed_unit = :seed_unit
            WHERE id = :id
        ")->execute([
            ':crop' => $crop,
            ':sow_date' => $sowDate,
            ':expected_harvest_date' => $expectedHarvestDate,
            ':tray_count' => $trayCount,
            ':tray_type' => $trayType,
            ':status' => $status,
            ':inventory_id' => $newInventoryId,
            ':seed_amount' => $newSeedAmount,
            ':seed_unit' => $newItem['unit'],
            ':id' => $id
        ]);

        $db->commit();

        header('Location: grow_batches.php');
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        die('Fout: ' . htmlspecialchars($e->getMessage()));
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
    <h1>✏️ <?= htmlspecialchars(__('edit_batch')) ?></h1>

    <div class="card">
        <form method="post">
            <p>
                <?= htmlspecialchars(__('crop')) ?><br>
                <input type="text" name="crop" value="<?= htmlspecialchars($batch['crop']) ?>" required>
            </p>

            <p>
                <?= htmlspecialchars(__('sowing_date')) ?><br>
                <input type="date" name="sow_date" value="<?= htmlspecialchars($batch['sow_date']) ?>" required>
            </p>

            <p>
                <?= htmlspecialchars(__('expected_harvest_date')) ?><br>
                <input type="date" name="expected_harvest_date" value="<?= htmlspecialchars($batch['expected_harvest_date'] ?? '') ?>">
            </p>

            <p>
                <?= htmlspecialchars(__('tray_count')) ?><br>
                <input type="number" name="tray_count" value="<?= htmlspecialchars($batch['tray_count']) ?>" min="1" required>
            </p>

            <p>
                <?= htmlspecialchars(__('tray_type')) ?><br>
                <input type="text" name="tray_type" value="<?= htmlspecialchars($batch['tray_type']) ?>">
            </p>

            <p>
                <?= htmlspecialchars(__('seed_usage')) ?><br>
                <select name="inventory_id" required>
                    <option value="">-- <?= htmlspecialchars(__('choose_seed_inventory')) ?> --</option>
                    <?php foreach ($inventoryItems as $item): ?>
                        <option value="<?= htmlspecialchars((string)$item['id']) ?>" <?= ((int)$batch['inventory_id'] === (int)$item['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($item['item_name']) ?>
                            (<?= number_format((float)$item['quantity'], 2, ',', '.') ?>
                            <?= htmlspecialchars($item['unit']) ?> <?= htmlspecialchars(__('available')) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <?= htmlspecialchars(__('seed_amount')) ?><br>
                <input type="number" step="0.01" name="seed_amount" value="<?= htmlspecialchars((string)($batch['seed_amount'] ?? 0)) ?>" required>
            </p>

            <p>
                <?= htmlspecialchars(__('status')) ?><br>
                <select name="status">
                    <?php foreach (['Gepland', 'Groeiend', 'Oogstklaar', 'Geoogst'] as $status): ?>
                        <option value="<?= htmlspecialchars($status) ?>" <?= ($batch['status'] === $status) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(match ($status) {
                                'Gepland' => __('status_planned'),
                                'Groeiend' => __('status_growing'),
                                'Oogstklaar' => __('status_ready_to_harvest'),
                                'Geoogst' => __('status_harvested'),
                                default => $status,
                            }) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <button class="btn" type="submit">💾 <?= htmlspecialchars(__('save')) ?></button>
                <a class="btn" href="grow_batches.php"><?= htmlspecialchars(__('back')) ?></a>
            </p>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>