<?php
// --- 1. CONFIGURATIE & DATABASE CONNECTIE ---
// We gebruiken dezelfde database connectie als in je oude bestand
$dbPath = __DIR__ . '/../database/MicrogreensERP_Development.sqlite';
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connectie mislukt: " . $e->getMessage());
}

// --- 2. NIEUWE MODULE ROUTERINGSLOGICA ---
$module_id = isset($_GET['module']) ? $_GET['module'] : 'dashboard';
$module_file = '';
$module_title = '';

$modules_map = [
    'b01' => ['file' => 'b01_seed_inventory.php', 'title' => 'Zaad Inventaris'],
    'b02' => ['file' => 'b02_germination.php', 'title' => 'Kiemen'],
    'b03' => ['file' => 'b03_growth_chamber.php', 'title' => 'Groeikamer'],
    'b04' => ['file' => 'b04_lighting.php', 'title' => 'Verlichting'],
    'b05' => ['file' => 'b05_irrigation.php', 'title' => 'Irrigatie'],
    'b06' => ['file' => 'b06_climate_monitoring.php', 'title' => 'Klimaat Monitoring'],
    'b07' => ['file' => 'b07_harvest_readiness.php', 'title' => 'Oogst Gereedheid'],
    'b08' => ['file' => 'b08_quality_control.php', 'title' => 'Kwaliteitscontrole'],
    'b09' => ['file' => 'b09_customer_order.php', 'title' => 'Klantenorders'],
    'b10' => ['file' => 'b10_iot_climate.php', 'title' => 'IoT Klimaat'],
    'b11' => ['file' => 'b11_packaging.php', 'title' => 'Verpakking'],
    'b12' => ['file' => 'b12_cleaning.php', 'title' => 'Schoonmaak'],
    'b13' => ['file' => 'b13_energy.php', 'title' => 'Energie'],
    'b14' => ['file' => 'b14_water_usage.php', 'title' => 'Watergebruik'],
    'b15' => ['file' => 'b15_substrate.php', 'title' => 'Substraat'],
    'b16' => ['file' => 'b16_task_scheduler.php', 'title' => 'Taak Planner'],
    'b17' => ['file' => 'b17_staff.php', 'title' => 'Personeel'],
    'b18' => ['file' => 'b18_supplier.php', 'title' => 'Leveranciers'],
    'b19' => ['file' => 'b19_financials.php', 'title' => 'Financiën'],
    'b20' => ['file' => 'b20_waste.php', 'title' => 'Afval'],
    'b21' => ['file' => 'b21_logistics.php', 'title' => 'Logistiek'],
    'b22' => ['file' => 'b22_maintenance.php', 'title' => 'Onderhoud'],
    'b23' => ['file' => 'b23_sops.php', 'title' => 'SOPs'],
    'b24' => ['file' => 'b24_sales_analytics.php', 'title' => 'Verkoop Analyse'],
    'b25' => ['file' => 'b25_customer_feedback.php', 'title' => 'Klantfeedback'],
    'b26' => ['file' => 'b26_system_settings.php', 'title' => 'Systeem Instellingen'],
    'b27' => ['file' => 'b27_api_integrations.php', 'title' => 'API Integraties'],
    'b28' => ['file' => 'b28_watchdog_alerts.php', 'title' => 'Watchdog & Alerts'],
];

// Bepaal welk bestand geladen moet worden
if (array_key_exists($module_id, $modules_map)) {
    $module_info = $modules_map[$module_id];
    // Let op: pad wijst naar de nieuwe map app/modules/
    $module_file = __DIR__ . '/../app/modules/' . $module_info['file'];
    $module_title = $module_info['title'];
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title><?php echo $module_title ? $module_title . ' - Microgreens ERP' : 'Microgreens ERP Dashboard'; ?></title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f4f9; }
        h1 { color: #2c3e50; }
        .card { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background: #ecf0f1; }
        .badge { padding: 4px 8px; border-radius: 4px; color: white; font-size: 0.8em; font-weight: bold; }
        .bg-completed { background: #27ae60; }
        .bg-running { background: #f39c12; }
        .nav-bar { margin-bottom: 20px; }
        .nav-bar a { margin-right: 10px; text-decoration: none; color: #2980b9; font-weight: bold; }
        .nav-bar a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <!-- Navigatie (Optioneel, voor makkelijk testen) -->
    <div class="nav-bar">
        <a href="index.php">🏠 Dashboard</a>
        <a href="index.php?module=b06">🌡️ Klimaat (B06)</a>
        <a href="index.php?module=b09">📦 Orders (B09)</a>
        <a href="index.php?module=b10">📡 IoT (B10)</a>
        <a href="index.php?module=b28">🚨 Watchdog (B28)</a>
    </div>

    <?php if ($module_title): ?>
        <h1><?php echo htmlspecialchars($module_title); ?></h1>
    <?php endif; ?>

    <div class="content">
        <?php
        // --- 3. DYNAMISCHE CONTENT LADER ---
        
        // SCENARIO A: Een module is gekozen EN het bestand bestaat
        if ($module_file && file_exists($module_file)) {
            include $module_file;
        } 
        // SCENARIO B: Dashboard (geen module gekozen)
        elseif (empty($module_id) || $module_id === 'dashboard') {
        ?>
            <!-- HIER IS JE OORSPRONKELIJKE DASHBOARD CODE -->
            <div class="card">
                <h2>Batches</h2>
                <table>
                    <tr><th>Code</th><th>Status</th><th>Gewicht</th></tr>
                    <?php
                    $stmt = $db->query("SELECT batch_code, status, actual_quantity, quantity_unit FROM production_batches ORDER BY created_at DESC LIMIT 5");
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr><td>{$row['batch_code']}</td><td>{$row['status']}</td><td>{$row['actual_quantity']} {$row['quantity_unit']}</td></tr>";
                    }
                    ?>
                </table>
            </div>
            <div class="card">
                <h2>Freeze-Dry Processen</h2>
                <table>
                    <tr><th>Cycle</th><th>Machine</th><th>Status</th><th>Yield</th></tr>
                    <?php
                    $stmt = $db->query("SELECT cycle_code, machine_id, status, ROUND((final_weight_g/input_weight_g)*100, 1) as yield FROM freeze_dry_processes ORDER BY started_at DESC LIMIT 5");
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $class = ($row['status'] == 'COMPLETED') ? 'bg-completed' : 'bg-running';
                        echo "<tr><td>{$row['cycle_code']}</td><td>{$row['machine_id']}</td><td><span class='badge {$class}'>{$row['status']}</span></td><td>{$row['yield']}%</td></tr>";
                    }
                    ?>
                </table>
            </div>
        <?php
        } 
        // SCENARIO C: Module niet gevonden
        else {
            echo "<div class='card'><p style='color:red;'>Fout: Module <strong>" . htmlspecialchars($module_id) . "</strong> niet gevonden.</p></div>";
        }
        ?>
    </div>

</body>
</html>