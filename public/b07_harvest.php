<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
require_once "/var/www/html/microgreens/PHP/app/includes/db_connection.php";
$db = getDbConnection();
require_once "/var/www/html/microgreens/PHP/app/includes/b07_harvest_engine.php";

$message = ""; $messageType = "";

// --- ACTIES ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // 1. OOGST REGISTREREN
    if ($action === "REGISTER_HARVEST") {
        $batchId = (int)($_POST["batch_id"] ?? 0);
        $harvestDate = $_POST["harvest_date"] ?? date("Y-m-d");
        $weightGrams = (float)($_POST["weight_grams"] ?? 0);
        $productId = (int)($_POST["product_id"] ?? 0);
        $finishedQty = (float)($_POST["finished_quantity"] ?? 0);
        $notes = $_POST["quality_notes"] ?? "";
        $userId = 1; // Hardcoded voor nu

        if ($batchId > 0 && $weightGrams > 0 && $productId > 0) {
            $result = b07_register_harvest($db, $batchId, $harvestDate, $weightGrams, $productId, $finishedQty, $notes, $userId);
            $message = $result["message"];
            $messageType = $result["success"] ? "success" : "error";
            if ($result["success"]) $_POST = array();
        } else {
            $message = "❌ Vul alle verplichte velden correct in.";
            $messageType = "error";
        }
    }

    // 2. NIEUWE BATCH TOEVOEGEN
    elseif ($action === "ADD_BATCH") {
        try {
            $crop = trim($_POST["new_crop"]);
            $sowDate = $_POST["new_sow_date"] ?? date("Y-m-d");
            $trays = (int)($_POST["new_trays"] ?? 1);
            if (empty($crop)) throw new Exception("Gewas is verplicht.");
            $stmt = $db->prepare("INSERT INTO production_batches (batch_code, crop_type, status, started_at, rack_position) VALUES (?, ?, 'ACTIVE', ?, ?)");
            $code = "BATCH-" . strtoupper(substr($crop, 0, 3)) . "-" . date("ymd");
            $stmt->execute([$code, $crop, $sowDate, $trays]);
            $message = "✅ Nieuwe batch toegevoegd: $code";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "❌ Fout: " . $e->getMessage();
            $messageType = "error";
        }
    }

    // 3. NIEUW PRODUCT TOEVOEGEN
    elseif ($action === "ADD_PRODUCT") {
        try {
            $name = trim($_POST["new_product_name"]);
            $unit = trim($_POST["new_product_unit"] ?? "stuk");
            if (empty($name)) throw new Exception("Naam is verplicht.");
            $stmt = $db->prepare("INSERT INTO products (name, unit) VALUES (?, ?)");
            $stmt->execute([$name, $unit]);
            $message = "✅ Nieuw product toegevoegd: $name";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "❌ Fout: " . $e->getMessage();
            $messageType = "error";
        }
    }

    // 4. VERWIJDEREN (SAFE DELETE)
    elseif ($action === "DELETE_ITEM") {
        $type = $_POST["delete_type"];
        $id = (int)$_POST["delete_id"];
        try {
            if ($type === "BATCH") {
                $stmt = $db->prepare("SELECT count(*) FROM production_outputs WHERE batch_id = ?");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) throw new Exception("Kan niet verwijderen: er zijn al output/oogsten aan deze batch gekoppeld.");
                $db->prepare("DELETE FROM production_batches WHERE id = ?")->execute([$id]);
                $message = "✅ Batch verwijderd.";
            } elseif ($type === "PRODUCT") {
                $stmt = $db->prepare("SELECT count(*) FROM finished_inventory WHERE product_id = ?");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) throw new Exception("Kan niet verwijderen: er is voorraad van dit product.");
                $db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
                $message = "✅ Product verwijderd.";
            }
            $messageType = "success";
        } catch (Exception $e) {
            $message = "⚠️ " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// DATA
$batches = $db->query("SELECT id, batch_code, crop_type, started_at, rack_position FROM production_batches WHERE status IN ('ACTIVE', 'PLANNED') ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$products = $db->query("SELECT id, name, unit FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$harvests = $db->query("SELECT h.id, h.harvest_date, h.weight_grams, pb.batch_code, pb.crop_type, p.name as prod_name FROM harvest_logs h JOIN production_batches pb ON h.batch_id = pb.id JOIN products p ON h.product_id = p.id ORDER BY h.harvest_date DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8"><title>B07 Oogst</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{font-family:sans-serif;background:#f4f6f8;padding:20px;}
.container{max-width:900px;margin:0 auto;background:#fff;padding:25px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
h1{color:#2e7d32; display:flex; justify-content:space-between; align-items:center;}
.form-group{margin-bottom:15px;}
label{display:block;font-weight:bold;margin-bottom:5px;}
select,input{width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;}
.btn-row{display:flex; gap:10px; align-items:center;}
button{background:#2e7d32;color:#fff;border:none;padding:10px 15px;font-size:14px;border-radius:4px;cursor:pointer;font-weight:bold;}
button:hover{background:#1b5e20;}
button:disabled{background:#ccc;}
.btn-add{background:#1976d2; padding:5px 10px; font-size:16px;}
.btn-delete{background:#d32f2f; padding:5px 8px; font-size:12px; margin-left:auto;}
.alert{padding:15px;border-radius:4px;margin-bottom:15px;font-weight:bold;}
.success{background:#e8f5e9;color:#2e7d32;}
.error{background:#ffebee;color:#c62828;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
th,td{padding:10px;text-align:left;border-bottom:1px solid #ddd;}
th{background:#f5f5f5;}
.action-cell{text-align:right;}
.panel{display:none; background:#e3f2fd; padding:15px; border-radius:4px; margin-bottom:15px; border:1px solid #90caf9;}
</style>
<script>
function togglePanel(id) {
    var el = document.getElementById(id);
    el.style.display = el.style.display === "none" ? "block" : "none";
}
function confirmDelete(type, id, name) {
    if(confirm("Verwijderen?\nItem: " + name + "\n\nWordt geblokkeerd als er afhankelijke data is.")) {
        var f = document.createElement("form"); f.method="POST";
        f.innerHTML = '<input type="hidden" name="action" value="DELETE_ITEM"><input type="hidden" name="delete_type" value="'+type+'"><input type="hidden" name="delete_id" value="'+id+'">';
        document.body.appendChild(f); f.submit();
    }
}
</script>
</head>
<body>
<div class="container">
<h1>🌾 Oogst (B07) <button type="button" class="btn-add" onclick="togglePanel('add_batch_panel')">+ Batch</button></h1>
<?php if($message): ?><div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<!-- PANEL: NIEUWE BATCH -->
<div id="add_batch_panel" class="panel">
    <h3 style="margin-top:0; color:#1565c0;">➕ Nieuwe Batch</h3>
    <form method="POST" class="btn-row">
        <input type="hidden" name="action" value="ADD_BATCH">
        <input type="text" name="new_crop" placeholder="Gewas (bijv. Radicchio)" required style="flex:2;">
        <input type="date" name="new_sow_date" value="<?= date("Y-m-d") ?>" style="flex:1;">
        <input type="number" name="new_trays" placeholder="Trays" value="1" style="flex:1;">
        <button type="submit" style="background:#1565c0;">Toevoegen</button>
    </form>
</div>

<!-- PANEL: NIEUW PRODUCT -->
<div id="add_product_panel" class="panel">
    <h3 style="margin-top:0; color:#1565c0;">➕ Nieuw Product</h3>
    <form method="POST" class="btn-row">
        <input type="hidden" name="action" value="ADD_PRODUCT">
        <input type="text" name="new_product_name" placeholder="Naam (bijv. Bakje 250g)" required style="flex:2;">
        <input type="text" name="new_product_unit" placeholder="Eenheid" value="stuk" style="flex:1;">
        <button type="submit" style="background:#1565c0;">Toevoegen</button>
    </form>
</div>

<form method="POST">
    <input type="hidden" name="action" value="REGISTER_HARVEST">
    <div class="form-group">
        <label>Batch <button type="button" class="btn-add" style="float:right; font-size:12px; padding:2px 8px;" onclick="togglePanel('add_batch_panel')">+</button></label>
        <select name="batch_id" required>
            <option value="">-- Selecteer --</option>
            <?php foreach($batches as $b): ?>
            <option value="<?= $b["id"] ?>"><?= htmlspecialchars($b["batch_code"]) ?> (<?= htmlspecialchars($b["crop_type"]) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Product <button type="button" class="btn-add" style="float:right; font-size:12px; padding:2px 8px;" onclick="togglePanel('add_product_panel')">+</button></label>
        <select name="product_id" required>
            <option value="">-- Selecteer --</option>
            <?php foreach($products as $p): ?>
            <option value="<?= $p["id"] ?>"><?= htmlspecialchars($p["name"]) ?> (<?= $p["unit"] ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="btn-row">
        <div class="form-group" style="flex:1;">
            <label>Gewicht (g)</label>
            <input type="number" step="0.1" name="weight_grams" required>
        </div>
        <div class="form-group" style="flex:1;">
            <label>Aantal Eindproduct</label>
            <input type="number" step="0.1" name="finished_quantity" required>
        </div>
    </div>
    <div class="form-group">
        <label>Notities</label>
        <textarea name="quality_notes" rows="2"></textarea>
    </div>
    <button type="submit">Oogst Registreren</button>
</form>

<h3>Recente Oogsten</h3>
<table>
<thead><tr><th>Datum</th><th>Batch</th><th>Product</th><th>Gewicht</th><th>Actie</th></tr></thead>
<tbody>
<?php foreach($harvests as $h): ?>
<tr>
    <td><?= $h["harvest_date"] ?></td>
    <td><?= htmlspecialchars($h["batch_code"]) ?></td>
    <td><?= htmlspecialchars($h["prod_name"]) ?></td>
    <td><?= $h["weight_grams"] ?>g</td>
    <td class="action-cell"><button class="btn-delete" onclick="confirmDelete('BATCH', <?= $h["id"] ?>, 'Oogst #<?= $h["id"] ?>')">Verw</button></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h3>Beheer Batches</h3>
<table>
<thead><tr><th>Code</th><th>Gewas</th><th>Actie</th></tr></thead>
<tbody>
<?php foreach($batches as $b): ?>
<tr>
    <td><?= htmlspecialchars($b["batch_code"]) ?></td>
    <td><?= htmlspecialchars($b["crop_type"]) ?></td>
    <td class="action-cell"><button class="btn-delete" onclick="confirmDelete('BATCH', <?= $b["id"] ?>, '<?= htmlspecialchars($b["batch_code"]) ?>')">Verwijder</button></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</body>
</html>