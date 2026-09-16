<?php
require_once __DIR__ . '/../app/includes/db_connection.php';
$db = getDbConnection();

echo "<h3>Kolommen in 'freeze_dry_processes':</h3><ul>";
$cols = $db->query("PRAGMA table_info(freeze_dry_processes)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    echo "<li><strong>" . $col['name'] . "</strong> (" . $col['type'] . ")</li>";
}
echo "</ul>";
?>