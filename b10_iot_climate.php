<?php
/**
 * B10 - IoT Climate & Sensor Sync (FIXED)
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$dbPath = __DIR__ . '/../database/MicrogreensERP_Live.sqlite';
if (!file_exists($dbPath)) { die("DB niet gevonden"); }
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("DB Fout: " . $e->getMessage()); }

$errors = [];
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'log_sensor') {
        $temp = floatval($_POST['temperature'] ?? 0);
        $hum = floatval($_POST['humidity'] ?? 0);
        $loc = $_POST['location_id'] ?? 'UNKNOWN';
        $batch_id = intval($_POST['batch_id'] ?? 0);
        
        // Sad Path: Fault Detection
        $status = 'OK';
        if ($temp < 0 || $temp > 50) $status = 'FAULT_TEMP';
        if ($hum < 0 || $hum > 100) $status = 'FAULT_HUM';
        if ($temp == 0 && $hum == 0) $status = 'FAULT_NO_DATA';

        try {
            // FIX: Gebruik datetime('now') direct in SQL om kolom-problemen te omzeilen
            $stmt = $pdo->prepare("
                INSERT INTO sensor_log (timestamp, temperature, humidity, pressure, light, location_id, batch_id, status)
                VALUES (datetime('now'), ?, ?, 0, 0, ?, ?, ?)
            ");
            $stmt->execute([$temp, $hum, $loc, $batch_id, $status]);
            $success_msg = "Data gelogd. Status: $status";
        } catch (Exception $e) {
            // Als het nog steeds faalt op created_at, probeer dan zonder die kolom expliciet te noemen
            if (strpos($e->getMessage(), 'created_at') !== false) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO sensor_log (timestamp, temperature, humidity, location_id, batch_id, status)
                        VALUES (datetime('now'), ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$temp, $hum, $loc, $batch_id, $status]);
                    $success_msg = "Data gelogd (Compatibiliteitsmodus). Status: $status";
                } catch (Exception $e2) {
                    $errors[] = "Log fout: " . $e2->getMessage();
                }
            } else {
                $errors[] = "Log fout: " . $e->getMessage();
            }
        }
    }
}

$recent_logs = [];
try {
    $stmt = $pdo->query("SELECT id, timestamp, temperature, humidity, location_id, status, batch_id FROM sensor_log ORDER BY id DESC LIMIT 20");
    $recent_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$locations = ['RACK-A', 'RACK-B', 'RACK-C', 'KAS-1'];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B10 IoT Sensor Sync</title>
    <style>
        body { font-family: sans-serif; margin: 2rem; background: #f4f4f4; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; }
        h1 { border-bottom: 2px solid #0056b3; padding-bottom: 0.5rem; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.75rem; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
        .status-OK { color: green; font-weight: bold; }
        .status-FAULT { color: red; font-weight: bold; }
        .card { border: 1px solid #ddd; padding: 1.5rem; border-radius: 4px; margin-bottom: 2rem; background: #fafafa; }
        .form-row { display: flex; gap: 1rem; margin-bottom: 1rem; }
        .form-group { flex: 1; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        input, select { width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background: #0056b3; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer; }
        button.sim { background: #28a745; }
    </style>
</head>
<body>
<div class="container">
    <h1>📡 B10: IoT Sensor Sync & Monitoring</h1>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><strong>Fout:</strong><?= htmlspecialchars($errors[0]) ?></div>
    <?php endif; ?>
    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>🧪 Simuleer Sensor Data</h2>
        <form method="POST">
            <input type="hidden" name="action" value="log_sensor">
            <div class="form-row">
                <div class="form-group">
                    <label>Locatie / Rack:</label>
                    <select name="location_id">
                        <?php foreach ($locations as $loc): ?><option value="<?= $loc ?>"><?= $loc ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Batch ID (Optioneel):</label>
                    <input type="number" name="batch_id" placeholder="Bijv. 1">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Temperatuur (°C):</label>
                    <input type="number" step="0.1" name="temperature" value="22.5" required>
                </div>
                <div class="form-group">
                    <label>Luchtvochtigheid (%):</label>
                    <input type="number" step="0.1" name="humidity" value="60.0" required>
                </div>
            </div>
            <button type="submit" class="sim">📡 Verstuur Meting</button>
        </form>
    </div>

    <h2>📊 Recente Metingen (Live)</h2>
    <table>
        <thead><tr><th>Tijd</th><th>Locatie</th><th>Temp (°C)</th><th>Hum (%)</th><th>Status</th><th>Batch ID</th></tr></thead>
        <tbody>
            <?php foreach ($recent_logs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['timestamp']) ?></td>
                    <td><?= htmlspecialchars($log['location_id']) ?></td>
                    <td><?= number_format($log['temperature'], 1) ?></td>
                    <td><?= number_format($log['humidity'], 1) ?></td>
                    <td>
                        <?php if (strpos($log['status'], 'FAULT') !== false): ?>
                            <span class="status-FAULT">⚠️ <?= htmlspecialchars($log['status']) ?></span>
                        <?php else: ?>
                            <span class="status-OK">✅ OK</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $log['batch_id'] ? htmlspecialchars($log['batch_id']) : '-' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
