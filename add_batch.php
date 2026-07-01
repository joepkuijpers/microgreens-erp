<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

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
    $inventory_id = (int)($_POST['inventory_id'] ?? 0);
    $seed_amount = (float)($_POST['seed_amount'] ?? 0);

    if ($crop === '' || $sow_date === '' || $expected_harvest_date === '' || $tray_count <= 0 || $inventory_id <= 0 || $seed_amount <= 0) {
        die('Ongeldige invoer. Controleer gewas, datums, trays en zaadverbruik.');
    }

    $stmt = $db->prepare("SELECT * FROM inventory WHERE id = :id");
    $stmt->execute([':id' => $inventory_id]);
    $seedItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$seedItem) {
        die('Zaadvoorraad niet gevonden.');
    }

    $quantity_before = (float)$seedItem['quantity'];
    $quantity_after = $quantity_before - $seed_amount;

    if ($quantity_after < 0) {
        die('Fout: onvoldoende voorraad zaad.');
    }

    $insert = $db->prepare("
        INSERT INTO grow_batches
        (crop, sow_date, expected_harvest_date, tray_count, tray_type, status, inventory_id, seed_amount, seed_unit)
        VALUES
        (:crop, :sow_date, :expected_harvest_date, :tray_count, :tray_type, :status, :inventory_id, :seed_amount, :seed_unit)
    ");

    $insert->execute([
        ':crop' => $crop,
        ':sow_date' => $sow_date,
        ':expected_harvest_date' => $expected_harvest_date,
        ':tray_count' => $tray_count,
        ':tray_type' => $tray_type,
        ':status' => $status,
        ':inventory_id' => $inventory_id,
        ':seed_amount' => $seed_amount,
        ':seed_unit' => $seedItem['unit']
    ]);

    $batch_id = $db->lastInsertId();

    $updateInventory = $db->prepare("
        UPDATE inventory
        SET quantity = :quantity
        WHERE id = :id
    ");

    $updateInventory->execute([
        ':quantity' => $quantity_after,
        ':id' => $inventory_id
    ]);

    $log = $db->prepare("
        INSERT INTO inventory_transactions
        (inventory_id, type, quantity_change, quantity_before, quantity_after, unit, note, reference_type, reference_id)
        VALUES
        (:inventory_id, :type, :quantity_change, :quantity_before, :quantity_after, :unit, :note, :reference_type, :reference_id)
    ");

    $log->execute([
        ':inventory_id' => $inventory_id,
        ':type' => 'VERBRUIK',
        ':quantity_change' => -$seed_amount,
        ':quantity_before' => $quantity_before,
        ':quantity_after' => $quantity_after,
        ':unit' => $seedItem['unit'],
        ':note' => 'Zaad gebruikt voor batch: ' . $crop,
        ':reference_type' => 'grow_batch',
        ':reference_id' => $batch_id
    ]);

    header('Location: grow_batches.php');
    exit;
}
?>

<div class="main">
    <h1>🌱 Nieuwe batch</h1>

    <div class="card">
        <form method="post">
            <p>
                Gewas<br>
                <input type="text" name="crop" required>
            </p>

            <p>
                Zaaidatum<br>
                <input type="date" name="sow_date" required>
            </p>

            <p>
                Verwachte oogstdatum<br>
                <input type="date" name="expected_harvest_date" required>
            </p>

            <p>
                Aantal trays<br>
                <input type="number" name="tray_count" value="1" min="1" required>
            </p>

            <p>
                Traytype<br>
                <input type="text" name="tray_type" value="1020">
            </p>

            <p>
                Zaadvoorraad<br>
                <select name="inventory_id" required>
                    <option value="">-- Kies zaadvoorraad --</option>
                    <?php foreach ($inventoryItems as $item): ?>
                        <option value="<?= htmlspecialchars($item['id']) ?>">
                            <?= htmlspecialchars($item['item_name']) ?>
                            (<?= number_format((float)$item['quantity'], 2, ',', '.') ?>
                            <?= htmlspecialchars($item['unit']) ?> beschikbaar)
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                Zaadverbruik<br>
                <input type="number" step="0.01" name="seed_amount" required>
            </p>

            <p>
                Status<br>
                <select name="status">
                    <option value="Gepland">Gepland</option>
                    <option value="Groeiend" selected>Groeiend</option>
                    <option value="Oogstklaar">Oogstklaar</option>
                    <option value="Geoogst">Geoogst</option>
                </select>
            </p>

            <p>
                <button class="btn" type="submit">💾 Batch opslaan</button>
                <a class="btn" href="grow_batches.php">Terug</a>
            </p>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>