<?php
require 'config/database.php';

$result = $db->query("
SELECT
    f.id,
    f.quantity,
    f.unit,
    p.name
FROM finished_inventory f
LEFT JOIN products p ON p.id = f.product_id
ORDER BY f.id
");

echo "<h1>Gereed Product Voorraad</h1>";

foreach($result as $row){
    echo $row['id'] . " - " .
         $row['name'] . " - " .
         $row['quantity'] . " " .
         $row['unit'] .
         "<br>";
}

echo "<br><a href='index.php'>Menu</a>";
?>
