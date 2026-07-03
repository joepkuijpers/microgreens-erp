<?php
require_once __DIR__ . '/../production_planner_engine.php';

$productionPlanner = getProductionPlanner($db);
?>

<div class="dashboard-section">
    <h2>📅 Productieplanner</h2>
</div>

<div class="live-sensor-card">
    <div class="live-sensor-header">
        <h3>📅 Productieplanning</h3>
        <span class="sensor-status-badge <?= $productionPlanner['summary']['needs_planning'] > 0 ? 'alarm' : 'ok' ?>">
            <?= (int)$productionPlanner['summary']['needs_planning'] ?> actie nodig
        </span>
    </div>

    <?php foreach ($productionPlanner['plans'] as $plan): ?>
        <div class="live-sensor-item <?= htmlspecialchars($plan['priority']) ?>">
            <span><?= htmlspecialchars($plan['crop_name']) ?></span>
            <small>
                Actief: <?= (int)$plan['active_trays'] ?> trays |
                Gepland: <?= (int)$plan['planned_trays'] ?> trays |
                Cyclus: <?= (int)$plan['cycle_days'] ?> dagen<br>
                Volgende zaai: <?= htmlspecialchars($plan['next_sow_date']) ?> |
                Verwachte oogst: <?= htmlspecialchars($plan['expected_harvest_date']) ?><br>
                Advies: <?= htmlspecialchars($plan['advice']) ?>
            </small>
        </div>
    <?php endforeach; ?>
</div>
