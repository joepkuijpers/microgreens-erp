<?php
// B28 Watchdog & Alerts - ERP Gezondheidscheck
ini_set("display_errors", 1); error_reporting(E_ALL);

$db_path = "/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite";
if (!file_exists($db_path)) { die("DB niet gevonden"); }

try {
    $db = new PDO("sqlite:$db_path");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) { die("DB Fout: " . $e->getMessage()); }

$alerts = [];
$warnings = [];
$info = [];

// 1. SKAL CHECK: Niet-gevalideerde middelen
$stmt = $db->query("SELECT product_name, MAX(cleaned_at) as last_used FROM cleaning_logs WHERE is_validated = 0 GROUP BY product_name");
$unvalidated = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!empty($unvalidated)) {
    $alerts[] = ['msg' => "🚨 Kritiek: Niet-gevalideerde reinigingsmiddelen gebruikt!", 'details' => $unvalidated];
}

// 2. HYGIËNE CHECK: Niet schoongemaakt > 7 dagen
$stmt = $db->query("SELECT object_name, object_type, MAX(cleaned_at) as last_cleaned FROM cleaning_logs GROUP BY object_name");
$cleaning_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
$now = new DateTime();
foreach ($cleaning_status as $c) {
    $last = new DateTime($c['last_cleaned']);
    $diff = $now->diff($last)->days;
    if ($diff > 7) {
        $warnings[] = ['msg' => "⚠️ Hygiëne: {$c['object_name']} niet schoongemaakt in $diff dagen.", 'detail' => "Laatst: {$c['last_cleaned']}"];
    }
}

// 3. VOORRAAD CHECK: Oogst > 10 dagen oud
$stmt = $db->query("SELECT product_name, harvest_date, weight_grams FROM finished_inventory WHERE status != 'packaged' ORDER BY harvest_date ASC");
$old_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($old_stock as $s) {
    $harvest = new DateTime($s['harvest_date']);
    $diff = $now->diff($harvest)->days;
    if ($diff > 10) {
        $warnings[] = ['msg' => "⚠️ Voorraad: {$s['product_name']} is $diff dagen oud.", 'detail' => "Nog: " . number_format($s['weight_grams']/1000, 2) . " kg"];
    }
}

// 4. INFO: Vandaag verpakt
$stmt = $db->query("SELECT COUNT(*) as count FROM packaging_units WHERE packaging_date LIKE date('now') || '%'");
$today_pkg = $stmt->fetchColumn();
$info[] = ['msg' => "ℹ️ Vandaag verpakt: $today_pkg eenheden."];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B28 Watchdog</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: sans-serif; background: #f4f6f9; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; color: #333; }
        .box { padding: 15px; border-radius: 5px; margin-bottom: 15px; border-left: 5px solid #ccc; }
        .critical { background: #f8d7da; border-left-color: #dc3545; color: #721c24; }
        .warning { background: #fff3cd; border-left-color: #ffc107; color: #856404; }
        .info { background: #d1ecf1; border-left-color: #17a2b8; color: #0c5460; }
        ul { margin: 5px 0 0 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🐕 B28: Watchdog & Alerts</h1>
    <p>Automatische scan op risico's en compliance.</p>

    <?php if (empty($alerts) && empty($warnings) && empty($info)): ?>
        <div class="box info"><strong>✅ Alles onder controle!</strong></div>
    <?php endif; ?>

    <?php foreach ($alerts as $a): ?>
    <div class="box critical">
        <strong><?= $a['msg'] ?></strong>
        <ul><?php foreach ($a['details'] as $d): ?><li><?= htmlspecialchars($d['product_name']) ?> (Laatst: <?= $d['last_used'] ?>)</li><?php endforeach; ?></ul>
    </div>
    <?php endforeach; ?>

    <?php foreach ($warnings as $w): ?>
    <div class="box warning">
        <strong><?= $w['msg'] ?></strong><br><small><?= $w['detail'] ?></small>
    </div>
    <?php endforeach; ?>

    <?php foreach ($info as $i): ?>
    <div class="box info"><strong><?= $i['msg'] ?></strong></div>
    <?php endforeach; ?>
    
    <hr>
    <small>Regels: Hygiëne > 7 dagen, Voorraad > 10 dagen.</small>
</div>
</body>
</html>
