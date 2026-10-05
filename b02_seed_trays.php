<?php
error_reporting(E_ALL); ini_set("display_errors", 1);
require_once '/var/www/html/microgreens/PHP/app/includes/db_connection.php';
$db = getDbConnection();
require_once '/var/www/html/microgreens/PHP/app/includes/b02_seed_trays_engine.php';

$message = '';
$messageType = '';

// Verwerk formulier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batchId = (int)($_POST['batch_id'] ?? 0);
    $locationId = (int)($_POST['location_id'] ?? 0);
    $trayCount = (int)($_POST['tray_count'] ?? 1);
    
    if ($batchId > 0 && $locationId > 0 && $trayCount > 0) {
        $result = b02_assign_trays($db, $batchId, $locationId, $trayCount);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        if ($result['success']) {
            // Reset na succes zodat je direct door kunt werken
            $_POST = array(); 
        }
    } else {
        $message = "❌ Vul alle verplichte velden correct in.";
        $messageType = 'error';
    }
}

// Haal actieve batches op (alleen die nog niet geoogst zijn)
$batches = $db->query("SELECT id, crop, sow_date, status FROM grow_batches WHERE status != 'Geoogst' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Haal vrije locaties op
$locations = $db->query("SELECT r.id, r.rack_code, r.shelf_number, r.tray_position, r.tray_type 
                         FROM rack_locations r 
                         LEFT JOIN tray_assignments t ON r.id = t.rack_location_id AND t.status = 'occupied'
                         WHERE r.is_active = 1 AND t.id IS NULL
                         ORDER BY r.rack_code, r.shelf_number")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B02: Trays Koppelen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f8; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2e7d32; margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
        select, input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; box-sizing: border-box; }
        button { background: #2e7d32; color: white; border: none; padding: 15px 30px; font-size: 18px; border-radius: 4px; cursor: pointer; width: 100%; font-weight: bold; }
        button:hover { background: #1b5e20; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: bold; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .stats { background: #e3f2fd; padding: 15px; border-radius: 4px; margin-top: 20px; font-size: 0.9em; color: #1565c0; }
    </style>
</head>
<body>

<div class="container">
    <h1>🌱 B02: Trays Koppelen</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="batch_id">Kies Batch (Gewas):</label>
            <select name="batch_id" id="batch_id" required>
                <option value="">-- Selecteer Batch --</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= (isset($_POST['batch_id']) && $_POST['batch_id'] == $b['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['crop']) ?> (Gezaaid: <?= $b['sow_date'] ?>) - ID: <?= $b['id'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="location_id">Kies Vrije Locatie:</label>
            <select name="location_id" id="location_id" required>
                <option value="">-- Selecteer Locatie --</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc['id'] ?>" <?= (isset($_POST['location_id']) && $_POST['location_id'] == $loc['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($loc['rack_code']) ?> - Schap <?= $loc['shelf_number'] ?> (Positie: <?= $loc['tray_position'] ?>, Type: <?= $loc['tray_type'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (count($locations) === 0): ?>
                <p style="color: red; font-size: 0.9em;">⚠️ Geen vrije locaties gevonden. Voeg eerst locaties toe.</p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="tray_count">Aantal Trays:</label>
            <input type="number" name="tray_count" id="tray_count" value="1" min="1" max="50" required>
        </div>

        <button type="submit">💾 Koppel Trays</button>
    </form>

    <div class="stats">
        <strong>Doel:</strong> Deze actie moet < 30 seconden duren.<br>
        <strong>Status:</strong> <?= count($batches) ?> actieve batches, <?= count($locations) ?> vrije locaties.
    </div>
</div>

</body>
</html>
