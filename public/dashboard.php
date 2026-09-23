<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);
session_start();
$dbPath = __DIR__ . "/../database/MicrogreensERP_Live.sqlite";
if (!file_exists($dbPath)) { die("DB niet gevonden"); }
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("DB Fout: " . $e->getMessage()); }
$stmt = $pdo->query("SELECT COUNT(*) FROM germination_records WHERE status != 'HARVESTED'");
$kpi_actieve = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT SUM(weight_grams) FROM harvests");
$kpi_oogst = $stmt->fetchColumn() ?: 0;
$stmt = $pdo->query("SELECT SUM(weight_grams) FROM finished_inventory WHERE status = 'AVAILABLE'");
$kpi_voorraad = $stmt->fetchColumn() ?: 0;
$logs = [];
try {
    $sql = "SELECT '🧺 Oogst: ' || sl.variety || ' (' || h.weight_grams || 'g)' as action, h.harvest_date as timestamp FROM harvests h JOIN germination_records gr ON h.germination_record_id = gr.id JOIN seed_inventory si ON gr.seed_inventory_id = si.id JOIN seed_lots sl ON si.seed_lot_id = sl.id UNION ALL SELECT '📦 Verpakking', created_at FROM packaging_units ORDER BY timestamp DESC LIMIT 5";
    $logs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $logs = []; }
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8"><title>Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{font-family:sans-serif;background:#f0f2f5;margin:0;padding:2rem}
.header{background:#0056b3;color:white;padding:1rem 2rem;border-radius:8px;margin-bottom:2rem;display:flex;justify-content:space-between}
.container{max-width:1200px;margin:0 auto}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;margin-bottom:2rem}
.kpi-card{background:white;padding:1.5rem;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);border-left:5px solid #0056b3}
.kpi-card.success{border-left-color:#28a745}
.kpi-value{font-size:2rem;font-weight:bold;color:#333}
.kpi-label{color:#666;text-transform:uppercase;font-size:0.85rem}
.section{background:white;padding:1.5rem;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);margin-bottom:2rem}
h2{margin-top:0;color:#333;border-bottom:2px solid #f0f2f5;padding-bottom:0.5rem}
table{width:100%;border-collapse:collapse}
th,td{padding:0.75rem;text-align:left;border-bottom:1px solid #eee}
th{background:#f8f9fa}
.nav-links{margin-top:2rem;display:flex;gap:1rem;flex-wrap:wrap}
.nav-links a{background:#0056b3;color:white;padding:0.75rem 1.5rem;text-decoration:none;border-radius:4px}
</style>
</head>
<body>
<div class="container">
<div class="header"><h1>📊 Microgreens ERP</h1><div><?= date('d-m-Y H:i') ?></div></div>
<div class="kpi-grid">
<div class="kpi-card"><div class="kpi-label">Actieve Teelten</div><div class="kpi-value"><?= $kpi_actieve ?></div></div>
<div class="kpi-card success"><div class="kpi-label">Totale Oogst</div><div class="kpi-value"><?= number_format($kpi_oogst, 1) ?> g</div></div>
<div class="kpi-card success"><div class="kpi-label">Verkoopbare Voorraad</div><div class="kpi-value"><?= number_format($kpi_voorraad, 1) ?> g</div></div>
</div>
<div class="section"><h2>🕒 Recente Activiteit</h2><table><thead><tr><th>Actie</th><th>Tijdstip</th></tr></thead><tbody>
<?php foreach($logs as $l): ?><tr><td><?= htmlspecialchars($l['action']) ?></td><td><?= htmlspecialchars($l['timestamp'] ?? '') ?></td></tr><?php endforeach; ?>
<?php if(empty($logs)): ?><tr><td colspan="2">Geen activiteit</td></tr><?php endif; ?>
</tbody></table></div>
<div class="nav-links">
<a href="b01_seed_inventory.php">🌱 B01</a>
<a href="b02_germination.php">🌿 B02</a>
<a href="b07_harvest.php">🧺 B07</a>
<a href="b09_packaging.php">📦 B09</a>
<a href="b19_financials.php">💰 B19</a>
</div>
</div>
</body>
</html>