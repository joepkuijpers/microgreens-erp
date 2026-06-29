<?php
require 'config/database.php';

$result = $db->query("
SELECT
    product,
    COUNT(*) as sales_count,
    SUM(amount) as revenue
FROM sales
GROUP BY product
ORDER BY revenue DESC
");

echo "<h1>Winst per product (voorlopig)</h1>";

foreach($result as $row){

    $estimated_cost = $row['revenue'] * 0.40;
    $profit = $row['revenue'] - $estimated_cost;

    echo $row['product'] .
         " | Omzet: EUR " . number_format($row['revenue'],2) .
         " | Geschatte kosten: EUR " . number_format($estimated_cost,2) .
         " | Winst: EUR " . number_format($profit,2) .
         "<br>";
}
?>
