<?php
require_once __DIR__ . '/../lighting_engine.php';

$apiPrefix = $navPrefix ?? '';
$lightingState = getLightingState($db);

$lightingStatusClass = $lightingState['relay_output'] ? 'alarm' : 'ok';
$lightingStatusText = $lightingState['relay_output'] ? __('lights_on_needed') : __('light_stable');
?>

<div class="climate-card lighting-card">
    <div class="live-sensor-header">
        <div>
            <h3>Lighting Engine</h3>
            <p><?= __('last_analysis') ?>: <?= htmlspecialchars($lightingState['timestamp'] ?? __('no_data')) ?></p>
        </div>

        <span class="sensor-status-badge <?= $lightingStatusClass ?>">
            <?= htmlspecialchars($lightingStatusText) ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <div class="live-sensor-item <?= $lightingState['light_level_ok'] ? 'ok' : 'alarm' ?>">
            <strong><?= __('light_level_label') ?></strong>
            <span><?= htmlspecialchars((string)$lightingState['current_lux']) ?> lux</span>
            <small><?= htmlspecialchars($lightingState['reason']) ?></small>
        </div>

        <div class="live-sensor-item <?= $lightingState['schedule_active'] ? 'ok' : 'alarm' ?>">
            <strong><?= __('photoperiod') ?></strong>
            <span><?= $lightingState['schedule_active'] ? __('active') : __('inactive') ?></span>
            <small><?= __('mode') ?>: <?= htmlspecialchars($lightingState['mode']) ?></small>
        </div>

        <div class="live-sensor-item <?= $lightingState['relay_output'] ? 'alarm' : 'ok' ?>">
            <strong><?= __('relay_output') ?></strong>
            <span><?= $lightingState['relay_output'] ? __('on') : __('off') ?></span>
            <small><?= __('prepared_for_gpio_control') ?></small>
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
        badge.textContent = data.relay_output ? '<?= __('lights_on_needed') ?>' : '<?= __('light_stable') ?>';
        badge.className = 'sensor-status-badge ' + (data.relay_output ? 'alarm' : 'ok');

    } catch (error) {
        console.error(error);
    }
}

setInterval(updateLightingCard, 5000);
</script>