<?php
require_once __DIR__ . '/../growth_stage_engine.php';

$stage = getGrowthStage($db);
$profile = $stage['profile'];
$daysToHarvest = null;

if ($stage['active'] && $stage['day_number'] !== null) {
    $daysToHarvest = max(0, (int)$stage['grow_days_min'] - (int)$stage['day_number']);
}
?>

<div class="climate-card">
    <div class="sensor-card-header">
        <div>
            <h3>🌱 <?= htmlspecialchars(__('growth_command_center')) ?></h3>
            <p><?= htmlspecialchars(__('active_crop_growth_stage_profile_strategy')) ?></p>
        </div>
        <span class="sensor-status-badge <?= $stage['stage'] === 'overdue' ? 'alarm' : 'ok' ?>">
            <?= htmlspecialchars($stage['label']) ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <div class="live-sensor-item ok">
            <strong><?= htmlspecialchars(__('crop')) ?></strong>
            <span><?= htmlspecialchars($profile['crop_name'] ?? __('unknown')) ?></span>
            <small><?= htmlspecialchars(__('batch')) ?> #<?= htmlspecialchars((string)($profile['batch_id'] ?? '-')) ?></small>
        </div>

        <div class="live-sensor-item ok">
            <strong><?= htmlspecialchars(__('day')) ?></strong>
            <span><?= htmlspecialchars((string)($stage['day_number'] ?? '-')) ?></span>
            <small><?= htmlspecialchars(__('blackout')) ?>: <?= htmlspecialchars((string)($stage['blackout_days'] ?? 0)) ?> <?= htmlspecialchars(__('days')) ?></small>
        </div>

        <div class="live-sensor-item <?= $stage['stage'] === 'overdue' ? 'alarm' : 'ok' ?>">
            <strong><?= htmlspecialchars(__('harvest')) ?></strong>
            <span><?= $daysToHarvest === null ? '-' : htmlspecialchars((string)$daysToHarvest) . ' ' . htmlspecialchars(__('days')) ?></span>
            <small><?= htmlspecialchars(__('window')) ?>: <?= htmlspecialchars(__('day')) ?> <?= htmlspecialchars((string)($stage['grow_days_min'] ?? '-')) ?>–<?= htmlspecialchars((string)($stage['grow_days_max'] ?? '-')) ?></small>
        </div>

        <div class="live-sensor-item ok">
            <strong><?= htmlspecialchars(__('climate')) ?></strong>
            <span><?= htmlspecialchars((string)$profile['temp_min']) ?>–<?= htmlspecialchars((string)$profile['temp_max']) ?> °C</span>
            <small><?= htmlspecialchars(__('relative_humidity_short')) ?> <?= htmlspecialchars((string)$profile['humidity_min']) ?>–<?= htmlspecialchars((string)$profile['humidity_max']) ?>%</small>
        </div>

        <div class="live-sensor-item ok">
            <strong><?= htmlspecialchars(__('light')) ?></strong>
            <span><?= htmlspecialchars((string)$profile['light_hours_per_day']) ?> <?= htmlspecialchars(__('hours_per_day')) ?></span>
            <small><?= htmlspecialchars(__('phase')) ?>: <?= htmlspecialchars($stage['stage']) ?></small>
        </div>

        <div class="live-sensor-item ok">
            <strong><?= htmlspecialchars(__('water')) ?></strong>
            <span><?= htmlspecialchars(__('automatic')) ?></span>
            <small><?= htmlspecialchars($profile['irrigation_notes'] ?? __('no_notes')) ?></small>
        </div>
    </div>
</div>