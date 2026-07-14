<?php
require_once __DIR__ . '/../sensor_service.php';

$apiPrefix = $navPrefix ?? '';

$liveSensorData = getLiveSensorStatus($db);
$statusClass = $liveSensorData['overall_status'] === 'ok' ? 'ok' : 'alarm';
$statusText = $liveSensorData['overall_label'];

$reading = $liveSensorData['reading'];
$checks = $liveSensorData['checks'];
?>

<div class="live-sensor-card">
    <div class="live-sensor-header">
        <div>
            <h3><?= htmlspecialchars(__('live_sensor_status')) ?></h3>
            <p><?= htmlspecialchars(__('last_measurement')) ?>: <?= htmlspecialchars($liveSensorData['timestamp'] ?? __('no_data')) ?></p>
        </div>

        <span class="sensor-status-badge <?= htmlspecialchars($statusClass) ?>">
            <?= htmlspecialchars($statusText) ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <div class="live-sensor-item <?= htmlspecialchars($checks['temperature']['status']) ?>">
            <strong><?= htmlspecialchars(__('temperature')) ?></strong>
            <span><?= htmlspecialchars((string)($reading['temperature'] ?? '-')) ?> °C</span>
            <small><?= htmlspecialchars($checks['temperature']['label']) ?></small>
        </div>

        <div class="live-sensor-item <?= htmlspecialchars($checks['humidity']['status']) ?>">
            <strong><?= htmlspecialchars(__('humidity')) ?></strong>
            <span><?= htmlspecialchars((string)($reading['humidity'] ?? '-')) ?> %</span>
            <small><?= htmlspecialchars($checks['humidity']['label']) ?></small>
        </div>

        <div class="live-sensor-item <?= htmlspecialchars($checks['light']['status']) ?>">
            <strong><?= htmlspecialchars(__('light')) ?></strong>
            <span><?= htmlspecialchars((string)($reading['light'] ?? '-')) ?> lux</span>
            <small><?= htmlspecialchars($checks['light']['label']) ?></small>
        </div>
    </div>
</div>

<script>
const liveSensorLastMeasurement = <?= json_encode(__('last_measurement')) ?>;

async function updateLiveSensorCard() {
    try {
        const response = await fetch('<?= $apiPrefix ?>api/sensor_health.php');
        const data = await response.json();

        const badge = document.querySelector('.sensor-status-badge');
        if (!badge) return;

        badge.textContent = data.overall_label || '--';
        badge.classList.remove('ok', 'alarm');
        badge.classList.add(data.overall_status === 'ok' ? 'ok' : 'alarm');

        const items = document.querySelectorAll('.live-sensor-item');

        const reading = data.reading || {};
        const checks = data.checks || {};

        const values = [
            (reading.temperature ?? '-') + ' °C',
            (reading.humidity ?? '-') + ' %',
            (reading.light ?? '-') + ' lux'
        ];

        const labels = [
            checks.temperature?.label || '--',
            checks.humidity?.label || '--',
            checks.light?.label || '--'
        ];

        const statuses = [
            checks.temperature?.status || 'alarm',
            checks.humidity?.status || 'alarm',
            checks.light?.status || 'alarm'
        ];

        items.forEach((item, index) => {
            item.classList.remove('ok', 'alarm');
            item.classList.add(statuses[index]);

            const valueElement = item.querySelector('span');
            if (valueElement) {
                valueElement.textContent = values[index];
            }

            const labelElement = item.querySelector('small');
            if (labelElement) {
                labelElement.textContent = labels[index];
            }
        });

        const timestamp = document.querySelector('.live-sensor-header p');
        if (timestamp) {
            timestamp.textContent = liveSensorLastMeasurement + ': ' + (data.timestamp || '--');
        }

    } catch (error) {
        console.error('Live sensor update failed:', error);
    }
}

setInterval(updateLiveSensorCard, 5000);
</script>