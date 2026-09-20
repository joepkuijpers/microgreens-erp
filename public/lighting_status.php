<?php
require_once __DIR__ . '/includes/sidebar.php'; // Zorg dat dit pad klopt in jouw structuur
// Fallback als sidebar niet direct laadt:
// echo "<html><body><h1>B10 Lighting Status</h1>";

$dbPath = '/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite';
$db = new PDO("sqlite:$dbPath");

echo "<h2>B10 Lighting Control</h2>";

// Config
$config = $db->query("SELECT * FROM lighting_configs LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "<h3>Configuratie</h3>";
echo "<p>Target PPFD: {$config['target_ppfd_min']} - {$config['target_ppfd_max']}</p>";
echo "<p>Spectrum Factor: {$config['spectrum_factor']}</p>";

// Laatste Actie
$log = $db->query("SELECT * FROM lighting_log ORDER BY timestamp DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "<h3>Laatste Actie</h3>";
if ($log) {
    echo "<p>Tijd: {$log['timestamp']}</p>";
    echo "<p>Lux: {$log['measured_lux']} | PPFD: {$log['calculated_ppfd']}</p>";
    echo "<p>Actie: <strong>{$log['action_taken']}</strong></p>";
    if ($log['error_message']) echo "<p style='color:red'>Fout: {$log['error_message']}</p>";
} else {
    echo "<p>Nog geen logs.</p>";
}

// Tabel laatste 10 logs
echo "<h3>Recente Logs</h3>";
echo "<table border='1' cellpadding='5'><tr><th>Tijd</th><th>Lux</th><th>PPFD</th><th>Actie</th><th>Fout</th></tr>";
$logs = $db->query("SELECT * FROM lighting_log ORDER BY timestamp DESC LIMIT 10");
while ($row = $logs->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['timestamp']}</td>";
    echo "<td>{$row['measured_lux']}</td>";
    echo "<td>{$row['calculated_ppfd']}</td>";
    echo "<td>{$row['action_taken']}</td>";
    echo "<td>{$row['error_message']}</td>";
    echo "</tr>";
}
echo "</table>";
?>
