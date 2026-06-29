<?php
require 'config/database.php';

$result = $db->query("SELECT * FROM grow_batches ORDER BY id");

echo "<h1>Teelten</h1>";

foreach ($result as $row) {
    echo $row['id'] . " - " .
         $row['crop'] . " - " .
         $row['status'] . "<br>";
}
?>