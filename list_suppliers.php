<?php

require 'config/database.php';

$result = $db->query("SELECT * FROM suppliers ORDER BY id");

echo "<h1>Leveranciers</h1>";

foreach ($result as $row) {
    echo $row['id'] . " - " . $row['name'] . "<br>";
}

?>