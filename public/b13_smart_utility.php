<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
$DB_PATH = '/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite';
$db = new SQLite3($DB_PATH);

if (isset($_GET['lang'])) $_SESSION['lang'] = $_GET['lang'];
$lang = $_SESSION['lang'] ?? 'nl';
$T = [
    'nl' => [
        'title' => 'Smart Utility (B13-B19)', 'meter' => 'Meter', 'val' => 'Stand', 'submit' => 'Opslaan', 
        'cost' => 'Geschatte Kosten', 'alert' => '⚠️ Alert', 'chart' => 'Verbruik Laatste 7 Dagen',
        'success' => 'Opgeslagen! Kosten: €', 'back' => 'Terug', 'daily_avg' => 'Gem. Dagverbruik',
        'total_cost' => 'Totale Kosten (Maand)', 'no_data' => 'Nog geen data'
    ],
    'en' => [
        'title' => 'Smart Utility (B13-B19)', 'meter' => 'Meter', 'val' => 'Reading', 'submit' => 'Save', 
        'cost' => 'Est. Cost', 'alert' => '⚠️ Alert', 'chart' => 'Usage Last 7 Days',
        'success' => 'Saved! Cost: €', 'back' => 'Back', 'daily_avg' => 'Avg Daily Usage',
        'total_cost' => 'Total Cost (Month)', 'no_data' => 'No data yet'
    ]
];
$t = $T[$lang];
$msg = "";

// --- VERWERKEN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meter_id'])) {
    $meter_id = intval($_POST['meter_id']);
    $type = $_POST['utility_type'];
    $raw = str_replace(',', '.', $_POST['reading_value']);
    $value = floatval($raw);
    $unit = $_POST['unit'];
    $date = date('Y-m-d');
    $time = date('H:i:s');

    if ($meter_id > 0 && $value > 0) {
        // Opslaan
        $stmt = $db->prepare("INSERT INTO utility_logs (meter_id, utility_type, reading_value, unit, log_date, log_time) VALUES (:mid, :type, :val, :unit, :date, :time)");
        $stmt->bindValue(':mid', $meter_id, SQLITE3_INTEGER);
        $stmt->bindValue(':type', $type, SQLITE3_TEXT);
        $stmt->bindValue(':val', $value, SQLITE3_FLOAT);
        $stmt->bindValue(':unit', $unit, SQLITE3_TEXT);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $stmt->bindValue(':time', $time, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            // Kosten berekenen (B19)
            $rate = $db->querySingle("SELECT price_per_unit FROM utility_rates WHERE meter_type='$type'");
            $cost = $value * $rate;
            $msg = "<div class='alert success'>✅ " . $t['success'] . number_format($cost, 2) . "</div>";
            
            // Alert check (B28) - Simpele check op laatste invoer vs drempel
            $threshold = $db->querySingle("SELECT max_daily_usage FROM utility_thresholds WHERE meter_type='$type'");
            if ($threshold && $value > $threshold) {
                $msg .= "<div class='alert warning'>" . $t['alert'] . ": $type verbruik ($value) boven drempel ($threshold)!</div>";
                // Hier zou je een log in een alert_tabel kunnen schrijven
            }
        }
    }
}

// --- DATA OPHALEN ---
$meters = []; $res = $db->query("SELECT id, meter_type, location, unit FROM meters");
while($row = $res->fetchArray(SQLITE3_ASSOC)) $meters[] = $row;

// Grafiek Data (Laatste 7 dagen per type)
$chartData = []; $labels = [];
for ($i=6; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d-m', strtotime($d));
    $types = ['Electricity', 'Water', 'Gas', 'Heat'];
    foreach($types as $type) {
        $val = $db->querySingle("SELECT SUM(reading_value) FROM utility_logs WHERE utility_type='$type' AND log_date='$d'");
        $chartData[$type][] = $val ? $val : 0;
    }
}
$chartLabelsJson = json_encode($labels);
$chartDataJson = json_encode($chartData);

// Kosten Totaal (Huidige Maand)
$currentMonth = date('Y-m');
$totalCost = 0;
$res = $db->query("SELECT l.reading_value, r.price_per_unit FROM utility_logs l JOIN utility_rates r ON l.utility_type = r.meter_type WHERE l.log_date LIKE '$currentMonth%'");
while($row = $res->fetchArray(SQLITE3_ASSOC)) { $totalCost += ($row['reading_value'] * $row['price_per_unit']); }
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8"><title><?= $t['title'] ?></title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    body{font-family:sans-serif;background:#f4f6f8;margin:0;padding:20px;color:#333;}
    .container{max-width:900px;margin:0 auto;background:#fff;padding:25px;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,0.1);}
    h2{color:#8b5cf6;border-bottom:2px solid #eee;padding-bottom:10px;}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;}
    @media(max-width:700px){.grid{grid-template-columns:1fr;}}
    .card{background:#f9f9f9;padding:15px;border-radius:8px;border-left:4px solid #8b5cf6;}
    .card h3{margin:0 0 10px 0;font-size:0.9rem;color:#666;}
    .card .val{font-size:1.5rem;font-weight:bold;color:#2c3e50;}
    label{display:block;margin-top:15px;font-weight:bold;}
    input,select{width:100%;padding:10px;margin-top:5px;border:1px solid #ddd;border-radius:5px;box-sizing:border-box;}
    button{background:#8b5cf6;color:white;border:none;padding:12px;width:100%;margin-top:20px;border-radius:5px;cursor:pointer;font-size:1rem;}
    button:hover{background:#7c3aed;}
    .alert{padding:10px;margin:10px 0;border-radius:5px;}
    .alert.success{background:#d4edda;color:#155724;border:1px solid #c3e6cb;}
    .alert.warning{background:#fff3cd;color:#856404;border:1px solid #ffeeba;}
    .back{display:block;margin-top:20px;text-align:center;color:#666;text-decoration:none;}
</style>
</head>
<body>
<div class="container">
    <a href="dashboard_full.php" class="back">← <?= $t['back'] ?></a>
    <h2><?= $t['title'] ?></h2>
    <?= $msg ?>
    
    <!-- Invoer Formulier (A) -->
    <form method="POST">
        <div class="grid">
            <div>
                <label><?= $t['meter'] ?></label>
                <select name="meter_id" id="meter_id" required onchange="updateForm()">
                    <option value="0">-- Select --</option>
                    <?php foreach($meters as $m): ?>
                    <option value="<?= $m['id'] ?>" data-type="<?= $m['meter_type'] ?>" data-unit="<?= $m['unit'] ?>"><?= $m['meter_type'] ?> (<?= $m['location'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label><?= $t['val'] ?></label>
                <input type="number" step="any" name="reading_value" required placeholder="0.00">
            </div>
        </div>
        <input type="hidden" name="utility_type" id="utility_type">
        <input type="hidden" name="unit" id="unit">
        <button type="submit"><?= $t['submit'] ?></button>
    </form>

    <!-- Stats (B & C) -->
    <div class="grid" style="margin-top:30px;">
        <div class="card">
            <h3><?= $t['total_cost'] ?></h3>
            <div class="val">€ <?= number_format($totalCost, 2) ?></div>
            <small><?= date('F Y') ?></small>
        </div>
        <div class="card" style="border-color:#e67e22;">
            <h3>Status</h3>
            <div class="val" style="color:green;">OK</div>
            <small>Geen kritieke alerts</small>
        </div>
    </div>

    <!-- Grafiek (D) -->
    <div style="margin-top:30px;">
        <h3><?= $t['chart'] ?></h3>
        <canvas id="usageChart" height="100"></canvas>
    </div>
</div>

<script>
function updateForm(){
    var s = document.getElementById('meter_id');
    var opt = s.options[s.selectedIndex];
    if(opt.value>0){
        document.getElementById('utility_type').value = opt.getAttribute('data-type');
        document.getElementById('unit').value = opt.getAttribute('data-unit');
    }
}
window.onload = function(){
    updateForm();
    var ctx = document.getElementById('usageChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= $chartLabelsJson ?>,
            datasets: [
                {label:'Electricity', data:<?= json_encode($chartData['Electricity']) ?>, backgroundColor:'#fbbf24'},
                {label:'Water', data:<?= json_encode($chartData['Water']) ?>, backgroundColor:'#3b82f6'},
                {label:'Gas', data:<?= json_encode($chartData['Gas']) ?>, backgroundColor:'#ef4444'},
                {label:'Heat', data:<?= json_encode($chartData['Heat']) ?>, backgroundColor:'#f97316'}
            ]
        },
        options: {responsive:true, scales:{y:{beginAtZero:true}}}
    });
};
</script>
</body>
</html>
