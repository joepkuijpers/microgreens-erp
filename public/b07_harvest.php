<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);
session_start();

$dbPath = __DIR__ . "/../database/MicrogreensERP_Live.sqlite";
if (!file_exists($dbPath)) { die("Database niet gevonden."); }
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("DB Fout: " . $e->getMessage()); }

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $germinationId = $_POST["germination_record_id"] ?? null;
    $weight = $_POST["weight_grams"] ?? 0;
    $operator = "Joep";
    $notes = $_POST["notes"] ?? "";
    
    if (!$germinationId || $weight <= 0) {
        $message = "Fout: Selecteer een kieming en vul een gewicht in.";
        $messageType = "error";
    } else {
        $now = date("Y-m-d H:i:s");
        $today = date("Y-m-d");
        $expiryDate = date("Y-m-d", strtotime("+10 days"));

        // Haal data op
        $stmt = $pdo->prepare("SELECT gr.quantity_seeds, sl.variety, t.id as tray_id FROM germination_records gr JOIN seed_inventory si ON gr.seed_inventory_id = si.id JOIN seed_lots sl ON si.seed_lot_id = sl.id JOIN trays t ON gr.tray_id = t.id WHERE gr.id = ?");
        $stmt->execute([$germinationId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            $message = "Fout: Kieming niet gevonden.";
            $messageType = "error";
        } else {
            $pdo->beginTransaction();
            try {
                // 1. Registreer Oogst (Geen strings in SQL, alleen parameters)
                $sqlHarvest = "INSERT INTO harvests (germination_record_id, harvest_date, weight_grams, operator, notes, quality_status) VALUES (?, ?, ?, ?, ?, ?)";
                $pdo->prepare($sqlHarvest)->execute([$germinationId, $now, $weight, $operator, $notes, "GOOD"]);

                $harvestId = $pdo->lastInsertId();

                // 2. Maak Eindproduct (Geen strings in SQL, alleen parameters)
                $productName = $data["variety"] . " Microgreens";
                $sqlInv = "INSERT INTO finished_inventory (harvest_id, product_name, weight_grams, harvest_date, expiry_date, status) VALUES (?, ?, ?, ?, ?, ?)";
                $pdo->prepare($sqlInv)->execute([$harvestId, $productName, $weight, $today, $expiryDate, "AVAILABLE"]);

                // 3. Update Tray (Parameter voor status)
                $pdo->prepare("UPDATE trays SET status = ? WHERE id = ?")->execute(["EMPTY", $data["tray_id"]]);

                // 4. Update Germination Record (Parameter voor status)
                $pdo->prepare("UPDATE germination_records SET status = ? WHERE id = ?")->execute(["HARVESTED", $germinationId]);

                $pdo->commit();
                
                $efficiency = ($weight / $data["quantity_seeds"]) * 100;
                $message = "Succes! Opbrengst: " . $weight . "g. Efficiëntie: " . round($efficiency, 1) . "%";
                $messageType = "success";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Fout: " . $e->getMessage();
                $messageType = "error";
            }
        }
    }
}

// Haal records op
$allRecords = $pdo->query("SELECT gr.id, sl.variety, t.tray_code, r.name as rack_name, gr.start_date, gr.expected_harvest_date, gr.quantity_seeds FROM germination_records gr JOIN seed_inventory si ON gr.seed_inventory_id = si.id JOIN seed_lots sl ON si.seed_lot_id = sl.id JOIN trays t ON gr.tray_id = t.id JOIN racks r ON t.rack_id = r.id ORDER BY gr.start_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B07 - Harvest</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: sans-serif; background: #f4f6f9; margin: 0; padding: 2rem; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #d35400; border-bottom: 2px solid #e67e22; padding-bottom: 0.5rem; }
        .alert { padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-weight: bold; }
        .alert.error { background: #f8d7da; color: #721c24; }
        .alert.success { background: #d4edda; color: #155724; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; color: #555; }
        .form-row { display: flex; gap: 1rem; margin-bottom: 1rem; }
        .form-group { flex: 1; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: #555; }
        select, input, textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; box-sizing: border-box; }
        button { background: #e67e22; color: white; border: none; padding: 1rem 2rem; font-size: 1rem; border-radius: 4px; cursor: pointer; width: 100%; }
        button:hover { background: #d35400; }
        a { display: inline-block; margin-top: 1rem; color: #555; text-decoration: none; }
    </style>
</head>
<body>
<div style="padding:20px;"><a href="dashboard.php" style="background:#2c3e50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;font-weight:bold;display:inline-block;">← Terug naar Dashboard</a></div>
<div class="container">
    <h1>🧺 B07: Oogst Registratie</h1>
    <?php if ($message): ?>
        <div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <h3>Actieve Kiemingen</h3>
    <?php if (empty($allRecords)): ?>
        <p>Geen kiemingen gevonden.</p>
    <?php else: ?>
        <form method="POST">
            <table>
                <thead>
                    <tr><th>Selecteer</th><th>Gewas</th><th>Tray</th><th>Rack</th><th>Gezaaid</th><th>Ingezaaid (g)</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($allRecords as $rec): ?>
                        <tr>
                            <td><input type="radio" name="germination_record_id" value="<?= $rec["id"] ?>" required></td>
                            <td><strong><?= htmlspecialchars($rec["variety"]) ?></strong></td>
                            <td><?= htmlspecialchars($rec["tray_code"]) ?></td>
                            <td><?= htmlspecialchars($rec["rack_name"]) ?></td>
                            <td><?= htmlspecialchars($rec["start_date"]) ?></td>
                            <td><?= $rec["quantity_seeds"] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="form-row">
                <div class="form-group">
                    <label>Oogst Gewicht (gram)</label>
                    <input type="number" name="weight_grams" step="0.1" required placeholder="Bijv. 120">
                </div>
                <div class="form-group">
                    <label>Notities</label>
                    <input type="text" name="notes" placeholder="Optioneel">
                </div>
            </div>

            <button type="submit">🧺 Registreer Oogst</button>
        </form>
    <?php endif; ?>
    <a href="dashboard.php">← Terug</a>
</div>
</body>
</html>
