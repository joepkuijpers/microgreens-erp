<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
require_once "/var/www/html/microgreens/PHP/app/includes/db_connection.php";
$db = getDbConnection();

$message = ""; $messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "ADD_SEED") {
        try {
            $item = trim($_POST["item_name"]);
            $lot = trim($_POST["lot_number"]);
            $qty = (float)$_POST["quantity"];
            $sup = (int)($_POST["supplier_id"] ?? 0);
            $org = (int)($_POST["organic_certified"] ?? 0);
            $stmt = $db->prepare("INSERT INTO inventory (item_name, category, quantity, unit, lot_number, supplier_id, organic_certified) VALUES (?, 'Zaad', ?, 'g', ?, ?, ?)");
            $stmt->execute([$item, $qty, $lot, $sup, $org]);
            $message = "✅ Zaad toegevoegd!"; $messageType = "success"; $_POST = array();
        } catch (Exception $e) { $message = "❌ " . $e->getMessage(); $messageType = "error"; }
    }
    elseif ($action === "ADD_SUPPLIER") {
        try {
            $name = trim($_POST["new_supplier_name"]);
            if (empty($name)) throw new Exception("Naam verplicht");
            $db->prepare("INSERT INTO suppliers (name) VALUES (?)")->execute([$name]);
            $message = "✅ Leverancier toegevoegd!"; $messageType = "success";
        } catch (Exception $e) { $message = "❌ " . $e->getMessage(); $messageType = "error"; }
    }
    elseif ($action === "DELETE_ITEM") {
        $type = $_POST["delete_type"]; $id = (int)$_POST["delete_id"];
        try {
            if ($type === "SUPPLIER") {
                $cnt = $db->prepare("SELECT count(*) FROM seed_inventory WHERE supplier_id = ?"); $cnt->execute([$id]);
                if ($cnt->fetchColumn() > 0) throw new Exception("Leverancier heeft nog zaad in voorraad.");
                // Check ook algemene inventory als die gelinkt is
                $db->prepare("DELETE FROM suppliers WHERE id = ?")->execute([$id]);
                $message = "✅ Leverancier verwijderd.";
            } elseif ($type === "SEED") {
                $cnt = $db->prepare("SELECT count(*) FROM production_batches WHERE id = (SELECT batch_id FROM ... WHERE seed_id = ?)"); // Vereenvoudigd: check alleen of het in inventory staat
                // Voor nu: gewoon verwijderen uit inventory
                $db->prepare("DELETE FROM inventory WHERE id = ?")->execute([$id]);
                $message = "✅ Zaad verwijderd.";
            }
            $messageType = "success";
        } catch (Exception $e) { $message = "⚠️ " . $e->getMessage(); $messageType = "error"; }
    }
}

$inventory = $db->query("SELECT i.*, s.name as supplier_name FROM inventory i LEFT JOIN suppliers s ON i.supplier_id = s.id WHERE lower(i.category) LIKE '%zaad%' ORDER BY i.item_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$suppliers = $db->query("SELECT * FROM suppliers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8"><title>B01 Seed Inventory</title>
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
function togglePanel(id){var el=document.getElementById(id); el.style.display=el.style.display==="none"?"block":"none";}
function confirmDelete(type,id,name){if(confirm("Verwijderen? "+name)){var f=document.createElement("form");f.method="POST";f.innerHTML='<input type="hidden" name="action" value="DELETE_ITEM"><input type="hidden" name="delete_type" value="'+type+'"><input type="hidden" name="delete_id" value="'+id+'">';document.body.appendChild(f);f.submit();}}
</script>
</head>
<body>
<div class="container">
<h1>🌱 Seed Inventory <button type="button" class="btn-add" onclick="togglePanel('add_sup_panel')">+ Leverancier</button></h1>
<?php if($message): ?><div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div id="add_sup_panel" class="panel">
    <h3 style="margin-top:0; color:#1565c0;">➕ Nieuwe Leverancier</h3>
    <form method="POST" class="btn-row">
        <input type="hidden" name="action" value="ADD_SUPPLIER">
        <input type="text" name="new_supplier_name" placeholder="Naam" required style="flex:2;">
        <button type="submit" style="background:#1565c0;">Toevoegen</button>
    </form>
</div>

<form method="POST">
    <input type="hidden" name="action" value="ADD_SEED">
    <div class="btn-row">
        <div class="form-group" style="flex:2;">
            <label>Gewas <button type="button" class="btn-add" style="float:right; font-size:12px;" onclick="alert('Gebruik Crop Profiles voor nieuwe gewassen')">+</button></label>
            <input type="text" name="item_name" required placeholder="Bijv. Radicchio">
        </div>
        <div class="form-group" style="flex:1;">
            <label>Hoeveelheid (g)</label>
            <input type="number" step="0.1" name="quantity" required>
        </div>
    </div>
    <div class="form-group">
        <label>Lot Nummer (SKAL) <button type="button" class="btn-add" style="float:right; font-size:12px;" onclick="alert('Uniek nummer invullen')">+</button></label>
        <input type="text" name="lot_number" required placeholder="SKAL-XXX">
    </div>
    <div class="btn-row">
        <div class="form-group" style="flex:2;">
            <label>Leverancier <button type="button" class="btn-add" style="float:right; font-size:12px;" onclick="togglePanel('add_sup_panel')">+</button></label>
            <select name="supplier_id">
                <option value="0">-- Geen --</option>
                <?php foreach($suppliers as $s): ?><option value="<?= $s["id"] ?>"><?= htmlspecialchars($s["name"]) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="flex:1;">
            <label>Biologisch?</label>
            <select name="organic_certified"><option value="1">Ja (SKAL)</option><option value="0">Nee</option></select>
        </div>
    </div>
    <button type="submit">Toevoegen aan Voorraad</button>
</form>

<h3>Voorraad</h3>
<table>
<thead><tr><th>Gewas</th><th>Lot</th><th>Hoeveelheid</th><th>Leverancier</th><th>Bio</th><th>Actie</th></tr></thead>
<tbody>
<?php foreach($inventory as $i): ?>
<tr>
    <td><?= htmlspecialchars($i["item_name"]) ?></td>
    <td><?= htmlspecialchars($i["lot_number"] ?? "-") ?></td>
    <td><?= $i["quantity"] ?>g</td>
    <td><?= htmlspecialchars($i["supplier_name"] ?? "-") ?></td>
    <td><?= $i["organic_certified"] ? "✅" : "-" ?></td>
    <td class="action-cell"><button class="btn-delete" onclick="confirmDelete('SEED', <?= $i["id"] ?>, '<?= htmlspecialchars($i["item_name"]) ?>')">Verw</button></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h3>Leveranciers</h3>
<table>
<thead><tr><th>Naam</th><th>Actie</th></tr></thead>
<tbody>
<?php foreach($suppliers as $s): ?>
<tr>
    <td><?= htmlspecialchars($s["name"]) ?></td>
    <td class="action-cell"><button class="btn-delete" onclick="confirmDelete('SUPPLIER', <?= $s["id"] ?>, '<?= htmlspecialchars($s["name"]) ?>')">Verwijder</button></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</body>
</html>