<?php
require_once __DIR__ . '/../harvest_forecast_engine.php';

$harvestForecast = getHarvestForecast($db, 14);
$forecastStatus = ($harvestForecast['summary']['overdue'] > 0 || $harvestForecast['summary']['today'] > 0) ? 'alarm' : 'ok';
?>

<div class="dashboard-section">
    <h2>🌾 <?= htmlspecialchars(__('harvest_forecast')) ?></h2>
</div>

<div class="live-sensor-card">
    <div class="live-sensor-header">
        <h3>🌾 <?= htmlspecialchars(__('harvest_forecast_14_days')) ?></h3>
        <span class="sensor-status-badge <?= $forecastStatus ?>">
            <?= (int)$harvestForecast['summary']['total'] ?> <?= htmlspecialchars(__('batches')) ?>
        </span>
    </div>

    <div class="live-sensor-item <?= $forecastStatus ?>">
        <span><?= htmlspecialchars(__('forecast_overview')) ?></span>
        <small>
            <?= htmlspecialchars(__('today')) ?>: <?= (int)$harvestForecast['summary']['today'] ?> |
            <?= htmlspecialchars(__('overdue')) ?>: <?= (int)$harvestForecast['summary']['overdue'] ?> |
            <?= htmlspecialchars(__('this_week')) ?>: <?= (int)$harvestForecast['summary']['this_week'] ?> |
            <?= htmlspecialchars(__('trays')) ?>: <?= (int)$harvestForecast['summary']['total_trays'] ?>
        </small>
    </div>

    <?php if (count($harvestForecast['items']) === 0): ?>
        <div class="live-sensor-item ok">
            <span><?= htmlspecialchars(__('no_harvests_planned')) ?></span>
            <small><?= htmlspecialchars(__('no_active_or_planned_batches_14_days')) ?></small>
        </div>
    <?php endif; ?>

    <?php foreach ($harvestForecast['items'] as $item): ?>
        <div class="live-sensor-item <?= htmlspecialchars($item['priority']) ?>">
            <span><?= htmlspecialchars($item['crop']) ?> — <?= htmlspecialchars(__('batch')) ?> #<?= (int)$item['batch_id'] ?></span>
            <small>
                <?= htmlspecialchars(__('harvest_date')) ?>: <?= htmlspecialchars($item['harvest_date']) ?> |
                <?= htmlspecialchars(__('trays')) ?>: <?= (int)$item['tray_count'] ?> |
                <?= htmlspecialchars(__('status')) ?>: <?= htmlspecialchars((string)$item['status']) ?><br>
                <?= htmlspecialchars($item['label']) ?>
            </small>
        </div>
    <?php endforeach; ?>
</div>