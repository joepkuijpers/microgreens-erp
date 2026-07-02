<?php
$assetPrefix = '../';
$navPrefix = '../';

include '../db_connect.php';
require_once __DIR__ . '/../includes/sensor_service.php';

$settings = $db->query("SELECT * FROM settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

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

<?php include __DIR__ . '/../includes/cards/live_sensor_status.php'; ?>

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
    </form>
</div>

<?php include '../includes/layout_end.php'; ?>