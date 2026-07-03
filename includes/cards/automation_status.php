<?php
require_once __DIR__ . '/../gpio_controller.php';

$apiPrefix = $navPrefix ?? '';
$automation = applyAutomation($db);

$outputs = $automation['decisions'] ?? [];
$context = $automation['context'] ?? [];

$activeOutputs = array_filter($outputs);
$automationClass = count($activeOutputs) > 0 ? 'alarm' : 'ok';
$automationText = count($activeOutputs) > 0 ? 'Acties actief' : 'Geen acties nodig';

$lightingReason = $context['lighting']['reason'] ?? 'Geen lichtdata';
$climateReason = $context['climate']['label'] ?? 'Geen klimaatdata';
$waterReason = $context['water']['reason'] ?? 'Geen waterdata';
?>

<div class="climate-card automation-card">
    <div class="live-sensor-header">
        <div>
            <h3>Automation Controller</h3>
            <p>Modus: <?= htmlspecialchars($automation['mode'] ?? 'onbekend') ?> | Laatste controle: <?= htmlspecialchars($automation['timestamp'] ?? '-') ?></p>
        </div>

        <span class="sensor-status-badge <?= $automationClass ?>">
            <?= htmlspecialchars($automationText) ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <div class="live-sensor-item <?= !empty($outputs['grow_light']) ? 'alarm' : 'ok' ?>">
            <strong>Grow light</strong>
            <span><?= !empty($outputs['grow_light']) ? 'AAN' : 'UIT' ?></span>
            <small><?= htmlspecialchars($lightingReason) ?></small>
        </div>

        <div class="live-sensor-item <?= (!empty($outputs['fan']) || !empty($outputs['humidifier']) || !empty($outputs['heater']) || !empty($outputs['cooler'])) ? 'alarm' : 'ok' ?>">
            <strong>Klimaat hardware</strong>
            <span><?= (!empty($outputs['fan']) || !empty($outputs['humidifier']) || !empty($outputs['heater']) || !empty($outputs['cooler'])) ? 'ACTIEF' : 'UIT' ?></span>
            <small><?= htmlspecialchars($climateReason) ?></small>
        </div>

        <div class="live-sensor-item <?= !empty($outputs['water_pump']) ? 'alarm' : 'ok' ?>">
            <strong>Waterpomp</strong>
            <span><?= !empty($outputs['water_pump']) ? 'AAN' : 'UIT' ?></span>
            <small><?= htmlspecialchars($waterReason) ?></small>
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

        const outputs = data.decisions || {};
        const active = Object.values(outputs).filter(Boolean).length;
        const badge = card.querySelector('.sensor-status-badge');

        badge.textContent = active > 0 ? 'Acties actief' : 'Geen acties nodig';
        badge.className = 'sensor-status-badge ' + (active > 0 ? 'alarm' : 'ok');

    } catch (error) {
        console.error(error);
    }
}

setInterval(updateAutomationCard, 5000);
</script>
