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
    $seedLotId = $_POST["seed_lot_id"] ?? null;
    $supplierId = $_POST["supplier_id"] ?? null;
    $stockGrams = $_POST["stock_grams"] ?? 0;
    
    // Bereken timestamp in PHP (omzeilt SQLite quote issues)
    $currentTimestamp = date("Y-m-d H:i:s");

    // 1. Organic Gate Check
    $stmt = $pdo->prepare("SELECT s.organic_status, s.certificate_valid_until FROM seed_lots s WHERE s.id = ?");
    $stmt->execute([$seedLotId]);
    $seedData = $stmt->fetch(PDO::FETCH_ASSOC);

    $gateOpen = false;
    if ($seedData) {
        $validUntil = strtotime($seedData["certificate_valid_until"]);
        $now = time();
        if ($seedData["organic_status"] === "CERTIFIED" && $validUntil > $now) {
            $gateOpen = true;
        }
    }

    if (!$gateOpen) {
        $message = "🚫 ORGANIC GATE GESLOTEN: Dit zaadlot heeft geen geldig biologisch certificaat of is verlopen.";
        $messageType = "error";
    } else {
        // 2. Insert met PHP timestamp (GEEN datetime(now) in SQL)
        $sql = "INSERT INTO seed_inventory (seed_lot_id, supplier_id, stock_grams, added_at) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$seedLotId, $supplierId, $stockGrams, $currentTimestamp])) {
            $message = "✅ Zaad succesvol toegevoegd. Organic status geverifieerd.";
            $messageType = "success";
        } else {
            $message = "Fout bij opslaan.";
            $messageType = "error";
        }
    }
}

$suppliers = $pdo->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$seedLots = $pdo->query("SELECT id, variety, organic_status, certificate_valid_until FROM seed_lots ORDER BY variety")->fetchAll(PDO::FETCH_ASSOC);

// --- TOEVOEGEN LOGICA seed_inventory ---
if (isset($_GET['action']) && $_GET['action'] == 'add_new' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $cols_seed_inventory = [];
        $vals_seed_inventory = [];
        $bind_seed_inventory = [];

        if (isset($_POST['variety']) && $_POST['variety'] != '') {
            $cols_seed_inventory[] = "variety";
            $vals_seed_inventory[] = "?";
            $bind_seed_inventory[] = $_POST['variety'];
        }

        if (isset($_POST['supplier_id']) && $_POST['supplier_id'] != '') {
            $cols_seed_inventory[] = "supplier_id";
            $vals_seed_inventory[] = "?";
            $bind_seed_inventory[] = $_POST['supplier_id'];
        }

        if (isset($_POST['lot_number']) && $_POST['lot_number'] != '') {
            $cols_seed_inventory[] = "lot_number";
            $vals_seed_inventory[] = "?";
            $bind_seed_inventory[] = $_POST['lot_number'];
        }

        if (isset($_POST['weight_grams']) && $_POST['weight_grams'] != '') {
            $cols_seed_inventory[] = "weight_grams";
            $vals_seed_inventory[] = "?";
            $bind_seed_inventory[] = $_POST['weight_grams'];
        }

        if (isset($_POST['purchase_date']) && $_POST['purchase_date'] != '') {
            $cols_seed_inventory[] = "purchase_date";
            $vals_seed_inventory[] = "?";
            $bind_seed_inventory[] = $_POST['purchase_date'];
        }

        if (isset($_POST['is_organic']) && $_POST['is_organic'] != '') {
            $cols_seed_inventory[] = "is_organic";
            $vals_seed_inventory[] = "?";
            $bind_seed_inventory[] = $_POST['is_organic'];
        }

        if (!empty($cols_seed_inventory)) {
            $sql = "INSERT INTO seed_inventory (" . implode(",", $cols_seed_inventory) . ") VALUES (" . implode(",", $vals_seed_inventory) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bind_seed_inventory);
            $success_msg_seed_inventory = "✅ Seed Inventory succesvol toegevoegd!";
        }
    } catch (Exception $e) {
        $error_msg_seed_inventory = "Fout: " . $e->getMessage();
    }
}
// --- EINDE TOEVOEGEN LOGICA ---

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B01 - Seed Inventory (Organic)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: sans-serif; background: #f4f6f9; margin: 0; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem; }
        .alert { padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-weight: bold; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: #555; }
        select, input { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; box-sizing: border-box; }
        button { background: #3498db; color: white; border: none; padding: 1rem 2rem; font-size: 1rem; border-radius: 4px; cursor: pointer; width: 100%; }
        button:hover { background: #2980b9; }
        button:disabled { background: #ccc; cursor: not-allowed; }
        .gate-status { font-size: 0.9rem; margin-top: 0.5rem; }
        .gate-open { color: #28a745; }
        .gate-closed { color: #dc3545; }
        a { display: inline-block; margin-top: 1rem; color: #555; text-decoration: none; }
    </style>
    <script>
        function checkOrganicGate() {
            const select = document.getElementById("seed_lot_id");
            const statusDiv = document.getElementById("gate_status");
            const selectedOption = select.options[select.selectedIndex];
            const status = selectedOption.getAttribute("data-status");
            const validUntil = selectedOption.getAttribute("data-valid");
            const now = new Date();
            const validDate = new Date(validUntil);
            
            if (status === "CERTIFIED" && validDate > now) {
                statusDiv.innerHTML = "✅ Organic Gate: OPEN (Geverifieerd)";
                statusDiv.className = "gate-status gate-open";
                document.getElementById("submitBtn").disabled = false;
            } else {
                statusDiv.innerHTML = "🚫 Organic Gate: GESLOTEN (Certificaat ongeldig/verlopen)";
                statusDiv.className = "gate-status gate-closed";
                document.getElementById("submitBtn").disabled = true;
            }
        }
    </script>
</head>
<body>
<div class="container">
    <h1>🌱 B01: Seed Inventory & Organic Gate</h1>
    <div style="margin: 20px 0; padding: 15px; background: #e8f5e9; border-radius: 5px; border-left: 5px solid #27ae60;">
        <a href="?action=add_new" style="text-decoration: none; color: #27ae60; font-weight: bold; font-size: 1.1em;">
            ➕ Nieuw Zaadlot
        </a>
    </div>
    
    
    <?php if ($message): ?>
        <div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="supplier_id">Leverancier</label>
            <select name="supplier_id" required>
                <option value="">-- Kies Leverancier --</option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s["id"] ?>"><?= htmlspecialchars($s["name"]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="seed_lot_id">Zaadlot (Variëteit)</label>
            <select name="seed_lot_id" id="seed_lot_id" required onchange="checkOrganicGate()">
                <option value="">-- Kies Zaadlot --</option>
                <?php foreach ($seedLots as $sl): ?>
                    <option value="<?= $sl["id"] ?>" 
                            data-status="<?= htmlspecialchars($sl["organic_status"]) ?>" 
                            data-valid="<?= htmlspecialchars($sl["certificate_valid_until"]) ?>">
                        <?= htmlspecialchars($sl["variety"]) ?> (Status: <?= htmlspecialchars($sl["organic_status"]) ?>, Geldig tot: <?= htmlspecialchars($sl["certificate_valid_until"]) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <div id="gate_status" class="gate-status">Selecteer een zaadlot om status te checken...</div>
        </div>

        <div class="form-group">
            <label for="stock_grams">Aanvangshoeveelheid (gram)</label>
            <input type="number" name="stock_grams" step="0.01" required placeholder="Bijv. 500">
        </div>

        <button type="submit" id="submitBtn" disabled>🔒 Toevoegen (Wacht op validatie)</button>
    </form>

    <a href="dashboard.php">← Terug naar Dashboard</a>
</div>

<!-- FORMULIER seed_inventory -->
<?php if (isset($_GET['action']) && $_GET['action'] == 'add_new'): ?>
<div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-top: 20px; border: 1px solid #ddd;">
    <h2>➕ Nieuw Zaadlot</h2>
    <?php if (isset($success_msg_seed_inventory)): ?><div style="color: green; margin-bottom: 10px; font-weight:bold;"><?php echo $success_msg_seed_inventory; ?></div><?php endif; ?>
    <?php if (isset($error_msg_seed_inventory)): ?><div style="color: red; margin-bottom: 10px; font-weight:bold;"><?php echo $error_msg_seed_inventory; ?></div><?php endif; ?>
    
    <form method="POST">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
<div><label style='display:block; margin-bottom:5px; font-weight:bold;'>Variëteit / Gewas</label><input type='text' name='variety' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;' required></div><div><label style='display:block; margin-bottom:5px; font-weight:bold;'>Leverancier ID</label><input type='number' name='supplier_id' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;' required></div><div><label style='display:block; margin-bottom:5px; font-weight:bold;'>Lot Nummer</label><input type='text' name='lot_number' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;' required></div><div><label style='display:block; margin-bottom:5px; font-weight:bold;'>Gewicht (gram)</label><input type='number' name='weight_grams' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;' required></div><div><label style='display:block; margin-bottom:5px; font-weight:bold;'>Aankoopdatum</label><input type='date' name='purchase_date' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;' required></div><div><label style='display:block; margin-bottom:5px; font-weight:bold;'>Biologisch?</label><select name='is_organic' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;'><option value='1'>1</option><option value='0'>0</option></select></div>
        </div>
        <div style="margin-top: 20px;">
            <button type="submit" style="background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px;">💾 Opslaan</button>
            <a href="?" style="margin-left: 10px; text-decoration: none; color: #555;">Annuleren</a>
        </div>
    </form>
</div>
<?php endif; ?>

</body>
</html>
