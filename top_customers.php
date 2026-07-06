<?php
require 'config/database.php';

$topCustomers = $db->query("
SELECT customer_name, SUM(amount) revenue
FROM sales
GROUP BY customer_name
ORDER BY revenue DESC
LIMIT 5
");

echo "<h1>Top Klanten</h1>";

foreach($topCustomers as $row){
    echo $row['customer_name'] .
         " - EUR " .
         number_format($row['revenue'],2) .
         "<br>";
}
?>
