<?php
require_once __DIR__ . '/../harvest_forecast_engine.php';

$harvestForecast = getHarvestForecast($db, 14);
$forecastStatus = ($harvestForecast['summary']['overdue'] > 0 || $harvestForecast['summary']['today'] > 0) ? 'alarm' : 'ok';
?>

<div class="dashboard-section">
    <h2>🌾 Harvest Forecast</h2>
</div>

<div class="live-sensor-card">
    <div class="live-sensor-header">
        <h3>🌾 Oogstverwachting 14 dagen</h3>
        <span class="sensor-status-badge <?= $forecastStatus ?>">
            <?= (int)$harvestForecast['summary']['total'] ?> batches
        </span>
    </div>

    <div class="live-sensor-item <?= $forecastStatus ?>">
        <span>Forecast overzicht</span>
        <small>
            Vandaag: <?= (int)$harvestForecast['summary']['today'] ?> |
            Te laat: <?= (int)$harvestForecast['summary']['overdue'] ?> |
            Deze week: <?= (int)$harvestForecast['summary']['this_week'] ?> |
            Trays: <?= (int)$harvestForecast['summary']['total_trays'] ?>
        </small>
    </div>

    <?php if (count($harvestForecast['items']) === 0): ?>
        <div class="live-sensor-item ok">
            <span>Geen oogsten gepland</span>
            <small>Geen actieve of geplande batches binnen 14 dagen.</small>
        </div>
    <?php endif; ?>

    <?php foreach ($harvestForecast['items'] as $item): ?>
        <div class="live-sensor-item <?= htmlspecialchars($item['priority']) ?>">
            <span><?= htmlspecialchars($item['crop']) ?> — Batch #<?= (int)$item['batch_id'] ?></span>
            <small>
                Oogstdatum: <?= htmlspecialchars($item['harvest_date']) ?> |
                Trays: <?= (int)$item['tray_count'] ?> |
                Status: <?= htmlspecialchars((string)$item['status']) ?><br>
                <?= htmlspecialchars($item['label']) ?>
            </small>
        </div>
    <?php endforeach; ?>
</div>
