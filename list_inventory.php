<?php
require 'config/database.php';

$result = $db->query("SELECT * FROM inventory ORDER BY id");

echo "<h1>Voorraad</h1>";

foreach ($result as $row) {
    echo $row['id'] . " - " .
         $row['item_name'] . " - " .
         $row['quantity'] . " " .
         $row['unit'] . "<br>";
}
?>
