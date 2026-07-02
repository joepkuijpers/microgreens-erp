cd /var/www/html/microgreens/PHP

cat > settings.php <<'PHP'
<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$message = "";

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
        $_POST['light_min'],
        $_POST['light_max'],
        $_POST['temp_min'],
        $_POST['temp_max'],
        $_POST['humidity_min'],
        $_POST['humidity_max'],
        $_POST['refresh_seconds']
    ]);

    $message = "✅ Instellingen opgeslagen.";
}

$settings = $db->query("SELECT * FROM settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
?>

<div class="main">

    <h1>⚙️ Instellingen</h1>

    <?php if ($message): ?>
        <div class="card" style="background:#d1fae5; color:#065f46; font-weight:bold;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="card">

            <h2>🌞 Licht</h2>

            <label>Minimum Lux</label><br>
            <input type="number" name="light_min" value="<?= htmlspecialchars($settings['light_min']) ?>"><br><br>

            <label>Maximum Lux</label><br>
            <input type="number" name="light_max" value="<?= htmlspecialchars($settings['light_max']) ?>"><br><br>

            <h2>🌡 Klimaat</h2>

            <label>Minimum temperatuur °C</label><br>
            <input type="number" step="0.1" name="temp_min" value="<?= htmlspecialchars($settings['temp_min']) ?>"><br><br>

            <label>Maximum temperatuur °C</label><br>
            <input type="number" step="0.1" name="temp_max" value="<?= htmlspecialchars($settings['temp_max']) ?>"><br><br>

            <label>Minimum luchtvochtigheid %</label><br>
            <input type="number" step="0.1" name="humidity_min" value="<?= htmlspecialchars($settings['humidity_min']) ?>"><br><br>

            <label>Maximum luchtvochtigheid %</label><br>
            <input type="number" step="0.1" name="humidity_max" value="<?= htmlspecialchars($settings['humidity_max']) ?>"><br><br>

            <h2>📈 Dashboard</h2>

            <label>Verversinterval seconden</label><br>
            <input type="number" name="refresh_seconds" value="<?= htmlspecialchars($settings['refresh_seconds']) ?>"><br><br>

            <button class="btn" type="submit">💾 Instellingen opslaan</button>

        </div>
    </form>

</div>

<?php include 'includes/footer.php'; ?>
PHP

php -l settings.php