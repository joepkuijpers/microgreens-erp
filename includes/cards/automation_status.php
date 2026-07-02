<?php
require_once __DIR__ . '/../gpio_controller.php';

$apiPrefix = $navPrefix ?? '';
$automation = applyAutomation($db);

$activeOutputs = array_filter($automation['outputs']);
$automationClass = count($activeOutputs) > 0 ? 'alarm' : 'ok';
$automationText = count($activeOutputs) > 0 ? 'Acties actief' : 'Geen acties nodig';
?>

<div class="climate-card automation-card">
    <div class="live-sensor-header">
        <div>
            <h3>Automation Controller</h3>
            <p>Modus: <?= htmlspecialchars($automation['mode']) ?> | Laatste controle: <?= htmlspecialchars($automation['timestamp']) ?></p>
        </div>

        <span class="sensor-status-badge <?= $automationClass ?>">
            <?= htmlspecialchars($automationText) ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <div class="live-sensor-item <?= $automation['outputs']['grow_light'] ? 'alarm' : 'ok' ?>">
            <strong>Grow light</strong>
            <span><?= $automation['outputs']['grow_light'] ? 'AAN' : 'UIT' ?></span>
            <small><?= htmlspecialchars($automation['sources']['lighting']) ?></small>
        </div>

        <div class="live-sensor-item <?= $automation['outputs']['fan'] || $automation['outputs']['humidifier'] ? 'alarm' : 'ok' ?>">
            <strong>Klimaat hardware</strong>
            <span><?= ($automation['outputs']['fan'] || $automation['outputs']['humidifier'] || $automation['outputs']['heater'] || $automation['outputs']['cooler']) ? 'ACTIEF' : 'UIT' ?></span>
            <small><?= htmlspecialchars($automation['sources']['climate']) ?></small>
        </div>

        <div class="live-sensor-item <?= $automation['outputs']['water_pump'] ? 'alarm' : 'ok' ?>">
            <strong>Waterpomp</strong>
            <span><?= $automation['outputs']['water_pump'] ? 'AAN' : 'UIT' ?></span>
            <small><?= htmlspecialchars($automation['sources']['water']) ?></small>
        </div>
    </div>
</div>

<script>
async function updateAutomationCard() {
    try {
        const response = await fetch('<?= $apiPrefix ?>api/automation.php');
        const data = await response.json();

        const card = document.querySelector('.automation-card');
        if (!card) return;

        const active = Object.values(data.outputs).filter(Boolean).length;
        const badge = card.querySelector('.sensor-status-badge');

        badge.textContent = active > 0 ? 'Acties actief' : 'Geen acties nodig';
        badge.className = 'sensor-status-badge ' + (active > 0 ? 'alarm' : 'ok');

    } catch (error) {
        console.error(error);
    }
}

setInterval(updateAutomationCard, 5000);
</script>
