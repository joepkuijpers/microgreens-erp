<?php
include 'includes/header.php';
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $stmt = $db->prepare("
        UPDATE settings SET
            light_min = ?,
            light_max = ?,
            temp_min = ?,
            temp_max = ?,
            humidity_min = ?,
            humidity_max = ?,
            refresh_seconds = ?
        WHERE id = 1
    ");

    $stmt->execute([
        $_POST["light_min"],
        $_POST["light_max"],
        $_POST["temp_min"],
        $_POST["temp_max"],
        $_POST["humidity_min"],
        $_POST["humidity_max"],
        $_POST["refresh_seconds"]
    ]);

    echo "<p class='alert-ok'>✅ Instellingen opgeslagen</p>";
}

$settings = $db->query("SELECT * FROM settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
?>

<h1>⚙️ Instellingen</h1>

<form method="post" class="grid">

<div class="card">
<h2>🌞 Licht</h2>
<p>Minimum licht</p>
<input type="number" name="light_min" value="<?= htmlspecialchars($settings['light_min']) ?>"> lux

<p>Maximum licht</p>
<input type="number" name="light_max" value="<?= htmlspecialchars($settings['light_max']) ?>"> lux
</div>

<div class="card">
<h2>🌡 Klimaat</h2>
<p>Minimum temperatuur</p>
<input type="number" step="0.1" name="temp_min" value="<?= htmlspecialchars($settings['temp_min']) ?>"> °C

<p>Maximum temperatuur</p>
<input type="number" step="0.1" name="temp_max" value="<?= htmlspecialchars($settings['temp_max']) ?>"> °C

<p>Minimum luchtvochtigheid</p>
<input type="number" step="0.1" name="humidity_min" value="<?= htmlspecialchars($settings['humidity_min']) ?>"> %

<p>Maximum luchtvochtigheid</p>
<input type="number" step="0.1" name="humidity_max" value="<?= htmlspecialchars($settings['humidity_max']) ?>"> %
</div>

<div class="card">
<h2>📈 Dashboard</h2>
<p>Verversinterval</p>
<input type="number" name="refresh_seconds" min="2" value="<?= htmlspecialchars($settings['refresh_seconds']) ?>"> seconden

<br><br>
<button type="submit">💾 Instellingen opslaan</button>
</div>

</form>

<?php include 'includes/footer.php'; ?>