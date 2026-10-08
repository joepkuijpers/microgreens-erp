<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/auth.php';

// Initialiseer variabelen om warnings te voorkomen
$message = '';
$error = '';
$last_weight = 0;
$last_tht = "";
$last_product = "";
$package_code = "";
$available_harvest = [];

// Bepaal het juiste protocol (http of https) automatisch
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
$base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/microgreens";

// --- VERWERKING FORMULIER ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "package") {
    $inventory_id = intval($_POST["inventory_id"] ?? 0);
    $weight = intval($_POST["weight"] ?? 0);
    $tht_date = $_POST["tht_date"] ?? date("Y-m-d", strtotime("+10 days"));
    
    if ($inventory_id > 0 && $weight > 0) {
        try {
            // 1. Haal productnaam en huidige voorraad op
            $stmt = $db->prepare("SELECT product_name, weight_grams FROM finished_inventory WHERE id = ? AND status = 'AVAILABLE'");
            $stmt->execute([$inventory_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($item) {
                $product_name = $item['product_name'];
                $current_stock = $item['weight_grams'];
                
                if ($current_stock >= $weight) {
                    // 2. Genereer unieke code
                    $package_code = "PKG-" . date("Ymd") . "-" . strtoupper(substr(md5(uniqid()), 0, 6));
                    
                    // 3. Update finished_inventory (verminder voorraad)
                    $stmt = $db->prepare("UPDATE finished_inventory SET weight_grams = weight_grams - ? WHERE id = ?");
                    $stmt->execute([$weight, $inventory_id]);
                    
                    // 4. Voeg toe aan packaging_units
                    // We slaan de VOLLEDIGE URL op in qr_data voor gemak
                    $trace_url = $base_url . "/trace.php?id=" . $package_code;
                    
                    $stmt = $db->prepare("INSERT INTO packaging_units (finished_inventory_id, package_code, total_weight_g, packaging_date, qr_data, expiry_date, status) VALUES (?, ?, ?, ?, ?, ?, 'AVAILABLE')");
                    $stmt->execute([$inventory_id, $package_code, $weight, date("Y-m-d H:i:s"), $trace_url, $tht_date]);
                    
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
try {
    $stmt = $db->query("SELECT id, product_name, SUM(weight_grams) as total_weight FROM finished_inventory WHERE status = 'AVAILABLE' GROUP BY product_name HAVING total_weight > 0");
    // FRESH + FREEZE_DRY PRODUCTEN (NULL = backward compatibility als FRESH)
$stmt = $db->query("
    SELECT id, product_name, SUM(weight_grams) as total_weight, output_type 
    FROM finished_inventory 
    WHERE status = 'AVAILABLE' 
      AND (output_type IN ('FRESH', 'FREEZE_DRY') OR output_type IS NULL) 
    GROUP BY product_name 
    HAVING total_weight > 0
");
$available_harvest = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Fout bij laden voorraad: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'nl'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang['module_b09'] ?? 'Verpakking'; ?> - Microgreens ERP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Liberation Sans', sans-serif; background: #f4f4f9; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input[type="number"], input[type="date"] { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; margin-right: 5px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Print Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 400px; border-radius: 8px; text-align: center; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
        #print-area { border: 1px dashed #ccc; padding: 20px; margin-top: 20px; background: #fff; }
        @media print {
            body * { visibility: hidden; }
            #print-area, #print-area * { visibility: visible; }
            #print-area { position: absolute; left: 0; top: 0; width: 100%; border: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom:20px;">&larr; <?php echo $lang['back'] ?? 'Terug'; ?></a>
    
    <h1>📦 <?php echo $lang['module_b09'] ?? 'Verpakking & Label'; ?></h1>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?>
            <?php if ($package_code): ?>
                <button type="button" onclick="showPrint('<?php echo htmlspecialchars($package_code) ?>', '<?php echo htmlspecialchars($last_product) ?>', '<?php echo $last_weight ?>', '<?php echo $last_tht ?>')" class="btn btn-primary" style="margin-left:10px; background:#2c3e50;">🖨️ <?php echo $lang['print_label'] ?? 'Print Label'; ?></button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div style="background:#f9f9f9; padding:20px; border-radius:8px; border:1px solid #eee;">
        <h2><?php echo $lang['new_packaging'] ?? 'Nieuwe Verpakking'; ?></h2>
        <form method="POST">
            <input type="hidden" name="action" value="package">
            
            <div class="form-group">
                <label><?php echo $lang['choose_product'] ?? 'Kies Product (Beschikbare Oogst)'; ?></label>
                <select name="inventory_id" required>
                    <option value="">-- Selecteer --</option>
                    <?php foreach ($available_harvest as $item): ?>
                        <option value="<?php echo $item['id']; ?>">
                            [<?php echo $item["output_type"] === "FREEZE_DRY" ? "🔵 GEDROOGD" : "🟢 VERS"; ?>] <?php echo htmlspecialchars($item["product_name"]); ?> (<?php echo number_format($item["total_weight"], 0); ?>g beschikbaar)
                        </option>
                    <?php endforeach; ?>
                </select>
                    <small style="color:#666;">💡 Tip: [🔵 GEDROOGD] = freeze-dried product, [🟢 VERS] = vers geoogst</small>
            </div>

            <div class="form-group">
                <label><?php echo $lang['weight_grams'] ?? 'Gewicht (gram)'; ?></label>
                <input type="number" name="weight" min="1" required placeholder="Bijv. 50">
            </div>

            <div class="form-group">
                <label><?php echo $lang['expiry_date'] ?? 'THT Datum'; ?></label>
                <input type="date" name="tht_date" value="<?php echo date('Y-m-d', strtotime('+10 days')); ?>" required>
            </div>

            <button type="submit" class="btn btn-success">📦 <?php echo $lang['package_generate'] ?? 'Verpak & Genereer Label'; ?></button>
        </form>
    </div>
</div>

<!-- PRINT MODAL -->
<div id="printModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closePrint()">&times;</span>
        <h2 id="print-title">Label</h2>
        <div id="print-area">
            <h3 id="p-product" style="margin:0; font-size:1.4em;"></h3>
            <p style="margin:5px 0; color:#555;"><?php echo $lang['net_weight'] ?? 'Netto Gewicht'; ?>: <span id="p-weight" style="font-weight:bold;"></span> g</p>
            <p style="margin:5px 0; color:#555;">THT: <span id="p-tht" style="font-weight:bold;"></span></p>
            <div style="margin:15px 0;" id="print-qr"></div>
            <p style="font-size:0.8em; color:#888;" id="p-code"></p>
        </div>
        <button onclick="window.print()" class="btn btn-primary no-print" style="margin-top:15px;">🖨️ Print</button>
    </div>
</div>

<script>
function showPrint(code, product, weight, tht) {
    document.getElementById('p-product').textContent = product;
    document.getElementById('p-weight').textContent = weight;
    document.getElementById('p-tht').textContent = tht;
    document.getElementById('p-code').textContent = "Code: " + code;
    
    // GEBRUIK LOKALE QR GENERATOR
    var qrUrl = 'app/includes/qr_image.php?data=' + encodeURIComponent('<?php echo $base_url; ?>/trace.php?id=' + code) + '&size=8';
    document.getElementById('print-qr').innerHTML = '<img src="' + qrUrl + '" alt="QR Code" style="width:150px;height:150px;" />';
    
    document.getElementById('printModal').style.display = "block";
}

function closePrint() {
    document.getElementById('printModal').style.display = "none";
}

// Sluit modal als je er buiten klikt
window.onclick = function(event) {
    var modal = document.getElementById('printModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

</body>
</html>
