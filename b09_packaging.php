<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);
session_start();

$dbPath = __DIR__ . "/../database/MicrogreensERP_Live.sqlite";
if (!file_exists($dbPath)) { die("Database niet gevonden"); }

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("DB Fout: " . $e->getMessage()); }

$message = "";
$error = "";
$package_code = "";
$last_product = "";
$last_weight = 0;
$last_tht = "";

// --- VERWERKING FORMULIER ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "package") {
    // We ontvangen nu het ID van de inventory, niet alleen de naam
    $inventory_id = intval($_POST["inventory_id"] ?? 0);
    $weight = intval($_POST["weight"] ?? 0);
    $tht_date = $_POST["tht_date"] ?? date("Y-m-d", strtotime("+10 days"));
    
    if ($inventory_id > 0 && $weight > 0) {
        try {
            // 1. Haal productnaam en huidige voorraad op uit finished_inventory
            $stmt = $pdo->prepare("SELECT product_name, weight_grams FROM finished_inventory WHERE id = ? AND status = 'AVAILABLE'");
            $stmt->execute([$inventory_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($item) {
                $product_name = $item['product_name'];
                $current_stock = $item['weight_grams'];
                
                if ($current_stock >= $weight) {
                    // 2. Genereer unieke code
                    $package_code = "PKG-" . date("Ymd") . "-" . strtoupper(substr(md5(uniqid()), 0, 6));
                    
                    // 3. Update finished_inventory (verminder voorraad)
                    $stmt = $pdo->prepare("UPDATE finished_inventory SET weight_grams = weight_grams - ? WHERE id = ?");
                    $stmt->execute([$weight, $inventory_id]);
                    
                    // 4. Voeg toe aan packaging_units (MET finished_inventory_id, ZONDER product_name)
                    $stmt = $pdo->prepare("INSERT INTO packaging_units (finished_inventory_id, package_code, total_weight_g, packaging_date, qr_data, status) VALUES (?, ?, ?, ?, ?, 'AVAILABLE')");
                    $stmt->execute([$inventory_id, $package_code, $weight, date("Y-m-d H:i:s"), $package_code]);
                    
                    $message = "✅ Verpakt! Code: $package_code.";
                    $last_product = $product_name;
                    $last_weight = $weight;
                    $last_tht = $tht_date;
                } else {
                    $error = "Niet genoeg voorraad! Beschikbaar: " . $current_stock . "g.";
                }
            } else {
                $error = "Product niet gevonden of niet beschikbaar.";
            }
        } catch (Exception $e) {
            $error = "Fout bij verpakken: " . $e->getMessage();
        }
    } else {
        $error = "Vul product en gewicht in.";
    }
}

// --- OPHALEN BESCHIKBARE OOGST ---
$available_harvest = [];
try {
    // We selecteren ID, naam en som van gewicht
    $stmt = $pdo->query("SELECT id, product_name, SUM(weight_grams) as total_weight FROM finished_inventory WHERE status = 'AVAILABLE' GROUP BY product_name HAVING total_weight > 0");
    $available_harvest = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B09 - Verpakking & Label</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>input[type="date"] { width: 100%; padding: 10px; cursor: pointer; box-sizing: border-box; } 
        body { font-family: sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .btn-back { display: inline-block; margin-bottom: 20px; background: #2c3e50; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background: #27ae60; color: white; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; }
        button:hover { background: #219150; }
        @media print {
            body * { visibility: hidden; }
            #print-label, #print-label * { visibility: visible; }
            #print-label { position: absolute; left: 0; top: 0; width: 100%; text-align: center; }
            .no-print { display: none !important; }
        }
        #print-label { display: none; border: 2px solid #000; padding: 20px; width: 300px; margin: 0 auto; font-family: Arial, sans-serif; }
    </style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="btn-back">← Terug naar Dashboard</a>
    <h1>📦 B09: Verpakking & Label</h1>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <?= $message ?>
            <?php if ($package_code): ?>
                <br><br>
                <strong>Product:</strong> <?= htmlspecialchars($last_product) ?><br>
                <strong>Gewicht:</strong> <?= $last_weight ?> g<br>
                <strong>THT:</strong> <?= $last_tht ?><br>
                <strong>Code:</strong> <?= htmlspecialchars($package_code) ?>
                <div style="margin-top:15px;">
                    <button type="button" onclick="showPrint('<?= htmlspecialchars($package_code) ?>', '<?= htmlspecialchars($last_product) ?>', '<?= $last_weight ?>', '<?= $last_tht ?>')" style="background:#2c3e50; width:auto;">🖨️ Print Label met QR</button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- VERPAK FORMULIER -->
    <div class="no-print">
        <h2>Nieuwe Verpakking</h2>
        <form method="POST">
            <input type="hidden" name="action" value="package">
            <div class="form-group">
                <label>Kies Product (Beschikbare Oogst)</label>
                <select name="inventory_id" required>
                    <option value="">-- Selecteer --</option>
                    <?php foreach ($available_harvest as $item): ?>
                        <!-- We sturen nu het ID mee, niet de naam -->
                        <option value="<?= $item['id'] ?>">
                            <?= htmlspecialchars($item['product_name']) ?> (<?= number_format($item['total_weight']/1000, 2) ?> kg beschikbaar)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Verpakkingsgrootte (gram)</label>
                <input type="number" name="weight" placeholder="Bijv. 50" required min="1">
            </div>
            <div class="form-group">
                <label>THT Datum</label>
                <input type="date" name="tht_date" value="<?= date('Y-m-d', strtotime('+10 days')) ?>" required>
            </div>
            <button type="submit">📦 Verpak & Genereer Label</button>
        </form>
    </div>

    <!-- PRINT LABEL (Verborgen op scherm) -->
    <div id="print-label">
        <h2 style="margin:0;">PAKBON</h2>
        <p style="margin:5px 0; font-size:12px;">Microgreens ERP</p>
        <hr style="border-top:1px solid #000;">
        <div id="print-qr" style="margin:20px 0;"></div>
        <h3 id="print-product" style="margin:10px 0;"></h3>
        <p><strong>Gewicht:</strong> <span id="print-weight"></span> g</p>
        <p><strong>THT:</strong> <span id="print-tht"></span></p>
        <p><strong>Code:</strong> <span id="print-code"></span></p>
        <p style="font-size:10px; margin-top:20px;">Scan voor traceability</p>
    </div>
</div>

<script>
function showPrint(code, product, weight, tht) {
    document.getElementById('print-code').innerText = code;
    document.getElementById('print-product').innerText = product;
    document.getElementById('print-weight').innerText = weight;
    document.getElementById('print-tht').innerText = tht;
    
    var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(code);
    document.getElementById('print-qr').innerHTML = '<img src="' + qrUrl + '" alt="QR Code" />';
    
    document.getElementById('print-label').style.display = 'block';
    window.print();
    document.getElementById('print-label').style.display = 'none';
}
</script>
</body>
</html>