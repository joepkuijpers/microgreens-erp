<?php
require 'config/database.php';

$result = $db->query("SELECT * FROM harvests ORDER BY id");

echo "<h1>Oogsten</h1>";

foreach ($result as $row) {
    echo $row['id'] . " - " .
         $row['weight_grams'] . " gram - " .
         $row['quality_notes'] . "<br>";
}
?>