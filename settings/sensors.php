<?php
$assetPrefix = '../';
$navPrefix = '../';

include '../db_connect.php';

$settings = $db->query("SELECT * FROM settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

$pageIcon = '🌡';
$pageTitle = 'Sensorinstellingen';
$pageDescription = 'Beheer alle sensorgrenzen en monitoringinstellingen.';

$pageActions = [
    [
        'href' => 'index.php',
        'icon' => '←',
        'label' => 'Terug'
    ]
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

<div class="card">

<h2>Sensorinstellingen</h2>

<table>

<tr>
    <th>Instelling</th>
    <th>Waarde</th>
</tr>

<tr>
    <td>Minimum Lux</td>
    <td><?= htmlspecialchars($settings['light_min']) ?></td>
</tr>

<tr>
    <td>Maximum Lux</td>
    <td><?= htmlspecialchars($settings['light_max']) ?></td>
</tr>

<tr>
    <td>Minimum Temperatuur</td>
    <td><?= htmlspecialchars($settings['temp_min']) ?> °C</td>
</tr>

<tr>
    <td>Maximum Temperatuur</td>
    <td><?= htmlspecialchars($settings['temp_max']) ?> °C</td>
</tr>

<tr>
    <td>Minimum Luchtvochtigheid</td>
    <td><?= htmlspecialchars($settings['humidity_min']) ?> %</td>
</tr>

<tr>
    <td>Maximum Luchtvochtigheid</td>
    <td><?= htmlspecialchars($settings['humidity_max']) ?> %</td>
</tr>

<tr>
    <td>Refresh interval</td>
    <td><?= htmlspecialchars($settings['refresh_seconds']) ?> seconden</td>
</tr>

</table>

</div>

<?php include '../includes/layout_end.php'; ?>
