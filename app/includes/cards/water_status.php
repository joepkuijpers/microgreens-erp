<?php
require_once __DIR__ . '/../water_engine.php';

$apiPrefix = $navPrefix ?? '';
$waterState = getWaterState($db);

$statusClass = $waterState['relay_output'] ? 'alarm' : 'ok';
$statusText = $waterState['relay_output'] ? __('pump_on_needed') : __('water_management_standby');
?>

<div class="climate-card water-card">
    <div class="live-sensor-header">
        <div>
            <h3><?= __('water_engine') ?></h3>
            <p><?= __('last_analysis') ?>: <?= htmlspecialchars($waterState['timestamp'] ?? __('no_data')) ?></p>
        </div>

        <span class="sensor-status-badge <?= $statusClass ?>">
            <?= htmlspecialchars($statusText) ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <div class="live-sensor-item ok">
            <strong><?= __('soil_moisture') ?></strong>
            <span><?= __('not_connected') ?></span>
            <small><?= __('sensor_prepared') ?></small>
        </div>

        <div class="live-sensor-item ok">
            <strong><?= __('water_reservoir') ?></strong>
            <span><?= __('not_connected') ?></span>
            <small><?= __('level_sensor_prepared') ?></small>
        </div>

        <div class="live-sensor-item <?= $waterState['relay_output'] ? 'alarm' : 'ok' ?>">
            <strong><?= __('pump_relay') ?></strong>
            <span><?= $waterState['relay_output'] ? __('on') : __('off') ?></span>
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
        badge.textContent = data.relay_output ? '<?= __('pump_on_needed') ?>' : '<?= __('water_management_standby') ?>';
        badge.className = 'sensor-status-badge ' + (data.relay_output ? 'alarm' : 'ok');

    } catch (error) {
        console.error(error);
    }
}

setInterval(updateWaterCard, 5000);
</script>