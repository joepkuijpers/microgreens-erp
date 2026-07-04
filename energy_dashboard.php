<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';
include 'includes/energy_engine.php';

$totalPower = 0;
$totalDailyKwh = 0;
$activeDevices = 0;

$rows = $db->query("
    SELECT
        wattage,
        hours_per_day,
        is_active
    FROM equipment
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    if (!$row['is_active']) {
        continue;
    }

    $activeDevices++;
    $totalPower += (float) $row['wattage'];

    $totalDailyKwh += calculateDailyKwh(
        (float) $row['wattage'],
        (float) $row['hours_per_day']
    );
}

$monthlyKwh = $totalDailyKwh * 30;
$settings = $db->query("
    SELECT electricity_price_per_kwh
    FROM settings
    WHERE id = 1
")->fetch(PDO::FETCH_ASSOC);



$electricityPrice = (float) ($settings['electricity_price_per_kwh'] ?? 0);
$dailyCost = $totalDailyKwh * $electricityPrice;
$monthlyCost = $monthlyKwh * $electricityPrice;
?>

<td><?= htmlspecialchars(number_format($monthlyKwh, 1, ',', '.')) ?> kWh</td>
</tr>

<h1>⚡ Energie Dashboard</h1>

<table>
    <tr>
        <th>Totaal actief vermogen</th>
        <td><?= htmlspecialchars(number_format($totalPower, 1, ',', '.')) ?> W</td>
    </tr>
    <tr>
        <th>Actieve apparaten</th>
        <td><?= htmlspecialchars((string) $activeDevices) ?></td>
    </tr>
    <tr>
        <th>Verbruik per dag</th>
        <td><?= htmlspecialchars(number_format($totalDailyKwh, 3, ',', '.')) ?> kWh</td>
    </tr>
    <tr>
        <th>Geschat verbruik per maand</th>
        <td><?= htmlspecialchars(number_format($monthlyKwh, 1, ',', '.')) ?> kWh</td>
    </tr>
  <tr>
    <th>Energiekosten per dag</th>
    <td>€ <?= htmlspecialchars(number_format($dailyCost, 2, ',', '.')) ?></td>
</tr>

<tr>
    <th>Geschatte energiekosten per maand</th>
    <td>€ <?= htmlspecialchars(number_format($monthlyCost, 2, ',', '.')) ?></td>
</tr>
  
</table>

<?php include 'includes/footer.php'; ?>