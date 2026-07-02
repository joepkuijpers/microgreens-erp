<?php
require_once __DIR__ . '/../climate_engine.php';

$apiPrefix = $navPrefix ?? '';
$climateState = getClimateState($db);
?>

<div class="climate-card">
    <div class="live-sensor-header">
        <div>
            <h3>Klimaat Engine</h3>
            <p>Laatste analyse: <?= htmlspecialchars($climateState['timestamp'] ?? 'Geen data') ?></p>
        </div>

        <span class="sensor-status-badge <?= $climateState['overall'] === 'ok' ? 'ok' : 'alarm' ?>">
            <?= htmlspecialchars($climateState['label']) ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <div class="live-sensor-item <?= $climateState['temperature']['status'] === 'ok' ? 'ok' : 'alarm' ?>">
            <strong>Temperatuurregeling</strong>
            <span><?= $climateState['temperature']['heater'] ? 'Verwarming AAN' : ($climateState['temperature']['cooler'] ? 'Koeling AAN' : 'Stabiel') ?></span>
            <small><?= htmlspecialchars($climateState['temperature']['label']) ?></small>
        </div>

        <div class="live-sensor-item <?= $climateState['humidity']['status'] === 'ok' ? 'ok' : 'alarm' ?>">
            <strong>Luchtregeling</strong>
            <span><?= $climateState['humidity']['humidifier'] ? 'Bevochtigen AAN' : ($climateState['humidity']['ventilation'] ? 'Ventilatie AAN' : 'Stabiel') ?></span>
            <small><?= htmlspecialchars($climateState['humidity']['label']) ?></small>
        </div>

        <div class="live-sensor-item <?= $climateState['light']['status'] === 'ok' ? 'ok' : 'alarm' ?>">
            <strong>Lichtregeling</strong>
            <span><?= $climateState['light']['grow_light'] ? 'Grow light AAN' : 'Stabiel' ?></span>
            <small><?= htmlspecialchars($climateState['light']['label']) ?></small>
        </div>
    </div>
</div>

<script>
async function updateClimateCard() {
    try {
        const response = await fetch('<?= $apiPrefix ?>api/climate.php');
        const data = await response.json();

        const card = document.querySelector('.climate-card');
        if (!card) return;

        card.querySelector('.sensor-status-badge').textContent = data.label;
        card.querySelector('.sensor-status-badge').className =
            'sensor-status-badge ' + (data.overall === 'ok' ? 'ok' : 'alarm');

    } catch (error) {
        console.error(error);
    }
}

setInterval(updateClimateCard, 5000);
</script>
