<?php
require_once __DIR__ . '/app/includes/header.php';
$pageTitle = __('dashboard_home');

// --- VEILIGE STATISTIEKEN OPHALEN (MET FALLBACK NAAR 0) ---
$stats = [
    'batches_active' => 0, 'batches_germinating' => 0, 'batches_growing' => 0,
    'trays_total' => 0, 'trays_empty' => 0, 'trays_decom' => 0,
    'fd_running' => 0, 'alerts_open' => 0,
    'energy_week' => 0.0, 'water_week' => 0.0, 'seed_varieties' => 0
];

try {
    $safeQuery = function($sql) use ($db) {
        try {
            $stmt = $db->query($sql);
            return $stmt ? $stmt->fetchColumn() : 0;
        } catch (Exception $e) { return 0; } // Fallback als tabel mist
    };

    $stats['batches_active'] = (int)$safeQuery("SELECT COUNT(*) FROM batches WHERE status='active'");
    $stats['batches_germinating'] = (int)$safeQuery("SELECT COUNT(*) FROM germination_records WHERE status='GERMINATING'");
    $stats['batches_growing'] = (int)$safeQuery("SELECT COUNT(*) FROM trays WHERE status='GROWING'");
    
    $stats['trays_total'] = (int)$safeQuery("SELECT COUNT(*) FROM trays");
    $stats['trays_empty'] = (int)$safeQuery("SELECT COUNT(*) FROM trays WHERE status='EMPTY'");
    $stats['trays_decom'] = (int)$safeQuery("SELECT COUNT(*) FROM trays WHERE status='DECOMMISSIONED'");
    
    $stats['fd_running'] = (int)$safeQuery("SELECT COUNT(*) FROM freeze_dry_processes WHERE status='RUNNING'");
    
    // Alerts (systeem tabel bestaat altijd)
    $stmt = $db->query("SELECT COUNT(*) FROM system_alerts WHERE acknowledged=0");
    $stats['alerts_open'] = $stmt ? (int)$stmt->fetchColumn() : 0;
    
    $stats['energy_week'] = (float)$safeQuery("SELECT COALESCE(SUM(kwh),0) FROM utility_logs WHERE created_at >= datetime('now', '-7 days')");
    $stats['water_week'] = (float)$safeQuery("SELECT COALESCE(SUM(liters),0) FROM water_measurements WHERE created_at >= datetime('now', '-7 days')");
    
    $stats['seed_varieties'] = (int)$safeQuery("SELECT COUNT(DISTINCT sl.id) FROM seed_inventory si JOIN seed_lots sl ON si.seed_lot_id = sl.id WHERE si.stock_grams > 0");

} catch (Exception $e) {
    // Als alles faalt, blijven de 0-waarden staan
}
?>

<div style="max-width:1400px; margin:0 auto;">
    <h1 style="color:#2c3e50; margin-bottom:10px;">👋 <?php echo __('welcome_back'); ?></h1>
    <p style="color:#666; margin-bottom:30px;"><?php echo __('dashboard_overview_text'); ?></p>
    
    <!-- Watchdog Alert (alleen tonen als er alerts zijn) -->
    <?php if($stats['alerts_open'] > 0 || ($wdStatus ?? 'ok') !== 'ok'): ?>
    <div style="background:#f8d7da; color:#721c24; padding:20px; border-radius:8px; margin-bottom:30px; border-left:5px solid #dc3545;">
        <strong>🚨 <?php echo __('system_alert'); ?>:</strong> 
        <?php echo htmlspecialchars($wdMsg ?? __('alerts_need_attention')); ?>
        <a href="b28_watchdog_alerts.php" style="display:block; margin-top:10px; color:#721c24; text-decoration:underline;"><?php echo __('view_all_alerts'); ?> →</a>
    </div>
    <?php endif; ?>

    <!-- RIJ 1: Kernprocessen -->
    <h3 style="color:#2c3e50; border-bottom:2px solid #3498db; padding-bottom:10px; margin-top:30px;">🌱 Kernprocessen</h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:30px;">
        <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-top:4px solid #3498db;">
            <h4 style="margin:0; color:#666; font-size:0.9em;"><?php echo __('active_batches'); ?></h4>
            <div style="font-size:2.5em; font-weight:bold; color:#3498db;"><?php echo $stats['batches_active']; ?></div>
            <a href="b01_seed_inventory.php" style="color:#3498db; font-weight:bold;">Beheer →</a>
        </div>
        <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-top:4px solid #27ae60;">
            <h4 style="margin:0; color:#666; font-size:0.9em;">Kiemen</h4>
            <div style="font-size:2.5em; font-weight:bold; color:#27ae60;"><?php echo $stats['batches_germinating']; ?></div>
            <a href="b02_germination.php" style="color:#27ae60; font-weight:bold;">Start →</a>
        </div>
        <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-top:4px solid #2ecc71;">
            <h4 style="margin:0; color:#666; font-size:0.9em;">Aan het Groeien</h4>
            <div style="font-size:2.5em; font-weight:bold; color:#2ecc71;"><?php echo $stats['batches_growing']; ?></div>
            <a href="b03_growth_stage.php" style="color:#2ecc71; font-weight:bold;">Bekijk →</a>
        </div>
        <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-top:4px solid #9b59b6;">
            <h4 style="margin:0; color:#666; font-size:0.9em;">Vriesdrogen</h4>
            <div style="font-size:2.5em; font-weight:bold; color:#9b59b6;"><?php echo $stats['fd_running']; ?></div>
            <a href="b08_freeze_dry.php" style="color:#9b59b6; font-weight:bold;">Start →</a>
        </div>
    </div>

    <!-- RIJ 2: Infrastructuur -->
    <h3 style="color:#2c3e50; border-bottom:2px solid #f39c12; padding-bottom:10px; margin-top:30px;">📦 Infrastructuur</h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:30px;">
        <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-top:4px solid #f39c12;">
            <h4 style="margin:0; color:#666; font-size:0.9em;">Totaal Trays</h4>
            <div style="font-size:2.5em; font-weight:bold; color:#f39c12;"><?php echo $stats['trays_total']; ?></div>
            <a href="b04_tray_management.php" style="color:#f39c12; font-weight:bold;">Beheer →</a>
        </div>
        <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-top:4px solid #27ae60;">
            <h4 style="margin:0; color:#666; font-size:0.9em;">Beschikbaar (Empty)</h4>
            <div style="font-size:2.5em; font-weight:bold; color:#27ae60;"><?php echo $stats['trays_empty']; ?></div>
            <small style="color:#666;">Klaar voor gebruik</small>
        </div>
        <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-top:4px solid #95a5a6;">
            <h4 style="margin:0; color:#666; font-size:0.9em;">Gedeactiveerd</h4>
            <div style="font-size:2.5em; font-weight:bold; color:#95a5a6;"><?php echo $stats['trays_decom']; ?></div>
            <small style="color:#666;">Traceability behouden</small>
        </div>
        <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-top:4px solid #16a085;">
            <h4 style="margin:0; color:#666; font-size:0.9em;">Zaad Variëteiten</h4>
            <div style="font-size:2.5em; font-weight:bold; color:#16a085;"><?php echo $stats['seed_varieties']; ?></div>
            <a href="b01_seed_inventory.php" style="color:#16a085; font-weight:bold;">Voorraad →</a>
        </div>
    </div>

    <!-- RIJ 3: Resources -->
    <h3 style="color:#2c3e50; border-bottom:2px solid #3498db; padding-bottom:10px; margin-top:30px;">⚡ Resources (Laatste 7 Dagen)</h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:30px;">
        <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-top:4px solid #f1c40f;">
            <h4 style="margin:0; color:#666; font-size:0.9em;">Energie Verbruik</h4>
            <div style="font-size:2.5em; font-weight:bold; color:#f1c40f;"><?php echo number_format($stats['energy_week'], 1); ?></div>
            <small style="color:#666;">kWh</small>
            <a href="b13_energy.php" style="display:block; color:#f1c40f; font-weight:bold; margin-top:5px;">Details →</a>
        </div>
        <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-top:4px solid #3498db;">
            <h4 style="margin:0; color:#666; font-size:0.9em;">Water Verbruik</h4>
            <div style="font-size:2.5em; font-weight:bold; color:#3498db;"><?php echo number_format($stats['water_week'], 1); ?></div>
            <small style="color:#666;">Liters</small>
            <a href="b14_water_usage.php" style="display:block; color:#3498db; font-weight:bold; margin-top:5px;">Details →</a>
        </div>
    </div>

    <!-- Snelle Acties -->
    <div style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); margin-top:30px;">
        <h3 style="margin-top:0;">⚡ <?php echo __('quick_actions'); ?></h3>
        <ul style="list-style:none; padding:0; display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:15px;">
            <li>🔹 <a href="b07_harvest.php" style="color:#27ae60; text-decoration:none; font-weight:bold;"><?php echo __('register_harvest'); ?></a></li>
            <li>🔹 <a href="b09_packaging.php" style="color:#27ae60; text-decoration:none; font-weight:bold;"><?php echo __('start_packaging'); ?></a></li>
            <li>🔹 <a href="b04_tray_management.php" style="color:#27ae60; text-decoration:none; font-weight:bold;"><?php echo __('tray_management'); ?></a></li>
            <li>🔹 <a href="b13_energy.php" style="color:#27ae60; text-decoration:none; font-weight:bold;"><?php echo __('view_energy_usage'); ?></a></li>
            <li>🔹 <a href="b28_watchdog_alerts.php" style="color:#e74c3c; text-decoration:none; font-weight:bold;"><?php echo __('view_all_alerts'); ?></a></li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
