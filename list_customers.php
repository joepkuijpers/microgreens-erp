<?php
require 'config/database.php';

$result = $db->query("SELECT * FROM customers ORDER BY id");

echo "<h1>Klanten</h1>";

foreach ($result as $row) {
    echo $row['id'] . " - " .
         $row['name'] . " - " .
         $row['email'] . "<br>";
}
?>
