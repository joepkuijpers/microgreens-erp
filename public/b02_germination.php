<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);
session_start();

$dbPath = __DIR__ . "/../database/MicrogreensERP_Live.sqlite";
if (!file_exists($dbPath)) { die("Database niet gevonden: " . $dbPath); }
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("DB Fout: " . $e->getMessage()); }

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $seedInvId = $_POST["seed_inventory_id"] ?? null;
    $trayId = $_POST["tray_id"] ?? null;
    $quantity = $_POST["quantity_seeds"] ?? 0;
    $operator = "Joep"; 
    
    if (!$seedInvId || !$trayId || $quantity <= 0) {
        $message = "🚫 Fout: Vul alle velden correct in.";
        $messageType = "error";
    } else {
        // Check voorraad
        $stmt = $pdo->prepare("SELECT stock_grams FROM seed_inventory WHERE id = ?");
        $stmt->execute([$seedInvId]);
        $currentStock = $stmt->fetchColumn();

        if ($currentStock === false || $currentStock < $quantity) {
            $message = "🚫 Onvoldoende voorraad. Beschikbaar: " . ($currentStock ?: 0) . "g";
            $messageType = "error";
        } else {
            $harvestDate = date("Y-m-d", strtotime("+7 days"));
            $now = date("Y-m-d H:i:s");

            // Transactie starten
            $pdo->beginTransaction();
            try {
                // 1. Update voorraad
                $pdo->prepare("UPDATE seed_inventory SET stock_grams = stock_grams - ? WHERE id = ?")
                    ->execute([$quantity, $seedInvId]);

                // 2. Update Tray
                $pdo->prepare("UPDATE trays SET status = ? WHERE id = ?")
                    ->execute(["GERMINATING", $trayId]);

                // 3. Insert Record
                $pdo->prepare("INSERT INTO germination_records (seed_inventory_id, tray_id, quantity_seeds, start_date, expected_harvest_date, operator) VALUES (?, ?, ?, ?, ?, ?)")
                    ->execute([$seedInvId, $trayId, $quantity, $now, $harvestDate, $operator]);

                $pdo->commit();
                $message = "✅ Zaaien gelukt! Oogst verwacht: " . $harvestDate;
                $messageType = "success";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Fout: " . $e->getMessage();
                $messageType = "error";
            }
        }
    }
}

// --- DATA OPHALEN (ROBUUSTE METHODE ZONDER COMPLEXE JOIN IN SQL) ---

// 1. Haal ALLE racks op
$racks = $pdo->query("SELECT id, name FROM racks ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// 2. Haal ALLE trays op (geen WHERE clause, dus geen quote issues)
$allTrays = $pdo->query("SELECT id, tray_code, rack_id, status FROM trays ORDER BY tray_code")->fetchAll(PDO::FETCH_ASSOC);

// 3. Filter in PHP naar alleen EMPTY trays
$emptyTrays = [];
foreach ($allTrays as $tray) {
    if ($tray["status"] === "EMPTY") {
        // Zoek de rack naam erbij uit de al geladen $racks array
        $rackName = "Onbekend";
        foreach ($racks as $r) {
            if ($r["id"] == $tray["rack_id"]) {
                $rackName = $r["name"];
                break;
            }
        }
        $tray["rack_name"] = $rackName;
        $emptyTrays[] = $tray;
    }
}

// 4. Haal zaad op
$seedInventory = $pdo->query("SELECT si.id, sl.variety, si.stock_grams, s.name as supplier FROM seed_inventory si JOIN seed_lots sl ON si.seed_lot_id = sl.id JOIN suppliers s ON si.supplier_id = s.id WHERE si.stock_grams > 0 ORDER BY sl.variety")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B02 - Germination</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: sans-serif; background: #f4f6f9; margin: 0; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #27ae60; padding-bottom: 0.5rem; }
        .alert { padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-weight: bold; }
        .alert.error { background: #f8d7da; color: #721c24; }
        .alert.success { background: #d4edda; color: #155724; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: #555; }
        select, input { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; box-sizing: border-box; }
        button { background: #27ae60; color: white; border: none; padding: 1rem 2rem; font-size: 1rem; border-radius: 4px; cursor: pointer; width: 100%; }
        button:hover { background: #219150; }
        a { display: inline-block; margin-top: 1rem; color: #555; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <h1>🌱 B02: Start Kieming</h1>
    <?php if ($message): ?>
        <div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Zaad (Voorraad > 0)</label>
            <select name="seed_inventory_id" required>
                <option value="">-- Kies --</option>
                <?php foreach ($seedInventory as $si): ?>
                    <option value="<?= $si["id"] ?>"><?= htmlspecialchars($si["variety"]) ?> (<?= $si["stock_grams"] ?>g)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Tray (Alleen lege)</label>
            <select name="tray_id" required>
                <option value="">-- Kies Tray --</option>
                <?php foreach ($emptyTrays as $t): ?>
                    <option value="<?= $t["id"] ?>"><?= htmlspecialchars($t["tray_code"]) ?> (in <?= htmlspecialchars($t["rack_name"]) ?>)</option>
                <?php endforeach; ?>
                <?php if (empty($emptyTrays)): ?>
                    <option disabled>Geen lege trays gevonden!</option>
                <?php endif; ?>
            </select>
            <?php if (empty($emptyTrays)): ?>
                <small style="color:red;">⚠️ Er zijn geen trays met status EMPTY. Controleer de database.</small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Hoeveelheid (gram)</label>
            <input type="number" name="quantity_seeds" step="0.1" required placeholder="Bijv. 20">
        </div>

        <button type="submit">🚀 Start Kieming</button>
    </form>
    <a href="dashboard.php">← Terug</a>
</div>
</body>
</html>
