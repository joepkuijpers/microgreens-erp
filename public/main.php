<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$DB_PATH = '/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite';
$db = new SQLite3($DB_PATH);
$stats = ['b'=>0, 'o'=>0, 'c'=>0];
$r = $db->query("SELECT COUNT(*) FROM production_batches WHERE status NOT IN ('COMPLETED','CANCELLED')"); if($r) $stats['b'] = $r->fetchArray()[0];
$r = $db->query("SELECT COUNT(*) FROM production_outputs"); if($r) $stats['o'] = $r->fetchArray()[0];
$r = $db->query("SELECT COUNT(*) FROM cleaning_logs"); if($r) $stats['c'] = $r->fetchArray()[0];
$util = ['val'=>'0', 'unit'=>'', 'type'=>'-'];
$u = $db->querySingle("SELECT utility_type, reading_value, unit FROM utility_logs ORDER BY log_date DESC LIMIT 1", true);
if($u) { $util['val'] = number_format($u['reading_value'], 1); $util['unit'] = $u['unit']; $util['type'] = $u['utility_type']; }
$db->close();
?>
<!DOCTYPE html>
<html><head><title>Dashboard Fixed</title>
<style>
body{font-family:sans-serif;background:#f4f6f8;padding:20px;}
.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;max-width:1000px;margin:0 auto;}
.card{background:#fff;padding:20px;border-radius:8px;text-align:center;border-top:5px solid #3498db;}
.val{font-size:24px;font-weight:bold;margin:10px 0;}
</style>
</head><body>
<h1>✅ Dashboard is Working!</h1>
<div class="grid">
<div class="card"><h3>Batches</h3><div class="val"><?=$stats['b']?></div></div>
<div class="card" style="border-color:#27ae60"><h3>Oogst</h3><div class="val"><?=$stats['o']?></div></div>
<div class="card" style="border-color:#e67e22"><h3>Reiniging</h3><div class="val"><?=$stats['c']?></div></div>
<div class="card" style="border-color:#8b5cf6;background:#f8f0ff"><h3>Utility</h3><div class="val"><?=$util['val']?> <small><?=$util['unit']?></small></div><small><?=$util['type']?></small></div>
</div>
<p style="text-align:center;margin-top:20px;color:green"><strong>Purple Card is LIVE!</strong></p>
</body></html>
