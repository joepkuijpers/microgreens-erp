<?php
$db = new PDO("sqlite:/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite");

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["setting_key"])) {
    $stmt = $db->prepare("INSERT OR REPLACE INTO system_settings (key, value) VALUES (?, ?)");
    $stmt->execute([$_POST["setting_key"], $_POST["setting_value"]]);
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

$stmt = $db->query("SELECT value FROM system_settings WHERE key = 'climate_watchdog_enabled'");
$is_active = $stmt ? ($stmt->fetchColumn() === '1') : false;
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Klimaat Watchdog - Instellingen</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light p-4">
    <div class="container my-4" style="max-width: 600px;">
        <div class="card shadow-sm p-4">
            <h3 class="mb-2">Klimaat Watchdog</h3>
            <p class="text-muted">Schakel meldingen in tijdens kweekperiodes, of pauzeer ze tijdens test-/ruststanden.</p>
            <form method="POST">
                <input type="hidden" name="setting_key" value="climate_watchdog_enabled">
                <?php if ($is_active): ?>
                    <input type="hidden" name="setting_value" value="0">
                    <button type="submit" class="btn btn-success w-100 py-3 fs-5">🟢 Kweken Actief (Meldingen AAN)</button>
                    <small class="text-muted d-block text-center mt-2">Klik om de meldingen te pauzeren.</small>
                <?php else: ?>
                    <input type="hidden" name="setting_value" value="1">
                    <button type="submit" class="btn btn-secondary w-100 py-3 fs-5">🔴 Ruststand / Testen (Meldingen UIT)</button>
                    <small class="text-muted d-block text-center mt-2">Klik om de klimaatbewaking te activeren.</small>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>