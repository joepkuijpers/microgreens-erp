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
            <h3>🌱 Teelt Command Center</h3>
            <p>Actieve teelt, groeifase en profielstrategie</p>
        </div>
        <span class="sensor-status-badge <?= $stage['stage'] === 'overdue' ? 'alarm' : 'ok' ?>">
            <?= htmlspecialchars($stage['label']) ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <div class="live-sensor-item ok">
            <strong>Gewas</strong>
            <span><?= htmlspecialchars($profile['crop_name'] ?? 'Onbekend') ?></span>
            <small>Batch #<?= htmlspecialchars((string)($profile['batch_id'] ?? '-')) ?></small>
        </div>

        <div class="live-sensor-item ok">
            <strong>Dag</strong>
            <span><?= htmlspecialchars((string)($stage['day_number'] ?? '-')) ?></span>
            <small>Blackout: <?= htmlspecialchars((string)($stage['blackout_days'] ?? 0)) ?> dagen</small>
        </div>

        <div class="live-sensor-item <?= $stage['stage'] === 'overdue' ? 'alarm' : 'ok' ?>">
            <strong>Oogst</strong>
            <span><?= $daysToHarvest === null ? '-' : htmlspecialchars((string)$daysToHarvest) . ' dagen' ?></span>
            <small>Venster: dag <?= htmlspecialchars((string)($stage['grow_days_min'] ?? '-')) ?>–<?= htmlspecialchars((string)($stage['grow_days_max'] ?? '-')) ?></small>
        </div>

        <div class="live-sensor-item ok">
            <strong>Klimaat</strong>
            <span><?= htmlspecialchars((string)$profile['temp_min']) ?>–<?= htmlspecialchars((string)$profile['temp_max']) ?> °C</span>
            <small>RV <?= htmlspecialchars((string)$profile['humidity_min']) ?>–<?= htmlspecialchars((string)$profile['humidity_max']) ?>%</small>
        </div>

        <div class="live-sensor-item ok">
            <strong>Licht</strong>
            <span><?= htmlspecialchars((string)$profile['light_hours_per_day']) ?> uur/dag</span>
            <small>Fase: <?= htmlspecialchars($stage['stage']) ?></small>
        </div>

        <div class="live-sensor-item ok">
            <strong>Water</strong>
            <span>Automatisch</span>
            <small><?= htmlspecialchars($profile['irrigation_notes'] ?? 'Geen notities') ?></small>
        </div>
    </div>
</div>
