<?php
/**
 * B02 - Germination & Seeding (AANGEPAST AAN ECHTE SCHEMA)
 * Kolommen: stock_grams, crop_type, physical_units.batch_id
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$dbPath = __DIR__ . '/../database/MicrogreensERP_Live.sqlite';
if (!file_exists($dbPath)) { die("DB niet gevonden"); }
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("DB Fout: " . $e->getMessage()); }

$errors = [];
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register_seeding') {
        $batch_id = intval($_POST['batch_id'] ?? 0);
        $seed_id = intval($_POST['seed_id'] ?? 0); // seed_inventory ID
        $rack_id = trim($_POST['rack_id'] ?? 'RACK-A');
        $position = intval($_POST['rack_position'] ?? 0);
        $seed_weight_g = floatval($_POST['seed_weight_g'] ?? 0);
        $unit_code = 'TRAY-' . strtoupper(substr(md5(uniqid()), 0, 8)); // Genereer unieke tray code

        // Validatie
        if ($batch_id <= 0) $errors[] = "Geen batch geselecteerd.";
        if ($seed_id <= 0) $errors[] = "Geen zaad geselecteerd.";
        if ($seed_weight_g <= 0) $errors[] = "Zaadhoeveelheid moet > 0 zijn.";

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // 1. Update Seed Inventory (Gebruik stock_grams)
                $stmt = $pdo->prepare("UPDATE seed_inventory SET stock_grams = stock_grams - ?, updated_at = datetime('now') WHERE id = ?");
                $stmt->execute([$seed_weight_g, $seed_id]);

                // 2. Maak Physical Unit (Moet batch_id hebben!)
                $stmt = $pdo->prepare("
                    INSERT INTO physical_units (batch_id, unit_code, container_type, status, created_at)
                    VALUES (?, ?, '1020', 'ACTIVE', datetime('now'))
                ");
                $stmt->execute([$batch_id, $unit_code]);
                $unit_id = $pdo->lastInsertId();

                // 3. Maak Spatial Allocation
                $stmt = $pdo->prepare("
                    INSERT INTO spatial_allocations (physical_unit_id, batch_id, rack_id, rack_position, allocation_type, status, created_at)
                    VALUES (?, ?, ?, ?, 'CROP', 'ACTIVE', datetime('now'))
                ");
                $stmt->execute([$unit_id, $batch_id, $rack_id, $position]);

                // 4. Update Batch Status
                $stmt = $pdo->prepare("UPDATE production_batches SET status = 'GERMINATING' WHERE id = ?");
                $stmt->execute([$batch_id]);

                $pdo->commit();
                $success_msg = "✅ Zaai-actie geregistreerd! Tray $unit_code geplaatst op $rack_id pos. $position.";

            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Fout: " . $e->getMessage();
            }
        }
    }
}

// Data ophalen
$batches = [];
$seeds = [];

try {
    // Batches die nog niet geoogst/completed zijn
    $stmt = $pdo->query("SELECT id, batch_code, crop_type, status FROM production_batches WHERE status NOT IN ('HARVESTED', 'COMPLETED') ORDER BY id DESC LIMIT 20");
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Seeds met voorraad > 0 (Gebruik stock_grams en crop_type)
    $stmt = $pdo->query("SELECT id, crop_type, stock_grams, organic_status FROM seed_inventory WHERE stock_grams > 0 ORDER BY id DESC LIMIT 20");
    $seeds = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { die("Query fout: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B02 Germination</title>
    <style>
        body { font-family: sans-serif; margin: 2rem; background: #f4f4f4; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; }
        h1 { border-bottom: 2px solid #0056b3; padding-bottom: 0.5rem; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        select, input { width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background: #0056b3; color: white; border: none; padding: 1rem; width: 100%; font-size: 1.1rem; border-radius: 4px; cursor: pointer; }
        .row { display: flex; gap: 1rem; } .col { flex: 1; }
    </style>
</head>
<body>
<div class="container">
    <h1>🌱 B02: Zaai & Kiem Registratie</h1>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><strong>Fout:</strong><ul><?php foreach($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="action" value="register_seeding">
        
        <div class="form-group">
            <label>Productie Batch:</label>
            <select name="batch_id" required>
                <option value="">-- Selecteer Batch --</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['crop_type']) ?> - <?= htmlspecialchars($b['batch_code']) ?> (<?= $b['status'] ?>)</option>
                <?php endforeach; ?>
                <?php if (empty($batches)): ?><option>Geen batches gevonden</option><?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Zaad Voorraad (Seed Inventory):</label>
            <select name="seed_id" required>
                <option value="">-- Selecteer Zaad --</option>
                <?php foreach ($seeds as $s): ?>
                    <option value="<?= $s['id'] ?>">
                        <?= htmlspecialchars($s['crop_type']) ?> (<?= number_format($s['stock_grams'], 1) ?>g) - <?= htmlspecialchars($s['organic_status']) ?>
                    </option>
                <?php endforeach; ?>
                <?php if (empty($seeds)): ?><option>Geen zaadvoorraad gevonden</option><?php endif; ?>
            </select>
        </div>

        <div class="row">
            <div class="col">
                <div class="form-group">
                    <label>Rack:</label>
                    <select name="rack_id">
                        <option value="RACK-A">RACK A</option>
                        <option value="RACK-B">RACK B</option>
                        <option value="RACK-C">RACK C</option>
                    </select>
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label>Positie:</label>
                    <input type="number" name="rack_position" value="1" required>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Gebruikte Zaadhoeveelheid (g):</label>
            <input type="number" step="0.1" name="seed_weight_g" value="5.0" required>
        </div>

        <button type="submit">🌱 Registreer Zaai-actie</button>
    </form>
</div>
</body>
</html>
