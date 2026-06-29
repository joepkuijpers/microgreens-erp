<?php
require 'config/database.php';

$result = $db->query("
SELECT item_name, quantity, unit, unit_cost, quantity * unit_cost AS value
FROM inventory
ORDER BY item_name
");

$total = 0;

echo "<h1>Voorraadwaarde</h1>";

foreach ($result as $row) {
    $total += $row['value'];

    echo $row['item_name'] .
         " | " . $row['quantity'] . " " . $row['unit'] .
         " | Kostprijs: EUR " . number_format($row['unit_cost'],2) .
         " | Waarde: EUR " . number_format($row['value'],2) .
         "<br>";
}

echo "<hr>";
echo "<h2>Totale voorraadwaarde: EUR " . number_format($total,2) . "</h2>";

echo "<br><a href='index.php'>Menu</a>";
?>
