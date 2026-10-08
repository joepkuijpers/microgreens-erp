<?php
ini_set("display_errors", 1); error_reporting(E_ALL);
try {
    // Zoek de database
    $paths = [
        __DIR__ . "/microgreens/PHP/database/MicrogreensERP_Live.sqlite",
        __DIR__ . "/../database/MicrogreensERP_Live.sqlite",
        "/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite"
    ];
    $dbPath = null;
    foreach($paths as $p) { if(file_exists($p)) { $dbPath = $p; break; } }
    if(!$dbPath) { die("Database niet gevonden"); }
    
    $db = new PDO("sqlite:" . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Alerts
    $alerts = $db->query("SELECT * FROM system_alerts WHERE resolved=0 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    // Finance
    $costs = $db->query("SELECT SUM(stock_grams * cost_price_eur) FROM seed_inventory")->fetchColumn() ?: 0;
    $value = $db->query("SELECT SUM(sale_price_eur) FROM packaging_units")->fetchColumn() ?: 0;
    $profit = $value - $costs;
    
    // Sensor (FIX: Haal ruwe data op, formatteer in PHP)
    $rows = $db->query("SELECT recorded_at, temperature_c, humidity_percent FROM sensor_readings ORDER BY recorded_at DESC LIMIT 24")->fetchAll(PDO::FETCH_ASSOC);
    $rows = array_reverse($rows);
    $labels = []; $temps = []; $humid = [];
    foreach($rows as $r) {
        $labels[] = date("H:i", strtotime($r["recorded_at"]));
        $temps[] = $r["temperature_c"];
        $humid[] = $r["humidity_percent"];
    }
    $l = json_encode($labels);
    $t = json_encode($temps);
    $h = json_encode($humid);
} catch (Exception $e) { die("Fout: " . $e->getMessage()); }
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Analytics</title><script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>body{font-family:sans-serif;background:#f4f6f9;padding:20px}.card{background:#fff;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1)}h1{color:#333}h2{color:#666}.kpi{display:flex;gap:20px}.box{flex:1;background:#f9f9f9;padding:20px;text-align:center;border-radius:8px}.val{font-size:24px;font-weight:bold}.ok{color:green}.nok{color:red}.alert{background:#fff3cd;border-left:5px solid #ffc107;padding:10px;margin:5px 0}canvas{max-height:300px}</style></head>
<body><h1>📈 Analytics & Watchdog</h1>
<div class="card"><h2>🚨 Alerts</h2><?php if(empty($alerts)):?><p>✅ Geen actieve waarschuwingen.</p><?php else: ?><?php foreach($alerts as $a):?><div class="alert"><?=htmlspecialchars($a["message"])?></div><?php endforeach; ?><?php endif; ?></div>
<div class="card"><h2>💰 Finance</h2><div class="kpi"><div class="box"><div>Kosten</div><div class="val">€<?=number_format($costs,2)?></div></div><div class="box"><div>Waarde</div><div class="val">€<?=number_format($value,2)?></div></div><div class="box"><div>Winst</div><div class="val <?=$profit>=0?"ok":"nok"?>">€<?=number_format($profit,2)?></div></div></div></div>
<div class="card"><h2>🌡️ Klimaat (Simulatie)</h2><canvas id="c"></canvas></div>
<a href="dashboard.php">← Terug naar Dashboard</a>
<script>new Chart(document.getElementById("c").getContext("2d"),{type:"line",data:{labels:<?=$l?>,datasets:[{label:"Temp °C",data:<?=$t?>,borderColor:"#e74c3c",tension:0.4},{label:"Vocht %",data:<?=$h?>,borderColor:"#3498db",tension:0.4}]},options:{responsive:true}});</script>
</body></html>
