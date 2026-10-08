<?php
require_once __DIR__ . '/app/includes/header.php';
$pageTitle = __('module_b09');
?>
} catch (Exception $e) {
    die("❌ DB Fout: " . $e->getMessage());
}
echo "<p>2. Laden Engine... </p>";
$enginePath = "/var/www/html/microgreens/PHP/app/b09_packaging.php";
if (!file_exists($enginePath)) {
    die("❌ Engine bestand niet gevonden op: $enginePath");
}
require_once $enginePath;
if (function_exists("b09_create_packaging")) {
    echo "✅ Engine Gelukt. Functie bestaat.<br>";
} else {
    die("❌ Functie niet gevonden!");
}
echo "<p>3. Check Data... </p>";
$stmt = $db->query("SELECT count(*) as c FROM production_outputs WHERE status='REGISTERED'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Aantal outputs: " . $row["c"] . "<br>";
if ($row["c"] > 0) {
    echo "✅ Data aanwezig!<br>";
} else {
    echo "⚠️ Geen data (oogst eerst).<br>";
}
echo "<hr><h3>Succes! De basis werkt.</h3>";
?>
<div class="next-step-bar">
    <div><a href="index.php" style="color:#666; text-decoration:none;">← Dashboard</a></div>
    <div><a href="b28_watchdog_alerts.php">Watchdog →</a></div>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
