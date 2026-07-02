<?php
$assetPrefix = '../';
$navPrefix = '../';

include '../db_connect.php';

$settings = $db->query("SELECT * FROM settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

$pageIcon = '🌡';
$pageTitle = 'Sensorinstellingen';
$pageDescription = 'Configureer sensorgrenzen en monitoring.';

$pageActions = [
    [
        'href' => 'index.php',
        'icon' => '←',
        'label' => 'Terug'
    ]
];

$breadcrumbs = [
    ['label'=>'Dashboard','href'=>'../dashboard.php'],
    ['label'=>'Instellingen','href'=>'index.php'],
    ['label'=>'Sensoren']
];

include '../includes/layout_start.php';
include '../includes/breadcrumbs.php';
include '../includes/page_header.php';
?>

<?php if(isset($_GET['saved'])): ?>

<div class="card">
    <strong>✅ Sensorinstellingen opgeslagen.</strong>
</div>

<?php endif; ?>

<?php if(isset($_GET['error'])): ?>

<div class="card">
    <strong>❌ Ongeldige invoer.</strong>
</div>

<?php endif; ?>

<form method="post" action="save_sensors.php">

<div class="card">

<h2>Sensorgrenzen</h2>

<table>

<tr>
<td>Minimum Lux</td>
<td><input type="number" name="light_min" value="<?= htmlspecialchars($settings['light_min']) ?>"></td>
</tr>

<tr>
<td>Maximum Lux</td>
<td><input type="number" name="light_max" value="<?= htmlspecialchars($settings['light_max']) ?>"></td>
</tr>

<tr>
<td>Minimum Temperatuur</td>
<td><input type="number" step="0.1" name="temp_min" value="<?= htmlspecialchars($settings['temp_min']) ?>"></td>
</tr>

<tr>
<td>Maximum Temperatuur</td>
<td><input type="number" step="0.1" name="temp_max" value="<?= htmlspecialchars($settings['temp_max']) ?>"></td>
</tr>

<tr>
<td>Minimum Luchtvochtigheid</td>
<td><input type="number" step="0.1" name="humidity_min" value="<?= htmlspecialchars($settings['humidity_min']) ?>"></td>
</tr>

<tr>
<td>Maximum Luchtvochtigheid</td>
<td><input type="number" step="0.1" name="humidity_max" value="<?= htmlspecialchars($settings['humidity_max']) ?>"></td>
</tr>

<tr>
<td>Refresh interval</td>
<td><input type="number" name="refresh_seconds" value="<?= htmlspecialchars($settings['refresh_seconds']) ?>"></td>
</tr>

</table>

<br>

<button class="button" type="submit">
💾 Sensorinstellingen opslaan
</button>

</div>

</form>

<?php include '../includes/layout_end.php'; ?>
