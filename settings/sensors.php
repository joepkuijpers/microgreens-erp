<?php
$assetPrefix = '../';
$navPrefix = '../';

include '../db_connect.php';

$settings = $db->query("SELECT * FROM settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

$latestSensor = $db->query("
    SELECT *
    FROM sensor_log
    ORDER BY id DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

function sensorStatus($value, $min, $max)
{
    if ($value === null || $value === '') {
        return 'sensor-warning';
    }

    if ($value < $min || $value > $max) {
        return 'sensor-danger';
    }

    return 'sensor-ok';
}

function sensorStatusText($value, $min, $max)
{
    if ($value === null || $value === '') {
        return 'Geen data';
    }

    if ($value < $min || $value > $max) {
        return 'ALARM';
    }

    return 'OK';
}

$pageIcon = '🌡';
$pageTitle = 'Sensorinstellingen';
$pageDescription = 'Professioneel live overzicht van sensoren, grenswaarden en monitoring.';

$pageActions = [
    ['href' => 'index.php', 'icon' => '←', 'label' => 'Terug']
];

$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => '../dashboard.php'],
    ['label' => 'Instellingen', 'href' => 'index.php'],
    ['label' => 'Sensoren']
];

include '../includes/layout_start.php';
include '../includes/breadcrumbs.php';
include '../includes/page_header.php';
?>

<?php if (isset($_GET['saved'])): ?>
<div class="card">
    <strong>✅ Sensorinstellingen opgeslagen.</strong>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="card">
    <strong>❌ Ongeldige invoer. Controleer minimum- en maximumwaarden.</strong>
</div>
<?php endif; ?>

<div class="grid">

    <div class="tile">
        <h2>💡 Lux</h2>
        <p id="live-light" class="<?= sensorStatus($latestSensor['light'] ?? null, $settings['light_min'], $settings['light_max']) ?>">
            <?= htmlspecialchars($latestSensor['light'] ?? '-') ?> lux
        </p>
        <strong id="status-light">
            <?= sensorStatusText($latestSensor['light'] ?? null, $settings['light_min'], $settings['light_max']) ?>
        </strong>
    </div>

    <div class="tile">
        <h2>🌡 Temperatuur</h2>
        <p id="live-temperature" class="<?= sensorStatus($latestSensor['temperature'] ?? null, $settings['temp_min'], $settings['temp_max']) ?>">
            <?= htmlspecialchars($latestSensor['temperature'] ?? '-') ?> °C
        </p>
        <strong id="status-temperature">
            <?= sensorStatusText($latestSensor['temperature'] ?? null, $settings['temp_min'], $settings['temp_max']) ?>
        </strong>
    </div>

    <div class="tile">
        <h2>💧 Luchtvochtigheid</h2>
        <p id="live-humidity" class="<?= sensorStatus($latestSensor['humidity'] ?? null, $settings['humidity_min'], $settings['humidity_max']) ?>">
            <?= htmlspecialchars($latestSensor['humidity'] ?? '-') ?> %
        </p>
        <strong id="status-humidity">
            <?= sensorStatusText($latestSensor['humidity'] ?? null, $settings['humidity_min'], $settings['humidity_max']) ?>
        </strong>
    </div>

    <div class="tile">
        <h2>🕒 Laatste meting</h2>
        <p id="live-timestamp" style="font-size:18px;">
            <?= htmlspecialchars($latestSensor['timestamp'] ?? 'Geen data') ?>
        </p>
        <strong id="live-age">Live monitoring actief</strong>
    </div>

</div>

<div class="card">
    <h2>Sensorgrenzen</h2>

    <form method="post" action="save_sensors.php">
        <table>
            <tr>
                <th>Instelling</th>
                <th>Waarde</th>
            </tr>

            <tr>
                <td>Minimum lux</td>
                <td><input id="light_min" type="number" name="light_min" value="<?= htmlspecialchars($settings['light_min']) ?>" required></td>
            </tr>

            <tr>
                <td>Maximum lux</td>
                <td><input id="light_max" type="number" name="light_max" value="<?= htmlspecialchars($settings['light_max']) ?>" required></td>
            </tr>

            <tr>
                <td>Minimum temperatuur</td>
                <td><input id="temp_min" type="number" step="0.1" name="temp_min" value="<?= htmlspecialchars($settings['temp_min']) ?>" required></td>
            </tr>

            <tr>
                <td>Maximum temperatuur</td>
                <td><input id="temp_max" type="number" step="0.1" name="temp_max" value="<?= htmlspecialchars($settings['temp_max']) ?>" required></td>
            </tr>

            <tr>
                <td>Minimum luchtvochtigheid</td>
                <td><input id="humidity_min" type="number" step="0.1" name="humidity_min" value="<?= htmlspecialchars($settings['humidity_min']) ?>" required></td>
            </tr>

            <tr>
                <td>Maximum luchtvochtigheid</td>
                <td><input id="humidity_max" type="number" step="0.1" name="humidity_max" value="<?= htmlspecialchars($settings['humidity_max']) ?>" required></td>
            </tr>

            <tr>
                <td>Refresh interval</td>
                <td><input id="refresh_seconds" type="number" name="refresh_seconds" value="<?= htmlspecialchars($settings['refresh_seconds']) ?>" min="1" required></td>
            </tr>
        </table>

        <br>

        <button class="button" type="submit">💾 Sensorinstellingen opslaan</button>
    </form>
</div>

<script>
function getStatus(value, min, max) {
    if (value === null || value === undefined || value === "" || isNaN(value)) {
        return {
            className: "sensor-warning",
            text: "Geen data"
        };
    }

    value = Number(value);
    min = Number(min);
    max = Number(max);

    if (value < min || value > max) {
        return {
            className: "sensor-danger",
            text: "ALARM"
        };
    }

    return {
        className: "sensor-ok",
        text: "OK"
    };
}

function updateSensorCard(valueId, statusId, value, suffix, min, max) {
    const valueElement = document.getElementById(valueId);
    const statusElement = document.getElementById(statusId);

    const status = getStatus(value, min, max);

    valueElement.textContent = value + " " + suffix;
    valueElement.className = status.className;
    statusElement.textContent = status.text;
}

async function refreshSensors() {
    try {
        const response = await fetch("../api/sensors.php");
        const data = await response.json();

        updateSensorCard(
            "live-light",
            "status-light",
            data.light,
            "lux",
            document.getElementById("light_min").value,
            document.getElementById("light_max").value
        );

        updateSensorCard(
            "live-temperature",
            "status-temperature",
            data.temperature,
            "°C",
            document.getElementById("temp_min").value,
            document.getElementById("temp_max").value
        );

        updateSensorCard(
            "live-humidity",
            "status-humidity",
            data.humidity,
            "%",
            document.getElementById("humidity_min").value,
            document.getElementById("humidity_max").value
        );

        document.getElementById("live-timestamp").textContent = data.timestamp ?? "Geen data";
        document.getElementById("live-age").textContent = "Automatisch bijgewerkt";

    } catch (error) {
        document.getElementById("live-age").textContent = "API-fout";
        console.error(error);
    }
}

setInterval(refreshSensors, 5000);
refreshSensors();
</script>

<?php include '../includes/layout_end.php'; ?>