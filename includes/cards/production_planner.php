<?php
require_once __DIR__ . '/../production_planner_engine.php';

$productionPlanner = getProductionPlanner($db);
?>

<div class="dashboard-section">
    <h2>📅 <?= htmlspecialchars(__('production_planner')) ?></h2>
</div>

<div class="live-sensor-card">
    <div class="live-sensor-header">
        <h3>📅 <?= htmlspecialchars(__('production_planning')) ?></h3>
        <span class="sensor-status-badge <?= $productionPlanner['summary']['needs_planning'] > 0 ? 'alarm' : 'ok' ?>">
            <?= (int)$productionPlanner['summary']['needs_planning'] ?> <?= htmlspecialchars(__('action_needed')) ?>
        </span>
    </div>

    <?php foreach ($productionPlanner['plans'] as $plan): ?>
        <div class="live-sensor-item <?= htmlspecialchars($plan['priority']) ?>">
            <span><?= htmlspecialchars($plan['crop_name']) ?></span>
            <small>
                <?= htmlspecialchars(__('active')) ?>: <?= (int)$plan['active_trays'] ?> <?= htmlspecialchars(__('trays')) ?> |
                <?= htmlspecialchars(__('planned')) ?>: <?= (int)$plan['planned_trays'] ?> <?= htmlspecialchars(__('trays')) ?> |
                <?= htmlspecialchars(__('cycle')) ?>: <?= (int)$plan['cycle_days'] ?> <?= htmlspecialchars(__('days')) ?><br>

                <?= htmlspecialchars(__('next_sowing')) ?>: <?= htmlspecialchars($plan['next_sow_date']) ?> |
                <?= htmlspecialchars(__('expected_harvest')) ?>: <?= htmlspecialchars($plan['expected_harvest_date']) ?><br>

                <?= htmlspecialchars(__('advice')) ?>: <?= htmlspecialchars($plan['advice']) ?>
            </small>
        </div>
    <?php endforeach; ?>
</div>