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
        return '⚠️ Geen data';
    }

    if ($value < $min || $value > $max) {
        return '🚨 ALARM';
    }

    return '✅ OK';
}

$pageIcon = '🌡';
$pageTitle = 'Sensorinstellingen';
$pageDescription = 'Configureer sensorgrenzen en monitoring voor de teeltruimte.';

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
        <h2>💡 Actuele lux</h2>
        <p class="<?= sensorStatus($latestSensor['light'] ?? null, $settings['light_min'], $settings['light_max']) ?>">
            <?= htmlspecialchars($latestSensor['light'] ?? '-') ?> lux
        </p>
        <p>
            <?= sensorStatusText($latestSensor['light'] ?? null, $settings['light_min'], $settings['light_max']) ?>
        </p>
    </div>

    <div class="tile">
        <h2>🌡 Actuele temperatuur</h2>
        <p class="<?= sensorStatus($latestSensor['temperature'] ?? null, $settings['temp_min'], $settings['temp_max']) ?>">
            <?= htmlspecialchars($latestSensor['temperature'] ?? '-') ?> °C
        </p>
        <p>
            <?= sensorStatusText($latestSensor['temperature'] ?? null, $settings['temp_min'], $settings['temp_max']) ?>
        </p>
    </div>

    <div class="tile">
        <h2>💧 Actuele luchtvochtigheid</h2>
        <p class="<?= sensorStatus($latestSensor['humidity'] ?? null, $settings['humidity_min'], $settings['humidity_max']) ?>">
            <?= htmlspecialchars($latestSensor['humidity'] ?? '-') ?> %
        </p>
        <p>
            <?= sensorStatusText($latestSensor['humidity'] ?? null, $settings['humidity_min'], $settings['humidity_max']) ?>
        </p>
    </div>

    <div class="tile">
        <h2>🕒 Laatste meting</h2>
        <p style="font-size:18px;">
            <?= htmlspecialchars($latestSensor['timestamp'] ?? 'Geen data') ?>
        </p>
    </div>

</div>

<form method="post" action="save_sensors.php">
    <div class="card">
        <h2>Sensorgrenzen</h2>

        <table>
            <tr>
                <td>Minimum lux</td>
                <td><input type="number" name="light_min" value="<?= htmlspecialchars($settings['light_min']) ?>" required></td>
            </tr>
            <tr>
                <td>Maximum lux</td>
                <td><input type="number" name="light_max" value="<?= htmlspecialchars($settings['light_max']) ?>" required></td>
            </tr>
            <tr>
                <td>Minimum temperatuur</td>
                <td><input type="number" step="0.1" name="temp_min" value="<?= htmlspecialchars($settings['temp_min']) ?>" required></td>
            </tr>
            <tr>
                <td>Maximum temperatuur</td>
                <td><input type="number" step="0.1" name="temp_max" value="<?= htmlspecialchars($settings['temp_max']) ?>" required></td>
            </tr>
            <tr>
                <td>Minimum luchtvochtigheid</td>
                <td><input type="number" step="0.1" name="humidity_min" value="<?= htmlspecialchars($settings['humidity_min']) ?>" required></td>
            </tr>
            <tr>
                <td>Maximum luchtvochtigheid</td>
                <td><input type="number" step="0.1" name="humidity_max" value="<?= htmlspecialchars($settings['humidity_max']) ?>" required></td>
            </tr>
            <tr>
                <td>Refresh interval</td>
                <td><input type="number" name="refresh_seconds" value="<?= htmlspecialchars($settings['refresh_seconds']) ?>" min="1" required></td>
            </tr>
        </table>

        <br>

        <button class="button" type="submit">💾 Sensorinstellingen opslaan</button>
    </div>
</form>

<?php include '../includes/layout_end.php'; ?>