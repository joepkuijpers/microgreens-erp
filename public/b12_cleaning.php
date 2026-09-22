<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../app/modules/b12_cleaning_sanitation.php';
$dbPath = '/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite';
$engine = new B12_Cleaning_Engine($dbPath);

$lang = $_SESSION['lang'] ?? 'nl';
if(isset($_GET['lang'])) { $_SESSION['lang'] = $_GET['lang']; $lang = $_GET['lang']; }
$T = [
    'nl'=>['t'=>'Reiniging (B12)','new'=>'Nieuw','obj'=>'Object','prod'=>'Middel','op'=>'Uitvoerder','save'=>'Opslaan','hist'=>'Geschiedenis','st'=>'Status','pend'=>'Open','val'=>'Gevalideerd','ok'=>'Opgeslagen!','back'=>'Terug','method'=>'Methode','notes'=>'Notities','click_validate'=>'Klik op de rij om te valideren'],
    'en'=>['t'=>'Cleaning (B12)','new'=>'New','obj'=>'Object','prod'=>'Product','op'=>'Operator','save'=>'Save','hist'=>'History','st'=>'Status','pend'=>'Pending','val'=>'Validated','ok'=>'Saved!','back'=>'Back','method'=>'Method','notes'=>'Notes','click_validate'=>'Click row to validate']
];
$t = $T[$lang]; $msg = "";

if($_SERVER['REQUEST_METHOD']==='POST' && $_POST['act']=='add'){
    if($engine->addLog($_POST['otype'],$_POST['oname'],$_POST['pname'],$_POST['op'],$_POST['method'],$_POST['notes'])) {
        $msg="<div style='color:green;padding:10px;background:#d4edda;margin:10px 0;'>✅ ".$t['ok']."</div>";
    }
}
// FIX: Alleen ID meesturen, geen validator naam meer
if($_SERVER['REQUEST_METHOD']==='POST' && $_POST['act']=='val'){
    if($engine->validateLog($_POST['id'])) $msg="<div style='color:green;padding:10px;background:#d4edda;margin:10px 0;'>✅ Validated!</div>";
}

$logs = $engine->getLogs();
?>
<!DOCTYPE html><html lang="<?=$lang?>"><head><meta charset="UTF-8"><title><?=$t['t']?></title>
<style>
body{font-family:sans-serif;background:#f4f6f8;margin:0;padding:20px;} 
.c{max-width:900px;margin:0 auto;background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);} 
h2{color:#e67e22;} .g{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:10px 0;} 
input,select,textarea{width:100%;padding:8px;margin-top:4px;box-sizing:border-box;} 
button{background:#e67e22;color:#fff;border:none;padding:10px;width:100%;margin-top:10px;cursor:pointer;} 
table{width:100%;border-collapse:collapse;margin-top:20px;} 
th,td{padding:8px;text-align:left;border-bottom:1px solid #eee;} 
.st{padding:2px 6px;border-radius:4px;font-size:0.8rem;} 
.st0{background:#fff3cd;color:#856404;} 
.st1{background:#d4edda;color:#155724;} 
a{display:block;margin-top:20px;color:#666;text-decoration:none;}
tr.clickable {cursor: pointer; transition: background 0.2s;}
tr.clickable:hover {background-color: #fff3cd;}
tr.validated {background-color: #f9f9f9; color: #888;}
</style></head><body><div class="c">
<a href="dashboard_full.php">← <?=$t['back']?></a>
<h2><?=$t['t']?></h2><?=$msg?>
<div style="background:#f9f9f9;padding:15px;border-left:4px solid #e67e22;margin:15px 0;">
<h3><?=$t['new']?></h3>
<form method="POST"><input type="hidden" name="act" value="add">
<div class="g"><div><label><?=$t['obj']?> Type</label><select name="otype"><option>ROOM</option><option>EQUIPMENT</option><option>CONTAINER</option></select></div>
<div><label><?=$t['obj']?> Naam</label><input type="text" name="oname" placeholder="Bijv. Kamer A" required></div></div>
<div class="g"><div><label><?=$t['prod']?></label><input type="text" name="pname" placeholder="Naam middel" required></div>
<div><label><?=$t['method']?></label><select name="method"><option>SPRAY</option><option>WIPE</option><option>FOAM</option><option>SOAK</option></select></div></div>
<div><label><?=$t['op']?></label><input type="text" name="op" required></div>
<div><label><?=$t['notes']?></label><textarea name="notes" rows="2"></textarea></div>
<button type="submit"><?=$t['save']?></button></form></div>
<h3><?=$t['hist']?> <small style="font-size:0.8rem;color:#666;">(<?=$t['click_validate']?>)</small></h3>
<table><thead><tr><th>Datum</th><th>Object</th><th>Middel</th><th>Method</th><th><?=$t['op']?></th><th><?=$t['st']?></th></tr></thead><tbody>
<?php foreach($logs as $l): 
    $date = isset($l['cleaned_at']) ? explode(' ', $l['cleaned_at'])[0] : '?';
    $isVal = $l['is_validated'];
    $rowClass = $isVal ? 'validated' : 'clickable';
    $onClick = $isVal ? '' : "onclick=\"document.getElementById('form_val_".$l['id']."').submit();\"";
?>
<tr class="<?=$rowClass?>" <?=$onClick?>>
    <form id="form_val_<?=$l['id']?>" method="POST" style="display:none;"><input type="hidden" name="act" value="val"><input type="hidden" name="id" value="<?=$l['id']?>"></form>
    <td><?=$date?></td><td><?=$l['object_type']?>: <?=$l['object_name']?></td><td><?=$l['product_name']?></td><td><?=$l['method']?></td><td><?=$l['operator_name']?></td>
    <td><span class="st st<?=$isVal?>"><?=$isVal?$t['val']:$t['pend']?></span></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></body></html>
