<?php
require 'config/database.php';

$result = $db->query("
SELECT customer_name,
       COUNT(*) as orders,
       SUM(amount) as revenue
FROM sales
GROUP BY customer_name
ORDER BY revenue DESC
");

echo "<h1>Omzet per klant</h1>";

foreach ($result as $row) {
    echo $row['customer_name'] .
         " | Orders: " . $row['orders'] .
         " | Omzet: EUR " . number_format($row['revenue'],2) .
         "<br>";
}
?>
