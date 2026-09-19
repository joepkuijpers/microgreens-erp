<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/includes/db_connection.php';
$db = getDbConnection(); // Cruciale fix: DB expliciet initialiseren

require_once __DIR__ . '/../app/includes/b07_harvest_engine.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batchId = (int)($_POST['batch_id'] ?? 0);
    $harvestDate = $_POST['harvest_date'] ?? date('Y-m-d');
    $weightGrams = (float)($_POST['weight_grams'] ?? 0);
    $productId = (int)($_POST['product_id'] ?? 0);
    $finishedQty = (float)($_POST['finished_quantity'] ?? 0);
    $notes = $_POST['quality_notes'] ?? '';

    if ($batchId > 0 && $weightGrams > 0 && $productId > 0) {
        $result = b07_register_harvest($db, $batchId, $harvestDate, $weightGrams, $productId, $finishedQty, $notes);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        if ($result['success']) $_POST = array();
    } else {
        $message = "❌ Vul alle verplichte velden correct in.";
        $messageType = 'error';
    }
}

$batches = $db->query("SELECT id, crop, sow_date, tray_count FROM grow_batches WHERE status IN ('Groeiend', 'Gezaaid') ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$products = $db->query("SELECT id, name, unit FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B07: Oogst Registreren</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f8; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2e7d32; margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
        select, input, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; box-sizing: border-box; }
        button { background: #2e7d32; color: white; border: none; padding: 15px 30px; font-size: 18px; border-radius: 4px; cursor: pointer; width: 100%; font-weight: bold; }
        button:hover { background: #1b5e20; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: bold; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
    </style>
</head>
<body>
<div class="container">
    <h1>🌾 Oogst Registreren (B07)</h1>
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label for="batch_id">Kies Batch:</label>
            <select name="batch_id" required>
                <option value="">-- Selecteer --</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['crop']) ?> (Gezaaid: <?= $b['sow_date'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="harvest_date">Datum:</label>
            <input type="date" name="harvest_date" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
            <label for="weight_grams">Gewicht (gram):</label>
            <input type="number" name="weight_grams" id="weight_grams" step="1" min="1" placeholder="Bijv. 50" value="" required>
            <small style="color:#666;">Knoppen: +1/-1 gram. Handmatig decimalen mogelijk.</small>
        </div>
        <div class="form-group">
            <label for="product_id">Product:</label>
            <select name="product_id" required>
                <option value="">-- Selecteer --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= $p['unit'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="finished_quantity">Hoeveelheid:</label>
            <input type="number" name="finished_quantity" step="0.01" value="1" required>
        </div>
        <div class="form-group">
            <label for="quality_notes">Notities:</label>
            <textarea name="quality_notes" rows="2">Goede kwaliteit</textarea>
        </div>
        <button type="submit">🌾 Registreer Oogst</button>
    </form>
</div>
<script>
// Zorg dat gewicht altijd een heel getal blijft bij gebruik van de pijltjes
document.getElementById("weight_grams").addEventListener("change", function() {
    if (this.value !== "") {
        this.value = Math.round(parseFloat(this.value));
    }
});
</script>
</body>
</html>
