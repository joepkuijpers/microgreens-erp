<?php
include 'db_connect.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Ongeldig batch-ID.');
}

$stmt = $db->prepare("
    SELECT
        crop,
        sow_date,
        expected_harvest_date,
        tray_count
    FROM grow_batches
    WHERE id = :id
");
$stmt->execute([':id' => $id]);
$batch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$batch) {
    die('Batch niet gevonden.');
}

$products = $db->query("
    SELECT id, name, unit
    FROM products
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $harvest_date = trim($_POST['harvest_date'] ?? '');
    $weight_grams = (float)($_POST['weight_grams'] ?? 0);
    $quality_notes = trim($_POST['quality_notes'] ?? '');
    $product_id = (int)($_POST['product_id'] ?? 0);
    $finished_quantity = (float)($_POST['finished_quantity'] ?? 0);

    if ($harvest_date === '' || $weight_grams <= 0 || $product_id <= 0 || $finished_quantity <= 0) {
        die('Ongeldige invoer. Controleer oogstdatum, gewicht, product en hoeveelheid.');
    }

    $stmt = $db->prepare("
        SELECT id, name, unit
        FROM products
        WHERE id = :id
    ");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die('Product niet gevonden.');
    }

    $db->beginTransaction();

    try {
        $insertHarvest = $db->prepare("
            INSERT INTO harvests
            (batch_id, harvest_date, weight_grams, quality_notes)
            VALUES
            (:batch_id, :harvest_date, :weight_grams, :quality_notes)
        ");

        $insertHarvest->execute([
            ':batch_id' => $id,
            ':harvest_date' => $harvest_date,
            ':weight_grams' => $weight_grams,
            ':quality_notes' => $quality_notes
        ]);

        $stmt = $db->prepare("
            SELECT id, product_id, quantity, unit
            FROM finished_inventory
            WHERE product_id = :product_id
        ");
        $stmt->execute([':product_id' => $product_id]);
        $finished = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($finished) {
            $newQuantity = (float)$finished['quantity'] + $finished_quantity;

            $updateFinished = $db->prepare("
                UPDATE finished_inventory
                SET quantity = :quantity,
                    unit = :unit
                WHERE product_id = :product_id
            ");

            $updateFinished->execute([
                ':quantity' => $newQuantity,
                ':unit' => $product['unit'],
                ':product_id' => $product_id
            ]);
        } else {
            $insertFinished = $db->prepare("
                INSERT INTO finished_inventory
                (product_id, quantity, unit)
                VALUES
                (:product_id, :quantity, :unit)
            ");

            $insertFinished->execute([
                ':product_id' => $product_id,
                ':quantity' => $finished_quantity,
                ':unit' => $product['unit']
            ]);
        }

        $updateBatch = $db->prepare("
            UPDATE grow_batches
            SET harvest_date = :harvest_date,
                status = 'Geoogst'
            WHERE id = :id
        ");

        $updateBatch->execute([
            ':harvest_date' => $harvest_date,
            ':id' => $id
        ]);

        $db->commit();

        header('Location: grow_batches.php');
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        die('Fout bij oogsten: ' . $e->getMessage());
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
    <h1>🌾 Batch oogsten</h1>

    <div class="card">
        <p><strong>Gewas:</strong> <?= htmlspecialchars((string)$batch['crop']) ?></p>
        <p><strong>Zaaidatum:</strong> <?= htmlspecialchars((string)$batch['sow_date']) ?></p>
        <p><strong>Verwachte oogstdatum:</strong> <?= htmlspecialchars((string)($batch['expected_harvest_date'] ?? '')) ?></p>
        <p><strong>Trays:</strong> <?= htmlspecialchars((string)$batch['tray_count']) ?></p>
    </div>

    <div class="card">
        <form method="post">
            <p>
                Werkelijke oogstdatum<br>
                <input type="date" name="harvest_date" value="<?= date('Y-m-d') ?>" required>
            </p>

            <p>
                Oogstgewicht in gram<br>
                <input type="number" step="0.01" name="weight_grams" required>
            </p>

            <p>
                Product voor eindvoorraad<br>
                <select name="product_id" required>
                    <option value="">-- Kies product --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= htmlspecialchars((string)$product['id']) ?>">
                            <?= htmlspecialchars((string)$product['name']) ?>
                            (<?= htmlspecialchars((string)($product['unit'] ?? '')) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                Hoeveelheid eindvoorraad<br>
                <input type="number" step="0.01" name="finished_quantity" required>
            </p>

            <p>
                Kwaliteit / opmerkingen<br>
                <input type="text" name="quality_notes" placeholder="Bijv. mooi, kort, geel, test">
            </p>

            <p>
                <button class="btn" type="submit">🌾 Oogst registreren</button>
                <a class="btn" href="grow_batches.php">Terug</a>
            </p>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>