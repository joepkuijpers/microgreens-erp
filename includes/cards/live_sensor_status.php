<?php
require_once __DIR__ . '/../sensor_service.php';
$apiPrefix = $navPrefix ?? '';

$liveSensorData = get_live_sensor_data($db);

$statusClass = $liveSensorData['overall_status'] === 'ok' ? 'ok' : 'alarm';
$statusText = $liveSensorData['overall_label'];

$reading = $liveSensorData['reading'];
$checks = $liveSensorData['checks'];
?>

<div class="live-sensor-card">
    <div class="live-sensor-header">
        <div>
            <h3>Live sensorstatus</h3>
            <p>Laatste meting: <?= htmlspecialchars($liveSensorData['timestamp'] ?? 'Geen data') ?></p>
        </div>

        <span class="sensor-status-badge <?= $statusClass ?>">
            <?= htmlspecialchars($statusText) ?>
        </span>
    </div>

    <div class="live-sensor-grid">
        <div class="live-sensor-item <?= $checks['temperature']['status'] ?>">
            <strong>Temperatuur</strong>
            <span><?= htmlspecialchars((string)($reading['temperature'] ?? '-')) ?> °C</span>
            <small><?= htmlspecialchars($checks['temperature']['label']) ?></small>
        </div>

        <div class="live-sensor-item <?= $checks['humidity']['status'] ?>">
            <strong>Luchtvochtigheid</strong>
            <span><?= htmlspecialchars((string)($reading['humidity'] ?? '-')) ?> %</span>
            <small><?= htmlspecialchars($checks['humidity']['label']) ?></small>
        </div>

        <div class="live-sensor-item <?= $checks['light']['status'] ?>">
            <strong>Licht</strong>
            <span><?= htmlspecialchars((string)($reading['light'] ?? '-')) ?> lux</span>
            <small><?= htmlspecialchars($checks['light']['label']) ?></small>
        </div>
    </div>
</div>
<script>
async function updateLiveSensorCard() {
    try {
       const response = await fetch('<?= $apiPrefix ?>api/sensor_health.php');        const data = await response.json();

        const badge = document.querySelector('.sensor-status-badge');

        if (!badge) return;

        badge.textContent = data.overall_label;
        badge.classList.remove('ok', 'alarm');
        badge.classList.add(data.overall_status);

        const items = document.querySelectorAll('.live-sensor-item');

        const values = [
            data.reading.temperature + ' °C',
            data.reading.humidity + ' %',
            data.reading.light + ' lux'
        ];

        const labels = [
            data.checks.temperature.label,
            data.checks.humidity.label,
            data.checks.light.label
        ];

        const states = [
            data.checks.temperature.status,
            data.checks.humidity.status,
            data.checks.light.status
        ];

        items.forEach((item, index) => {
            item.classList.remove('ok', 'alarm');
            item.classList.add(states[index]);

            item.querySelector('span').textContent = values[index];
            item.querySelector('small').textContent = labels[index];
        });

        const timestamp = document.querySelector('.live-sensor-header p');
        timestamp.textContent = 'Laatste meting: ' + data.timestamp;

    } catch (e) {
        console.error(e);
    }
}

setInterval(updateLiveSensorCard, 5000);
</script>