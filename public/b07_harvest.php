<?php
error_reporting(0); ini_set('display_errors', 0); session_start();
$DB_PATH = '/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite';
$db = new SQLite3($DB_PATH);
$lang = $_SESSION['lang'] ?? 'nl';
$T = ['nl'=>['t'=>'Oogst Registratie (B07)','batch'=>'Kies Batch','weight'=>'Gewicht (g)','date'=>'Datum','operator'=>'Uitvoerder','save'=>'Oogsten','back'=>'Terug','err_no_batch'=>'Fout: Geen batch geselecteerd.','err_neg_weight'=>'Fout: Gewicht kan niet negatief zijn.','err_future_date'=>'Fout: Datum kan niet in de toekomst liggen.','ok'=>'Oogst succesvol geregistreerd!'], 'en'=>['t'=>'Harvest Registration (B07)','batch'=>'Select Batch','weight'=>'Weight (g)','date'=>'Date','operator'=>'Operator','save'=>'Harvest','back'=>'Back','err_no_batch'=>'Error: No batch selected.','err_neg_weight'=>'Error: Weight cannot be negative.','err_future_date'=>'Error: Date cannot be in the future.','ok'=>'Harvest successfully recorded!']];
$t = $T[$lang]; $msg = "";
if($_SERVER['REQUEST_METHOD']==='POST'){
    $batch_id = $_POST['batch_id'] ?? 0; $weight = $_POST['weight'] ?? 0; $date = $_POST['date'] ?? date('Y-m-d'); $operator = $_POST['operator'] ?? 'Unknown';
    $error = "";
    if(empty($batch_id)) $error = $t['err_no_batch']; elseif($weight <= 0) $error = $t['err_neg_weight']; elseif(strtotime($date) > strtotime('tomorrow')) $error = $t['err_future_date'];
    if($error){ $msg = "<div class='alert alert-danger'>$error</div>"; } 
    else {
        $sql = "INSERT INTO harvests (batch_id, weight_grams, harvest_date, operator_name, created_at) VALUES (:bid, :w, :d, :o, datetime('now'))";
        $stmt = $db->prepare($sql); $stmt->bindValue(':bid', $batch_id, SQLITE3_INTEGER); $stmt->bindValue(':w', $weight, SQLITE3_FLOAT); $stmt->bindValue(':d', $date, SQLITE3_TEXT); $stmt->bindValue(':o', $operator, SQLITE3_TEXT);
        if($stmt->execute()){ $msg = "<div class='alert alert-success'>".$t['ok']."</div>"; } else { $msg = "<div class='alert alert-danger'>DB Fout: ".$db->lastErrorMsg()."</div>"; }
    }
}
?>
<!DOCTYPE html><html lang="<?=$lang?>"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?=$t['t']?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background-color:#f8f9fa;}.card{box-shadow:0 4px 8px rgba(0,0,0,0.1);border:none;}.form-label{font-weight:bold;}</style></head><body>
<div class="container py-5"><div class="row justify-content-center"><div class="col-md-8">
<div class="d-flex justify-content-between align-items-center mb-4"><h2>🌱 <?=$t['t']?></h2><a href="index.php" class="btn btn-outline-secondary"><?=$t['back']?></a></div><?=$msg?>
<div class="card"><div class="card-body p-4"><form method="POST">
<div class="mb-3"><label class="form-label"><?=$t['batch']?></label><select name="batch_id" class="form-select" required><option value="">-- Kies een actieve batch --</option><?php $res = $db->query("SELECT id, batch_code, crop_type, started_at FROM production_batches WHERE status IN ('ACTIVE', 'READY') ORDER BY started_at DESC"); while($row = $res->fetchArray()){ echo "<option value='{$row['id']}'>{$row['batch_code']} - {$row['crop_type']}</option>"; } ?></select></div>
<div class="mb-3"><label class="form-label"><?=$t['weight']?></label><input type="number" step="0.1" name="weight" class="form-control" placeholder="150.5" required></div>
<div class="mb-3"><label class="form-label"><?=$t['date']?></label><input type="date" name="date" class="form-control" value="<?=date('Y-m-d')?>" required></div>
<div class="mb-3"><label class="form-label"><?=$t['operator']?></label><input type="text" name="operator" class="form-control" value="<?=htmlspecialchars($_SESSION['user_name'] ?? 'Joep')?>" required></div>
<button type="submit" class="btn btn-primary w-100"><?=$t['save']?></button></form></div></div>
<div class="mt-4"><h5>Laatste 5 oogsten</h5><table class="table table-sm table-striped"><thead><tr><th>Datum</th><th>Batch</th><th>Gewicht</th><th>Door</th></tr></thead><tbody><?php
$sql = "SELECT COALESCE(h.created_at, h.harvest_date) as time, h.weight_grams, h.operator_name, pb.batch_code FROM harvests h LEFT JOIN production_batches pb ON h.batch_id = pb.id ORDER BY time DESC LIMIT 5";
$recent = $db->query($sql); if($recent){ while($r = $recent->fetchArray()){ $timeDisplay = $r['time'] ? $r['time'] : 'Onbekend'; $w = $r['weight_grams'] ? $r['weight_grams'] : '0'; $op = $r['operator_name'] ? $r['operator_name'] : '-'; $bc = $r['batch_code'] ? $r['batch_code'] : '?'; echo "<tr><td>{$timeDisplay}</td><td>{$bc}</td><td>{$w}g</td><td>{$op}</td></tr>"; } } ?></tbody></table></div>
</div></div></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script></body></html>
