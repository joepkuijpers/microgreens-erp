<?php
// ==========================================================
// MICROGREENS ERP - CENTRAL DASHBOARD
// ==========================================================
require_once __DIR__ . '/app/includes/header.php';
$pageTitle = __('dashboard_home'); // Zorg dat deze key bestaat, anders valt het terug op 'ERP'
?>

<div style="max-width: 1200px; margin: 0 auto;">
    
    <!-- Welkom Sectie -->
    <div style="margin-bottom: 30px;">
        <h1 style="color:#2c3e50; margin-bottom: 10px;">👋 <?php echo __('welcome_back'); ?></h1>
        <p style="color:#666;"><?php echo __('dashboard_overview_text'); ?></p>
    </div>

    <!-- Watchdog Alert (Als er iets kritieks is) -->
    <?php if($wdStatus === 'critical' || $wdStatus === 'warning'): ?>
        <div style="background: <?php echo $wdStatus==='critical'?'#f8d7da':'#fff3cd'; ?>; color: <?php echo $wdStatus==='critical'?'#721c24':'#856404'; ?>; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 5px solid <?php echo $wdStatus==='critical'?'#dc3545':'#ffc107'; ?>;">
            <h3 style="margin:0 0 10px 0;">🚨 <?php echo __('system_alert'); ?></h3>
            <p style="margin:0; font-size: 1.1em;"><?php echo htmlspecialchars($wdMsg); ?></p>
            <a href="b28_watchdog_alerts.php" style="display:inline-block; margin-top:15px; color:inherit; text-decoration:underline;"><?php echo __('view_all_alerts'); ?> →</a>
        </div>
    <?php endif; ?>

    <!-- Grid Layout voor Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        
        <!-- Card 1: Batch Queue (B01) -->
        <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-top:4px solid #3498db;">
            <h3 style="margin-top:0; color:#2c3e50;">🌱 <?php echo __('active_batches'); ?></h3>
            <?php
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM batches WHERE status = 'active'");
                $count = $stmt->fetchColumn();
                echo "<div style='font-size:2.5em; font-weight:bold; color:#3498db;'>$count</div>";
                echo "<p style='color:#666;'>" . __('batches_currently_running') . "</p>";
            } catch (Exception $e) { echo "<p>...</p>"; }
            ?>
            <a href="b01_seed_inventory.php" style="display:block; margin-top:15px; color:#3498db; text-decoration:none; font-weight:bold;">Beheer Batches →</a>
        </div>

        <!-- Card 2: Freeze Dry (B08) -->
        <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-top:4px solid #9b59b6;">
            <h3 style="margin-top:0; color:#2c3e50;">🧊 <?php echo __('freeze_dry_status'); ?></h3>
            <?php
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM freeze_dry_processes WHERE status = 'RUNNING'");
                $count = $stmt->fetchColumn();
                echo "<div style='font-size:2.5em; font-weight:bold; color:#9b59b6;'>$count</div>";
                echo "<p style='color:#666;'>" . __('cycles_running') . "</p>";
            } catch (Exception $e) { echo "<p>...</p>"; }
            ?>
            <a href="b08_freeze_dry.php" style="display:block; margin-top:15px; color:#9b59b6; text-decoration:none; font-weight:bold;">Start Cyclus →</a>
        </div>

        <!-- Card 3: Alerts / Watchdog -->
        <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-top:4px solid #e74c3c;">
            <h3 style="margin-top:0; color:#2c3e50;">⚠️ <?php echo __('open_alerts'); ?></h3>
            <?php
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM system_alerts WHERE acknowledged = 0");
                $count = $stmt->fetchColumn();
                $color = $count > 0 ? '#e74c3c' : '#27ae60';
                echo "<div style='font-size:2.5em; font-weight:bold; color:$color;'>$count</div>";
                echo "<p style='color:#666;'>" . ($count > 0 ? __('alerts_need_attention') : __('all_systems_go')) . "</p>";
            } catch (Exception $e) { echo "<p>...</p>"; }
            ?>
            <a href="b28_watchdog_alerts.php" style="display:block; margin-top:15px; color:#e74c3c; text-decoration:none; font-weight:bold;">Bekijk Alerts →</a>
        </div>

        <!-- Card 4: Snelle Acties -->
        <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-top:4px solid #27ae60;">
            <h3 style="margin-top:0; color:#2c3e50;">⚡ <?php echo __('quick_actions'); ?></h3>
            <ul style="list-style:none; padding:0; margin:0;">
                <li style="margin-bottom:10px;">🔹 <a href="b07_harvest.php" style="color:#27ae60; text-decoration:none;"><?php echo __('register_harvest'); ?></a></li>
                <li style="margin-bottom:10px;">🔹 <a href="b09_packaging.php" style="color:#27ae60; text-decoration:none;"><?php echo __('start_packaging'); ?></a></li>
                <li style="margin-bottom:10px;">🔹 <a href="b13_energy.php" style="color:#27ae60; text-decoration:none;"><?php echo __('view_energy_usage'); ?></a></li>
            </ul>
        </div>

    </div>

    <!-- Hier kunnen we later de bestaande 'cards' includes laden -->
    <!-- Bijvoorbeeld: <?php // include __DIR__.'/app/includes/cards/climate_status.php'; ?> -->

</div>

<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
