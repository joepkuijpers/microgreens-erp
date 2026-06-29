<?php
require 'config/database.php';

$result = $db->query("SELECT * FROM products ORDER BY id");

echo "<h1>Producten</h1>";

foreach ($result as $row) {
    echo $row['id'] . " - " .
         $row['name'] . " - EUR " .
         number_format($row['sale_price'], 2) .
         "<br>";
}
?>
