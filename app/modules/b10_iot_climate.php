<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_path = "/var/www/html/microgreens/PHP/app/MicrogreensERP_Live.sqlite";
$error = "";

try {
    $pdo = new PDO("sqlite:" . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $latest = $pdo->query("SELECT * FROM haccp_climate_logs ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $logs = $pdo->query("SELECT * FROM haccp_climate_logs ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = "Database Fout: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module B10 - IoT Sensor & Klimaatmonitoring</title>
    <style>
        :root { --primary-color: #2e7d32; --bg-color: #f4f6f8; --card-bg: #ffffff; --border-color: #e0e0e0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: var(--bg-color); color: #333; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: var(--card-bg); padding: 25px; border-radius: 8px; border: 1px solid var(--border-color); box-shadow: 0 2px 4px rgba(0,0,0,0.03); margin-bottom: 20px; }
        h1 { color: var(--primary-color); margin-top: 0; }
        .grid-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: #f9f9f9; padding: 15px; border-radius: 6px; border: 1px solid var(--border-color); text-align: center; }
        .stat-label { font-size: 0.85em; color: #666; font-weight: bold; text-transform: uppercase; }
        .stat-value { font-size: 2em; font-weight: bold; color: var(--primary-color); margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { background-color: #f9f9f9; font-weight: bold; }
        .back-link { display: inline-block; margin-bottom: 15px; color: var(--primary-color); text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
<?php include '/var/www/html/menu.php'; ?>

<div class="container">
    <a href="/" class="back-link">&#8592; Terug naar Dashboard</a>

    <div class="card">
        <h1>Module B10: IoT Sensor & Klimaatmonitoring</h1>
        <p>Live weergave van Raspberry Pi GPIO / ESP32 sensorwaardes en historische klimaattrends.</p>

        <?php if (!empty($error)): ?>
            <p style="color: red; font-weight: bold;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <div class="grid-cards">
            <div class="stat-card">
                <div class="stat-label">Laatste Temperatuur</div>
                <div class="stat-value"><?= $latest ? number_format($latest['temperature'], 1, ',', '.') . ' °C' : '--' ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Laatste Luchtvochtigheid</div>
                <div class="stat-value"><?= $latest ? number_format($latest['humidity'], 1, ',', '.') . ' %' : '--' ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Locatie</div>
                <div class="stat-value" style="font-size:1.3em; margin-top:12px;"><?= $latest ? htmlspecialchars($latest['location']) : 'Geen data' ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Laatste Update</div>
                <div class="stat-value" style="font-size:1em; margin-top:15px; color:#555;"><?= $latest ? $latest['log_time'] : '--' ?></div>
            </div>
        </div>

        <h3>Klimaat Logboek (Laatste 20 Metingen)</h3>
        <?php if (empty($logs)): ?>
            <p style="color: #666; font-style: italic;">Nog geen IoT-metingen ontvangen.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Tijdstip</th>
                        <th>Locatie</th>
                        <th>Temperatuur (°C)</th>
                        <th>Luchtvochtigheid (%)</th>
                        <th>Bron</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td><?= $l['log_time'] ?></td>
                            <td><?= htmlspecialchars($l['location']) ?></td>
                            <td><strong><?= number_format($l['temperature'], 1, ',', '.') ?> °C</strong></td>
                            <td><strong><?= number_format($l['humidity'], 1, ',', '.') ?> %</strong></td>
                            <td><small><?= htmlspecialchars($l['logged_by'] ?: 'IoT') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
