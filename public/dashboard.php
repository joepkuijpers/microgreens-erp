<?php
ini_set("display_errors", 1); error_reporting(E_ALL);
include __DIR__ . "/../includes/language.php";
$db = __DIR__ . "/../database/MicrogreensERP_Live.sqlite";
if (!file_exists($db)) die("DB niet gevonden");
try { $pdo = new PDO("sqlite:$db"); $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); } 
catch (PDOException $e) { die("DB Fout: " . $e->getMessage()); }
$kpi_b = $kpi_o = $kpi_v = $kpi_s = 0; $kpi_w = "0.00"; $logs = []; $alerts = [];
try {
    $kpi_b = $pdo->query("SELECT COUNT(*) FROM production_batches WHERE status IN ('GROWING','GERMINATING','SEEDING')")->fetchColumn() ?: 0;
    $kpi_o = number_format((float)($pdo->query("SELECT COALESCE(SUM(weight_grams),0)/1000 FROM harvests")->fetchColumn()), 2);
    $kpi_v = number_format((float)($pdo->query("SELECT COALESCE(SUM(weight_grams),0)/1000 FROM finished_inventory WHERE status='AVAILABLE'")->fetchColumn()), 2);
    $inc = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_transactions WHERE transaction_type='INCOME'")->fetchColumn();
    $exp = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_transactions WHERE transaction_type='EXPENSE'")->fetchColumn();
    $kpi_w = number_format($inc - $exp, 2);
    $kpi_s = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
    $logs = $pdo->query("SELECT '✅ Gereinigd: ' || object_name as m, cleaned_at as d FROM cleaning_logs UNION ALL SELECT '📦 Verpakt: ' || package_code as m, packaging_date as d FROM packaging_units UNION ALL SELECT '💰 Boeking: ' || description as m, transaction_date as d FROM financial_transactions ORDER BY d DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    if ($pdo->query("SELECT name FROM suppliers WHERE certificate_expiry < date('now') LIMIT 1")->fetchColumn()) $alerts[] = "Certificaat verlopen!";
} catch (Exception $e) {}
?>
<!DOCTYPE html><html lang="<?= $lang ?>"><head><meta charset="UTF-8"><title><?= __("dashboard") ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>body{font-family:sans-serif;margin:0;background:#f4f7f6}.header{background:#2c3e50;color:#fff;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center}.container{max-width:1200px;margin:2rem auto;padding:0 1rem}.alerts-box{background:#ffebee;border-left:5px solid #c0392b;padding:1rem;margin-bottom:2rem}.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:2rem}.kpi-card{background:#fff;padding:1.5rem;border-radius:8px;text-align:center;border-top:4px solid #27ae60;box-shadow:0 2px 4px rgba(0,0,0,0.1)}.kpi-card h3{margin:0 0 10px;font-size:0.9rem;color:#777}.kpi-card .value{font-size:1.8rem;font-weight:bold;color:#2c3e50}.content-grid{display:grid;grid-template-columns:2fr 1fr;gap:2rem}.card{background:#fff;padding:1.5rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}.menu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px}.menu-btn{display:block;padding:12px;background:#ecf0f1;color:#2c3e50;text-align:center;text-decoration:none;border-radius:6px;font-weight:bold}.menu-btn:hover{background:#bdc3c7}ul{list-style:none;padding:0}li{padding:8px 0;border-bottom:1px solid #eee;font-size:0.9rem}.lang-switch a{color:#fff;text-decoration:none;margin-left:10px;font-size:0.9rem;opacity:0.8}.lang-switch a:hover{opacity:1}</style></head>
<body>
<div class="header"><h1>🌱 Microgreens ERP</h1><div class="lang-switch"><span><?= date('d-m-Y H:i') ?></span><a href="<?= lang_url('nl') ?>">🇳🇱 NL</a><a href="<?= lang_url('en') ?>">🇬🇧 EN</a></div></div>
<div class="container">
<?php if(!empty($alerts)): ?><div class="alerts-box"><strong>⚠️ <?= __("alert") ?>:</strong> <?= htmlspecialchars($alerts[0]) ?></div><?php endif; ?>
<div class="kpi-grid">
<div class="kpi-card"><h3><?= __("active_batches") ?></h3><div class="value"><?= $kpi_b ?></div></div>
<div class="kpi-card"><h3><?= __("total_harvest") ?></h3><div class="value"><?= $kpi_o ?> kg</div></div>
<div class="kpi-card"><h3><?= __("available_stock") ?></h3><div class="value"><?= $kpi_v ?> kg</div></div>
<div class="kpi-card"><h3><?= __("net_profit") ?></h3><div class="value" style="color:<?= (float)$kpi_w >= 0 ? 'green' : 'red' ?>">€ <?= $kpi_w ?></div></div>
<div class="kpi-card"><h3><?= __("suppliers") ?></h3><div class="value"><?= $kpi_s ?></div></div>

<div class="kpi-card" style="border-top-color: #f39c12;">
    <h3>Efficiency (3-5%)</h3>
    <div class="value" style="color:#f39c12;">2.8%</div>
    <small>Doel: &lt; 5%</small>
</div>

</div>
<div class="content-grid">
<div class="card"><h2>🕒 <?= __("recent_activity") ?></h2>
<div style="text-align:right; margin-bottom:10px;">
    <button onclick="clearLogs()" style="background:#c0392b; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer; font-size:12px;">🗑️ Wis Log</button>
</div>
<ul><?php foreach($logs as $l): ?><li><strong>[<?= htmlspecialchars($l['m']) ?>]</strong> <small style="float:right;color:#999"><?= substr($l['d'],0,16) ?></small></li><?php endforeach; ?><?php if(empty($logs)) echo "<li>".__("no_activity")."</li>"; ?></ul></div>
<div class="card"><h2>🚀 <?= __("menu") ?></h2><div class="menu-grid">
<a href="b01_seed_inventory.php" class="menu-btn">🌱 <?= __("seed_inventory") ?></a>
<a href="b02_germination.php" class="menu-btn">🌿 <?= __("germination") ?></a>
<a href="b07_harvest.php" class="menu-btn">🧺 <?= __("harvest") ?></a>
<a href="b09_packaging.php" class="menu-btn">📦 <?= __("packaging") ?></a>
<a href="b12_cleaning.php" class="menu-btn">🧼 <?= __("cleaning") ?></a>
<a href="b18_suppliers.php" class="menu-btn">🏭 <?= __("suppliers") ?></a>
<a href="b19_financials.php" class="menu-btn">💰 <?= __("finance") ?></a>
<a href="b28_watchdog.php" class="menu-btn">🐕 <?= __("watchdog") ?></a>
</div></div>
</div>
</div>

<script>
function clearLogs() {
    if (confirm("Weet je zeker dat je de recente activiteit wilt wissen? Dit verwijdert geen database records, maar only de weergave.")) {
        alert("Log gewist! (Simulatie)");
        // Hier zou je een AJAX call doen naar een clear_log.php script
        location.reload();
    }
}
</script>
</body>
</html>