<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
session_start();
$DB_PATH = '/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite';
$db = new SQLite3($DB_PATH);

// Taalondersteuning
$lang = $_SESSION['lang'] ?? 'nl';
if(isset($_GET['lang'])) { $_SESSION['lang'] = $_GET['lang']; $lang = $_GET['lang']; }
$T = [
    'nl'=>['t'=>'Water & Irrigatie (B14)','tab1'=>'Waterkwaliteit','tab2'=>'Irrigatie Log','ph'=>'pH','ec'=>'EC (mS)','temp'=>'°C','src'=>'Bron','save'=>'Opslaan','hist'=>'Geschiedenis','back'=>'Terug','zone'=>'Zone','dur'=>'Duur (min)','vol'=>'Volume (L)','ok'=>'Opgeslagen!','date'=>'Datum','time'=>'Tijd','op'=>'Uitvoerder','note'=>'Notities'],
    'en'=>['t'=>'Water & Irrigation (B14)','tab1'=>'Water Quality','tab2'=>'Irrigation Log','ph'=>'pH','ec'=>'EC (mS)','temp'=>'°C','src'=>'Source','save'=>'Save','hist'=>'History','back'=>'Back','zone'=>'Zone','dur'=>'Duration (min)','vol'=>'Volume (L)','ok'=>'Saved!','date'=>'Date','time'=>'Time','op'=>'Operator','note'=>'Notes']
];
$t = $T[$lang]; 
$msg = ""; 
$activeTab = $_GET['tab'] ?? 'water';

// Standaard grenswaarden (fallback als config tabel ontbreekt)
$ph_min = 5.5; $ph_max = 6.5; $ec_max = 1.5;

// --- VERWERKEN FORMULIER ---
if($_SERVER['REQUEST_METHOD']==='POST'){
    if($_POST['type']=='water'){
        $stmt=$db->prepare("INSERT INTO water_measurements (log_date,log_time,source_type,ph_value,ec_value,temperature,operator_name,notes) VALUES (:d,time('now','localtime'),:s,:p,:e,:t,:o,:n)");
        $stmt->bindValue(':d',$_POST['date'],SQLITE3_TEXT); 
        $stmt->bindValue(':s',$_POST['source'],SQLITE3_TEXT);
        $stmt->bindValue(':p',$_POST['ph'],SQLITE3_FLOAT); 
        $stmt->bindValue(':e',$_POST['ec'],SQLITE3_FLOAT);
        $stmt->bindValue(':t',$_POST['temp'],SQLITE3_FLOAT);
        $stmt->bindValue(':o',$_POST['operator'],SQLITE3_TEXT);
        $stmt->bindValue(':n',$_POST['notes'],SQLITE3_TEXT);
        if($stmt->execute()){ $msg = "<div class='alert alert-success'>".$t['ok']."</div>"; }
    } elseif($_POST['type']=='irrigation'){
        $stmt=$db->prepare("INSERT INTO irrigation_logs (log_date,log_time,zone_name,duration_min,volume_liters,operator_name,notes) VALUES (:d,time('now','localtime'),:z,:dur,:v,:o,:n)");
        $stmt->bindValue(':d',$_POST['date'],SQLITE3_TEXT);
        $stmt->bindValue(':z',$_POST['zone'],SQLITE3_TEXT);
        $stmt->bindValue(':dur',$_POST['duration'],SQLITE3_INTEGER);
        $stmt->bindValue(':v',$_POST['volume'],SQLITE3_FLOAT);
        $stmt->bindValue(':o',$_POST['operator'],SQLITE3_TEXT);
        $stmt->bindValue(':n',$_POST['notes'],SQLITE3_TEXT);
        if($stmt->execute()){ $msg = "<div class='alert alert-success'>".$t['ok']."</div>"; }
    }
}
?>
<!DOCTYPE html>
<html lang="<?=$lang?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$t['t']?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { box-shadow: 0 2px 4px rgba(0,0,0,0.1); border: none; margin-bottom: 20px; }
        .nav-tabs .nav-link.active { font-weight: bold; border-top: 3px solid #0d6efd; }
        .status-ok { color: green; font-weight: bold; }
        .status-warn { color: orange; font-weight: bold; }
        .status-err { color: red; font-weight: bold; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>💧 <?=$t['t']?></h2>
        <a href="index.php" class="btn btn-outline-secondary"><?=$t['back']?></a>
    </div>
    
    <?=$msg?>

    <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
        <li class="nav-item"><a class="nav-link <?=$activeTab=='water'?'active':''?>" href="?tab=water"><?=$t['tab1']?></a></li>
        <li class="nav-item"><a class="nav-link <?=$activeTab=='irrigation'?'active':''?>" href="?tab=irrigation"><?=$t['tab2']?></a></li>
    </ul>

    <?php if($activeTab=='water'): ?>
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="type" value="water">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label"><?=$t['date']?></label>
                        <input type="date" name="date" class="form-control" value="<?=date('Y-m-d')?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><?=$t['src']?></label>
                        <select name="source" class="form-select">
                            <option value="TAP">Kraanwater</option>
                            <option value="RESERVOIR">Reservoir</option>
                            <option value="RUNOFF">Afvoer</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?=$t['ph']?> <small class="text-muted">(<?=$ph_min?>-<?=$ph_max?>)</small></label>
                        <input type="number" step="0.1" name="ph" class="form-control" placeholder="7.0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?=$t['ec']?> <small class="text-muted">(max <?=$ec_max?>)</small></label>
                        <input type="number" step="0.1" name="ec" class="form-control" placeholder="0.0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?=$t['temp']?></label>
                        <input type="number" step="0.1" name="temp" class="form-control" placeholder="20">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label"><?=$t['op']?></label>
                        <input type="text" name="operator" class="form-control" value="<?=htmlspecialchars($_SESSION['user_name'] ?? 'Joep')?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label"><?=$t['note']?></label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><?=$t['save']?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5><?=$t['hist']?></h5></div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-hover">
                <thead><tr><th>Datum</th><th>Bron</th><th>pH</th><th>EC</th><th>Temp</th><th>Uitvoerder</th><th>Status</th></tr></thead>
                <tbody>
                <?php
                $res = $db->query("SELECT * FROM water_measurements ORDER BY log_date DESC, log_time DESC LIMIT 20");
                while($r = $res->fetchArray()){
                    $status = 'status-ok';
                    if($r['ph_value'] < $ph_min || $r['ph_value'] > $ph_max) $status = 'status-warn';
                    if($r['ec_value'] > $ec_max) $status = 'status-err';
                    echo "<tr>";
                    echo "<td>{$r['log_date']}</td><td>{$r['source_type']}</td>";
                    echo "<td class='$status'>{$r['ph_value']}</td>";
                    echo "<td class='$status'>{$r['ec_value']}</td>";
                    echo "<td>{$r['temperature']}</td>";
                    echo "<td>{$r['operator_name']}</td>";
                    echo "<td><span class='$status'>".($status=='status-ok'?'OK':'Check')."</span></td>";
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if($activeTab=='irrigation'): ?>
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="type" value="irrigation">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label"><?=$t['date']?></label>
                        <input type="date" name="date" class="form-control" value="<?=date('Y-m-d')?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><?=$t['zone']?></label>
                        <select name="zone" class="form-select">
                            <option value="Zone A">Zone A</option>
                            <option value="Zone B">Zone B</option>
                            <option value="Room 1">Room 1</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?=$t['dur']?></label>
                        <input type="number" name="duration" class="form-control" placeholder="5">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?=$t['vol']?></label>
                        <input type="number" step="0.1" name="volume" class="form-control" placeholder="10.5">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label"><?=$t['op']?></label>
                        <input type="text" name="operator" class="form-control" value="<?=htmlspecialchars($_SESSION['user_name'] ?? 'Joep')?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label"><?=$t['note']?></label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><?=$t['save']?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5><?=$t['hist']?></h5></div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-hover">
                <thead><tr><th>Datum</th><th>Zone</th><th>Duur</th><th>Volume</th><th>Uitvoerder</th></tr></thead>
                <tbody>
                <?php
                $res = $db->query("SELECT * FROM irrigation_logs ORDER BY log_date DESC, log_time DESC LIMIT 20");
                while($r = $res->fetchArray()){
                    echo "<tr>";
                    echo "<td>{$r['log_date']}</td><td>{$r['zone_name']}</td>";
                    echo "<td>{$r['duration_min']} min</td><td>{$r['volume_liters']} L</td><td>{$r['operator_name']}</td>";
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
