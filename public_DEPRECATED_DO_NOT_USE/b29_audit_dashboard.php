<?php
// Zelfstandige verbinding zonder externe depends voor stabiliteit
$dbPath = __DIR__ . '/../database/MicrogreensERP_Live.sqlite';
if (!file_exists($dbPath)) {
    // Fallback als pad anders is
    $dbPath = '/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite';
}

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database fout: " . $e->getMessage());
}

$stats = [];
$modules = ['B01', 'B02', 'B07', 'B09'];

foreach ($modules as $mod) {
    try {
        // Controleer eerst of de tabel bestaat
        $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user_activity_log'")->fetch();
        if (!$tableCheck) {
            $stats[$mod] = ['count' => 0, 'avg' => 0, 'error' => 'Tabel mist'];
            continue;
        }

        $stmt = $db->prepare("SELECT COUNT(*) as count, SUM(duration_seconds) as total_time FROM user_activity_log WHERE module = :mod");
        $stmt->execute(['mod' => $mod]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $count = $res ? (int)$res['count'] : 0;
        $total = $res ? (float)$res['total_time'] : 0;
        $avg = ($count > 0) ? round($total / $count, 1) : 0;
        
        $stats[$mod] = ['count' => $count, 'avg' => $avg];
    } catch (Exception $e) {
        $stats[$mod] = ['count' => 0, 'avg' => 0, 'error' => $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B29 - Efficiency Audit</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f4f4; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #eee; }
        .ok { color: green; font-weight: bold; }
        .warn { color: orange; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
        a { text-decoration: none; color: #007bff; font-weight: bold; }
        .error { color: red; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🕒 Efficiency Audit (3–5% Regel)</h1>
        <p>Doel: Gemiddelde registratietijd ≤ 5 seconden per actie.</p>
        <?php if (empty(array_filter($stats, fn($s) => $s['count'] > 0))) : ?>
            <p style="color:orange;">⚠️ Geen data gevonden. De <code>user_activity_log</code> tabel is nog leeg of bestaat niet.</p>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr><th>Module</th><th>Acties</th><th>Gem. Tijd (s)</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $m => $d): 
                    if (isset($d['error'])) {
                        echo "<tr><td>$m</td><td colspan='3' class='error'>Fout: {$d['error']}</td></tr>";
                        continue;
                    }
                    $class = $d['avg'] <= 5 ? 'ok' : ($d['avg'] <= 10 ? 'warn' : 'fail');
                    $status = $d['avg'] == 0 && $d['count'] == 0 ? 'GEEN DATA' : ($d['avg'] <= 5 ? 'OPTIMAAL' : ($d['avg'] <= 10 ? 'LET OP' : 'TE TRAAG'));
                    if ($d['avg'] == 0 && $d['count'] == 0) $class = '';
                ?>
                <tr>
                    <td><?= $m ?></td>
                    <td><?= $d['count'] ?></td>
                    <td><?= $d['avg'] ?></td>
                    <td class="<?= $class ?>"><?= $status ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <br>
        <a href="dashboard.php">← Terug naar Dashboard</a>
    </div>
</body>
</html>
