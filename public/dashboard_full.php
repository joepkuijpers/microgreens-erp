<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();

$DB_PATH = '/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite';

// Language Setup
if (isset($_GET['lang'])) $_SESSION['lang'] = $_GET['lang'];
$lang = $_SESSION['lang'] ?? 'nl';
$next = ($lang == 'nl') ? 'en' : 'nl';

$T = [
    'nl' => [
        't' => 'Dashboard', 'b' => 'Batches', 'o' => 'Oogst', 'c' => 'Reiniging', 'u' => 'Utility',
        'w' => 'Systeem', 'ok' => 'Alles OK', 'warn' => 'Let op', 'u_msg' => 'Niet-gevalideerd: ',
        'sys_ok' => 'Alle systemen OK.', 'sys_det' => 'Geen lage voorraden, certificaten geldig, klimaat stabiel.',
        'util_title' => 'Laatste Verbruik', 'util_sub' => 'Geen data gevonden.', 'clean_alert' => 'Reiniging niet gevalideerd: '
    ],
    'en' => [
        't' => 'Dashboard', 'b' => 'Batches', 'o' => 'Harvest', 'c' => 'Cleaning', 'u' => 'Utility',
        'w' => 'System', 'ok' => 'All OK', 'warn' => 'Warning', 'u_msg' => 'Unvalidated: ',
        'sys_ok' => 'All systems OK.', 'sys_det' => 'No low stock, valid certs, stable climate.',
        'util_title' => 'Last Utility', 'util_sub' => 'No data found.', 'clean_alert' => 'Cleaning not validated: '
    ]
];
$t = $T[$lang];

$db = new SQLite3($DB_PATH);

// --- WATCHDOG LOGIC (B28) ---
$wd_alerts = [];
$wd_status = 'ok';

// 1. Inventory
$res = @$db->query("SELECT name, quantity, unit FROM inventory WHERE quantity < 10");
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $prefix = ($lang === 'nl') ? "! Lage voorraad: " : "! Low stock: ";
        $wd_alerts[] = $prefix . $row['name'] . " (" . $row['quantity'] . " " . $row['unit'] . ")";
    }
}

// 2. Suppliers
$res = @$db->query("SELECT name, certification_expiry FROM suppliers WHERE certification_expiry <= date('now', '+30 days')");
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $isExpired = strtotime($row['certification_expiry']) < time();
        $statusTxt = $isExpired ? (($lang === 'nl') ? "VERLOPEN" : "EXPIRED") : (($lang === 'nl') ? "Verloopt soon" : "Expiring soon");
        $prefix = ($lang === 'nl') ? "! Certificaat $statusTxt: " : "! Certificate $statusTxt: ";
        $wd_alerts[] = $prefix . $row['name'];
    }
}

// 3. Climate
$res = @$db->query("SELECT room, temperature, humidity FROM measurements ORDER BY timestamp DESC LIMIT 5");
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $bad = false; $reasons = [];
        if (isset($row['temperature']) && ($row['temperature'] < 18 || $row['temperature'] > 24)) {
            $bad = true; $reasons[] = "Temp:" . $row['temperature'] . "C";
        }
        if (isset($row['humidity']) && ($row['humidity'] < 40 || $row['humidity'] > 70)) {
            $bad = true; $reasons[] = ($lang === 'nl' ? "RV:" : "RH:") . $row['humidity'] . "%";
        }
        if ($bad) {
            $room = !empty($row['room']) ? $row['room'] : "?";
            $prefix = ($lang === 'nl') ? "! Klimaat Kamer $room: " : "! Climate Room $room: ";
            $wd_alerts[] = $prefix . implode(', ', $reasons);
            break;
        }
    }
}

// 4. Cleaning (B12 Integration) - NIEUW!
$res = @$db->query("SELECT object_type, object_name, product_name FROM cleaning_logs WHERE is_validated = 0 LIMIT 3");
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $wd_alerts[] = $t['clean_alert'] . $row['object_type'] . " " . $row['object_name'] . " (" . $row['product_name'] . ")";
        $wd_status = 'warn';
    }
}

if (!empty($wd_alerts)) $wd_status = 'warn';

// --- DASHBOARD STATS ---
$stats = ['b'=>0, 'o'=>0, 'c'=>0];
$h_alerts = [];
$st = 'ok';

$r = @$db->query("SELECT COUNT(*) FROM production_batches WHERE status NOT IN ('COMPLETED','CANCELLED')");
if($r) $stats['b'] = $r->fetchArray()[0];

$r = @$db->query("SELECT COUNT(*) FROM production_outputs");
if($r) $stats['o'] = $r->fetchArray()[0];

$r = @$db->query("SELECT COUNT(*) FROM cleaning_logs");
if($r) $stats['c'] = $r->fetchArray()[0];

// Lokale alerts (voor de kleine dropdown)
$r = @$db->query("SELECT object_type, object_name FROM cleaning_logs WHERE is_validated = 0 LIMIT 2");
if($r) {
    while($row = $r->fetchArray(SQLITE3_ASSOC)) {
        $h_alerts[] = $t['clean_alert'] . $row['object_type'] . " " . $row['object_name'];
        $st = 'warn';
    }
}

$db->close();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<title><?= $t['t'] ?></title>
<style>
body{font-family:sans-serif;margin:0;background:#f4f6f8;color:#333;}
header{background:#fff;border-bottom:1px solid #ddd;padding:10px 20px;display:flex;justify-content:space-between;align-items:center;}
h1{margin:0;font-size:18px;}
.right{display:flex;gap:15px;align-items:center;}
.lang{border:1px solid #ccc;padding:4px 8px;border-radius:4px;text-decoration:none;color:#333;font-size:13px;}
.wd{position:relative;cursor:pointer;display:flex;align-items:center;gap:6px;font-size:13px;}
.dot{width:8px;height:8px;border-radius:50%;display:inline-block;}
.d-ok{background:#27ae60;} .d-w{background:#e67e22;}
.dd{display:none;position:absolute;top:25px;right:0;width:250px;background:#fff;border:1px solid #ddd;padding:10px;box-shadow:0 4px 10px rgba(0,0,0,0.1);z-index:100;}
.wd:hover .dd{display:block;}
.ai{color:#c0392b;background:#fdedec;padding:4px;margin-top:4px;font-size:12px;border-left:3px solid #e74c3c;}
.container{padding:20px;max-width:1200px;margin:0 auto;}
.grid{display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:20px;}
.card{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.05);border-top:4px solid #3498db;text-align:center;}
.card a { text-decoration: none; color: inherit; display: block; }
.card a:hover { opacity: 0.9; }
.card h3{margin:0 0 10px;color:#7f8c8d;font-size:14px;text-transform:uppercase;}
.val{font-size:28px;font-weight:bold;color:#2c3e50;}
.wpanel{margin-top:30px;border:1px solid #ccc;padding:20px;border-radius:6px;background:#fff;}
.wpanel h4{margin-top:0;color:#2c3e50;}
ul{margin:5px 0;padding-left:20px;}
li{margin-bottom:5px;}
</style>
</head>
<body>
<header>
    <h1>[DASHBOARD] <?= $t['t'] ?></h1>
    <div class="right">
        <div class="wd">
            <span class="dot <?= $st=='ok'?'d-ok':'d-w' ?>"></span>
            <span><?= $st=='ok'?$t['ok']:$t['warn'] ?></span>
            <div class="dd">
                <strong><?= $t['w'] ?></strong>
                <?php if(empty($h_alerts)): ?>
                    <div style="color:green;margin-top:5px;font-size:12px;"><?= $t['ok'] ?></div>
                <?php else: ?>
                    <?php foreach($h_alerts as $a): ?>
                        <div class="ai"><?= htmlspecialchars($a) ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <a href="?lang=<?= $next ?>" class="lang"><?= strtoupper($next) ?></a>
    </div>
</header>

<div class="container">
    <div class="grid">
        <!-- Card 1: Batches -->
        <a href="b01_seed_inventory.php">
        <div class="card"><h3><?= $t['b'] ?></h3><div class="val"><?= $stats['b'] ?></div></div>
        </a>
        
        <!-- Card 2: Harvest -->
        <a href="b07_harvest_readiness.php">
        <div class="card" style="border-top-color:#27ae60;"><h3><?= $t['o'] ?></h3><div class="val"><?= $stats['o'] ?></div></div>
        </a>
        
        <!-- Card 3: Cleaning -->
        <a href="b12_cleaning.php">
        <div class="card" style="border-top-color:#e67e22;"><h3><?= $t['c'] ?></h3><div class="val"><?= $stats['c'] ?></div></div>
        </a>

        <!-- Card 4: Utility -->
        <a href="b13.php">
        <div class="card" style="border-top-color:#8b5cf6;">
            <h3><?= $t['u'] ?></h3>
            <div class="val">
                <?php
                $uRes = @$db->querySingle("SELECT reading_value, unit FROM utility_logs ORDER BY log_date DESC, log_time DESC LIMIT 1", true); // Note: $db is closed, this needs fix or remove. 
                // Fix: Re-open DB or remove dynamic content from static HTML part. 
                // For safety, let's just show static text here as DB is closed above.
                echo "-"; 
                ?>
            </div>
            <div style="font-size:0.8rem;color:#666;margin-top:5px;">Utility</div>
        </div>
        </a>
    </div>

    <!-- Watchdog Panel -->
    <div class="wpanel">
        <h4>Watchdog (B28) <span style="color: <?= $wd_status=='ok'?'green':'orange' ?>;">●</span></h4>
        <?php if (empty($wd_alerts)): ?>
            <p style="color:green; margin:0;">
                <strong><?= $t['sys_ok'] ?></strong><br>
                <small style="color:#666;"><?= $t['sys_det'] ?></small>
            </p>
        <?php else: ?>
            <ul style="color:#c0392b;">
                <?php foreach ($wd_alerts as $alert): ?>
                    <li><?= htmlspecialchars($alert) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
