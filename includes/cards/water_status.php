<?php
require_once __DIR__ . '/../water_engine.php';

$apiPrefix = $navPrefix ?? '';
$waterState = getWaterState($db);

$statusClass = $waterState['relay_output'] ? 'alarm' : 'ok';
$statusText = $waterState['relay_output'] ? 'Pomp AAN nodig' : 'Waterbeheer standby';
?>

<div class="climate-card water-card">
    <div class="live-sensor-header">
        <div>
            <h3>Waterbeheer Engine</h3>
            <p>Laatste analyse: <?= htmlspecialchars($waterState['timestamp'] ?? 'Geen data') ?></p>
        </div>

        <span class="sensor-status-badge <?= $statusClass ?>">
            <?= htmlspecialchars($statusText) ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <div class="live-sensor-item ok">
            <strong>Bodemvocht</strong>
            <span>Niet gekoppeld</span>
            <small>Sensor voorbereid</small>
        </div>

        <div class="live-sensor-item ok">
            <strong>Waterreservoir</strong>
            <span>Niet gekoppeld</span>
            <small>Niveausensor voorbereid</small>
        </div>

        <div class="live-sensor-item <?= $waterState['relay_output'] ? 'alarm' : 'ok' ?>">
            <strong>Pomprelais</strong>
            <span><?= $waterState['relay_output'] ? 'AAN' : 'UIT' ?></span>
            <small><?= htmlspecialchars($waterState['reason']) ?></small>
        </div>
    </div>
</div>

<script>
async function updateWaterCard() {
    try {
        const response = await fetch('<?= $apiPrefix ?>api/water.php');
        const data = await response.json();

        const card = document.querySelector('.water-card');
        if (!card) return;

        const badge = card.querySelector('.sensor-status-badge');
        badge.textContent = data.relay_output ? 'Pomp AAN nodig' : 'Waterbeheer standby';
        badge.className = 'sensor-status-badge ' + (data.relay_output ? 'alarm' : 'ok');

    } catch (error) {
        console.error(error);
    }
}

setInterval(updateWaterCard, 5000);
</script>
