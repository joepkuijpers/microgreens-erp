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
            <h3>🌱 <?= htmlspecialchars(t('growth_command_center')) ?></h3>
            <p><?= htmlspecialchars(t('active_crop_growth_stage_profile_strategy')) ?></p>
        </div>
        <span class="sensor-status-badge <?= $stage['stage'] === 'overdue' ? 'alarm' : 'ok' ?>">
            <?= htmlspecialchars($stage['label']) ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <div class="live-sensor-item ok">
            <strong><?= htmlspecialchars(t('crop')) ?></strong>
            <span><?= htmlspecialchars($profile['crop_name'] ?? t('unknown')) ?></span>
            <small><?= htmlspecialchars(t('batch')) ?> #<?= htmlspecialchars((string)($profile['batch_id'] ?? '-')) ?></small>
        </div>

        <div class="live-sensor-item ok">
            <strong><?= htmlspecialchars(t('day')) ?></strong>
            <span><?= htmlspecialchars((string)($stage['day_number'] ?? '-')) ?></span>
            <small><?= htmlspecialchars(t('blackout')) ?>: <?= htmlspecialchars((string)($stage['blackout_days'] ?? 0)) ?> <?= htmlspecialchars(t('days')) ?></small>
        </div>

        <div class="live-sensor-item <?= $stage['stage'] === 'overdue' ? 'alarm' : 'ok' ?>">
            <strong><?= htmlspecialchars(t('harvest')) ?></strong>
            <span><?= $daysToHarvest === null ? '-' : htmlspecialchars((string)$daysToHarvest) . ' ' . htmlspecialchars(t('days')) ?></span>
            <small><?= htmlspecialchars(t('window')) ?>: <?= htmlspecialchars(t('day')) ?> <?= htmlspecialchars((string)($stage['grow_days_min'] ?? '-')) ?>–<?= htmlspecialchars((string)($stage['grow_days_max'] ?? '-')) ?></small>
        </div>

        <div class="live-sensor-item ok">
            <strong><?= htmlspecialchars(t('climate')) ?></strong>
            <span><?= htmlspecialchars((string)$profile['temp_min']) ?>–<?= htmlspecialchars((string)$profile['temp_max']) ?> °C</span>
            <small><?= htmlspecialchars(t('relative_humidity_short')) ?> <?= htmlspecialchars((string)$profile['humidity_min']) ?>–<?= htmlspecialchars((string)$profile['humidity_max']) ?>%</small>
        </div>

        <div class="live-sensor-item ok">
            <strong><?= htmlspecialchars(t('light')) ?></strong>
            <span><?= htmlspecialchars((string)$profile['light_hours_per_day']) ?> <?= htmlspecialchars(t('hours_per_day')) ?></span>
            <small><?= htmlspecialchars(t('phase')) ?>: <?= htmlspecialchars($stage['stage']) ?></small>
        </div>

        <div class="live-sensor-item ok">
            <strong><?= htmlspecialchars(t('water')) ?></strong>
            <span><?= htmlspecialchars(t('automatic')) ?></span>
            <small><?= htmlspecialchars($profile['irrigation_notes'] ?? t('no_notes')) ?></small>
        </div>
    </div>
</div>