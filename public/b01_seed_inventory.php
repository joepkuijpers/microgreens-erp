<?php
error_reporting(E_ALL); ini_set('display_errors', 1); session_start();
$DB_PATH = '/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite';
$db = new SQLite3($DB_PATH);

$lang = $_SESSION['lang'] ?? 'nl';
$T = [
    'nl'=>['t'=>'Zaad Inventory (B01)','add'=>'Toevoegen','edit'=>'Bewerken','item'=>'Zaadsoort','lot'=>'Lot Nummer','qty'=>'Hoeveelheid (g)','sup'=>'Leverancier','org'=>'Biologisch?','cert'=>'Certificaat Nr.','req'=>'Verplicht bij biologisch','save'=>'Opslaan','cancel'=>'Annuleren','back'=>'Terug',
    'err_empty'=>'Naam en Lot nummer zijn verplicht.','err_neg'=>'Hoeveelheid moet positief zijn.','err_cert'=>'Certificaat nummer verplicht voor biologisch zaad.','ok_add'=>'Zaad toegevoegd!','ok_upd'=>'Zaad bijgewerkt! Audit log geregistreerd.','del'=>'Verwijderen','conf_del'=>'Weet je zeker dat je dit item wilt verwijderen?','del_ok'=>'Item verwijderd.'],
    'en'=>['t'=>'Seed Inventory (B01)','add'=>'Add','edit'=>'Edit','item'=>'Seed Type','lot'=>'Lot Number','qty'=>'Quantity (g)','sup'=>'Supplier','org'=>'Organic?','cert'=>'Cert. No.','req'=>'Required for organic','save'=>'Save','cancel'=>'Cancel','back'=>'Back',
    'err_empty'=>'Name and Lot number are required.','err_neg'=>'Quantity must be positive.','err_cert'=>'Certificate number required for organic seed.','ok_add'=>'Seed added!','ok_upd'=>'Seed updated! Audit log recorded.','del'=>'Delete','conf_del'=>'Are you sure you want to delete this item?','del_ok'=>'Item deleted.']
];
$t = $T[$lang]; $msg = "";

// --- EDIT MODE CHECK ---
$editMode = false;
$editData = null;
if(isset($_GET['edit_id'])){
    $id = (int)$_GET['edit_id'];
    $stmt = $db->prepare("SELECT * FROM inventory WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $res = $stmt->execute();
    $editData = $res->fetchArray(SQLITE3_ASSOC);
    if($editData) $editMode = true;
}

// --- POST VERWERKING ---
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action = $_POST['action'] ?? '';
    
    if($action === 'SAVE_SEED'){
        $id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $item = trim($_POST['item_name']);
        $lot = trim($_POST['lot_number']);
        $qty = (float)$_POST['quantity'];
        $sup = (int)($_POST['supplier_id'] ?? 0);
        $is_org = isset($_POST['organic_certified']) ? 1 : 0;
        $cert_ref = trim($_POST['organic_certificate_ref'] ?? '');
        
        $error = "";
        if(empty($item) || empty($lot)) $error = $t['err_empty'];
        elseif($qty <= 0) $error = $t['err_neg'];
        elseif($is_org && empty($cert_ref)) $error = $t['err_cert'];
        
        if($error){
            $msg = "<div class='alert alert-danger'>$error</div>";
            // Bij fout blijven we in edit mode als dat zo was
            if($id > 0) { $editMode = true; $editData = $_POST; } 
        } else {
            if($id > 0){
                // --- UPDATE BESTAAND (MET AUDIT) ---
                // 1. Haal oude waarden op voor audit
                $old = $db->querySingle("SELECT item_name, quantity, organic_certified, organic_certificate_ref FROM inventory WHERE id = $id", true);
                
                // 2. Update uitvoeren
                $stmt = $db->prepare("UPDATE inventory SET item_name=:item, lot_number=:lot, quantity=:qty, supplier_id=:sup, organic_certified=:org, organic_certificate_ref=:cert, updated_at=datetime('now') WHERE id=:id");
                $stmt->bindValue(':item', $item, SQLITE3_TEXT);
                $stmt->bindValue(':lot', $lot, SQLITE3_TEXT);
                $stmt->bindValue(':qty', $qty, SQLITE3_FLOAT);
                $stmt->bindValue(':sup', $sup, SQLITE3_INTEGER);
                $stmt->bindValue(':org', $is_org, SQLITE3_INTEGER);
                $stmt->bindValue(':cert', $cert_ref, SQLITE3_TEXT);
                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                
                if($stmt->execute()){
                    $msg = "<div class='alert alert-success'>".$t['ok_upd']."</div>";
                    
                    // 3. Audit Log Schrijven (Alleen als er iets veranderd is)
                    $changes = [];
                    if($old['item_name'] != $item) $changes[] = "Naam: {$old['item_name']} -> {$item}";
                    if($old['quantity'] != $qty) $changes[] = "Hoeveelheid: {$old['quantity']}g -> {$qty}g";
                    if($old['organic_certified'] != $is_org) $changes[] = "Bio Status: ".($old['organic_certified']?'Ja':'Nee')." -> ".($is_org?'Ja':'Nee');
                    if($old['organic_certificate_ref'] != $cert_ref) $changes[] = "Certificaat: {$old['organic_certificate_ref']} -> {$cert_ref}";
                    
                    if(!empty($changes)){
                        $logMsg = "Wijziging door ".($_SESSION['user_name'] ?? 'Gebruiker').": ".implode("; ", $changes);
                        $auditStmt = $db->prepare("INSERT INTO audit_events (event_type, description, affected_id, created_at) VALUES (:type, :desc, :id, datetime('now'))");
                        $auditStmt->bindValue(':type', 'INVENTORY_UPDATE', SQLITE3_TEXT);
                        $auditStmt->bindValue(':desc', $logMsg, SQLITE3_TEXT);
                        $auditStmt->bindValue(':id', $id, SQLITE3_INTEGER);
                        $auditStmt->execute();
                    }
                    $editMode = false; // Terug naar overzicht na succes
                } else {
                    $msg = "<div class='alert alert-danger'>DB Fout: ".$db->lastErrorMsg()."</div>";
                }
            } else {
                // --- NIEUWE TOEVOEGEN ---
                $stmt = $db->prepare("INSERT INTO inventory (item_name, category, quantity, unit, lot_number, supplier_id, organic_certified, organic_certificate_ref, created_at, updated_at) VALUES (:item, 'Zaad', :qty, 'g', :lot, :sup, :org, :cert, datetime('now'), datetime('now'))");
                $stmt->bindValue(':item', $item, SQLITE3_TEXT);
                $stmt->bindValue(':qty', $qty, SQLITE3_FLOAT);
                $stmt->bindValue(':lot', $lot, SQLITE3_TEXT);
                $stmt->bindValue(':sup', $sup, SQLITE3_INTEGER);
                $stmt->bindValue(':org', $is_org, SQLITE3_INTEGER);
                $stmt->bindValue(':cert', $cert_ref, SQLITE3_TEXT);
                
                if($stmt->execute()){
                    $msg = "<div class='alert alert-success'>".$t['ok_add']."</div>";
                    $_POST = array();
                    $editMode = false;
                } else {
                    $msg = "<div class='alert alert-danger'>DB Fout: ".$db->lastErrorMsg()."</div>";
                }
            }
        }
    }
    elseif($action === 'DELETE'){
        $id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        if($id > 0){
            $db->exec("DELETE FROM inventory WHERE id = $id");
            $msg = "<div class='alert alert-warning'>".$t['del_ok']."</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?=$lang?>">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$t['t']?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{background:#f8f9fa;}.card{box-shadow:0 2px 4px rgba(0,0,0,0.1);border:none;margin-bottom:20px;}.req{color:red;font-size:0.8em;}</style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>🌱 <?=$t['t']?></h2>
        <a href="index.php" class="btn btn-outline-secondary"><?=$t['back']?></a>
    </div>
    <?=$msg?>
    
    <!-- FORMULIER (Toevoegen OF Bewerken) -->
    <div class="card">
        <div class="card-header <?=$editMode ? 'bg-warning text-dark' : 'bg-primary text-white'?>">
            <h5><?=$editMode ? $t['edit'] : $t['add']?></h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="SAVE_SEED">
                <input type="hidden" name="item_id" value="<?=$editMode ? ($editData['id'] ?? '') : ''?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label"><?=$t['item']?></label>
                        <input type="text" name="item_name" class="form-control" required value="<?=$editMode ? htmlspecialchars($editData['item_name']) : ''?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><?=$t['lot']?></label>
                        <input type="text" name="lot_number" class="form-control" required value="<?=$editMode ? htmlspecialchars($editData['lot_number']) : ''?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?=$t['qty']?></label>
                        <input type="number" step="0.1" name="quantity" class="form-control" required value="<?=$editMode ? $editData['quantity'] : ''?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><?=$t['sup']?></label>
                        <select name="supplier_id" class="form-select">
                            <option value="0">-- Kies Leverancier --</option>
                            <?php
                            $sups = $db->query("SELECT id, name FROM suppliers ORDER BY name");
                            while($s = $sups->fetchArray()){ 
                                $selected = ($editMode && $editData['supplier_id'] == $s['id']) ? 'selected' : '';
                                echo "<option value='{$s['id']}' $selected>{$s['name']}</option>"; 
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?=$t['org']?></label>
                        <input type="checkbox" name="organic_certified" id="orgCheck" value="1" <?=$editMode && $editData['organic_certified'] ? 'checked' : ''?>>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?=$t['cert']?> <span class="req">*</span></label>
                        <input type="text" name="organic_certificate_ref" id="certField" class="form-control" value="<?=$editMode ? htmlspecialchars($editData['organic_certificate_ref'] ?? '') : ''?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success"><?=$t['save']?></button>
                        <?php if($editMode): ?>
                            <a href="b01_seed_inventory.php" class="btn btn-secondary"><?=$t['cancel']?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- OVERZICHT -->
    <div class="card">
        <div class="card-header"><h5>Huidige Voorraad</h5></div>
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Datum</th><th>Gewijzigd</th><th>Zaadsoort</th><th>Lot</th><th>Hoeveelheid</th><th>Leverancier</th><th>Bio</th><th>Certificaat</th><th>Actie</th></tr></thead>
                <tbody>
                    <?php
                    $items = $db->query("SELECT i.*, s.name as sup_name FROM inventory i LEFT JOIN suppliers s ON i.supplier_id = s.id ORDER BY i.created_at DESC");
                    while($row = $items->fetchArray()){
                        $bioBadge = $row['organic_certified'] ? '<span class="badge bg-success">Biologisch</span>' : '<span class="badge bg-secondary">Conventioneel</span>';
                        $certVal = $row['organic_certificate_ref'] ? $row['organic_certificate_ref'] : '-';
                        $dateVal = $row['created_at'] ? $row['created_at'] : 'Onbekend';
                        $updVal = $row['updated_at'] ? '<small class="text-muted">'.$row['updated_at'].'</small>' : '-';
                        echo "<tr>";
                        echo "<td>{$dateVal}</td>";
                        echo "<td>{$updVal}</td>";
                        echo "<td><strong>{$row['item_name']}</strong></td>";
                        echo "<td>{$row['lot_number']}</td>";
                        echo "<td>{$row['quantity']} g</td>";
                        echo "<td>{$row['sup_name']}</td>";
                        echo "<td>{$bioBadge}</td>";
                        echo "<td>{$certVal}</td>";
                        echo "<td>
                            <a href='?edit_id={$row['id']}' class='btn btn-sm btn-primary'>{$t['edit']}</a>
                            <form method='POST' style='display:inline;' onsubmit='return confirm(\"{$t['conf_del']}\")'>
                                <input type='hidden' name='action' value='DELETE'>
                                <input type='hidden' name='item_id' value='{$row['id']}'>
                                <button type='submit' class='btn btn-sm btn-danger'>{$t['del']}</button>
                            </form>
                        </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
document.getElementById('orgCheck').addEventListener('change', function(){
    var cert = document.getElementById('certField');
    if(this.checked){ cert.required = true; cert.style.borderColor = 'red'; }
    else { cert.required = false; cert.style.borderColor = ''; }
});
</script>
</body>
</html>
