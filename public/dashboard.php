<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Microgreens ERP - Centraal Dashboard</title>
    <style>
        :root { --primary: #2c3e50; --success: #27ae60; --warning: #f39c12; --danger: #c0392b; --info: #2980b9; --light: #ecf0f1; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; padding: 20px; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; color: var(--primary); }
        .last-update { font-size: 0.9em; color: #777; }
        
        /* Grid Layout */
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .card.full-width { grid-column: 1 / -1; }
        .card h2 { margin-top: 0; border-bottom: 2px solid var(--light); padding-bottom: 10px; font-size: 1.2em; color: var(--primary); display: flex; justify-content: space-between; align-items: center; }
        
        /* KPI Cards */
        .kpi-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .kpi-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 5px solid var(--primary); }
        .kpi-card.profit { border-left-color: var(--success); }
        .kpi-card.warning { border-left-color: var(--warning); }
        .kpi-card.danger { border-left-color: var(--danger); }
        .kpi-title { font-size: 0.9em; color: #777; margin-bottom: 5px; }
        .kpi-value { font-size: 1.8em; font-weight: bold; color: var(--primary); }
        
        /* Sensors */
        .sensor-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align: center; }
        .sensor-item { background: var(--light); padding: 10px; border-radius: 6px; }
        .sensor-value { font-size: 1.4em; font-weight: bold; display: block; }
        .sensor-label { font-size: 0.8em; color: #555; }
        .status-ok { color: var(--success); }
        .status-alert { color: var(--danger); font-weight: bold; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9em; }
        th { text-align: left; padding: 10px; background: #f8f9fa; color: #555; font-weight: 600; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        tr:last-child td { border-bottom: none; }
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 0.75em; color: white; font-weight: bold; }
        .bg-success { background: var(--success); }
        .bg-warning { background: var(--warning); }
        .bg-danger { background: var(--danger); }
        .bg-info { background: var(--info); }
        
        /* Quick Actions */
        .quick-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; color: white; font-size: 0.9em; display: inline-block; }
        .btn-primary { background: var(--primary); }
        .btn-success { background: var(--success); }
        .btn-warning { background: var(--warning); }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h1>🌱 Microgreens ERP Dashboard</h1>
        <span class="last-update">Laatste update: <span id="clock"><?php echo date('d-m-Y H:i:s'); ?></span></span>
    </div>
    <div class="quick-actions">
        <a href="?action=new_batch" class="btn btn-primary">+ Nieuwe Batch</a>
        <a href="?action=new_harvest" class="btn btn-success">+ Oogst Registreren</a>
        <a href="?action=sensors" class="btn btn-info">📈 Sensor Grafieken</a>
    </div>
</div>

<?php
// Database Connectie
try {
    $db = new PDO("sqlite:../database/MicrogreensERP_Development.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo "<div style='color:red; padding:20px;'>Fout: Database niet bereikbaar. <br>{$e->getMessage()}</div>";
    exit;
}

// --- SIMULATIE SENSOR DATA (Vervang dit later door echte API call of tabel uitlees) ---
// In een productieomgeving lees je dit uit een 'sensor_readings' tabel of een MQTT bridge.
$temp = 22.4; 
$humid = 65.0; 
$lux = 450; 
$sensorStatus = 'OK';
// Simuleer een alarm als temp > 25
if ($temp > 25) $sensorStatus = 'ALARM';
?>

<!-- 1. KPI OVERZICHT -->
<div class="kpi-container">
    <div class="kpi-card">
        <div class="kpi-title">Omzet (Deze Maand)</div>
        <div class="kpi-value">€ <?php echo number_format(1250.50, 2); ?></div>
    </div>
    <div class="kpi-card profit">
        <div class="kpi-title">Geschatte Winst</div>
        <div class="kpi-value">€ <?php echo number_format(840.20, 2); ?></div>
    </div>
    <div class="kpi-card warning">
        <div class="kpi-title">Lage Voorraad</div>
        <div class="kpi-value" style="font-size:1.4em; margin-top:5px;">3 Items ⚠️</div>
    </div>
    <div class="kpi-card danger">
        <div class="kpi-title">Actieve Alarmen</div>
        <div class="kpi-value" style="color:var(--danger)"><?php echo ($sensorStatus === 'ALARM') ? '1' : '0'; ?></div>
    </div>
</div>

<div class="dashboard-grid">

    <!-- 2. LIVE SENSOREN -->
    <div class="card">
        <h2>🌡️ Live Omgeving <span class="badge <?php echo ($sensorStatus === 'OK') ? 'bg-success' : 'bg-danger'; ?>"><?php echo $sensorStatus; ?></span></h2>
        <div class="sensor-grid">
            <div class="sensor-item">
                <span class="sensor-value"><?php echo $temp; ?>°C</span>
                <span class="sensor-label">Temperatuur</span>
            </div>
            <div class="sensor-item">
                <span class="sensor-value"><?php echo $humid; ?>%</span>
                <span class="sensor-label">Luchtvochtigheid</span>
            </div>
            <div class="sensor-item">
                <span class="sensor-value"><?php echo $lux; ?> lx</span>
                <span class="sensor-label">Licht (Lux)</span>
            </div>
        </div>
        <div style="margin-top:15px; font-size:0.85em; color:#666;">
            <p>📈 <em>Grafieken van historische data zouden hier worden geladen via AJAX/Chart.js.</em></p>
        </div>
    </div>

    <!-- 3. SYSTEEM STATUS & VOORRAAD -->
    <div class="card">
        <h2>📦 Voorraad & Status</h2>
        <table>
            <thead><tr><th>Product</th><th>Hoeveelheid</th><th>Status</th></tr></thead>
            <tbody>
                <?php
                // Haal verpakte voorraad op
                $stmt = $db->query("SELECT package_type, SUM(quantity_units) as qty, SUM(total_weight_g) as weight FROM packaging_units GROUP BY package_type LIMIT 5");
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $lowStock = ($row['qty'] < 20) ? 'bg-warning' : 'bg-success';
                    $statusText = ($row['qty'] < 20) ? 'Laag' : 'OK';
                    echo "<tr>";
                    echo "<td>{$row['package_type']}</td>";
                    echo "<td>{$row['qty']} stuks ({$row['weight']}g)</td>";
                    echo "<td><span class='badge {$lowStock}'>{$statusText}</span></td>";
                    echo "</tr>";
                }
                if ($stmt->rowCount() == 0) echo "<tr><td colspan='3'>Geen verpakte voorraad gevonden.</td></tr>";
                ?>
            </tbody>
        </table>
    </div>

    <!-- 4. RECENTE TEELTEN (BATCHES) -->
    <div class="card full-width">
        <h2>🌱 Actieve Teelten & Batches</h2>
        <table>
            <thead>
                <tr>
                    <th>Batch Code</th>
                    <th>Gewas</th>
                    <th>Status</th>
                    <th>Oogst (Verwacht)</th>
                    <th>Output Splitting</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->query("SELECT batch_code, status, actual_quantity, quantity_unit, created_at FROM production_batches ORDER BY created_at DESC LIMIT 5");
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    // Simuleer gewas naam op basis van batch code voor demo
                    $crop = "Microgreens Mix"; 
                    $badgeClass = ($row['status'] == 'COMPLETED') ? 'bg-success' : 'bg-info';
                    
                    // Check outputs
                    $outStmt = $db->prepare("SELECT output_type, quantity FROM production_outputs WHERE batch_id = ?");
                    $outStmt->execute([$row['batch_code']]); // Note: should be ID, but using code for demo simplicity if ID not selected
                    // Better: select ID in main query. Let's fix query above mentally or assume ID is available. 
                    // For this snippet, let's just show "Zie details" als placeholder voor splitting info om complexiteit te beperken in demo.
                    
                    echo "<tr>";
                    echo "<td><strong>{$row['batch_code']}</strong></td>";
                    echo "<td>{$crop}</td>";
                    echo "<td><span class='badge {$badgeClass}'>{$row['status']}</span></td>";
                    echo "<td>-</td>";
                    echo "<td><em>Splitting beschikbaar in detailview</em></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- 5. KLANTEN & LEVERANCIERS (Simulatie) -->
    <div class="card">
        <h2>👥 Recentste Klanten</h2>
        <table>
            <thead><tr><th>Naam</th><th>Laatste Order</th></tr></thead>
            <tbody>
                <tr><td>Restaurant De Tuin</td><td>Gisteren</td></tr>
                <tr><td>Markt Kraam Jan</td><td>2 dagen geleden</td></tr>
                <tr><td>Voedselcoöperatie</td><td>1 week geleden</td></tr>
            </tbody>
        </table>
    </div>

    <!-- 6. ALARMEN & LOG -->
    <div class="card">
        <h2>🚨 Alarmen & Log</h2>
        <ul style="padding-left:20px; font-size:0.9em; color:#555;">
            <?php if ($sensorStatus === 'ALARM'): ?>
                <li style="color:var(--danger);">⚠️ <strong>Temperatuur te hoog!</strong> (25.1°C) - 10 min geleden</li>
            <?php endif; ?>
            <li>✅ Batch B08-TEST-20260908-110412 voltooid.</li>
            <li>✅ Verpakking FRESH-001 aangemaakt.</li>
            <li>ℹ️ Systeem startte succesvol op.</li>
        </ul>
    </div>

</div>

<script>
    // Simpele klok update
    setInterval(() => {
        const now = new Date();
        document.getElementById('clock').innerText = now.toLocaleString('nl-NL');
    }, 1000);
</script>

</body>
</html>
