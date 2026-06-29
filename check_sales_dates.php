<?php
require 'config/database.php';

$result = $db->query("
SELECT id, sale_date, customer_name, amount
FROM sales
ORDER BY id
");

echo "<h1>Controle verkopen</h1>";

foreach ($result as $row) {
    echo $row['id'] . " | " .
         $row['sale_date'] . " | " .
         $row['customer_name'] . " | EUR " .
         $row['amount'] . "<br>";
}
?>
