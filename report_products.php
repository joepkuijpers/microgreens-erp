<?php
require 'config/database.php';

$result = $db->query("
SELECT product,
       COUNT(*) as sales_count,
       SUM(amount) as revenue
FROM sales
GROUP BY product
ORDER BY revenue DESC
");

echo "<h1>Omzet per product</h1>";

foreach ($result as $row) {
    echo $row['product'] .
         " | Verkopen: " . $row['sales_count'] .
         " | Omzet: EUR " . number_format($row['revenue'],2) .
         "<br>";
}
?>
