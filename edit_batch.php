<?php
include 'db_connect.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Ongeldig batch-ID.');
}

$stmt = $db->prepare("SELECT * FROM grow_batches WHERE id = :id");
$stmt->execute([':id' => $id]);
$batch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$batch) {
    die('Batch niet gevonden.');
}

$inventoryItems = $db->query("
    SELECT id, item_name, quantity, unit
    FROM inventory
    ORDER BY item_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crop = trim($_POST['crop'] ?? '');
    $sow_date = trim($_POST['sow_date'] ?? '');
    $expected_harvest_date = trim($_POST['expected_harvest_date'] ?? '');
    $tray_count = (int)($_POST['tray_count'] ?? 1);
    $tray_type = trim($_POST['tray_type'] ?? '');
    $status = trim($_POST['status'] ?? 'Groeiend');
    $new_inventory_id = (int)($_POST['inventory_id'] ?? 0);
    $new_seed_amount = (float)($_POST['seed_amount'] ?? 0);

    if ($crop === '' || $sow_date === '' || $tray_count <= 0 || $new_inventory_id <= 0 || $new_seed_amount < 0) {
        die('Ongeldige invoer.');
    }

    $old_inventory_id = (int)($batch['inventory_id'] ?? 0);
    $old_seed_amount = (float)($batch['seed_amount'] ?? 0);

    $db->beginTransaction();

    try {
        if ($old_inventory_id > 0 && $old_seed_amount > 0) {
            $stmt = $db->prepare("SELECT * FROM inventory WHERE id = :id");
            $stmt->execute([':id' => $old_inventory_id]);
            $oldItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($oldItem) {
                $before = (float)$oldItem['quantity'];
                $after = $before + $old_seed_amount;

                $db->prepare("UPDATE inventory SET quantity = :quantity WHERE id = :id")
                   ->execute([':quantity' => $after, ':id' => $old_inventory_id]);

                $db->prepare("
                    INSERT INTO inventory_transactions
                    (inventory_id, type, quantity_change, quantity_before, quantity_after, unit, note, reference_type, reference_id)
                    VALUES
                    (:inventory_id, :type, :quantity_change, :quantity_before, :quantity_after, :unit, :note, :reference_type, :reference_id)
                ")->execute([
                    ':inventory_id' => $old_inventory_id,
                    ':type' => 'BATCH_EDIT_RETOUR',
                    ':quantity_change' => $old_seed_amount,
                    ':quantity_before' => $before,
                    ':quantity_after' => $after,
                    ':unit' => $oldItem['unit'],
                    ':note' => 'Oude zaadverbruik teruggezet bij batchbewerking',
                    ':reference_type' => 'grow_batch',
                    ':reference_id' => $id
                ]);
            }
        }

        $stmt = $db->prepare("SELECT * FROM inventory WHERE id = :id");
        $stmt->execute([':id' => $new_inventory_id]);
        $newItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$newItem) {
            throw new Exception('Nieuwe zaadvoorraad niet gevonden.');
        }

        $before = (float)$newItem['quantity'];
        $after = $before - $new_seed_amount;

        if ($after < 0) {
            throw new Exception('Onvoldoende voorraad voor deze wijziging.');
        }

        $db->prepare("UPDATE inventory SET quantity = :quantity WHERE id = :id")
           ->execute([':quantity' => $after, ':id' => $new_inventory_id]);

        $db->prepare("
            INSERT INTO inventory_transactions
            (inventory_id, type, quantity_change, quantity_before, quantity_after, unit, note, reference_type, reference_id)
            VALUES
            (:inventory_id, :type, :quantity_change, :quantity_before, :quantity_after, :unit, :note, :reference_type, :reference_id)
        ")->execute([
            ':inventory_id' => $new_inventory_id,
            ':type' => 'BATCH_EDIT_VERBRUIK',
            ':quantity_change' => -$new_seed_amount,
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
            ':sow_date' => $sow_date,
            ':expected_harvest_date' => $expected_harvest_date,
            ':tray_count' => $tray_count,
            ':tray_type' => $tray_type,
            ':status' => $status,
            ':inventory_id' => $new_inventory_id,
            ':seed_amount' => $new_seed_amount,
            ':seed_unit' => $newItem['unit'],
            ':id' => $id
        ]);

        $db->commit();

        header('Location: grow_batches.php');
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        die('Fout: ' . $e->getMessage());
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
    <h1>✏️ Batch bewerken</h1>

    <div class="card">
        <form method="post">
            <p>Gewas<br>
                <input type="text" name="crop" value="<?= htmlspecialchars($batch['crop']) ?>" required>
            </p>

            <p>Zaaidatum<br>
                <input type="date" name="sow_date" value="<?= htmlspecialchars($batch['sow_date']) ?>" required>
            </p>

            <p>Verwachte oogstdatum<br>
                <input type="date" name="expected_harvest_date" value="<?= htmlspecialchars($batch['expected_harvest_date'] ?? '') ?>">
            </p>

            <p>Aantal trays<br>
                <input type="number" name="tray_count" value="<?= htmlspecialchars($batch['tray_count']) ?>" min="1" required>
            </p>

            <p>Traytype<br>
                <input type="text" name="tray_type" value="<?= htmlspecialchars($batch['tray_type']) ?>">
            </p>

            <p>Zaadvoorraad<br>
                <select name="inventory_id" required>
                    <option value="">-- Kies zaadvoorraad --</option>
                    <?php foreach ($inventoryItems as $item): ?>
                        <option value="<?= htmlspecialchars($item['id']) ?>" <?= ((int)$batch['inventory_id'] === (int)$item['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($item['item_name']) ?>
                            (<?= number_format((float)$item['quantity'], 2, ',', '.') ?>
                            <?= htmlspecialchars($item['unit']) ?> beschikbaar)
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>Zaadverbruik<br>
                <input type="number" step="0.01" name="seed_amount" value="<?= htmlspecialchars($batch['seed_amount'] ?? 0) ?>" required>
            </p>

            <p>Status<br>
                <select name="status">
                    <?php foreach (['Gepland', 'Groeiend', 'Oogstklaar', 'Geoogst'] as $status): ?>
                        <option value="<?= $status ?>" <?= ($batch['status'] === $status) ? 'selected' : '' ?>>
                            <?= $status ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <button class="btn" type="submit">💾 Opslaan</button>
                <a class="btn" href="grow_batches.php">Terug</a>
            </p>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>