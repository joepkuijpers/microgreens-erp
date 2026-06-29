<?php
require 'config/database.php';

$result = $db->query("
SELECT name
FROM sqlite_master
WHERE type='table'
ORDER BY name
");

echo "<h1>Tabellen</h1>";

foreach ($result as $row) {
    echo $row['name'] . "<br>";
}
?>