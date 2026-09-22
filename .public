<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
require_once "/var/www/html/microgreens/PHP/app/includes/db_connection.php";
$db = getDbConnection();
require_once "/var/www/html/microgreens/PHP/app/b09_packaging.php";

$message = ""; $messageType = "";
$modalContent = ""; // Voor popups

// --- ACTIES VERWERKEN ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // 1. Normale Verpakking Registratie
    if ($action === "REGISTER_PACKAGING") {
        try {
            $outputId = (int)($_POST["output_id"] ?? 0);
            $pkgType = trim($_POST["package_type"] ?? "CLAMSHELL");
            $weight = (float)($_POST["weight_per_unit"] ?? 250);
            $qty = (int)($_POST["quantity"] ?? 1);
            $label = trim($_POST["label_code"] ?? "PKG-" . time());
            $bestBefore = $_POST["best_before"] ?? date("Y-m-d", strtotime("+7 days"));

            if ($outputId === 0) throw new Exception("Geen output geselecteerd.");
            
            $stmt = $db->prepare("SELECT quantity FROM production_outputs WHERE id = ? AND status = 'REGISTERED'");
            $stmt->execute([$outputId]);
            $avail = $stmt->fetchColumn();
            if (!$avail) throw new Exception("Output niet gevonden of al verwerkt.");
            if (($weight * $qty) > $avail) throw new Exception("Niet genoeg gewicht! Beschikbaar: {$avail}g, Nodig: " . ($weight*$qty) . "g");

            b09_create_packaging($db, "OUTPUT", $outputId, $pkgType, $weight, $qty, $label, $bestBefore, null);
            
            $message = "✅ Succes! {$qty} x {$pkgType} geregistreerd.";
            $messageType = "success";
            $_POST = array();
        } catch (Exception $e) {
            $message = "❌ Fout: " . $e->getMessage();
            $messageType = "error";
        }
    }

    // 2. NIEUWE OUTPUT TOEVOEGEN (Direct vanuit dropdown)
    elseif ($action === "ADD_OUTPUT") {
        try {
            $batchId = (int)$_POST["new_batch_id"];
            $quantity = (float)$_POST["new_quantity"];
            $unit = "g";
            $notes = trim($_POST["new_notes"] ?? "");
            
            if ($batchId === 0 || $quantity <= 0) throw new Exception("Ongeldige batch of hoeveelheid.");

            $stmt = $db->prepare("INSERT INTO production_outputs (batch_id, output_type, quantity, unit, status, notes) VALUES (?, 'FRESH', ?, ?, 'REGISTERED', ?)");
            $stmt->execute([$batchId, $quantity, $unit, $notes]);
            
            $message = "✅ Nieuwe output toegevoegd! Je kunt deze nu selecteren.";
            $messageType = "success";
            // We verversen de data niet direct via POST reset, zodat de user de nieuwe selectie kan zien
        } catch (Exception $e) {
            $message = "❌ Fout bij toevoegen output: " . $e->getMessage();
            $messageType = "error";
        }
    }

    // 3. NIEUW VERPAKKINGSTYPE TOEVOEGEN (Simulatie: we gebruiken een custom value veld of voegen toe aan lijst)
    // Voor nu: we accepteren custom input in het textveld als "CUSTOM" geselecteerd is, 
    // maar voor de dropdown logica voegen we een "Anders..." optie toe die een textveld toont.
    
    // 4. VERWIJDEREN (DELETE) MET INTEGRITEITSCHECK
    elseif ($action === "DELETE_ITEM") {
        $type = $_POST["delete_type"]; // "OUTPUT" of "PACKAGING"
        $id = (int)$_POST["delete_id"];
        
        try {
            if ($type === "OUTPUT") {
                // Check afhankelijkheden: Is deze output al gebruikt in packaging_units?
                $stmt = $db->prepare("SELECT count(*) FROM packaging_units WHERE source_id = ? AND source_type = 'OUTPUT'");
                $stmt->execute([$id]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    throw new Exception("Kan niet verwijderen! Deze output is al gebruikt in {$count} verpakking(en). Verwijder eerst de verpakkingen.");
                }
                
                // Veilig om te verwijderen
                $stmt = $db->prepare("DELETE FROM production_outputs WHERE id = ?");
                $stmt->execute([$id]);
                $message = "✅ Output verwijderd.";
            } 
            elseif ($type === "PACKAGING") {
                // Check afhankelijkheden: Is deze verpakking gelinkt aan een order? (Indien tabel bestaat)
                // Voor nu: gewoon verwijderen uit packaging_units
                $stmt = $db->prepare("DELETE FROM packaging_units WHERE id = ?");
                $stmt->execute([$id]);
                $message = "✅ Verpakking verwijderd.";
            }
            $messageType = "success";
        } catch (Exception $e) {
            $message = "⚠️ Verwijderen mislukt: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// --- DATA OPHALEN ---
$outputs = $db->query("SELECT po.id, po.quantity, po.unit, pb.batch_code, pb.crop_type FROM production_outputs po JOIN production_batches pb ON po.batch_id = pb.id WHERE po.status='REGISTERED' ORDER BY po.produced_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$recent = $db->query("SELECT * FROM packaging_units ORDER BY packed_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
$batches = $db->query("SELECT id, batch_code, crop_type FROM production_batches WHERE status IN ('ACTIVE', 'PLANNED') ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8"><title>B09 Verpakking & Beheer</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{font-family:sans-serif;background:#f4f6f8;padding:20px;}
.container{max-width:900px;margin:0 auto;background:#fff;padding:25px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
h1{color:#2e7d32;margin-top:0; display:flex; justify-content:space-between; align-items:center;}
.form-group{margin-bottom:15px; position:relative;}
label{display:block;font-weight:bold;margin-bottom:5px;}
select,input{width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;}
.btn-row{display:flex; gap:10px; align-items:center;}
button{background:#2e7d32;color:#fff;border:none;padding:10px 15px;font-size:14px;border-radius:4px;cursor:pointer;font-weight:bold;}
button:hover{background:#1b5e20;}
button:disabled{background:#ccc;cursor:not-allowed;}
.btn-add{background:#1976d2; padding:5px 10px; font-size:16px;}
.btn-add:hover{background:#0d47a1;}
.btn-delete{background:#d32f2f; padding:5px 8px; font-size:12px; margin-left:auto;}
.btn-delete:hover{background:#b71c1c;}
.alert{padding:15px;border-radius:4px;margin-bottom:15px;font-weight:bold;}
.success{background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;}
.error{background:#ffebee;color:#c62828;border:1px solid #ffcdd2;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
th,td{padding:10px;text-align:left;border-bottom:1px solid #ddd;}
th{background:#f5f5f5;}
.action-cell{text-align:right;}
small{color:#666; font-size:0.85em;}
/* Modal Styles */
.modal{display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);}
.modal-content{background-color:#fff; margin:10% auto; padding:20px; border-radius:8px; width:90%; max-width:500px; position:relative;}
.close{color:#aaa; float:right; font-size:28px; font-weight:bold; cursor:pointer;}
.close:hover{color:#000;}
</style>
<script>
function toggleAddOutput() {
    var div = document.getElementById("add_output_div");
    div.style.display = div.style.display === "none" ? "block" : "none";
}
function confirmDelete(type, id, name) {
    if(confirm("Weet je zeker dat je dit wilt verwijderen?\nItem: " + name + "\n\nAls er afhankelijke records zijn (bijv. verpakkingen gekoppeld aan deze output), wordt het verwijderen geblokkeerd om data-integriteit te waarborgen.")) {
        var form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = '<input type="hidden" name="action" value="DELETE_ITEM">' +
                         '<input type="hidden" name="delete_type" value="'+type+'">'+
                         '<input type="hidden" name="delete_id" value="'+id+'">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
</head>
<body>
<div class="container">
<h1>
    📦 Verpakking & Beheer
    <button type="button" class="btn-add" onclick="toggleAddOutput()" title="Nieuwe Output Aanmaken">+ Nieuwe Output</button>
</h1>

<?php if($message): ?><div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<!-- NIEUWE OUTPUT FORMULIER (Verborgen/Sticky) -->
<div id="add_output_div" style="display:none; background:#e3f2fd; padding:15px; border-radius:4px; margin-bottom:15px; border:1px solid #90caf9;">
    <h3 style="margin-top:0; color:#1565c0;">➕ Nieuwe Oogst Output Toevoegen</h3>
    <form method="POST" class="btn-row">
        <input type="hidden" name="action" value="ADD_OUTPUT">
        <div style="flex:2;">
            <label>Batch</label>
            <select name="new_batch_id" required>
                <option value="">Kies Batch...</option>
                <?php foreach($batches as $b): ?>
                <option value="<?= $b["id"] ?>"><?= htmlspecialchars($b["batch_code"]) ?> (<?= htmlspecialchars($b["crop_type"]) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex:1;">
            <label>Hoeveelheid (g)</label>
            <input type="number" step="0.1" name="new_quantity" placeholder="Bijv. 500" required>
        </div>
        <div style="flex:1;">
            <label>Notities</label>
            <input type="text" name="new_notes" placeholder="Optioneel">
        </div>
        <div style="flex:0; align-self:flex-end;">
            <button type="submit" style="background:#1565c0;">Toevoegen</button>
        </div>
    </form>
</div>

<form method="POST">
    <input type="hidden" name="action" value="REGISTER_PACKAGING">
    <div class="form-group">
        <label>Beschikbare Oogst (Output)</label>
        <div class="btn-row">
            <select name="output_id" required style="flex:1;">
                <option value="">-- Selecteer Output --</option>
                <?php foreach($outputs as $o): ?>
                <option value="<?= $o["id"] ?>"><?= htmlspecialchars($o["batch_code"]) ?> (<?= htmlspecialchars($o["crop_type"]) ?>) - <?= $o["quantity"] ?><?= $o["unit"] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <small>Tip: Heb je geen output? Klik boven op "+ Nieuwe Output".</small>
    </div>
    
    <div class="form-group">
        <label>Leveringsvorm / Type</label>
        <select name="package_type" id="pkg_type" onchange="updateDefaults()">
            <option value="CLAMSHELL">Clamshell (Retail)</option>
            <option value="BAG">Zak (Retail)</option>
            <option value="BOX">Doos (Groter)</option>
            <option value="BULK">🚚 BULK / LOS (Horeca)</option>
            <option value="CUSTOM">📝 Anders... (Handmatig invoeren)</option>
        </select>
        <input type="text" name="package_type_custom" id="pkg_type_custom" placeholder="Typ hier je eigen type..." style="display:none; margin-top:5px;">
    </div>

    <div class="form-group">
        <label id="weight_label">Gewicht per eenheid (gram)</label>
        <input type="number" step="0.1" id="weight_field" name="weight_per_unit" value="250" required>
    </div>

    <div class="form-group">
        <label>Aantal eenheden</label>
        <input type="number" name="quantity" value="1" min="1" required>
    </div>

    <div class="form-group">
        <label>Referentiecode / Label</label>
        <input type="text" id="label_field" name="label_code" value="PKG-<?= date("Ymd-His") ?>" required>
    </div>

    <div class="form-group">
        <label>THT Datum</label>
        <input type="date" name="best_before" value="<?= date("Y-m-d", strtotime("+7 days")) ?>">
    </div>

    <button type="submit" <?= empty($outputs) ? "disabled" : "" ?>>Registreren</button>
</form>

<h3 style="margin-top:30px;">Recente Registraties <small>(Rechtsklik of knop om te verwijderen)</small></h3>
<table>
<thead><tr><th>Ref</th><th>Type</th><th>Aantal</th><th>Gewicht</th><th>Actie</th></tr></thead>
<tbody>
<?php foreach($recent as $r): ?>
<tr>
    <td><?= htmlspecialchars($r["label_code"]) ?></td>
    <td><?= htmlspecialchars($r["package_type"]) ?></td>
    <td><?= $r["quantity_units"] ?></td>
    <td><?= $r["total_weight_g"] ?>g</td>
    <td class="action-cell">
        <button class="btn-delete" onclick="confirmDelete('PACKAGING', <?= $r["id"] ?>, '<?= htmlspecialchars($r["label_code"]) ?>')">Verwijder</button>
    </td>
</tr>
<?php endforeach; ?>
<?php if(empty($recent)): ?><tr><td colspan="5" style="text-align:center;color:#999;">Nog geen registraties.</td></tr><?php endif; ?>
</tbody>
</table>

<h3 style="margin-top:30px;">Beheer Outputs <small>(Verwijder ongebruikte outputs)</small></h3>
<table>
<thead><tr><th>Batch</th><th>Gewicht</th><th>Datum</th><th>Actie</th></tr></thead>
<tbody>
<?php foreach($outputs as $o): ?>
<tr>
    <td><?= htmlspecialchars($o["batch_code"]) ?> (<?= htmlspecialchars($o["crop_type"]) ?>)</td>
    <td><?= $o["quantity"] ?><?= $o["unit"] ?></td>
    <td><?= $o["produced_at"] ?></td>
    <td class="action-cell">
        <button class="btn-delete" onclick="confirmDelete('OUTPUT', <?= $o["id"] ?>, '<?= htmlspecialchars($o["batch_code"]) ?>')">Verwijder</button>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</div>

<script>
function updateDefaults() {
    var typeSelect = document.getElementById("pkg_type");
    var customInput = document.getElementById("pkg_type_custom");
    var weightField = document.getElementById("weight_field");
    var labelField = document.getElementById("label_field");
    
    if (typeSelect.value === "CUSTOM") {
        customInput.style.display = "block";
        customInput.name = "package_type"; // Wissel naam om deze te versturen
        typeSelect.name = ""; // Zet origineel op non-actief
    } else {
        customInput.style.display = "none";
        customInput.name = "";
        typeSelect.name = "package_type";
        
        if (typeSelect.value === "BULK") {
            weightField.value = "1000";
            labelField.value = "BULK-" + new Date().toISOString().slice(0,10).replace(/-/g,"");
        } else {
            weightField.value = "250";
            labelField.value = "PKG-" + Date.now();
        }
    }
}
</script>
</body>
</html>