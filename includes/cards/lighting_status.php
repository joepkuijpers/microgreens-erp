<?php
require_once __DIR__ . '/../lighting_engine.php';

$apiPrefix = $navPrefix ?? '';
$lightingState = getLightingState($db);

$lightingStatusClass = $lightingState['relay_output'] ? 'alarm' : 'ok';
$lightingStatusText = $lightingState['relay_output'] ? 'Lampen AAN nodig' : 'Licht stabiel';
?>

<div class="climate-card lighting-card">
    <div class="live-sensor-header">
        <div>
            <h3>Lighting Engine</h3>
            <p>Laatste analyse: <?= htmlspecialchars($lightingState['timestamp'] ?? 'Geen data') ?></p>
        </div>

        <span class="sensor-status-badge <?= $lightingStatusClass ?>">
            <?= htmlspecialchars($lightingStatusText) ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <div class="live-sensor-item <?= $lightingState['light_level_ok'] ? 'ok' : 'alarm' ?>">
            <strong>Lichtniveau</strong>
            <span><?= htmlspecialchars((string)$lightingState['current_lux']) ?> lux</span>
            <small><?= htmlspecialchars($lightingState['reason']) ?></small>
        </div>

        <div class="live-sensor-item <?= $lightingState['schedule_active'] ? 'ok' : 'alarm' ?>">
            <strong>Fotoperiode</strong>
            <span><?= $lightingState['schedule_active'] ? 'Actief' : 'Inactief' ?></span>
            <small>Modus: <?= htmlspecialchars($lightingState['mode']) ?></small>
        </div>

        <div class="live-sensor-item <?= $lightingState['relay_output'] ? 'alarm' : 'ok' ?>">
            <strong>Relaisuitgang</strong>
            <span><?= $lightingState['relay_output'] ? 'AAN' : 'UIT' ?></span>
            <small>Voorbereid voor GPIO-aansturing</small>
        </div>
    </div>
</div>

<script>
async function updateLightingCard() {
    try {
        const response = await fetch('<?= $apiPrefix ?>api/lighting.php');
        const data = await response.json();

        const card = document.querySelector('.lighting-card');
        if (!card) return;

        const badge = card.querySelector('.sensor-status-badge');
        badge.textContent = data.relay_output ? 'Lampen AAN nodig' : 'Licht stabiel';
        badge.className = 'sensor-status-badge ' + (data.relay_output ? 'alarm' : 'ok');

    } catch (error) {
        console.error(error);
    }
}

setInterval(updateLightingCard, 5000);
</script>
