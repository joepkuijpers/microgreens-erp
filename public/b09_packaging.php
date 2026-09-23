<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);
session_start();

$dbPath = __DIR__ . "/../database/MicrogreensERP_Live.sqlite";
if (!file_exists($dbPath)) { die("DB niet gevonden"); }
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("DB Fout: " . $e->getMessage()); }

$message = "";
$messageType = "";
$labelData = null;

// Verwerk verpakking
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "pack") {
    $invId = $_POST["inventory_id"] ?? null;
    $packWeight = $_POST["pack_weight"] ?? 0;
    
    if ($invId && $packWeight > 0) {
        // Check beschikbare voorraad
        $stmt = $pdo->prepare("SELECT weight_grams, product_name, expiry_date FROM finished_inventory WHERE id = ? AND status = 'AVAILABLE'");
        $stmt->execute([$invId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($item && $item["weight_grams"] >= $packWeight) {
            $pkgCode = "PKG-" . date("Ymd") . "-" . strtoupper(substr(md5(uniqid()), 0, 6));
            $qrString = "https://192.168.18.11/trace.php?id=" . $pkgCode;
            $now = date("Y-m-d H:i:s");
            
            $pdo->beginTransaction();
            try {
                // 1. Maak verpakking
                $stmt = $pdo->prepare("INSERT INTO packaging_units (finished_inventory_id, package_code, total_weight_g, expiry_date, qr_data, status) VALUES (?, ?, ?, ?, ?, 'AVAILABLE')");
                $stmt->execute([$invId, $pkgCode, $packWeight, $item["expiry_date"], $qrString]);
                $pkgId = $pdo->lastInsertId();
                
                // 2. Update voorraad (aftrekken)
                $newStock = $item["weight_grams"] - $packWeight;
                $newStatus = ($newStock <= 0.1) ? 'SOLD' : 'AVAILABLE'; // Als alles verpakt is, markeer als verkocht/leeg
                $pdo->prepare("UPDATE finished_inventory SET weight_grams = ?, status = ? WHERE id = ?")->execute([$newStock, $newStatus, $invId]);
                
                $pdo->commit();
                $message = "✅ Verpakt! Code: $pkgCode. Resterende voorraad: $newStock g";
                $messageType = "success";
                
                // Bereid label data voor
                $labelData = [
                    "code" => $pkgCode,
                    "product" => $item["product_name"],
                    "weight" => $packWeight,
                    "expiry" => $item["expiry_date"],
                    "qr" => $qrString
                ];
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Fout: " . $e->getMessage();
                $messageType = "error";
            }
        } else {
            $message = "🚫 Fout: Onvoldoende voorraad of item niet beschikbaar.";
            $messageType = "error";
        }
    }
}

// Haal beschikbare voorraad op
$inventory = $pdo->query("SELECT id, product_name, weight_grams, harvest_date, expiry_date FROM finished_inventory WHERE status = 'AVAILABLE' AND weight_grams > 0 ORDER BY harvest_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B09 - Verpakking & Label</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: sans-serif; background: #f4f6f9; margin: 0; padding: 2rem; }
        .container { max-width: 900px; margin: 0 auto; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        h1 { color: #8e44ad; border-bottom: 2px solid #9b59b6; padding-bottom: 0.5rem; }
        .alert { padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-weight: bold; }
        .alert.success { background: #d4edda; color: #155724; }
        .alert.error { background: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        input, select { padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; width: 100%; box-sizing: border-box; margin-bottom: 1rem; }
        button { background: #8e44ad; color: white; border: none; padding: 1rem 2rem; font-size: 1rem; border-radius: 4px; cursor: pointer; width: 100%; }
        button:hover { background: #732d91; }
        /* Label Print Style */
        #label-preview { border: 2px dashed #333; padding: 20px; width: 300px; background: white; margin-top: 20px; display: none; }
        .label-title { font-weight: bold; font-size: 1.2rem; text-align: center; }
        .label-detail { font-size: 0.9rem; margin: 5px 0; }
        .qr-placeholder { width: 100px; height: 100px; background: #eee; margin: 10px auto; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; text-align: center; }
        @media print {
            body * { visibility: hidden; }
            #label-preview, #label-preview * { visibility: visible; }
            #label-preview { position: absolute; left: 0; top: 0; border: 2px solid #000; }
        }
        .nav-links { margin-top: 2rem; }
        .nav-links a { margin-right: 15px; text-decoration: none; color: #0056b3; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>📦 B09: Verpakking & Label</h1>
        <?php if ($message): ?>
            <div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($labelData): ?>
            <div style="text-align:center;">
                <h3>✅ Verpakking Geslaagd!</h3>
                <button onclick="window.print()" style="width:auto; background:#27ae60;">🖨️ Print Label</button>
                <div id="label-preview" style="display:block;">
                    <div class="label-title"><?= htmlspecialchars($labelData["product"]) ?></div>
                    <div class="label-detail">Gewicht: <?= $labelData["weight"] ?> g</div>
                    <div class="label-detail">THT: <?= $labelData["expiry"] ?></div>
                    <div class="label-detail">Code: <?= $labelData["code"] ?></div>
                    <div class="qr-placeholder">
                        [QR Code]<br><?= substr($labelData["qr"], -10) ?>...
                    </div>
                    <small>Scan voor traceability</small>
                </div>
            </div>
            <hr style="margin: 2rem 0;">
        <?php endif; ?>

        <h3>Beschikbare Oogst</h3>
        <?php if (empty($inventory)): ?>
            <p>Geen beschikbare voorraad om te verpakken.</p>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="action" value="pack">
                <label>Kies Product</label>
                <select name="inventory_id" required>
                    <?php foreach ($inventory as $item): ?>
                        <option value="<?= $item["id"] ?>">
                            <?= htmlspecialchars($item["product_name"]) ?> (Beschikbaar: <?= $item["weight_grams"] ?> g)
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <label>Verpakkingsgrootte (gram)</label>
                <input type="number" name="pack_weight" step="1" required placeholder="Bijv. 50">
                
                <button type="submit">📦 Verpak & Genereer Label</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="nav-links">
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="b07_harvest.php">🧺 Oogst</a>
    </div>
</div>
</body>
</html>
