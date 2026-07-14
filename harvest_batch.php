<?php
include 'db_connect.php';
include 'includes/language.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die(__('invalid_batch_id'));
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
    die(__('batch_not_found'));
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
        die(__('invalid_harvest_input'));
    }

    $stmt = $db->prepare("
        SELECT id, name, unit
        FROM products
        WHERE id = :id
    ");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
   die(__('product_not_found'));     
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

        $harvestId = (int)$db->lastInsertId();

        $insertFinished = $db->prepare("
            INSERT INTO finished_inventory
            (product_id, quantity, unit, batch_id, harvest_id)
            VALUES
            (:product_id, :quantity, :unit, :batch_id, :harvest_id)
        ");

        $insertFinished->execute([
            ':product_id' => $product_id,
            ':quantity' => $finished_quantity,
            ':unit' => $product['unit'],
            ':batch_id' => $id,
            ':harvest_id' => $harvestId
        ]);

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
    <h1>🌾 <?= htmlspecialchars(__('harvest_batch')) ?></h1>

    <div class="card">
     <p><strong><?= htmlspecialchars(__('crop')) ?>:</strong> <?= htmlspecialchars((string)$batch['crop']) ?></p>
<p><strong><?= htmlspecialchars(__('sowing_date')) ?>:</strong> <?= htmlspecialchars((string)$batch['sow_date']) ?></p>
<p><strong><?= htmlspecialchars(__('expected_harvest_date')) ?>:</strong> <?= htmlspecialchars((string)($batch['expected_harvest_date'] ?? '')) ?></p>
<p><strong><?= htmlspecialchars(__('trays')) ?>:</strong> <?= htmlspecialchars((string)$batch['tray_count']) ?></p>   
    </div>

    <div class="card">
        <form method="post">
            <p>
                Werkelijke oogstdatum<br>
                <input type="date" name="harvest_date" value="<?= date('Y-m-d') ?>" required>
            </p>

            <p>
                <?= htmlspecialchars(__('harvest_weight_grams')) ?><br>
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
                <?= htmlspecialchars(__('quality_notes')) ?><br>
                <input type="text" name="quality_notes" placeholder="Bijv. mooi, kort, geel, test">
            </p>

            <p>
                <button class="btn" type="submit">🌾 <?= htmlspecialchars(__('register_harvest')) ?></button>
                
            </p><a class="btn" href="grow_batches.php"><?= htmlspecialchars(__('back')) ?></a>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>