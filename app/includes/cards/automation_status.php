<?php
require_once __DIR__ . '/../gpio_controller.php';

$apiPrefix = $navPrefix ?? '';
$automation = applyAutomation($db);

$outputs = $automation['decisions'] ?? [];
$context = $automation['context'] ?? [];

$activeOutputs = array_filter($outputs);
$automationClass = count($activeOutputs) > 0 ? 'alarm' : 'ok';
$automationText = count($activeOutputs) > 0 ? __('actions_active') : __('no_actions_needed');

$lightingReason = $context['lighting']['reason'] ?? __('no_light_data');
$climateReason = $context['climate']['label'] ?? __('no_climate_data');
$waterReason = $context['water']['reason'] ?? __('no_water_data');

$climateActive = !empty($outputs['fan']) || !empty($outputs['humidifier']) || !empty($outputs['heater']) || !empty($outputs['cooler']);
?>

<div class="climate-card automation-card">
    <div class="live-sensor-header">
        <div>
            <h3><?= htmlspecialchars(__('automation_controller')) ?></h3>
            <p><?= htmlspecialchars(__('mode')) ?>: <?= htmlspecialchars($automation['mode'] ?? __('unknown')) ?> | <?= htmlspecialchars(__('last_check')) ?>: <?= htmlspecialchars($automation['timestamp'] ?? '-') ?></p>
        </div>

        <span class="sensor-status-badge <?= $automationClass ?>">
            <?= htmlspecialchars($automationText) ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <div class="live-sensor-item <?= !empty($outputs['grow_light']) ? 'alarm' : 'ok' ?>">
            <strong><?= htmlspecialchars(__('grow_light')) ?></strong>
            <span><?= !empty($outputs['grow_light']) ? htmlspecialchars(__('on')) : htmlspecialchars(__('off')) ?></span>
            <small><?= htmlspecialchars($lightingReason) ?></small>
        </div>

        <div class="live-sensor-item <?= $climateActive ? 'alarm' : 'ok' ?>">
            <strong><?= htmlspecialchars(__('climate_hardware')) ?></strong>
            <span><?= $climateActive ? htmlspecialchars(__('active')) : htmlspecialchars(__('off')) ?></span>
            <small><?= htmlspecialchars($climateReason) ?></small>
        </div>

        <div class="live-sensor-item <?= !empty($outputs['water_pump']) ? 'alarm' : 'ok' ?>">
            <strong><?= htmlspecialchars(__('water_pump')) ?></strong>
            <span><?= !empty($outputs['water_pump']) ? htmlspecialchars(__('on')) : htmlspecialchars(__('off')) ?></span>
            <small><?= htmlspecialchars($waterReason) ?></small>
        </div>
    </div>
</div>

<script>
const automationActionsActive = <?= json_encode(__('actions_active')) ?>;
const automationNoActionsNeeded = <?= json_encode(__('no_actions_needed')) ?>;

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