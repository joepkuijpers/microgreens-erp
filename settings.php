<?php
include 'includes/header.php';
include 'db_connect.php';

$settings = $db->query("SELECT * FROM settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
?>

<h1>⚙️ Instellingen</h1>

<div class="grid">

<div class="card">
<h2>🌞 Licht</h2>
<p>Minimum licht: <?= $settings['light_min'] ?> lux</p>
<p>Maximum licht: <?= $settings['light_max'] ?> lux</p>
</div>

<div class="card">
<h2>🌡 Klimaat</h2>
<p>Minimum temperatuur: <?= $settings['temp_min'] ?> °C</p>
<p>Maximum temperatuur: <?= $settings['temp_max'] ?> °C</p>
<p>Minimum luchtvochtigheid: <?= $settings['humidity_min'] ?> %</p>
<p>Maximum luchtvochtigheid: <?= $settings['humidity_max'] ?> %</p>
</div>

<div class="card">
<h2>📈 Dashboard</h2>
<p>Verversinterval: <?= $settings['refresh_seconds'] ?> seconden</p>
</div>

</div>

<?php include 'includes/footer.php'; ?>