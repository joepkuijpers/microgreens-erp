<?php
/**
 * Microgreens ERP - Operationeel Dashboard
 * Doel: Real-time inzicht in productie, voorraad en waarschuwingen.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$dbPath = __DIR__ . '/../database/MicrogreensERP_Live.sqlite';
if (!file_exists($dbPath)) { die("DB niet gevonden"); }
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("DB Fout: " . $e->getMessage()); }

// --- KPI's OPHALEN ---

// 1. Aantal Actieve Batches (in teelt)
$stmt = $pdo->query("SELECT COUNT(*) FROM production_batches WHERE status IN ('GERMINATING', 'GROWING')");
$kpi_actieve_batches = $stmt->fetchColumn();

// 2. Verwachte Oogst (deze week) - Simpele schatting op basis van startdatum + 14 dagen
$stmt = $pdo->query("SELECT SUM(planned_quantity) FROM production_batches WHERE status IN ('GERMINATING', 'GROWING') AND started_at >= date('now', '-14 days')");
$kpi_verwachte_oogst = $stmt->fetchColumn() ?: 0;

// 3. Totale Waarde Voorraad (Verpakt & Beschikbaar)
// Som van total_weight_g uit packaging_units waar nog niet verkocht (als we een verkoop-status zouden hebben, nu gewoon alles)
$stmt = $pdo->query("SELECT SUM(total_weight_g) FROM packaging_units");
$kpi_voorraad_g = $stmt->fetchColumn() ?: 0;

// 4. Waarschuwingen: Sensor Faults (laatste 24 uur)
$stmt = $pdo->query("SELECT COUNT(*) FROM sensor_log WHERE status LIKE 'FAULT%' AND timestamp >= datetime('now', '-1 day')");
$kpi_sensor_faults = $stmt->fetchColumn();

// 5. Waarschuwingen: Lage Zaadvoorraad (< 50g)
$stmt = $pdo->query("SELECT COUNT(*) FROM seed_inventory WHERE stock_grams < 50");
$kpi_lage_zaadvoorraad = $stmt->fetchColumn();

// 6. Laatste Activiteit
$stmt = $pdo->query("SELECT action, timestamp FROM audit_events ORDER BY id DESC LIMIT 5"); // Fallback als tabel mist
$recent_logs = []; 
// Als audit_events niet bestaat, pakken we een alternatief
try {
    $stmt = $pdo->query("SELECT 'Verpakking' as action, created_at as timestamp FROM packaging_units UNION ALL SELECT 'Oogst', harvest_date FROM harvests ORDER BY timestamp DESC LIMIT 5");
    $recent_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// 7. Rack Bezetting (Visualisatie)
$stmt = $pdo->query("SELECT rack_id, COUNT(*) as count FROM spatial_allocations WHERE status = 'ACTIVE' GROUP BY rack_id");
$rack_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Microgreens ERP Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Liberation Sans', sans-serif; margin: 0; background: #f0f2f5; }
        .header { background: #0056b3; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 1.5rem; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        
        /* KPI Grid */
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .kpi-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 5px solid #0056b3; }
        .kpi-card.warning { border-left-color: #dc3545; }
        .kpi-card.success { border-left-color: #28a745; }
        .kpi-value { font-size: 2rem; font-weight: bold; color: #333; }
        .kpi-label { color: #666; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; }
        
        /* Secties */
        .section { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        h2 { margin-top: 0; color: #333; border-bottom: 2px solid #f0f2f5; padding-bottom: 0.5rem; }
        
        /* Rack Visualisatie */
        .rack-bar { height: 30px; background: #e9ecef; border-radius: 4px; overflow: hidden; margin-top: 5px; }
        .rack-fill { height: 100%; background: #28a745; text-align: center; color: white; font-size: 0.8rem; line-height: 30px; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #555; }
        .status-badge { padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .status-FAULT { background: #f8d7da; color: #721c24; }
        .status-OK { background: #d4edda; color: #155724; }
    </style>
</head>
<body>

<div class="header">
    <h1>📊 Microgreens ERP Dashboard</h1>
    <div><?= date('d-m-Y H:i') ?></div>
</div>

<div class="container">
    
    <!-- KPI ROW -->
    <div class="kpi-grid">
        <div class="kpi-card success">
            <div class="kpi-label">Actieve Teelten</div>
            <div class="kpi-value"><?= $kpi_actieve_batches ?></div>
            <div style="font-size:0.8rem; color:#666;">Batches in groei</div>
        </div>
        
        <div class="kpi-card">
            <div class="kpi-label">Verwachte Oogst</div>
            <div class="kpi-value"><?= number_format($kpi_verwachte_oogst, 0) ?>g</div>
            <div style="font-size:0.8rem; color:#666;">Komende 7 dagen</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">Voorraad (Verpakt)</div>
            <div class="kpi-value"><?= number_format($kpi_voorraad_g, 0) ?>g</div>
            <div style="font-size:0.8rem; color:#666;">Totaal gewicht</div>
        </div>

        <?php if ($kpi_sensor_faults > 0): ?>
        <div class="kpi-card warning">
            <div class="kpi-label">⚠️ Sensor Faults</div>
            <div class="kpi-value"><?= $kpi_sensor_faults ?></div>
            <div style="font-size:0.8rem; color:#666;">Laatste 24 uur</div>
        </div>
        <?php endif; ?>

        <?php if ($kpi_lage_zaadvoorraad > 0): ?>
        <div class="kpi-card warning">
            <div class="kpi-label">⚠️ Lage Zaadvoorraad</div>
            <div class="kpi-value"><?= $kpi_lage_zaadvoorraad ?></div>
            <div style="font-size:0.8rem; color:#666;">< 50g resterend</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- RACK BEZETTING -->
    <div class="section">
        <h2>🏗️ Rack Bezetting</h2>
        <?php if (empty($rack_data)): ?>
            <p>Geen actieve allocaties gevonden.</p>
        <?php else: ?>
            <?php foreach ($rack_data as $rack): ?>
                <div style="margin-bottom: 1rem;">
                    <div style="display:flex; justify-content:space-between;">
                        <strong><?= htmlspecialchars($rack['rack_id']) ?></strong>
                        <span><?= $rack['count'] ?> trays bezet</span>
                    </div>
                    <div class="rack-bar">
                        <!-- Simpele visualisatie: max 20 posities assumed -->
                        <?php $percentage = min(100, ($rack['count'] / 20) * 100); ?>
                        <div class="rack-fill" style="width: <?= $percentage ?>%;"><?= round($percentage) ?>%</div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- RECENTE ACTIVITEIT -->
    <div class="section">
        <h2>🕒 Recente Activiteit</h2>
        <table>
            <thead><tr><th>Actie</th><th>Tijdstip</th></tr></thead>
            <tbody>
                <?php foreach ($recent_logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['action']) ?></td>
                        <td><?= htmlspecialchars($log['timestamp']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recent_logs)): ?><tr><td colspan="2">Geen recente activiteit.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
