<?php
require_once __DIR__ . '/../gpio_controller.php';

$apiPrefix = $navPrefix ?? '';
$automation = applyAutomation($db);

$outputs = $automation['decisions'] ?? [];
$context = $automation['context'] ?? [];

$activeOutputs = array_filter($outputs);
$automationClass = count($activeOutputs) > 0 ? 'alarm' : 'ok';
$automationText = count($activeOutputs) > 0 ? t('actions_active') : t('no_actions_needed');

$lightingReason = $context['lighting']['reason'] ?? t('no_light_data');
$climateReason = $context['climate']['label'] ?? t('no_climate_data');
$waterReason = $context['water']['reason'] ?? t('no_water_data');

$climateActive = !empty($outputs['fan']) || !empty($outputs['humidifier']) || !empty($outputs['heater']) || !empty($outputs['cooler']);
?>

<div class="climate-card automation-card">
    <div class="live-sensor-header">
        <div>
            <h3><?= htmlspecialchars(t('automation_controller')) ?></h3>
            <p><?= htmlspecialchars(t('mode')) ?>: <?= htmlspecialchars($automation['mode'] ?? t('unknown')) ?> | <?= htmlspecialchars(t('last_check')) ?>: <?= htmlspecialchars($automation['timestamp'] ?? '-') ?></p>
        </div>

        <span class="sensor-status-badge <?= $automationClass ?>">
            <?= htmlspecialchars($automationText) ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <div class="live-sensor-item <?= !empty($outputs['grow_light']) ? 'alarm' : 'ok' ?>">
            <strong><?= htmlspecialchars(t('grow_light')) ?></strong>
            <span><?= !empty($outputs['grow_light']) ? htmlspecialchars(t('on')) : htmlspecialchars(t('off')) ?></span>
            <small><?= htmlspecialchars($lightingReason) ?></small>
        </div>

        <div class="live-sensor-item <?= $climateActive ? 'alarm' : 'ok' ?>">
            <strong><?= htmlspecialchars(t('climate_hardware')) ?></strong>
            <span><?= $climateActive ? htmlspecialchars(t('active')) : htmlspecialchars(t('off')) ?></span>
            <small><?= htmlspecialchars($climateReason) ?></small>
        </div>

        <div class="live-sensor-item <?= !empty($outputs['water_pump']) ? 'alarm' : 'ok' ?>">
            <strong><?= htmlspecialchars(t('water_pump')) ?></strong>
            <span><?= !empty($outputs['water_pump']) ? htmlspecialchars(t('on')) : htmlspecialchars(t('off')) ?></span>
            <small><?= htmlspecialchars($waterReason) ?></small>
        </div>
    </div>
</div>

<script>
const automationActionsActive = <?= json_encode(t('actions_active')) ?>;
const automationNoActionsNeeded = <?= json_encode(t('no_actions_needed')) ?>;

async function updateAutomationCard() {
    try {
        const response = await fetch('<?= $apiPrefix ?>api/automation.php');
        const data = await response.json();

        const card = document.querySelector('.automation-card');
        if (!card) return;

        const outputs = data.decisions || {};
        const active = Object.values(outputs).filter(Boolean).length;
        const badge = card.querySelector('.sensor-status-badge');

        badge.textContent = active > 0 ? automationActionsActive : automationNoActionsNeeded;
        badge.className = 'sensor-status-badge ' + (active > 0 ? 'alarm' : 'ok');

    } catch (error) {
        console.error(error);
    }
}

setInterval(updateAutomationCard, 5000);
</script>